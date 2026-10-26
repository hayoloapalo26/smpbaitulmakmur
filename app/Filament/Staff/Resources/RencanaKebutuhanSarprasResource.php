<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Pages;
use App\Models\AcademicYear;
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

class RencanaKebutuhanSarprasResource extends Resource
{
    protected static ?string $model = RencanaKebutuhanSarpras::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Perencanaan dan Pengadaan';
    protected static ?string $navigationLabel = 'Menyusun Rencana Kebutuhan';
    protected static ?string $modelLabel = 'Rencana Kebutuhan Sarpras';
    protected static ?string $pluralModelLabel = 'Rencana Kebutuhan Sarpras';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Sarpras'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 2 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['sarpras', 'kepala_sekolah', 'superadmin']) ?? false;
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

                Forms\Components\Section::make('Informasi Dokumen & Perencanaan Kebutuhan')
                    ->description('Lengkapi data umum perencanaan kebutuhan sarana dan prasarana sekolah')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_rencana')
                            ->label('Nomor Dokumen Rencana')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: RKB/2026/10/0001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_rencana')
                            ->label('Nama / Judul Rencana Kebutuhan')
                            ->placeholder('Contoh: Pengadaan Meubeler Ruang Kelas Baru dan Fasilitas Lab Komputer')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->options(function () {
                                $years = AcademicYear::pluck('name', 'name')->toArray();
                                return !empty($years) ? $years : ['2026/2027' => '2026/2027', '2025/2026' => '2025/2026'];
                            })
                            ->default(fn () => AcademicYear::first()?->name ?? '2026/2027')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester / Periode')
                            ->options([
                                'Ganjil' => 'Semester Ganjil',
                                'Genap' => 'Semester Genap',
                                'Tahunan' => 'Tahunan (1 Tahun Ajaran)',
                                'Insidental' => 'Insidental / Mendesak',
                            ])
                            ->default('Ganjil')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('skala_prioritas')
                            ->label('Skala Prioritas Pengajuan')
                            ->options([
                                'Sangat Mendesak' => '🔴 Sangat Mendesak (Prioritas Utama)',
                                'Mendesak' => '🟠 Mendesak (Urgent)',
                                'Sedang' => '🟡 Sedang (Rutin / Terjadwal)',
                                'Rendah / Rutin' => '⚪ Rendah / Pelengkap',
                            ])
                            ->default('Sedang')
                            ->required(),

