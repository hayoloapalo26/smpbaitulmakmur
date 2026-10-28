<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\NewsResource\Pages;
use App\Models\Institution;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Pengumuman';
    protected static ?string $modelLabel = 'Pengumuman';
    protected static ?string $pluralModelLabel = 'Pengumuman & Informasi';
    protected static ?string $navigationGroup = 'Pengumuman & Informasi';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['yayasan', 'ketua_yayasan', 'kepala_sekolah', 'humas', 'tata_usaha', 'superadmin']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasAnyRole(['superadmin', 'yayasan', 'ketua_yayasan', 'kepala_sekolah'])) return true;
        if ($user->hasAnyRole(['humas', 'tata_usaha']) && $record->author_id === $user->id) return true;
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['superadmin', 'yayasan', 'ketua_yayasan', 'kepala_sekolah']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Non-superadmin users only see published news, unless they are the author
        if ($user && !$user->hasRole('superadmin')) {
            $userRoles = $user->getRoleNames()->toArray();
            $instId = (string) $user->institution_id;

            $query->where(function ($q) use ($user, $userRoles, $instId) {
                $q->where(function ($sub) use ($userRoles, $instId) {
                    $sub->where('is_published', true)
                        ->where(function ($sq) use ($instId) {
                            $sq->whereNull('target_institutions')
                                ->orWhere('target_institutions', '[]')
                                ->orWhereJsonContains('target_institutions', $instId)
                                ->orWhereJsonContains('target_institutions', (int) $instId);
                        })
                        ->where(function ($sq) use ($userRoles) {
                            $sq->whereNull('target_roles')
                                ->orWhere('target_roles', '[]')
                                ->orWhere(function ($rq) use ($userRoles) {
                                    foreach ($userRoles as $role) {
                                        $rq->orWhereJsonContains('target_roles', $role);
                                    }
                                });
                        });
                })->orWhere('author_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('author_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\Section::make('Informasi Pengumuman')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Pengumuman')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('Isi Pengumuman')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Lampiran Gambar (Opsional)')
                            ->image()
                            ->directory('news-images')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Sasaran & Status Publikasi')
                    ->description('Pilih kepada siapa pengumuman ini disiarkan.')
                    ->schema([
                        Forms\Components\Select::make('target_institutions')
                            ->label('Tujuan Lembaga')
                            ->multiple()
                            ->options(function () {
                                $options = Institution::pluck('name', 'id')->toArray();
                                $options['yayasan'] = 'Tim Yayasan';
                                return $options;
                            })
                            ->searchable()
                            ->placeholder('Semua Lembaga (Kosongkan jika untuk semua)'),
                        Forms\Components\Select::make('target_roles')
                            ->label('Tujuan Role / Jabatan')
                            ->multiple()
                            ->options(function () {
                                return Role::pluck('name', 'name')->toArray();
                            })
                            ->searchable()
                            ->placeholder('Semua Role (Kosongkan jika untuk semua)'),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Terbitkan Pengumuman Sekarang?')
                            ->default(true)
                            ->helperText('Jika aktif, seluruh staf/pengguna yang ditargetkan akan langsung menerima notifikasi lonceng di pojok kanan atas.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Judul Pengumuman')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('author.name')
                                    ->label('Diterbitkan Oleh')
                                    ->badge()
                                    ->color('info')
                                    ->default('Yayasan / Manajemen'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Tanggal Publikasi')
                                    ->dateTime('d F Y, H:i WIB'),
                                Infolists\Components\IconEntry::make('is_published')
                                    ->label('Status Terbit')
                                    ->boolean(),
                            ]),
                        Infolists\Components\ImageEntry::make('image')
                            ->label('Lampiran Gambar')
                            ->visible(fn ($record) => filled($record->image)),
                        Infolists\Components\TextEntry::make('content')
                            ->label('Isi Pengumuman')
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Pengumuman')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Diterbitkan Oleh')
                    ->badge()
                    ->color('info')
                    ->default('Yayasan / Manajemen'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Terbit')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Baca')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn (News $record) => $record->title),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNews::route('/'),
        ];
    }
}
