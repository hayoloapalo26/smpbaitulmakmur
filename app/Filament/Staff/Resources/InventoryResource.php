<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventarisasi & Administrasi';
    protected static ?string $navigationLabel = 'Inventarisasi Seluruh Sarpras';
    protected static ?string $modelLabel = 'Inventaris Sarpras';
    protected static ?string $pluralModelLabel = 'Inventaris Sarpras';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'nama_barang';

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Sarpras'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 3 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']) ?? false;
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

                Forms\Components\Section::make('Identitas & Kode Sarpras')
                    ->description('Informasi pokok mengenai barang, kategori, merk, dan status inventarisasi')
                    ->schema([
                        Forms\Components\TextInput::make('kode_barang')
                            ->label('Kode Barang / Aset')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: INV/2026/0001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_barang')
                            ->label('Nama Sarana / Barang')
                            ->placeholder('Contoh: Laptop ASUS ExpertBook Core i5')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Sarpras')
                            ->options([
                                'Meubeler & Furnitur' => 'Meubeler & Furnitur Kelas/Kantor',
                                'Elektronik & TIK' => 'Elektronik & Perangkat Komputer/TIK',
                                'Alat Laboratorium' => 'Peralatan Laboratorium (IPA/Sains)',
                                'Alat Peraga & Media' => 'Alat Peraga & Media Pembelajaran',
                                'Perlengkapan Ibadah' => 'Perlengkapan Ibadah & Masjid',
                                'Sarana Olahraga & UKS' => 'Sarana Olahraga & Kesehatan (UKS)',
                                'Kesenian & Musik' => 'Perlengkapan Kesenian & Musik',
                                'Kebersihan & Sanitasi' => 'Sarana Kebersihan & Sanitasi',
                                'Gedung & Ruangan' => 'Sarana Bangunan, Ruang & Gedung',
                                'Kendaraan Operasional' => 'Kendaraan Operasional Sekolah',
                                'Buku & Pustaka' => 'Buku & Koleksi Perpustakaan',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Meubeler & Furnitur')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('merk_model')
                            ->label('Merk / Brand / Model')
                            ->placeholder('Contoh: ASUS ExpertBook B1400 / Olympic / Daikin')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nomor_seri')
                            ->label('Nomor Seri Pabrik (S/N)')
                            ->placeholder('Contoh: SN-89218392173 (Opsional)')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_perolehan')
                            ->label('Tanggal Perolehan / Masuk')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('tahun_perolehan', date('Y', strtotime($state)));
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tahun_perolehan')
                            ->label('Tahun Perolehan')
                            ->default(date('Y'))
                            ->placeholder('Contoh: 2026')
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_inventaris')
                            ->label('Status Inventarisasi')
                            ->options([
                                'Aktif / Digunakan' => '🟢 Aktif / Digunakan',
                                'Dalam Perbaikan / Servis' => '🟡 Dalam Perbaikan / Servis',
                                'Dipinjam / Mutasi Sementara' => '🔵 Dipinjam / Mutasi Sementara',
                                'Diarsipkan / Afkir' => '🔴 Diarsipkan / Afkir',
                            ])
                            ->default('Aktif / Digunakan')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Kuantitas, Kondisi Fisik & Nilai Perolehan')
                    ->description('Pencatatan volume barang, breakdown kondisi fisik (Baik/Rusak), dan nilai perolehan aset')
                    ->schema([
                        Forms\Components\TextInput::make('jumlah_total')
                            ->label('Jumlah Total Unit')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $set('jumlah_kondisi_baik', (int) ($state ?: 1));
                                $set('jumlah_kondisi_rusak_ringan', 0);
                                $set('jumlah_kondisi_rusak_berat', 0);
                                $harga = (float) ($get('harga_perolehan') ?: 0);
                                $set('total_nilai_aset', ((int) ($state ?: 1)) * $harga);
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('satuan')
                            ->label('Satuan')
                            ->options([
                                'Unit' => 'Unit',
                                'Buah' => 'Buah',
                                'Set' => 'Set',
                                'Paket' => 'Paket',
                                'Pcs' => 'Pcs',
                                'Rim' => 'Rim',
                                'Meter' => 'Meter',
                                'Dus/Box' => 'Dus / Box',
                                'Lembar' => 'Lembar',
                            ])
                            ->default('Unit')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('harga_perolehan')
                            ->label('Harga Perolehan per Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $total = (int) ($get('jumlah_total') ?: 1);
                                $set('total_nilai_aset', $total * ((float) ($state ?: 0)));
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_nilai_aset')
                            ->label('Total Nilai Aset')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jumlah_kondisi_baik')
                            ->label('Kondisi Baik (Layak Pakai)')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jumlah_kondisi_rusak_ringan')
                            ->label('Rusak Ringan (Perlu Servis)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jumlah_kondisi_rusak_berat')
                            ->label('Rusak Berat (Afkir / Mati)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('kondisi')
                            ->label('Kondisi Fisik Umum')
                            ->options([
                                'Baik' => '🟢 Baik (Layak Pakai)',
                                'Rusak Ringan' => '🟡 Rusak Ringan',
                                'Rusak Berat' => '🔴 Rusak Berat',
                            ])
                            ->default('Baik')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(4),

                Forms\Components\Section::make('Lokasi Penempatan & Kepemilikan')
                    ->description('Rincian ruang penempatan sarpras dan status sumber anggaran aset')
                    ->schema([
                        Forms\Components\TextInput::make('ruangan_lokasi')
                            ->label('Ruangan / Lokasi Penempatan')
                            ->placeholder('Contoh: Lab Komputer 1, Ruang Kelas 7A, Ruang Guru, Perpustakaan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('penanggung_jawab')
                            ->label('Penanggung Jawab / Pengguna')
                            ->placeholder('Contoh: Koordinator Lab TIK / Kepala Tata Usaha')
                            ->columnSpan(1),

                        Forms\Components\Select::make('sumber_dana')
                            ->label('Sumber Anggaran / Dana')
                            ->options([
                                'Yayasan / SPP' => 'Anggaran Yayasan / Dana SPP',
                                'Anggaran Rutin Sarpras' => 'Anggaran Rutin Sarpras Sekolah',
                                'Komite / Infaq' => 'Komite Sekolah / Infaq Sarpras',
                                'Bantuan Pemerintah / Hibah' => 'Bantuan Pemerintah / Hibah',
                                'Wakaf / Donatur' => 'Wakaf / Donatur Pribadi',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Yayasan / SPP')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_kepemilikan')
                            ->label('Status Kepemilikan')
                            ->options([
                                'Milik Sendiri' => 'Milik Sendiri (Sekolah / Yayasan)',
                                'Sewa / Kontrak' => 'Sewa / Kontrak',
                                'Pinjam Pakai' => 'Pinjam Pakai',
                                'Titipan' => 'Titipan Sementara',
                            ])
                            ->default('Milik Sendiri')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Spesifikasi Teknis, Foto Fisik & Dokumen Pendukung')
                    ->description('Dokumentasi visual dan catatan teknis pelengkap sarpras')
                    ->schema([
                        Forms\Components\Textarea::make('spesifikasi')
                            ->label('Spesifikasi Teknis / Dimensi / Bahan')
                            ->placeholder('Tuliskan spesifikasi detail material, ukuran/dimensi, kapasitas, daya listrik, port konektivitas, dll...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan Riwayat / Keterangan')
                            ->placeholder('Catatan riwayat perbaikan, mutasi ruangan, riwayat pemeliharaan, atau catatan khusus...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('foto_barang')
                            ->label('Foto Fisik Sarpras (Link Google Drive)')
                            ->placeholder('https://drive.google.com/file/d/... atau https://drive.google.com/drive/folders/...')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file foto di hosting server demi efisiensi storage. Tempelkan link Google Drive foto fisik sarpras. Pastikan izin link dapat dibuka oleh tim sekolah/yayasan.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openFotoLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link foto di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung / KIB / Faktur (Link Google Drive)')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://docs.google.com/...')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file dokumen di hosting server. Tempelkan link Google Drive untuk Kartu Inventaris Barang (KIB), nota/faktur pembelian, manual book, sertifikat garansi, dll.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identitas Sarana & Prasarana')
                    ->schema([
                        Infolists\Components\TextEntry::make('kode_barang')
                            ->label('Kode Barang')
                            ->badge()
                            ->color('primary')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nama_barang')
                            ->label('Nama Sarpras')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('kategori')
                            ->label('Kategori Sarpras')
                            ->badge(),

                        Infolists\Components\TextEntry::make('merk_model')
                            ->label('Merk / Model')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('nomor_seri')
                            ->label('Nomor Seri (S/N)')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('tanggal_perolehan')
                            ->label('Tanggal Perolehan')
                            ->date('d F Y')
                            ->icon('heroicon-m-calendar-days'),

                        Infolists\Components\TextEntry::make('tahun_perolehan')
                            ->label('Tahun Perolehan')
                            ->icon('heroicon-m-clock'),

                        Infolists\Components\TextEntry::make('status_inventaris')
                            ->label('Status Inventarisasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Aktif / Digunakan' => 'success',
                                'Dalam Perbaikan / Servis' => 'warning',
                                'Dipinjam / Mutasi Sementara' => 'info',
                                'Diarsipkan / Afkir' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('Kuantitas & Kondisi Fisik')
                    ->schema([
                        Infolists\Components\TextEntry::make('jumlah_total')
                            ->label('Total Unit')
                            ->formatStateUsing(fn ($state, Inventory $record) => "{$state} {$record->satuan}")
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->icon('heroicon-m-cube'),

                        Infolists\Components\TextEntry::make('kondisi')
                            ->label('Kondisi Umum')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Baik' => 'success',
                                'Rusak Ringan' => 'warning',
                                'Rusak Berat' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('jumlah_kondisi_baik')
                            ->label('Kondisi Baik')
                            ->formatStateUsing(fn ($state, Inventory $record) => "{$state} {$record->satuan}")
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('jumlah_kondisi_rusak_ringan')
                            ->label('Rusak Ringan')
                            ->formatStateUsing(fn ($state, Inventory $record) => "{$state} {$record->satuan}")
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('jumlah_kondisi_rusak_berat')
                            ->label('Rusak Berat')
                            ->formatStateUsing(fn ($state, Inventory $record) => "{$state} {$record->satuan}")
                            ->badge()
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('harga_perolehan')
                            ->label('Harga Perolehan Satuan')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('total_nilai_aset')
                            ->label('Total Nilai Aset')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('primary')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])->columns(4),

                Infolists\Components\Section::make('Lokasi Penempatan & Sumber Anggaran')
                    ->schema([
                        Infolists\Components\TextEntry::make('ruangan_lokasi')
                            ->label('Lokasi Penempatan')
                            ->icon('heroicon-m-map-pin')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('penanggung_jawab')
                            ->label('Penanggung Jawab')
                            ->default('-')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('sumber_dana')
                            ->label('Sumber Anggaran')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('status_kepemilikan')
                            ->label('Status Kepemilikan')
                            ->badge()
                            ->color('info'),
                    ])->columns(4),

                Infolists\Components\Section::make('Spesifikasi Teknis & Dokumentasi Google Drive')
                    ->schema([
                        Infolists\Components\TextEntry::make('foto_barang')
                            ->label('Foto Fisik Sarpras (Google Drive)')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Foto Fisik di Google Drive ↗' : 'Tidak ada foto terlampir')
                            ->url(fn ($record) => $record->foto_barang, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-photo')
                            ->color(fn ($state) => $state ? 'info' : 'gray')
                            ->badge(fn ($state) => (bool) $state),

                        Infolists\Components\TextEntry::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung / KIB (Google Drive)')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Dokumen di Google Drive ↗' : 'Tidak ada dokumen terlampir')
                            ->url(fn ($record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color(fn ($state) => $state ? 'info' : 'gray')
                            ->badge(fn ($state) => (bool) $state),

                        Infolists\Components\TextEntry::make('spesifikasi')
                            ->label('Spesifikasi Teknis')
                            ->default('-')
                            ->columnSpanFull()
                            ->prose(),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Catatan Riwayat & Keterangan')
                            ->default('-')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(2),

                Infolists\Components\Section::make('Distribusi & Penempatan di Ruangan (KIR)')
                    ->description('Daftar alokasi penempatan sarpras ini di berbagai ruangan/kelas beserta penanggung jawabnya')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('penempatanItems')
                            ->label('Lokasi Penempatan')
                            ->schema([
                                Infolists\Components\TextEntry::make('penempatanSarpras.nama_ruangan')
                                    ->label('Ruangan / Lokasi')
                                    ->weight('bold')
                                    ->icon('heroicon-m-map-pin'),

                                Infolists\Components\TextEntry::make('penempatanSarpras.nama_penanggung_jawab')
                                    ->label('Penanggung Jawab (PJ)')
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('penempatanSarpras.jabatan_pj')
                                    ->label('Jabatan PJ')
                                    ->default('-'),

                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah di Ruangan')
                                    ->formatStateUsing(fn ($state, $record) => "{$state} " . ($record->satuan ?? 'Unit'))
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('kondisi')
                                    ->label('Kondisi di Ruang')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Baik' => 'success',
                                        'Rusak Ringan' => 'warning',
                                        'Rusak Berat' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('posisi_spesifik')
                                    ->label('Posisi Spesifik')
                                    ->default('-'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Riwayat Pemeliharaan & Perawatan Berkala')
                    ->description('Daftar riwayat pemeriksaan rutin bulanan, servis teknisi, dan perbaikan aset ini')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('perawatanItems')
                            ->label('Riwayat Perawatan')
                            ->schema([
                                Infolists\Components\TextEntry::make('perawatan.nomor_laporan')
                                    ->label('No. Laporan')
                                    ->weight('bold')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('perawatan.nama_kegiatan')
                                    ->label('Kegiatan Pemeliharaan')
                                    ->description(fn ($record) => "Periode: {$record->perawatan?->periode_bulan} {$record->perawatan?->periode_tahun}"),

                                Infolists\Components\TextEntry::make('perawatan.tanggal_pelaksanaan')
                                    ->label('Tgl Pelaksanaan')
                                    ->date('d M Y'),

                                Infolists\Components\TextEntry::make('tindakan_perawatan')
                                    ->label('Tindakan Dilakukan'),

                                Infolists\Components\TextEntry::make('kondisi_setelah')
                                    ->label('Kondisi Pasca Servis')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Baik' => 'success',
                                        'Rusak Ringan' => 'warning',
                                        'Rusak Berat' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('biaya')
                                    ->label('Biaya Servis')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('rincian_tindakan')
                                    ->label('Rincian Pengerjaan')
                                    ->default('-')
                                    ->columnSpan(3),

                                Infolists\Components\TextEntry::make('perawatan.status_persetujuan')
                                    ->label('Status Persetujuan KS')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'Disetujui' => 'success',
                                        'Menunggu Persetujuan' => 'warning',
                                        'Perlu Perbaikan' => 'danger',
                                        default => 'gray',
                                    })
                                    ->columnSpan(3),
                            ])
                            ->columns(6)
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Riwayat Penanganan Kerusakan & Penggantian Aset')
                    ->description('Daftar riwayat perbaikan atau penggantian jika barang ini dilaporkan rusak atau hilang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('perbaikanPenggantian')
                            ->label('Riwayat Kasus & Penanganan')
                            ->schema([
                                Infolists\Components\TextEntry::make('nomor_pengurusan')
                                    ->label('No. Tiket')
                                    ->weight('bold')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('tanggal_laporan')
                                    ->label('Tgl Laporan')
                                    ->date('d M Y'),

                                Infolists\Components\TextEntry::make('jenis_kasus')
                                    ->label('Jenis Kasus')
                                    ->badge()
                                    ->colors([
                                        'warning' => 'Rusak Ringan',
                                        'danger' => 'Rusak Berat',
                                        'gray' => 'Hilang / Tidak Ditemukan',
                                        'info' => 'Aus Akibat Usia Teknis',
                                        'primary' => 'Keadaan Kahar / Musibah',
                                    ]),

                                Infolists\Components\TextEntry::make('rencana_tindakan')
                                    ->label('Tindakan')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('status_proses')
                                    ->label('Status Penanganan')
                                    ->badge()
                                    ->colors([
                                        'warning' => 'Dilaporkan',
                                        'info' => 'Sedang Diperbaiki',
                                        'primary' => 'Menunggu Penggantian Unit',
                                        'success' => 'Selesai Diperbaiki',
                                        'success' => 'Selesai Diganti Unit Baru',
                                        'danger' => 'Dihapuskan / Afkir',
                                    ]),

                                Infolists\Components\TextEntry::make('realisasi_biaya')
                                    ->label('Biaya Akhir')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('kronologi_penyebab')
                                    ->label('Kronologi Masalah')
                                    ->columnSpan(3),

                                Infolists\Components\TextEntry::make('keterangan_penyelesaian')
                                    ->label('Hasil Akhir / Garansi')
                                    ->placeholder('Dalam proses')
                                    ->columnSpan(3),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Pencatat & Lembaga')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Dicatat Oleh'),
                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga / Sekolah'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Pencatatan')
                            ->dateTime('d F Y, H:i WIB'),
                    ])->columns(3)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_barang')
                    ->label('Kode Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Sarana / Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Inventory $record) => "{$record->kategori}" . ($record->merk_model ? " • {$record->merk_model}" : '')),

                Tables\Columns\TextColumn::make('jumlah_total')
                    ->label('Total Unit')
                    ->formatStateUsing(fn ($state, Inventory $record) => "{$state} {$record->satuan}")
                    ->alignCenter()
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baik' => 'success',
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'gray',
                    })
                    ->description(fn (Inventory $record) => "Baik: {$record->jumlah_kondisi_baik} | RR: {$record->jumlah_kondisi_rusak_ringan} | RB: {$record->jumlah_kondisi_rusak_berat}"),

                Tables\Columns\TextColumn::make('ruangan_lokasi')
                    ->label('Lokasi Penempatan')
                    ->searchable()
                    ->icon('heroicon-m-map-pin')
                    ->description(fn (Inventory $record) => $record->penanggung_jawab ? "PJ: {$record->penanggung_jawab}" : null),

                Tables\Columns\TextColumn::make('total_nilai_aset')
                    ->label('Nilai Aset')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_inventaris')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif / Digunakan' => 'success',
                        'Dalam Perbaikan / Servis' => 'warning',
                        'Dipinjam / Mutasi Sementara' => 'info',
                        'Diarsipkan / Afkir' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sumber_dana')
                    ->label('Sumber Dana')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('foto_barang')
                    ->label('Foto')
                    ->formatStateUsing(fn ($state) => $state ? 'Foto ↗' : '-')
                    ->url(fn (Inventory $record) => $record->foto_barang, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-photo' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('link_drive_dokumen')
                    ->label('Dokumen')
                    ->formatStateUsing(fn ($state) => $state ? 'Drive ↗' : '-')
                    ->url(fn (Inventory $record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tahun_perolehan')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori Sarpras')
                    ->options([
                        'Meubeler & Furnitur' => 'Meubeler & Furnitur',
                        'Elektronik & TIK' => 'Elektronik & TIK',
                        'Alat Laboratorium' => 'Alat Laboratorium',
                        'Alat Peraga & Media' => 'Alat Peraga & Media',
                        'Perlengkapan Ibadah' => 'Perlengkapan Ibadah',
                        'Sarana Olahraga & UKS' => 'Sarana Olahraga & UKS',
                        'Kesenian & Musik' => 'Kesenian & Musik',
                        'Kebersihan & Sanitasi' => 'Kebersihan & Sanitasi',
                        'Gedung & Ruangan' => 'Gedung & Ruangan',
                        'Kendaraan Operasional' => 'Kendaraan Operasional',
                        'Buku & Pustaka' => 'Buku & Pustaka',
                        'Lainnya' => 'Lainnya',
                    ]),
                Tables\Filters\SelectFilter::make('kondisi')
                    ->label('Kondisi Fisik')
                    ->options([
                        'Baik' => 'Baik',
                        'Rusak Ringan' => 'Rusak Ringan',
                        'Rusak Berat' => 'Rusak Berat',
                    ]),
                Tables\Filters\SelectFilter::make('status_inventaris')
                    ->label('Status Inventaris')
                    ->options([
                        'Aktif / Digunakan' => 'Aktif / Digunakan',
                        'Dalam Perbaikan / Servis' => 'Dalam Perbaikan / Servis',
                        'Dipinjam / Mutasi Sementara' => 'Dipinjam / Mutasi Sementara',
                        'Diarsipkan / Afkir' => 'Diarsipkan / Afkir',
                    ]),
                Tables\Filters\SelectFilter::make('sumber_dana')
                    ->label('Sumber Anggaran')
                    ->options([
                        'Yayasan / SPP' => 'Anggaran Yayasan / SPP',
                        'Anggaran Rutin Sarpras' => 'Anggaran Rutin Sarpras',
                        'Komite / Infaq' => 'Komite / Infaq',
                        'Bantuan Pemerintah / Hibah' => 'Bantuan Pemerintah / Hibah',
                        'Wakaf / Donatur' => 'Wakaf / Donatur',
                        'Lainnya' => 'Lainnya',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye'),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'view' => Pages\ViewInventory::route('/{record}'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