                        Forms\Components\Select::make('sumber_anggaran')
                            ->label('Rencana Sumber Anggaran')
                            ->options([
                                'Yayasan / SPP' => 'Anggaran Yayasan / Dana SPP',
                                'Anggaran Rutin Sarpras' => 'Anggaran Rutin Sarpras Sekolah',
                                'Komite / Infaq' => 'Komite Sekolah / Infaq Sarpras',
                                'Donatur / Hibah' => 'Donatur / Bantuan Hibah',
                                'Wakaf' => 'Wakaf / Donatur Pribadi',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Yayasan / SPP')
                            ->required(),

                        Forms\Components\TextInput::make('target_waktu_pengadaan')
                            ->label('Target Waktu Pengadaan')
                            ->placeholder('Contoh: November 2026 / Menjelang Semester Genap')
                            ->maxLength(100),

                        Forms\Components\Select::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Draft' => 'Draft (Penyusunan Rencana)',
                                'Diajukan' => 'Diajukan ke Kepala Sekolah',
                                'Disetujui untuk Pengadaan' => 'Disetujui untuk Pengadaan',
                                'Sedang Proses Pengadaan' => 'Sedang Proses Pengadaan',
                                'Terealisasi' => 'Terealisasi / Selesai',
                                'Dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('Draft')
                            ->required(),

                        Forms\Components\TextInput::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung & Foto Bukti (Link Google Drive)')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://docs.google.com/...')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file dokumen/foto di hosting server demi efisiensi kapasitas. Tautkan link Google Drive untuk proposal teknis, surat penawaran harga vendor, katalog spesifikasi barang, atau foto kondisi sarana eksisting. Pastikan hak akses dapat dibuka oleh Kepala Sekolah.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('tujuan_manfaat')
                            ->label('Tujuan, Latar Belakang & Manfaat Kebutuhan')
                            ->placeholder('Jelaskan latar belakang, urgensi, dan manfaat dari pengadaan sarana/prasarana ini untuk menunjang kegiatan pembelajaran dan operasional sekolah...')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Daftar Sarana & Prasarana yang Dibutuhkan')
                    ->description('Rincian spesifikasi, volume, satuan, dan estimasi biaya barang/alat yang direncanakan untuk pengadaan')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('nama_barang')
                                    ->label('Nama Barang / Sarana')
                                    ->placeholder('Contoh: Proyektor LCD Epson EB-X500')
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\Select::make('kategori')
                                    ->label('Kategori Sarpras')
                                    ->options([
                                        'Meubeler & Furnitur' => 'Meubeler & Furnitur Kelas/Kantor',
                                        'Elektronik & TIK' => 'Elektronik & Perangkat Komputer/TIK',
                                        'Alat Peraga & Media' => 'Alat Peraga & Media Pembelajaran',
                                        'Perlengkapan Ibadah' => 'Perlengkapan Ibadah & Masjid',
                                        'Sarana Olahraga & Seni' => 'Sarana Olahraga & Kesenian',
                                        'Sanitasi & Kebersihan' => 'Perlengkapan Sanitasi & Kebersihan',
                                        'Sarana Gedung & Ruang' => 'Sarana Prasarana Gedung / Ruangan',
                                        'Bahan Habis Pakai & ATK' => 'Bahan Habis Pakai / ATK',
                                        'Lainnya' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Volume / Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $harga = (float) ($get('estimasi_harga_satuan') ?? 0);
                                        $set('subtotal_biaya', ((int) ($state ?: 1)) * $harga);
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

                                Forms\Components\TextInput::make('estimasi_harga_satuan')
                                    ->label('Estimasi Harga Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $jumlah = (int) ($get('jumlah') ?: 1);
                                        $set('subtotal_biaya', $jumlah * ((float) ($state ?: 0)));
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('subtotal_biaya')
                                    ->label('Subtotal Estimasi')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(3),

                                Forms\Components\Select::make('prioritas_item')
                                    ->label('Prioritas Item')
                                    ->options([
                                        'Sangat Mendesak' => '🔴 Sangat Mendesak',
                                        'Mendesak' => '🟠 Mendesak',
                                        'Sedang' => '🟡 Sedang',
                                        'Pelengkap' => '⚪ Pelengkap',
                                    ])
                                    ->default('Sedang')
                                    ->required()
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('target_lokasi')
                                    ->label('Rencana Lokasi Penempatan')
                                    ->placeholder('Contoh: Lab Komputer 1, Ruang Kelas 7B, Kantor Guru')
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('spesifikasi')
                                    ->label('Spesifikasi Teknis / Merk / Dimensi')
                                    ->placeholder('Contoh: 3600 Lumens, Resolusi XGA, Port HDMI & VGA, Garansi Resmi')
                                    ->columnSpan(6),

                                Forms\Components\TextInput::make('alasan_kebutuhan')
                                    ->label('Alasan Kebutuhan / Justifikasi')
                                    ->placeholder('Contoh: Penggantian unit lama yang rusak mati total dan penambahan rombel baru')
                                    ->columnSpan(6),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->cloneable()
                            ->addActionLabel('Tambah Barang Kebutuhan')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status & Catatan Persetujuan Kepala Sekolah')
                    ->description('Bagian ini memuat riwayat persetujuan resmi dari Kepala Sekolah')
                    ->schema([
                        Forms\Components\TextInput::make('status_persetujuan')
                            ->label('Status Persetujuan Kepala Sekolah')
                            ->default('Menunggu Persetujuan')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('tanggal_persetujuan')
                            ->label('Tanggal Respon / ACC')
                            ->disabled()
                            ->placeholder('Belum ada respon'),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->disabled()
                            ->placeholder('Belum ada catatan dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Dokumen Rencana Kebutuhan Sarpras')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_rencana')
                            ->label('Nomor Dokumen')
                            ->weight('bold')
                            ->badge()
                            ->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nama_rencana')
                            ->label('Nama Rencana Kebutuhan')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->icon('heroicon-m-academic-cap'),

                        Infolists\Components\TextEntry::make('semester')
                            ->label('Semester / Periode')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->date('l, d F Y')
                            ->icon('heroicon-m-calendar-days'),

                        Infolists\Components\TextEntry::make('skala_prioritas')
                            ->label('Skala Prioritas')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Sangat Mendesak' => 'danger',
                                'Mendesak' => 'warning',
                                'Sedang' => 'info',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('sumber_anggaran')
                            ->label('Sumber Anggaran')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('target_waktu_pengadaan')
                            ->label('Target Pengadaan')
                            ->default('-')
                            ->icon('heroicon-m-clock'),

                        Infolists\Components\TextEntry::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Draft' => 'gray',
                                'Diajukan' => 'info',
                                'Disetujui untuk Pengadaan' => 'primary',
                                'Sedang Proses Pengadaan' => 'warning',
                                'Terealisasi' => 'success',
                                'Dibatalkan' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung Google Drive')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Dokumen di Google Drive ↗' : 'Tidak ada dokumen terlampir')
                            ->url(fn ($record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color(fn ($state) => $state ? 'info' : 'gray'),

                        Infolists\Components\TextEntry::make('tujuan_manfaat')
                            ->label('Latar Belakang & Manfaat Pengadaan')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(3),

                Infolists\Components\Section::make('Rincian Barang & Sarpras yang Dibutuhkan')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('Daftar Kebutuhan')
                            ->schema([
                                Infolists\Components\TextEntry::make('nama_barang')
                                    ->label('Nama Barang')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('kategori')
                                    ->label('Kategori')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('spesifikasi')
                                    ->label('Spesifikasi Teknis')
                                    ->default('-'),

                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Volume / Qty')
                                    ->formatStateUsing(fn ($state, $record) => "{$state} {$record->satuan}")
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('estimasi_harga_satuan')
                                    ->label('Estimasi Satuan')
                                    ->money('IDR'),

                                Infolists\Components\TextEntry::make('subtotal_biaya')
                                    ->label('Subtotal Estimasi')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('prioritas_item')
                                    ->label('Prioritas')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Sangat Mendesak' => 'danger',
                                        'Mendesak' => 'warning',
                                        'Sedang' => 'info',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('target_lokasi')
                                    ->label('Target Lokasi')
                                    ->default('-')
                                    ->icon('heroicon-m-map-pin'),

                                Infolists\Components\TextEntry::make('alasan_kebutuhan')
                                    ->label('Alasan Kebutuhan')
                                    ->default('-')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Rekapitulasi Anggaran Kebutuhan')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_item')
                            ->label('Total Ragam Barang')
                            ->formatStateUsing(fn ($state) => "{$state} Jenis Barang")
                            ->icon('heroicon-m-cube'),

                        Infolists\Components\TextEntry::make('total_estimasi_anggaran')
                            ->label('Total Estimasi Anggaran')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('primary')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])->columns(2),

                Infolists\Components\Section::make('Status Persetujuan Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_persetujuan')
                            ->label('Status ACC')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'Disetujui' => 'success',
                                'Perlu Perbaikan' => 'warning',
                                'Ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (?string $state): string => match ($state) {
                                'Disetujui' => 'heroicon-m-check-circle',
                                'Perlu Perbaikan' => 'heroicon-m-exclamation-triangle',
                                'Ditolak' => 'heroicon-m-x-circle',
                                default => 'heroicon-m-clock',
                            }),

                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Ditinjau / Di-ACC Oleh')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('tanggal_persetujuan')
                            ->label('Waktu Persetujuan')
                            ->dateTime('d F Y, H:i WIB')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->default('Belum ada catatan')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Informasi Pengaju & Lembaga')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Disusun Oleh (Staf Sarpras)'),
                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga / Sekolah'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Dibuat')
                            ->dateTime('d F Y, H:i WIB'),
                    ])->columns(3)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_rencana')
                    ->label('No. Rencana')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_rencana')
                    ->label('Nama Rencana Kebutuhan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (RencanaKebutuhanSarpras $record) => "TA {$record->tahun_ajaran} ({$record->semester}) • {$record->total_item} item"),

                Tables\Columns\TextColumn::make('total_estimasi_anggaran')
                    ->label('Estimasi Biaya')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('skala_prioritas')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Mendesak' => 'danger',
                        'Mendesak' => 'warning',
                        'Sedang' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sumber_anggaran')
                    ->label('Sumber Dana')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('link_drive_dokumen')
                    ->label('Dokumen')
                    ->formatStateUsing(fn ($state) => $state ? 'Drive ↗' : '-')
                    ->url(fn (RencanaKebutuhanSarpras $record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state),

                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Pelaksanaan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Diajukan' => 'info',
                        'Disetujui untuk Pengadaan' => 'primary',
                        'Sedang Proses Pengadaan' => 'warning',
                        'Terealisasi' => 'success',
                        'Dibatalkan' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status ACC KS')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Disetujui' => 'success',
                        'Perlu Perbaikan' => 'warning',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'Disetujui' => 'heroicon-m-check-circle',
                        'Perlu Perbaikan' => 'heroicon-m-exclamation-triangle',
                        'Ditolak' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-clock',
                    }),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan KS')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Di-ACC Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Disusun Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tgl Pengajuan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan KS')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('status_pelaksanaan')
                    ->label('Status Pelaksanaan')
                    ->options([
                        'Draft' => 'Draft',
                        'Diajukan' => 'Diajukan',
                        'Disetujui untuk Pengadaan' => 'Disetujui untuk Pengadaan',
                        'Sedang Proses Pengadaan' => 'Sedang Proses Pengadaan',
                        'Terealisasi' => 'Terealisasi',
                        'Dibatalkan' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('skala_prioritas')
                    ->label('Skala Prioritas')
                    ->options([
                        'Sangat Mendesak' => 'Sangat Mendesak',
                        'Mendesak' => 'Mendesak',
                        'Sedang' => 'Sedang',
                        'Rendah / Rutin' => 'Rendah / Rutin',
                    ]),
                Tables\Filters\SelectFilter::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                        'Tahunan' => 'Tahunan',
                        'Insidental' => 'Insidental',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye'),

                // Aksi Staf Sarpras: Ajukan Dokumen ke Kepala Sekolah
                Tables\Actions\Action::make('ajukan')
                    ->label('Ajukan')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->button()
                    ->visible(fn (RencanaKebutuhanSarpras $record) => auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) && $record->status_pelaksanaan === 'Draft')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Rencana Kebutuhan Sarpras')
                    ->modalDescription('Apakah dokumen rencana kebutuhan ini sudah lengkap dan siap diajukan untuk ditinjau Kepala Sekolah?')
                    ->action(function (RencanaKebutuhanSarpras $record) {
                        $record->update([
                            'status_pelaksanaan' => 'Diajukan',
                            'status_persetujuan' => 'Menunggu Persetujuan',
                        ]);
                        Notification::make()
                            ->title('Rencana Kebutuhan Sarpras Berhasil Diajukan')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: ACC / Setujui
                Tables\Actions\Action::make('setujui')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (RencanaKebutuhanSarpras $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('ACC Rencana Kebutuhan Sarpras')
                    ->modalDescription('Apakah Anda yakin menyetujui rencana kebutuhan sarpras ini untuk masuk tahap pengadaan?')
                    ->modalSubmitActionLabel('Ya, Setujui (ACC)')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan / Instruksi Pengadaan (Opsional)')
                            ->placeholder('Tuliskan catatan atau instruksi khusus dari Kepala Sekolah jika ada...'),
                    ])
                    ->action(function (RencanaKebutuhanSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'status_pelaksanaan' => 'Disetujui untuk Pengadaan',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Rencana Kebutuhan Sarpras Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Minta Perbaikan / Revisi
                Tables\Actions\Action::make('perbaiki')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn (RencanaKebutuhanSarpras $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan / Revisi')
                            ->required()
                            ->placeholder('Tuliskan poin-poin yang perlu diperbaiki atau disesuaikan oleh staf Sarpras...'),
                    ])
                    ->action(function (RencanaKebutuhanSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Catatan Revisi Terkirim ke Bagian Sarpras')
                            ->warning()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Tolak
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->button()
                    ->visible(fn (RencanaKebutuhanSarpras $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Ditolak')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan penolakan rencana kebutuhan sarpras ini...'),
                    ])
                    ->action(function (RencanaKebutuhanSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Ditolak',
                            'status_pelaksanaan' => 'Dibatalkan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Rencana Kebutuhan Sarpras Ditolak')
                            ->danger()
                            ->send();
                    }),

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
            'index' => Pages\ListRencanaKebutuhanSarpras::route('/'),
            'create' => Pages\CreateRencanaKebutuhanSarpras::route('/create'),
            'view' => Pages\ViewRencanaKebutuhanSarpras::route('/{record}'),
            'edit' => Pages\EditRencanaKebutuhanSarpras::route('/{record}/edit'),
        ];
    }
}
