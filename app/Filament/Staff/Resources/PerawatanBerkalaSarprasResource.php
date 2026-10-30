<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Pages;
use App\Models\Inventory;
use App\Models\PenempatanSarpras;
use App\Models\PerawatanBerkalaSarpras;
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

class PerawatanBerkalaSarprasResource extends Resource
{
    protected static ?string $model = PerawatanBerkalaSarpras::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Pemeliharaan';
    protected static ?string $navigationLabel = 'Laporan Perawatan Berkala';
    protected static ?string $modelLabel = 'Laporan Perawatan Berkala';
    protected static ?string $pluralModelLabel = 'Laporan Perawatan Berkala';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'nomor_laporan';

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Sarpras'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 5 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'sarpras',
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
            $count = static::getModel()::where('status_persetujuan', 'Menunggu Persetujuan')->count();
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
        return auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) ?? false;
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

                Forms\Components\Section::make('Informasi Dokumen & Jadwal Perawatan Bulanan')
                    ->description('Lengkapi data periode bulan dan tanggal pelaksanaan pemeliharaan sarpras')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_laporan')
                            ->label('Nomor Laporan Perawatan')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: RWT/2026/10/0001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_kegiatan')
                            ->label('Nama / Judul Kegiatan Pemeliharaan')
                            ->placeholder('Contoh: Pemeliharaan Rutin Bulanan Sarpras & Elektronik Lab Komputer')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('periode_bulan')
                            ->label('Periode Bulan')
                            ->options([
                                'Januari' => 'Januari',
                                'Februari' => 'Februari',
                                'Maret' => 'Maret',
                                'April' => 'April',
                                'Mei' => 'Mei',
                                'Juni' => 'Juni',
                                'Juli' => 'Juli',
                                'Agustus' => 'Agustus',
                                'September' => 'September',
                                'Oktober' => 'Oktober',
                                'November' => 'November',
                                'Desember' => 'Desember',
                            ])
                            ->default(function () {
                                $bulanMap = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                return $bulanMap[(int) date('n')] ?? 'Januari';
                            })
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('periode_tahun')
                            ->label('Tahun Pelaksanaan')
                            ->numeric()
                            ->default(fn () => (int) date('Y'))
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Mulai / Pelaksanaan')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai Pengerjaan')
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_pemeliharaan')
                            ->label('Jenis Pemeliharaan')
                            ->options([
                                'Perawatan Rutin Bulanan' => 'Perawatan Rutin Bulanan (Routine)',
                                'Pemeliharaan Pencegahan (Preventive)' => 'Pemeliharaan Pencegahan (Preventive)',
                                'Perbaikan Ringan (Corrective)' => 'Perbaikan Ringan (Corrective Maintenance)',
                                'Servis Berkala Teknisi Rekanan' => 'Servis Berkala Teknisi / Vendor Rekanan',
                                'Kalibrasi & Pengecekan Kelistrikan' => 'Kalibrasi & Pengecekan Sistem Kelistrikan',
                                'Pembersihan & Sanitasi Menyeluruh' => 'Pembersihan & Sanitasi Fisik Fasilitas',
                            ])
                            ->default('Perawatan Rutin Bulanan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_perawatan')
                            ->label('Status Pengerjaan')
                            ->options([
                                'Terjadwal' => '🟡 Terjadwal / Belum Mulai',
                                'Dalam Pengerjaan' => '🔵 Dalam Pengerjaan',
                                'Selesai' => '🟢 Selesai Dikerjakan',
                                'Perlu Tindak Lanjut Khusus' => '🔴 Perlu Tindak Lanjut Khusus',
                            ])
                            ->default('Selesai')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Lokasi, Pelaksana Pemeliharaan & Anggaran')
                    ->description('Tentukan ruangan, teknisi/tim pelaksana, serta sumber dana biaya pemeliharaan')
                    ->schema([
                        Forms\Components\Select::make('penempatan_sarpras_id')
                            ->label('Pilih Ruangan / Fasilitas (Opsional)')
                            ->options(function () {
                                return PenempatanSarpras::pluck('nama_ruangan', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $room = PenempatanSarpras::find($state);
                                    if ($room) {
                                        $set('lokasi_ruangan', $room->nama_ruangan);
                                    }
                                }
                            })
                            ->helperText('Pilih ruangan jika perawatan terpusat di satu ruangan tertentu.')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('lokasi_ruangan')
                            ->label('Deskripsi Lokasi / Ruangan')
                            ->placeholder('Contoh: Lab Komputer 1 / Seluruh Ruang Kelas / Area Masjid')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('petugas_pelaksana')
                            ->label('Petugas / Teknisi Pelaksana')
                            ->placeholder('Contoh: Tim Sarpras Sekolah / Bpk. Sutrisno (Teknisi AC) / CV Maju Mandiri')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('kontak_teknisi')
                            ->label('No. Telepon / WhatsApp Teknisi')
                            ->placeholder('Contoh: 081234567890')
                            ->columnSpan(1),

                        Forms\Components\Select::make('sumber_dana')
                            ->label('Sumber Anggaran')
                            ->options([
                                'Anggaran Rutin Sarpras' => 'Anggaran Rutin Sarpras Sekolah',
                                'Yayasan Baitul Makmur' => 'Anggaran Yayasan / SPP',
                                'Iuran Komite / SPP' => 'Iuran Komite / Infaq',
                                'Wakaf / Donatur' => 'Wakaf / Donatur',
                                'Bantuan Hibah' => 'Bantuan Hibah',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Anggaran Rutin Sarpras')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('catatan_umum')
                            ->label('Catatan Hasil Pemeriksaan & Rekomendasi')
                            ->placeholder('Tuliskan rangkuman temuan lapangan, kendala teknis, atau saran peremajaan aset sarpras di masa mendatang...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Dokumentasi Google Drive (Hemat Penyimpanan Hosting)')
                    ->description('Tautkan folder Google Drive foto kondisi sebelum-sesudah perawatan & kuitansi nota servis')
                    ->schema([
                        Forms\Components\TextInput::make('link_drive_dokumentasi')
                            ->label('Link Google Drive Dokumentasi & Kuitansi')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://drive.google.com/file/d/...')
                            ->url()
                            ->helperText('Sistem tidak mengunggah file foto/dokumen ke server hosting untuk menjaga efisiensi ruang simpan. Tempelkan link Google Drive foto bukti pengerjaan dan bukti pembayaran.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openDriveLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link Google Drive di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Rincian Sarana & Inventaris yang Dirawat (Terkoneksi ke Master Inventaris)')
                    ->description('Pilih barang dari Data Master Inventaris. Sistem akan mencatat kondisi sebelum, tindakan perawatan, kondisi setelah, dan biaya.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_id')
                                    ->label('Pilih Sarana / Inventaris dari Master')
                                    ->options(function () {
                                        return Inventory::all()->mapWithKeys(function ($inv) {
                                            return [
                                                $inv->id => "[{$inv->kode_barang}] {$inv->nama_barang} ({$inv->kategori}) • Kondisi Terakhir: [{$inv->kondisi}]",
                                            ];
                                        })->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $inv = Inventory::find($state);
                                            if ($inv) {
                                                $set('kondisi_sebelum', $inv->kondisi ?: 'Baik');
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('jumlah_unit')
                                    ->label('Jumlah Unit')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('kondisi_sebelum')
                                    ->label('Kondisi Sebelum')
                                    ->options([
                                        'Baik' => '🟢 Baik',
                                        'Rusak Ringan' => '🟡 Rusak Ringan',
                                        'Rusak Berat' => '🔴 Rusak Berat',
                                    ])
                                    ->default('Baik')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('tindakan_perawatan')
                                    ->label('Tindakan / Metode Perawatan')
                                    ->options([
                                        'Pembersihan & Pengecekan Fungsi Rutin' => 'Pembersihan & Pengecekan Fungsi Rutin',
                                        'Perbaikan Ringan / Servis Mandiri' => 'Perbaikan Ringan / Servis Mandiri',
                                        'Penggantian Komponen / Sparepart' => 'Penggantian Komponen / Sparepart',
                                        'Servis Teknisi Luar / Vendor Rekanan' => 'Servis Teknisi Luar / Vendor Rekanan',
                                        'Kalibrasi & Pengecekan Kelistrikan' => 'Kalibrasi & Pengecekan Kelistrikan',
                                        'Pemberian Pelumas / Maintenance Fisik' => 'Pemberian Pelumas / Maintenance Fisik',
                                        'Pengecatan / Perapian Finishing' => 'Pengecatan / Perapian Finishing',
                                        'Lainnya' => 'Lainnya',
                                    ])
                                    ->default('Pembersihan & Pengecekan Fungsi Rutin')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('kondisi_setelah')
                                    ->label('Kondisi Pasca Perawatan')
                                    ->options([
                                        'Baik' => '🟢 Baik / Siap Digunakan',
                                        'Rusak Ringan' => '🟡 Masih Rusak Ringan (Butuh Pantauan)',
                                        'Rusak Berat' => '🔴 Rusak Berat (Perlu Diganti/Afkir)',
                                    ])
                                    ->default('Baik')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('biaya')
                                    ->label('Biaya Servis / Sparepart (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('rincian_tindakan')
                                    ->label('Rincian Tindakan / Pengerjaan Detail')
                                    ->placeholder('Contoh: Pembersihan filter udara indoor AC, penambahan refrigeran R32 20 psi, pengecekan pipa drainase')
                                    ->rows(2)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('keterangan')
                                    ->label('Keterangan / Serial Number / Garansi')
                                    ->placeholder('Contoh: Garansi servis 30 hari teknisi')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Sarpras yang Dirawat')
                            ->reorderableWithButtons(),
                    ]),

                Forms\Components\Section::make('Verifikasi & Persetujuan Kepala Sekolah (Approval)')
                    ->description('Tinjauan dan disposisi Kepala Sekolah terhadap laporan pemeliharaan sarpras')
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
                            ->placeholder('Tuliskan catatan evaluasi, tindak lanjut penganggaran, atau instruksi pemeliharaan lanjutan...')
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
                Tables\Columns\TextColumn::make('nomor_laporan')
                    ->label('No. Laporan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_kegiatan')
                    ->label('Kegiatan Pemeliharaan')
                    ->searchable()
                    ->limit(35)
                    ->description(fn (PerawatanBerkalaSarpras $record): string => 
                        ($record->lokasi_ruangan ?: 'Seluruh Ruangan') . ' • Pelaksana: ' . ($record->petugas_pelaksana ?: '-')
                    ),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode Bulan')
                    ->getStateUsing(fn (PerawatanBerkalaSarpras $record): string => "{$record->periode_bulan} {$record->periode_tahun}")
                    ->badge()
                    ->color('info')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('periode_tahun', $direction)->orderBy('periode_bulan', $direction);
                    }),

                Tables\Columns\TextColumn::make('tanggal_pelaksanaan')
                    ->label('Tgl Pelaksanaan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_unit_diperiksa')
                    ->label('Total Diperiksa')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_kondisi_baik')
                    ->label('Baik')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->suffix(' unit'),

                Tables\Columns\TextColumn::make('total_kondisi_rusak_ringan')
                    ->label('Rusak Ringan')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('total_kondisi_rusak_berat')
                    ->label('Rusak Berat')
                    ->badge()
                    ->color('danger')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('total_biaya_pemeliharaan')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_perawatan')
                    ->label('Status Servis')
                    ->badge()
                    ->colors([
                        'warning' => 'Terjadwal',
                        'info' => 'Dalam Pengerjaan',
                        'success' => 'Selesai',
                        'danger' => 'Perlu Tindak Lanjut Khusus',
                    ]),

                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status KS')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu Persetujuan',
                        'success' => 'Disetujui',
                        'danger' => 'Perlu Perbaikan',
                        'gray' => 'Ditolak',
                    ]),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->catatan_kepsek),

                Tables\Columns\TextColumn::make('link_drive_dokumentasi')
                    ->label('Dokumen / Foto')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->getStateUsing(fn ($record) => !empty($record->link_drive_dokumentasi) ? 'Buka Drive' : '-')
                    ->url(fn ($record) => $record->link_drive_dokumentasi, shouldOpenInNewTab: true)
                    ->color(fn ($state) => $state === 'Buka Drive' ? 'primary' : 'gray')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('periode_bulan')
                    ->label('Filter Bulan')
                    ->options([
                        'Januari' => 'Januari',
                        'Februari' => 'Februari',
                        'Maret' => 'Maret',
                        'April' => 'April',
                        'Mei' => 'Mei',
                        'Juni' => 'Juni',
                        'Juli' => 'Juli',
                        'Agustus' => 'Agustus',
                        'September' => 'September',
                        'Oktober' => 'Oktober',
                        'November' => 'November',
                        'Desember' => 'Desember',
                    ]),

                Tables\Filters\SelectFilter::make('status_perawatan')
                    ->label('Status Servis')
                    ->options([
                        'Terjadwal' => 'Terjadwal',
                        'Dalam Pengerjaan' => 'Dalam Pengerjaan',
                        'Selesai' => 'Selesai',
                        'Perlu Tindak Lanjut Khusus' => 'Perlu Tindak Lanjut Khusus',
                    ]),

                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan KS')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !auth()->user()?->hasRole('kepala_sekolah')),

                // Aksi Cepat Kepala Sekolah: ACC / Setujui
                Tables\Actions\Action::make('setujui_cepat')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (PerawatanBerkalaSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        $record->status_persetujuan !== 'Disetujui'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('ACC Laporan Perawatan Berkala')
                    ->modalDescription(fn ($record) => "Apakah Anda menyetujui Laporan Perawatan Sarpras {$record->nomor_laporan} ({$record->nama_kegiatan})?")
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Arahan (Opsional)')
                            ->placeholder('Tuliskan catatan tindak lanjut jika ada...'),
                    ])
                    ->action(function (PerawatanBerkalaSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        Notification::make()
                            ->title('Laporan Perawatan Berhasil Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Minta Perbaikan / Revisi
                Tables\Actions\Action::make('minta_revisi')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn (PerawatanBerkalaSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        in_array($record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Revisi / Arahan')
                            ->required()
                            ->placeholder('Tuliskan poin perawatan yang perlu ditinjau ulang oleh tim sarpras...'),
                    ])
                    ->action(function (PerawatanBerkalaSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        Notification::make()
                            ->title('Catatan Revisi Terkirim ke Bagian Sarpras')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !auth()->user()?->hasRole('kepala_sekolah')),
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
                Infolists\Components\Section::make('Identitas Laporan & Jadwal Perawatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_laporan')
                            ->label('Nomor Laporan')
                            ->weight('bold')
                            ->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nama_kegiatan')
                            ->label('Nama Kegiatan Pemeliharaan')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('periode')
                            ->label('Periode Bulan & Tahun')
                            ->getStateUsing(fn ($record) => "{$record->periode_bulan} {$record->periode_tahun}")
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->date('d F Y')
                            ->placeholder('Selesai pada hari yang sama'),

                        Infolists\Components\TextEntry::make('jenis_pemeliharaan')
                            ->label('Jenis Pemeliharaan')
                            ->badge(),

                        Infolists\Components\TextEntry::make('status_perawatan')
                            ->label('Status Pengerjaan')
                            ->badge()
                            ->colors([
                                'warning' => 'Terjadwal',
                                'info' => 'Dalam Pengerjaan',
                                'success' => 'Selesai',
                                'danger' => 'Perlu Tindak Lanjut Khusus',
                            ]),

                        Infolists\Components\TextEntry::make('status_persetujuan')
                            ->label('Status Persetujuan KS')
                            ->badge()
                            ->colors([
                                'warning' => 'Menunggu Persetujuan',
                                'success' => 'Disetujui',
                                'danger' => 'Perlu Perbaikan',
                                'gray' => 'Ditolak',
                            ]),
                    ])->columns(4),

                Infolists\Components\Section::make('Lokasi, Petugas Pelaksana & Sumber Pembiayaan')
                    ->schema([
                        Infolists\Components\TextEntry::make('lokasi_ruangan')
                            ->label('Ruangan / Lokasi Sarpras')
                            ->weight('bold')
                            ->icon('heroicon-m-map-pin'),

                        Infolists\Components\TextEntry::make('petugas_pelaksana')
                            ->label('Teknisi / Petugas Pelaksana')
                            ->icon('heroicon-m-user-circle'),

                        Infolists\Components\TextEntry::make('kontak_teknisi')
                            ->label('Kontak Teknisi')
                            ->placeholder('Tidak dicantumkan')
                            ->icon('heroicon-m-phone'),

                        Infolists\Components\TextEntry::make('sumber_dana')
                            ->label('Sumber Pembiayaan')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('catatan_umum')
                            ->label('Catatan Hasil Pemeriksaan & Temuan Lapangan')
                            ->placeholder('Tidak ada catatan umum khusus')
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Rekapitulasi Kondisi Sarpras & Total Biaya')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_item_diperiksa')
                            ->label('Ragam Jenis Sarpras')
                            ->suffix(' jenis barang')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('total_unit_diperiksa')
                            ->label('Total Unit Fisik Diperiksa')
                            ->suffix(' unit')
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('total_kondisi_baik')
                            ->label('Kondisi Layak / Baik')
                            ->badge()
                            ->color('success')
                            ->suffix(' unit'),

                        Infolists\Components\TextEntry::make('total_kondisi_rusak_ringan')
                            ->label('Masih Rusak Ringan')
                            ->badge()
                            ->color('warning')
                            ->suffix(' unit'),

                        Infolists\Components\TextEntry::make('total_kondisi_rusak_berat')
                            ->label('Rusak Berat (Butuh Penggantian)')
                            ->badge()
                            ->color('danger')
                            ->suffix(' unit'),

                        Infolists\Components\TextEntry::make('total_biaya_pemeliharaan')
                            ->label('Total Realisasi Biaya')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),
                    ])->columns(3),

                Infolists\Components\Section::make('Dokumentasi Google Drive')
                    ->schema([
                        Infolists\Components\TextEntry::make('link_drive_dokumentasi')
                            ->label('Tautan Berkas Google Drive (Foto & Kuitansi Nota Servis)')
                            ->getStateUsing(fn ($record) => !empty($record->link_drive_dokumentasi) ? $record->link_drive_dokumentasi : 'Belum ditautkan link Google Drive')
                            ->url(fn ($record) => $record->link_drive_dokumentasi, shouldOpenInNewTab: true)
                            ->color(fn ($record) => !empty($record->link_drive_dokumentasi) ? 'primary' : 'gray')
                            ->icon(fn ($record) => !empty($record->link_drive_dokumentasi) ? 'heroicon-m-arrow-top-right-on-square' : null),
                    ]),

                Infolists\Components\Section::make('Disposisi & Persetujuan Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Kepala Sekolah Penyetuju')
                            ->placeholder('Belum ditinjau'),

                        Infolists\Components\TextEntry::make('tanggal_persetujuan')
                            ->label('Tanggal Persetujuan')
                            ->dateTime('d F Y, H:i')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan Disposisi Kepala Sekolah')
                            ->placeholder('Tidak ada catatan khusus dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Rincian Seluruh Sarpras yang Dirawat')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('Daftar Sarana dan Inventaris')
                            ->schema([
                                Infolists\Components\TextEntry::make('inventory.kode_barang')
                                    ->label('Kode Barang')
                                    ->weight('bold')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('inventory.nama_barang')
                                    ->label('Nama Sarpras')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('jumlah_unit')
                                    ->label('Unit Diperiksa')
                                    ->suffix(' unit'),

                                Infolists\Components\TextEntry::make('kondisi_sebelum')
                                    ->label('Kondisi Awal')
                                    ->badge()
                                    ->colors([
                                        'success' => 'Baik',
                                        'warning' => 'Rusak Ringan',
                                        'danger' => 'Rusak Berat',
                                    ]),

                                Infolists\Components\TextEntry::make('tindakan_perawatan')
                                    ->label('Tindakan Dilakukan')
                                    ->weight('medium'),

                                Infolists\Components\TextEntry::make('kondisi_setelah')
                                    ->label('Kondisi Pasca Servis')
                                    ->badge()
                                    ->colors([
                                        'success' => 'Baik',
                                        'warning' => 'Rusak Ringan',
                                        'danger' => 'Rusak Berat',
                                    ]),

                                Infolists\Components\TextEntry::make('biaya')
                                    ->label('Biaya Servis')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('rincian_tindakan')
                                    ->label('Rincian Pengerjaan')
                                    ->placeholder('-')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Keterangan / Serial')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                            ])->columns(4),
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
            'index' => Pages\ListPerawatanBerkalaSarpras::route('/'),
            'create' => Pages\CreatePerawatanBerkalaSarpras::route('/create'),
            'view' => Pages\ViewPerawatanBerkalaSarpras::route('/{record}'),
            'edit' => Pages\EditPerawatanBerkalaSarpras::route('/{record}/edit'),
        ];
    }
}
