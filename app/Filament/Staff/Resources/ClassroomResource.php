<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ClassroomResource\Pages;
use App\Models\Classroom;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassroomResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah', 'tata_usaha']);
    }

    protected static ?string $model = Classroom::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $navigationLabel = 'Data Kelas / Rombel';
    protected static ?string $modelLabel = 'Kelas / Rombel';
    protected static ?string $pluralModelLabel = 'Data Kelas / Rombel';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 'Akademik & Kurikulum';
        }
        return 'Administrasi & Data Induk';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 'Setting Wali Kelas & Rombel';
        }
        return 'Data Kelas / Rombel';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kelas')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->relationship('institution', 'name')
                            ->label('Lembaga (Khusus Superadmin)')
                            ->visible(fn () => auth()->user()->hasRole('superadmin'))
                            ->required(fn () => auth()->user()->hasRole('superadmin')),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kelas')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tingkat')
                            ->label('Tingkat')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('wali_kelas_id')
                            ->label('Wali Kelas (Homeroom Teacher)')
                            ->options(function () {
                                $query = User::where('is_active', true);
                                // Filter by institution if not superadmin
                                if (!auth()->user()->hasRole('superadmin')) {
                                    $query->where(function ($q) {
                                        $q->where('institution_id', auth()->user()->institution_id)
                                          ->orWhereNull('institution_id')
                                          ->orWhere('institution_id', 1);
                                    });
                                }
                                // Filter by teaching roles
                                $query->whereHas('roles', function ($q) {
                                    $q->whereIn('name', ['guru_mapel', 'wali_kelas', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
                                });
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->disabled(fn () => !auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']))
                            ->helperText(fn () => auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']) 
                                ? 'Pilih guru untuk ditetapkan sebagai wali kelas.' 
                                : 'Hanya Waka Kurikulum dan Superadmin yang dapat mengubah penetapan wali kelas.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waliKelas.name')
                    ->label('Wali Kelas')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->default('Belum Ditetapkan'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (Classroom $record) {
                        if ($record->wali_kelas_id) {
                            $user = User::find($record->wali_kelas_id);
                            if ($user && !$user->hasRole('wali_kelas')) {
                                $user->assignRole('wali_kelas');
                            }
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}