<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\SchoolAccreditationResource\Pages;
use App\Models\SchoolAccreditation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolAccreditationResource extends Resource
{
    protected static ?string $model = SchoolAccreditation::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Akreditasi Lembaga';
    protected static ?string $modelLabel = 'Akreditasi Lembaga';
    protected static ?string $pluralModelLabel = 'Akreditasi Lembaga';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Sertifikat & Penetapan Akreditasi')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Satuan Pendidikan')
                            ->relationship('institution', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('nomor_akreditasi')
                            ->label('No. SK / Sertifikat Akreditasi')
                            ->placeholder('Contoh: 123/BAN-SM/SK/2024')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('hasil_akreditasi')
                            ->label('Hasil Akreditasi')
                            ->options([
                                'A (Unggul)' => 'A (Unggul)',
                                'A' => 'A',
                                'B (Baik)' => 'B (Baik)',
                                'B' => 'B',
                                'C (Cukup)' => 'C (Cukup)',
                                'C' => 'C',
                                'Unggul' => 'Unggul',
                                'Baik Sekali' => 'Baik Sekali',
                                'Baik' => 'Baik',
                                'Tidak Terakreditasi' => 'Tidak Terakreditasi',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('masa_berlaku_sampai_tahun')
                            ->label('Masa Berlaku Sampai Tahun')
                            ->numeric()
                            ->placeholder('Contoh: 2029')
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->required(),

                        Forms\Components\TextInput::make('nilai_angka')
                            ->label('Nilai Skor (Angka)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 95')
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status Akreditasi')
                            ->options([
                                'Aktif' => 'Aktif (Berlaku)',
                                'Dalam Proses' => 'Dalam Proses Re-akreditasi',
                                'Kedaluwarsa' => 'Kedaluwarsa (Expired)',
                            ])
                            ->default('Aktif')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Tim Asesor Akreditasi')
                    ->schema([
                        Forms\Components\TextInput::make('nama_assesor_1')
                            ->label('Nama Assesor 1')
                            ->required(),

                        Forms\Components\TextInput::make('nama_assesor_2')
                            ->label('Nama Assesor 2')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Dokumen & Berkas Pendukung')
                    ->schema([
                        Forms\Components\TextInput::make('link_dokumen_drive')
                            ->label('Link Dokumen yang Sudah di Drive')
                            ->url()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan / Rekomendasi Asesor')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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

                Tables\Columns\TextColumn::make('nomor_akreditasi')
                    ->label('No. SK / Sertifikat')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('hasil_akreditasi')
                    ->label('Hasil Akreditasi')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'A') || str_contains($state, 'Unggul') => 'success',
                        str_contains($state, 'B') || str_contains($state, 'Baik') => 'info',
                        str_contains($state, 'C') || str_contains($state, 'Cukup') => 'warning',
                        default => 'danger',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('masa_berlaku_sampai_tahun')
                    ->label('Masa Berlaku (Tahun)')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(function ($record) {
                        $currentYear = (int) date('Y');
                        $expYear = (int) $record->masa_berlaku_sampai_tahun;
                        if ($expYear < $currentYear) return 'danger';
                        if ($expYear === $currentYear) return 'warning';
                        return 'success';
                    })
                    ->description(function ($record) {
                        $currentYear = (int) date('Y');
                        $expYear = (int) $record->masa_berlaku_sampai_tahun;
                        if ($expYear < $currentYear) return 'Kedaluwarsa';
                        if ($expYear === $currentYear) return 'Habis Tahun Ini';
                        $diff = $expYear - $currentYear;
                        return "Sisa {$diff} thn";
                    }),

                Tables\Columns\TextColumn::make('nama_assesor_1')
                    ->label('Assesor 1')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nama_assesor_2')
                    ->label('Assesor 2')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('link_dokumen_drive')
                    ->label('Dokumen Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->formatStateUsing(fn () => 'Buka Drive')
                    ->url(fn ($record) => $record->link_dokumen_drive, true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->relationship('institution', 'name'),

                Tables\Filters\SelectFilter::make('hasil_akreditasi')
                    ->label('Hasil Akreditasi')
                    ->options([
                        'A (Unggul)' => 'A (Unggul)',
                        'A' => 'A',
                        'B (Baik)' => 'B (Baik)',
                        'B' => 'B',
                        'C (Cukup)' => 'C (Cukup)',
                        'C' => 'C',
                        'Unggul' => 'Unggul',
                        'Baik Sekali' => 'Baik Sekali',
                        'Baik' => 'Baik',
                    ]),
            ])
            ->actions([
                Action::make('open_drive')
                    ->label('Buka Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn ($record) => $record->link_dokumen_drive, true),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSchoolAccreditations::route('/'),
        ];
    }
}
