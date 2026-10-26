<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Pages;
use App\Models\EvaluasiProgramSarpras;
use App\Models\ProgramSarpras;
use App\Models\RencanaKebutuhanSarpras;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EvaluasiProgramSarprasResource extends Resource
{
    protected static ?string $model = EvaluasiProgramSarpras::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Sarpras';
    protected static ?string $navigationLabel = 'Evaluasi Program Kerja';
    protected static ?string $modelLabel = 'Evaluasi Program Kerja Sarpras';
    protected static ?string $pluralModelLabel = 'Evaluasi Program Kerja Sarpras';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nomor_evaluasi';

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 7 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'sarpras',
            'tata_usaha',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'sarpras',
            'tata_usaha',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            $count = static::getModel()::where('status_review', 'Menunggu Review')->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\Hidden::make('source_type')
                    ->default('App\\Models\\ProgramSarpras'),
                Forms\Components\Hidden::make('source_id'),

                Forms\Components\Section::make('Pilih Program Kerja yang Telah di-ACC Kepala Sekolah')
                    ->description('Silakan pilih program kerja sarpras atau rencana kebutuhan yang telah berstatus Disetujui (ACC) oleh Kepala Sekolah')
                    ->schema([
                        Forms\Components\Select::make('program_selector')
                            ->label('Pilih Program Kerja yang Sudah di-ACC')
                            ->placeholder('-- Klik untuk memilih program kerja sarpras yang telah disetujui Kepala Sekolah --')
                            ->options(function () {
                                $options = [];

                                // 1. Program Kerja Sarpras (yang di-ACC)
                                $programs = ProgramSarpras::where('status_persetujuan', 'Disetujui')->get();
                                foreach ($programs as $prog) {
                                    $options["ProgramSarpras:{$prog->id}"] = "[Program Sarpras] {$prog->nama_program} ({$prog->kategori} - TA {$prog->tahun_ajaran})";
                                }

                                // 2. Rencana Kebutuhan Sarpras (yang di-ACC)
                                $rkb = RencanaKebutuhanSarpras::where('status_persetujuan', 'Disetujui')->get();
                                foreach ($rkb as $item) {
                                    $options["RencanaKebutuhan:{$item->id}"] = "[Rencana Kebutuhan] {$item->nama_rencana} (TA {$item->tahun_ajaran} - Anggaran: Rp " . number_format($item->total_estimasi_anggaran, 0, ',', '.') . ")";
                                }

                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (empty($state)) return;

                                [$type, $id] = explode(':', $state);

                                if ($type === 'ProgramSarpras') {
                                    $prog = ProgramSarpras::find($id);
                                    if ($prog) {
                                        $set('program_sarpras_id', $prog->id);
                                        $set('source_type', 'App\\Models\\ProgramSarpras');
                                        $set('source_id', $prog->id);
                                        $set('nama_program', $prog->nama_program);
                                        $set('kategori_program', $prog->kategori ?: 'Pengadaan');
                                        $set('tahun_ajaran', $prog->tahun_ajaran ?: '2026/2027');
                                        $set('waktu_pelaksanaan', $prog->waktu_pelaksanaan ?: 'Sesuai Jadwal');
                                        $set('estimasi_anggaran', (float) ($prog->estimasi_anggaran ?: 0));
                                    }
                                } elseif ($type === 'RencanaKebutuhan') {
                                    $rkb = RencanaKebutuhanSarpras::find($id);
                                    if ($rkb) {
                                        $set('program_sarpras_id', null);
                                        $set('source_type', 'App\\Models\\RencanaKebutuhanSarpras');
                                        $set('source_id', $rkb->id);
                                        $set('nama_program', $rkb->nama_rencana);
                                        $set('kategori_program', 'Pengadaan');
                                        $set('tahun_ajaran', $rkb->tahun_ajaran ?: '2026/2027');
                                        $set('waktu_pelaksanaan', 'Sesuai Rencana Pengadaan');
                                        $set('estimasi_anggaran', (float) ($rkb->total_estimasi_anggaran ?: 0));
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Identitas Dokumen & Program Kerja yang Dievaluasi')
                    ->description('Data ringkas program sarpras yang diambil otomatis dari data pengajuan awal')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_evaluasi')
                            ->label('Nomor Dokumen Evaluasi')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: EVAL-SARPRAS/2026/10/0001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program Kerja yang Dievaluasi')
                            ->placeholder('Terisi otomatis dari program yang dipilih di atas')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Select::make('kategori_program')
                            ->label('Kategori Program')
                            ->options([
                                'Pengadaan' => 'Pengadaan Sarpras',
                                'Pemeliharaan' => 'Pemeliharaan & Servis',
                                'Inventarisasi' => 'Inventarisasi & Administrasi',
                                'Renovasi & Pembangunan' => 'Renovasi & Pembangunan Fasilitas',
                                'Sanitasi & Kebersihan' => 'Sanitasi & Kebersihan Lingkungan',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Pengadaan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Jadwal Pelaksanaan Awal')
                            ->placeholder('Contoh: Semester Ganjil / Oktober 2026')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_evaluasi')
                            ->label('Tanggal Evaluasi')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran Awal (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('realisasi_anggaran')
                            ->label('Realisasi Anggaran Akhir (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Keterlaksanaan & Kinerja Pelaksanaan Program')
                    ->description('Lengkapi penilaian ketercapaian target fisik, kendala lapangan, dan solusi penanganannya')
                    ->schema([
                        Forms\Components\Select::make('status_keterlaksanaan')
                            ->label('Status Keterlaksanaan Program')
                            ->options([
                                'Terlaksana Sepenuhnya' => '🟢 Terlaksana Sepenuhnya (100%)',
                                'Terlaksana Sebagian' => '🟡 Terlaksana Sebagian (Sebagian Unit/Target)',
                                'Tidak Terlaksana / Tertunda' => '🟠 Tidak Terlaksana / Tertunda',
                                'Dibatalkan' => '🔴 Dibatalkan',
                            ])
                            ->default('Terlaksana Sepenuhnya')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('persentase_ketercapaian')
                            ->label('Persentase Ketercapaian (%)')
                            ->numeric()
                            ->default(100)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $pct = (int) $state;
                                if ($pct >= 90) {
                                    $set('predikat_capaian', 'Sangat Baik');
                                } elseif ($pct >= 75) {
                                    $set('predikat_capaian', 'Baik');
                                } elseif ($pct >= 60) {
                                    $set('predikat_capaian', 'Cukup');
                                } else {
                                    $set('predikat_capaian', 'Kurang');
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('predikat_capaian')
                            ->label('Predikat Ketercapaian')
                            ->options([
                                'Sangat Baik' => '⭐ Sangat Baik (>= 90%)',
                                'Baik' => '👍 Baik (75% - 89%)',
                                'Cukup' => '👌 Cukup (60% - 74%)',
                                'Kurang' => '⚠️ Kurang (< 60%)',
                            ])
                            ->default('Sangat Baik')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('deskripsi_ketercapaian')
                            ->label('Uraian Faktual Realisasi & Ketercapaian Fisik Sarpras')
                            ->placeholder('Jelaskan secara spesifik barang/fasilitas yang berhasil diadakan, dipasang, atau dirawat sesuai perencanaan...')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('kendala_hambatan')
                            ->label('Kendala & Hambatan Lapangan (Jika Ada)')
                            ->placeholder('Tuliskan kendala teknis, pengiriman barang oleh vendor, cuaca, atau penyesuaian biaya lapangan...')
                            ->rows(2)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('solusi_tindak_lanjut')
                            ->label('Solusi Pemecahan yang Dilakukan')
                            ->placeholder('Tuliskan langkah pemecahan yang diambil oleh staf sarpras selama kegiatan berlangsung...')
                            ->rows(2)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('rekomendasi_periode_berikutnya')
                            ->label('Rekomendasi untuk Program Kerja Sarpras Periode Berikutnya')
                            ->placeholder('Tuliskan saran perbaikan spesifikasi teknis, pemilihan vendor rekanan, atau alokasi jadwal untuk tahun depan...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Dokumentasi Google Drive (Hemat Penyimpanan Hosting)')
                    ->description('Tautkan folder Google Drive laporan pertanggungjawaban (LPJ), foto fisik hasil pekerjaan, atau bukti nota belanja')
                    ->schema([
                        Forms\Components\TextInput::make('link_drive_lpj')
                            ->label('Link Google Drive Dokumen LPJ & Foto Dokumentasi')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://drive.google.com/file/d/...')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file dokumen/foto pada hosting server demi efisiensi storage. Tautkan link Google Drive berkas LPJ, foto hasil fisik sarana, berita acara serah terima (BAST), atau kuitansi nota belanja.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openDriveLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link Google Drive di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Tinjauan & Tanggapan Kepala Sekolah (Review & Feedback KS)')
                    ->description('Persetujuan evaluasi dan catatan arahan evaluasi oleh Kepala Sekolah')
                    ->schema([
                        Forms\Components\Select::make('status_review')
                            ->label('Status Tinjauan Kepala Sekolah')
                            ->options([
                                'Menunggu Review' => '⏳ Menunggu Review',
                                'Disetujui (Selesai Dievaluasi)' => '✅ Disetujui (Selesai Dievaluasi)',
                                'Perlu Perbaikan Laporan' => '⚠️ Perlu Perbaikan Laporan',
                            ])
                            ->default('Menunggu Review')
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Review / Apresiasi / Arahan Kepala Sekolah')
                            ->placeholder('Tuliskan catatan apresiasi, evaluasi pemenuhan target, atau arahan tindak lanjut untuk tim sarpras...')
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
                Tables\Columns\TextColumn::make('nomor_evaluasi')
                    ->label('No. Evaluasi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Program Kerja Sarpras')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (EvaluasiProgramSarpras $record): string => 
                        "Kategori: {$record->kategori_program} • TA: {$record->tahun_ajaran}"
                    ),

                Tables\Columns\TextColumn::make('status_keterlaksanaan')
                    ->label('Keterlaksanaan')
                    ->badge()
                    ->colors([
                        'success' => 'Terlaksana Sepenuhnya',
                        'warning' => 'Terlaksana Sebagian',
                        'info' => 'Tidak Terlaksana / Tertunda',
                        'danger' => 'Dibatalkan',
                    ]),

                Tables\Columns\TextColumn::make('persentase_ketercapaian')
                    ->label('Capaian')
                    ->alignCenter()
                    ->suffix('%')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat_capaian')
                    ->label('Predikat')
                    ->badge()
                    ->colors([
                        'success' => 'Sangat Baik',
                        'primary' => 'Baik',
                        'warning' => 'Cukup',
                        'danger' => 'Kurang',
                    ])
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('realisasi_anggaran')
                    ->label('Realisasi Biaya')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('tanggal_evaluasi')
                    ->label('Tgl Evaluasi')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_review')
                    ->label('Review KS')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu Review',
                        'success' => 'Disetujui (Selesai Dievaluasi)',
                        'danger' => 'Perlu Perbaikan Laporan',
                    ]),

                Tables\Columns\TextColumn::make('link_drive_lpj')
                    ->label('Berkas LPJ')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->getStateUsing(fn ($record) => !empty($record->link_drive_lpj) ? 'Buka LPJ' : '-')
                    ->url(fn ($record) => $record->link_drive_lpj, shouldOpenInNewTab: true)
                    ->color(fn ($state) => $state === 'Buka LPJ' ? 'primary' : 'gray')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_program')
                    ->options([
                        'Pengadaan' => 'Pengadaan',
                        'Pemeliharaan' => 'Pemeliharaan',
                        'Inventarisasi' => 'Inventarisasi',
                        'Renovasi & Pembangunan' => 'Renovasi & Pembangunan',
                        'Sanitasi & Kebersihan' => 'Sanitasi & Kebersihan',
                    ]),

                Tables\Filters\SelectFilter::make('status_keterlaksanaan')
                    ->options([
                        'Terlaksana Sepenuhnya' => 'Terlaksana Sepenuhnya',
                        'Terlaksana Sebagian' => 'Terlaksana Sebagian',
                        'Tidak Terlaksana / Tertunda' => 'Tidak Terlaksana / Tertunda',
                        'Dibatalkan' => 'Dibatalkan',
                    ]),

                Tables\Filters\SelectFilter::make('status_review')
                    ->label('Status Review KS')
                    ->options([
                        'Menunggu Review' => 'Menunggu Review',
                        'Disetujui (Selesai Dievaluasi)' => 'Disetujui (Selesai Dievaluasi)',
                        'Perlu Perbaikan Laporan' => 'Perlu Perbaikan Laporan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Aksi Cepat Kepala Sekolah: ACC / Setujui Hasil Evaluasi
                Tables\Actions\Action::make('setujui_evaluasi')
                    ->label('ACC Evaluasi')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (EvaluasiProgramSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        $record->status_review !== 'Disetujui (Selesai Dievaluasi)'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('ACC Laporan Evaluasi Program Kerja Sarpras')
                    ->modalDescription(fn ($record) => "Apakah Anda menyetujui hasil evaluasi pelaksanaan program '{$record->nama_program}'?")
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Review / Apresiasi (Opsional)')
                            ->placeholder('Tuliskan tanggapan atau apresiasi pelaksanaan program...'),
                    ])
                    ->action(function (EvaluasiProgramSarpras $record, array $data) {
                        $record->update([
                            'status_review' => 'Disetujui (Selesai Dievaluasi)',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'reviewed_by' => auth()->id(),
                            'tanggal_review' => now(),
                        ]);

                        Notification::make()
                            ->title('Laporan Evaluasi Program Sarpras Berhasil Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Cepat Kepala Sekolah: Minta Perbaikan Laporan Evaluasi
                Tables\Actions\Action::make('minta_revisi_evaluasi')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->visible(fn (EvaluasiProgramSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        in_array($record->status_review, ['Menunggu Review', 'Disetujui (Selesai Dievaluasi)'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan / Arahan')
                            ->required()
                            ->placeholder('Tuliskan poin realisasi atau kelengkapan berkas yang perlu disempurnakan...'),
                    ])
                    ->action(function (EvaluasiProgramSarpras $record, array $data) {
                        $record->update([
                            'status_review' => 'Perlu Perbaikan Laporan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'reviewed_by' => auth()->id(),
                            'tanggal_review' => now(),
                        ]);

                        Notification::make()
                            ->title('Catatan Revisi Evaluasi Terkirim ke Bagian Sarpras')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identitas Laporan Evaluasi & Program Kerja')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_evaluasi')
                            ->label('Nomor Evaluasi')
                            ->weight('bold')
                            ->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nama_program')
                            ->label('Nama Program Kerja')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('kategori_program')
                            ->label('Kategori Program')
                            ->badge(),

                        Infolists\Components\TextEntry::make('tahun_ajaran')
                            ->label('Tahun Ajaran'),

                        Infolists\Components\TextEntry::make('waktu_pelaksanaan')
                            ->label('Jadwal Pelaksanaan Awal')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('tanggal_evaluasi')
                            ->label('Tanggal Evaluasi')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('status_keterlaksanaan')
                            ->label('Status Keterlaksanaan')
                            ->badge()
                            ->colors([
                                'success' => 'Terlaksana Sepenuhnya',
                                'warning' => 'Terlaksana Sebagian',
                                'info' => 'Tidak Terlaksana / Tertunda',
                                'danger' => 'Dibatalkan',
                            ]),

                        Infolists\Components\TextEntry::make('status_review')
                            ->label('Status Review KS')
                            ->badge()
                            ->colors([
                                'warning' => 'Menunggu Review',
                                'success' => 'Disetujui (Selesai Dievaluasi)',
                                'danger' => 'Perlu Perbaikan Laporan',
                            ]),
                    ])->columns(4),

                Infolists\Components\Section::make('Ketercapaian Fisik & Realisasi Anggaran')
                    ->schema([
                        Infolists\Components\TextEntry::make('persentase_ketercapaian')
                            ->label('Tingkat Ketercapaian')
                            ->suffix('%')
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('predikat_capaian')
                            ->label('Predikat Ketercapaian')
                            ->badge()
                            ->colors([
                                'success' => 'Sangat Baik',
                                'primary' => 'Baik',
                                'warning' => 'Cukup',
                                'danger' => 'Kurang',
                            ]),

                        Infolists\Components\TextEntry::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran Rencana')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('realisasi_anggaran')
                            ->label('Realisasi Anggaran Akhir')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('deskripsi_ketercapaian')
                            ->label('Uraian Realisasi & Hasil Fisik Sarana Prasarana')
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Analisis Kendala, Solusi, & Rekomendasi Masa Depan')
                    ->schema([
                        Infolists\Components\TextEntry::make('kendala_hambatan')
                            ->label('Kendala & Hambatan Lapangan')
                            ->placeholder('Tidak ada kendala berarti')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('solusi_tindak_lanjut')
                            ->label('Solusi Pemecahan Masalah')
                            ->placeholder('Tidak ada solusi khusus')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('rekomendasi_periode_berikutnya')
                            ->label('Rekomendasi untuk Program Kerja Sarpras Periode Berikutnya')
                            ->placeholder('Tidak ada rekomendasi khusus')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Dokumentasi Google Drive')
                    ->schema([
                        Infolists\Components\TextEntry::make('link_drive_lpj')
                            ->label('Tautan Dokumen LPJ & Bukti Foto Fisik (Google Drive)')
                            ->getStateUsing(fn ($record) => !empty($record->link_drive_lpj) ? $record->link_drive_lpj : 'Belum ditautkan link Google Drive')
                            ->url(fn ($record) => $record->link_drive_lpj, shouldOpenInNewTab: true)
                            ->color(fn ($record) => !empty($record->link_drive_lpj) ? 'primary' : 'gray')
                            ->icon(fn ($record) => !empty($record->link_drive_lpj) ? 'heroicon-m-arrow-top-right-on-square' : null),
                    ]),

                Infolists\Components\Section::make('Tinjauan & Tanggapan Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('reviewer.name')
                            ->label('Kepala Sekolah Peninjau')
                            ->placeholder('Belum ditinjau'),

                        Infolists\Components\TextEntry::make('tanggal_review')
                            ->label('Tanggal Review')
                            ->dateTime('d F Y, H:i')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan Review / Apresiasi / Arahan Kepala Sekolah')
                            ->placeholder('Tidak ada catatan khusus dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(2),
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
            'index' => Pages\ListEvaluasiProgramSarpras::route('/'),
            'create' => Pages\CreateEvaluasiProgramSarpras::route('/create'),
            'view' => Pages\ViewEvaluasiProgramSarpras::route('/{record}'),
            'edit' => Pages\EditEvaluasiProgramSarpras::route('/{record}/edit'),
        ];
    }
}
