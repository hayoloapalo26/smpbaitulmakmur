<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\SchoolVisionMissionResource\Pages;
use App\Models\SchoolVisionMission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolVisionMissionResource extends Resource
{
    protected static ?string $model = SchoolVisionMission::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Visi & Misi Lembaga';
    protected static ?string $modelLabel = 'Visi & Misi Lembaga';
    protected static ?string $pluralModelLabel = 'Visi & Misi Lembaga';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('VisiMisiTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Visi')
                            ->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Lembaga / Satuan Pendidikan')
                                    ->relationship('institution', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('slogan')
                                    ->label('Motto / Slogan Lembaga')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('visi')
                                    ->label('Rumusan Visi')
                                    ->rows(4)
                                    ->required(),
                                Forms\Components\Textarea::make('indikator_visi')
                                    ->label('Indikator Visi')
                                    ->rows(3),
                            ]),
                        Forms\Components\Tabs\Tab::make('Misi')
                            ->schema([
                                Forms\Components\Repeater::make('misi')
                                    ->label('Poin-Poin Misi')
                                    ->schema([
                                        Forms\Components\TextInput::make('poin')
                                            ->label('Isi Misi')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel('+ Tambah Poin Misi')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Tujuan')
                            ->schema([
                                Forms\Components\Repeater::make('tujuan')
                                    ->label('Poin-Poin Tujuan')
                                    ->schema([
                                        Forms\Components\Select::make('kategori')
                                            ->options([
                                                'Tujuan Umum' => 'Tujuan Umum',
                                                'Jangka Pendek (1 Tahun)' => 'Jangka Pendek (1 Tahun)',
                                                'Jangka Menengah (4 Tahun)' => 'Jangka Menengah (4 Tahun)',
                                                'Jangka Panjang' => 'Jangka Panjang',
                                            ])
                                            ->default('Jangka Menengah (4 Tahun)')
                                            ->columnSpan(4),
                                        Forms\Components\TextInput::make('poin')
                                            ->label('Tujuan')
                                            ->required()
                                            ->columnSpan(8),
                                    ])
                                    ->columns(12)
                                    ->addActionLabel('+ Tambah Poin Tujuan')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Periode')
                            ->schema([
                                Forms\Components\TextInput::make('tahun_ajaran')
                                    ->default('2026/2027')
                                    ->required(),
                                Forms\Components\TextInput::make('periode')
                                    ->default('2024 - 2029')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Aktif' => 'Aktif',
                                        'Draft' => 'Draft',
                                        'Arsip' => 'Arsip',
                                    ])
                                    ->default('Aktif')
                                    ->required(),
                            ])->columns(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('visi')
                    ->label('Visi Lembaga')
                    ->limit(65)
                    ->tooltip(fn ($record) => $record->visi)
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('misi')
                    ->label('Misi')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' Poin' : '1 Poin')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' Target' : '1 Target')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Draft' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('🎯 Visi Lembaga')
                    ->schema([
                        Infolists\Components\TextEntry::make('institution.name')->label('Lembaga')->badge(),
                        Infolists\Components\TextEntry::make('slogan')->label('Motto / Slogan')->visible(fn ($record) => !empty($record->slogan)),
                        Infolists\Components\TextEntry::make('visi')->label('Visi Utama')->weight('bold'),
                        Infolists\Components\TextEntry::make('indikator_visi')->label('Indikator Visi')->visible(fn ($record) => !empty($record->indikator_visi)),
                    ]),
                Infolists\Components\Section::make('🚀 Misi Lembaga')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('misi')
                            ->schema([
                                Infolists\Components\TextEntry::make('poin')->label('Misi')->bulleted(),
                            ]),
                    ]),
                Infolists\Components\Section::make('🏆 Tujuan Lembaga')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('tujuan')
                            ->schema([
                                Infolists\Components\TextEntry::make('kategori')->badge()->color('info'),
                                Infolists\Components\TextEntry::make('poin')->label('Tujuan'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSchoolVisionMissions::route('/'),
        ];
    }
}
