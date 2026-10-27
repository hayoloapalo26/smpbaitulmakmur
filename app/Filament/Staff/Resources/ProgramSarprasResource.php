<?php

namespace App\Filament\Staff\Resources;

use App\Models\ProgramSarpras;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramSarprasResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            $count = static::getModel()::where('status_persetujuan', 'Menunggu Persetujuan')->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static ?string $model = ProgramSarpras::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Sarpras';
    protected static ?string $modelLabel = 'Program Kerja Sarpras';
    protected static ?string $pluralModelLabel = 'Program Kerja Sarpras';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                
                Forms\Components\Section::make('Informasi Program')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program / Kegiatan')
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Program')
                            ->options(['Pengadaan' => 'Pengadaan', 'Pemeliharaan' => 'Pemeliharaan', 'Inventarisasi' => 'Inventarisasi', 'Lainnya' => 'Lainnya'])
                            ->required(),
                            
                        Forms\Components\Select::make('tipe_pelaksanaan')
                            ->label('Tipe / Frekuensi')
                            ->options([
                                'Harian' => 'Harian',
                                'Mingguan' => 'Mingguan',
                                'Bulanan' => 'Bulanan',
                                'Semesteran' => 'Semesteran',
                                'Tahunan' => 'Tahunan',
                                'Insidentil' => 'Insidentil',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('sifat')
                            ->label('Sifat Kegiatan')
                            ->options([
                                'Wajib' => 'Wajib',
                                'Kontinu' => 'Kontinu',
                                'Terjadwal' => 'Terjadwal',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Rencana' => 'Rencana',
                                'Sedang Berjalan' => 'Sedang Berjalan',
                                'Selesai' => 'Selesai',
                                'Batal' => 'Batal',
                            ])
                            ->default('Rencana')
                            ->required(),

                        Forms\Components\TextInput::make('sasaran')
                            ->label('Sasaran Peserta')
                            ->placeholder('Contoh: Seluruh Guru / Siswa Kelas X'),
                            
                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Waktu Pelaksanaan')
                            ->placeholder('Contoh: Setiap Semester / Juli 2026'),
                            
                        Forms\Components\TextInput::make('dokumen_terkait')
                            ->label('Dokumen Terkait (Link Google Drive)')
                            ->placeholder('https://drive.google.com/...')
                            ->helperText('Tempelkan link Google Drive dokumen pendukung (SK, proposal, foto bukti) demi efisiensi hosting server.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            ),
                            
                        Forms\Components\TextInput::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Opsional (Ketik angka saja)'),
                            
                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail & Pelaksanaan')
                    ->schema([
                        Forms\Components\Textarea::make('tujuan')
                            ->label('Tujuan Program')
                            ->rows(3),
                        Forms\Components\RichEditor::make('teknis_pelaksanaan')
                            ->label('Teknis Pelaksanaan')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                    ])->columns(1),

                Forms\Components\Section::make('Verifikasi & Persetujuan Kepala Sekolah (Approval)')
                    ->description('Tinjauan dan disposisi persetujuan program kerja oleh Kepala Sekolah')
                    ->schema([
                        Forms\Components\Select::make('status_persetujuan')
                            ->label('Status Persetujuan')
                            ->options([
                                'Menunggu Persetujuan' => '⏳ Menunggu Persetujuan',
                                'Disetujui' => '✅ Disetujui (ACC)',
                                'Perlu Perbaikan' => '⚠️ Perlu Perbaikan',
                                'Ditolak' => '❌ Ditolak',
                            ])
                            ->default('Menunggu Persetujuan')
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Arahan Kepala Sekolah')
                            ->placeholder('Tuliskan arahan pelaksanaan atau tindak lanjut penganggaran program kerja sarpras...')
                            ->rows(3)
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                            ->columnSpan(2),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('tipe_pelaksanaan')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Insidentil' => 'warning',
                        'Harian' => 'info',
                        'Mingguan' => 'primary',
                        'Bulanan' => 'success',
                        'Semesteran' => 'gray',
                        'Tahunan' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('sifat')
                    ->label('Sifat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Wajib' => 'danger',
                        'Kontinu' => 'info',
                        'Terjadwal' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waktu_pelaksanaan')
                    ->label('Waktu')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rencana' => 'gray',
                        'Sedang Berjalan' => 'warning',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status KS')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu Persetujuan',
                        'success' => 'Disetujui',
                        'danger' => 'Perlu Perbaikan',
                        'gray' => 'Ditolak',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options(['Pengadaan' => 'Pengadaan', 'Pemeliharaan' => 'Pemeliharaan', 'Inventarisasi' => 'Inventarisasi', 'Lainnya' => 'Lainnya']),
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan KS')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('tipe_pelaksanaan')
                    ->options([
                        'Harian' => 'Harian',
                        'Mingguan' => 'Mingguan',
                        'Bulanan' => 'Bulanan',
                        'Semesteran' => 'Semesteran',
                        'Tahunan' => 'Tahunan',
                        'Insidentil' => 'Insidentil',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Aksi Cepat Kepala Sekolah: ACC / Setujui
                Tables\Actions\Action::make('setujui_cepat')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (ProgramSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        $record->status_persetujuan !== 'Disetujui'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('ACC Program Kerja Sarpras')
                    ->modalDescription(fn ($record) => "Apakah Anda menyetujui program kerja sarpras '{$record->nama_program}'?")
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Arahan (Opsional)')
                            ->placeholder('Tuliskan arahan pelaksanaan program jika ada...'),
                    ])
                    ->action(function (ProgramSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Program Kerja Sarpras Berhasil Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Minta Perbaikan / Revisi
                Tables\Actions\Action::make('minta_revisi')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->visible(fn (ProgramSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        in_array($record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Revisi / Arahan Perbaikan')
                            ->required()
                            ->placeholder('Tuliskan poin program kerja atau estimasi anggaran yang perlu disesuaikan...'),
                    ])
                    ->action(function (ProgramSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Catatan Revisi Terkirim ke Bagian Sarpras')
                            ->warning()
                            ->send();
                    }),

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
            'index' => \App\Filament\Staff\Resources\ProgramSarprasResource\Pages\ManageProgramSarpras::route('/'),
        ];
    }
}
