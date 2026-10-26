<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\SchoolAccreditationResource\Pages;
use App\Models\SchoolAccreditation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchoolAccreditationResource extends Resource
{
    protected static ?string $model = SchoolAccreditation::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Akreditasi Sekolah';
    protected static ?string $modelLabel = 'Akreditasi Sekolah';
    protected static ?string $pluralModelLabel = 'Akreditasi Sekolah';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'kepala_sekolah',
            'superadmin',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $latest = static::getModel()::latest('masa_berlaku_sampai_tahun')->first();
            return $latest ? (string) $latest->hasil_akreditasi : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $latest = static::getModel()::latest('masa_berlaku_sampai_tahun')->first();
            if (!$latest) return null;
            return match (true) {
                str_contains($latest->hasil_akreditasi, 'A') || str_contains($latest->hasil_akreditasi, 'Unggul') => 'success',
                str_contains($latest->hasil_akreditasi, 'B') || str_contains($latest->hasil_akreditasi, 'Baik') => 'info',
                str_contains($latest->hasil_akreditasi, 'C') || str_contains($latest->hasil_akreditasi, 'Cukup') => 'warning',
                default => 'danger',
            };
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Informasi Sertifikat & Penetapan Akreditasi')
                    ->description('Masukkan nomor SK/sertifikat, masa berlaku, serta hasil akreditasi sekolah.')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Satuan Pendidikan')
                            ->relationship('institution', 'name')
                            ->default(fn () => auth()->user()?->institution_id)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                            ->required(),

                        // 1. No / Nomor Akreditasi
                        Forms\Components\TextInput::make('nomor_akreditasi')
                            ->label('No. SK / Sertifikat Akreditasi')
                            ->placeholder('Contoh: 123/BAN-SM/SK/2024 atau 001/AKR/2024')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nomor Surat Keputusan (SK) atau nomor sertifikat resmi'),

                        // 3. Hasil Akreditasi
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
                            ->required()
                            ->helperText('Peringkat / status akreditasi yang diperoleh'),

                        // 2. Masa Berlaku sampai tahun berapa
                        Forms\Components\TextInput::make('masa_berlaku_sampai_tahun')
                            ->label('Masa Berlaku Sampai Tahun')
                            ->numeric()
                            ->placeholder('Contoh: 2029')
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->required()
                            ->helperText('Tahun berakhirnya masa berlaku sertifikat akreditasi (biasanya 5 tahun)'),

                        Forms\Components\TextInput::make('nilai_angka')
                            ->label('Nilai Skor (Angka)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 95')
                            ->nullable()
                            ->helperText('Skor total visitasi (opsional)'),

                        Forms\Components\DatePicker::make('tanggal_sk')
                            ->label('Tanggal Penetapan SK')
                            ->placeholder('Pilih tanggal penetapan')
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
                    ->description('Data tim penilai / asesor yang ditugaskan oleh Badan Akreditasi.')
                    ->schema([
                        // 5. Nama Assesor 1
                        Forms\Components\TextInput::make('nama_assesor_1')
                            ->label('Nama Assesor 1')
                            ->placeholder('Nama lengkap dan gelar Asesor 1')
                            ->required()
                            ->maxLength(255),

                        // 6. Nama Assesor 2
                        Forms\Components\TextInput::make('nama_assesor_2')
                            ->label('Nama Assesor 2')
                            ->placeholder('Nama lengkap dan gelar Asesor 2')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Dokumen & Berkas Pendukung')
                    ->description('Tautan dokumen akreditasi, berkas instrumen, dan bukti fisik.')
                    ->schema([
                        // 7. Link dokumen yang sudah di drive
                        Forms\Components\TextInput::make('link_dokumen_drive')
                            ->label('Link Dokumen yang Sudah di Drive (Google Drive)')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://drive.google.com/file/d/...')
                            ->url()
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->helperText('Pastikan tautan Google Drive disetel publik / "Siapa saja yang memiliki link dapat melihat".')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan / Rekomendasi Asesor')
                            ->placeholder('Catatan khusus, catatan tindak lanjut, atau saran rekomendasi dari tim asesor...')
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

                // 1. No / Nomor SK
                Tables\Columns\TextColumn::make('nomor_akreditasi')
                    ->label('No. SK / Sertifikat')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Nomor akreditasi disalin'),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'ketua_yayasan', 'pembina_yayasan']) || auth()->user()?->institution_id == 1),

                // 3. Hasil Akreditasi
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

                Tables\Columns\TextColumn::make('nilai_angka')
                    ->label('Nilai Skor')
                    ->sortable()
                    ->alignCenter()
                    ->placeholder('-')
                    ->toggleable(),

                // 2. Masa Berlaku sampai tahun berapa
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

                // 5. Nama Assesor 1
                Tables\Columns\TextColumn::make('nama_assesor_1')
                    ->label('Assesor 1')
                    ->searchable()
                    ->toggleable(),

                // 6. Nama Assesor 2
                Tables\Columns\TextColumn::make('nama_assesor_2')
                    ->label('Assesor 2')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                // 7. Link Dokumen di Drive
                Tables\Columns\TextColumn::make('link_dokumen_drive')
                    ->label('Dokumen Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->formatStateUsing(fn () => 'Buka Drive')
                    ->url(fn ($record) => $record->link_dokumen_drive, true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Dalam Proses' => 'warning',
                        'Kedaluwarsa' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Input')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hasil_akreditasi')
                    ->label('Peringkat Hasil')
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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Masa Berlaku')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Dalam Proses' => 'Dalam Proses',
                        'Kedaluwarsa' => 'Kedaluwarsa',
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
            ->defaultSort('masa_berlaku_sampai_tahun', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSchoolAccreditations::route('/'),
        ];
    }
}
