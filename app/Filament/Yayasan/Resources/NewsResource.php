<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\NewsResource\Pages;
use App\Models\News;
use App\Models\Institution;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Berita & Pengumuman';
    protected static ?string $modelLabel = 'Berita';
    protected static ?string $pluralModelLabel = 'Berita & Pengumuman';
    protected static ?string $navigationGroup = 'Informasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('author_id')->default(fn () => auth()->id()),
                Forms\Components\Section::make('Konten Berita')
                    ->description('Tuliskan berita atau pengumuman di sini.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Berita')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state) . '-' . time()) : null),
                        Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(News::class, 'slug', ignoreRecord: true),
                        Forms\Components\FileUpload::make('image')
                            ->label('Gambar Utama')
                            ->image()
                            ->directory('news-images')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('Isi Berita')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Sasaran (Target)')
                    ->description('Pilih kepada siapa berita ini ditujukan. (Biarkan kosong jika untuk semua)')
                    ->schema([
                        Forms\Components\Select::make('target_institutions')
                            ->label('Tujuan Lembaga')
                            ->multiple()
                            ->options(function () {
                                $options = Institution::pluck('name', 'id')->toArray();
                                $options['yayasan'] = 'Tim Yayasan'; // Add yayasan as an option
                                return $options;
                            })
                            ->searchable()
                            ->placeholder('Pilih Lembaga...'),
                            
                        Forms\Components\Select::make('target_roles')
                            ->label('Tujuan Role/Jabatan')
                            ->multiple()
                            ->options(function () {
                                return Role::pluck('name', 'name')->toArray();
                            })
                            ->searchable()
                            ->placeholder('Pilih Role...'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Terbitkan Berita?')
                            ->default(true),
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
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Penulis')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
