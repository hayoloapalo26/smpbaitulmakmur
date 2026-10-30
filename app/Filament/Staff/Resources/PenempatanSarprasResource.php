<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PenempatanSarprasResource\Pages;
use App\Models\Inventory;
use App\Models\PenempatanSarpras;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PenempatanSarprasResource extends Resource
{
    protected static ?string $model = PenempatanSarpras::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Inventarisasi & Administrasi';
    protected static ?string $navigationLabel = 'Penempatan Sarpras & Ruangan';
    protected static ?string $modelLabel = 'Penempatan Sarpras Ruangan';
    protected static ?string $pluralModelLabel = 'Penempatan Sarpras Ruangan';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nama_ruangan';

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Sarpras'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 4 : static::$navigationSort;
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

                Forms\Components\Section::make('Identitas & Informasi Ruangan')
                    ->description('Tentukan nama ruangan, kategori, letak gedung/lantai, serta status pemakaian')
                    ->schema([
                        Forms\Components\TextInput::make('kode_ruangan')
                            ->label('Kode Ruangan')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: R-KLS-001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_ruangan')
                            ->label('Nama Ruangan / Fasilitas')
                            ->placeholder('Contoh: Ruang Kelas 7A / Lab Komputer 1 / Ruang Guru')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('kategori_ruangan')
                            ->label('Kategori Ruangan')
                            ->options([
                                'Ruang Kelas' => 'Ruang Kelas Pembelajaran',
                                'Laboratorium / Praktik' => 'Laboratorium Komputer / Sains / Bahasa',
                                'Ruang Pendidik / Guru' => 'Ruang Guru & Pendidik',
                                'Ruang Pimpinan & TU' => 'Ruang Kepala Sekolah & Tata Usaha',
                                'Perpustakaan' => 'Perpustakaan Sekolah',
                                'Tempat Ibadah / Masjid' => 'Masjid / Musholla Sekolah',
                                'Ruang UKS' => 'Ruang Unit Kesehatan Sekolah (UKS)',
                                'Ruang Konseling & BK' => 'Ruang Bimbingan & Konseling (BK)',
                                'Gudang & Logistik' => 'Gudang Sarpras & Logistik',
                                'Sarana Olahraga / Terbuka' => 'Lapangan Olahraga & Area Terbuka',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Ruang Kelas')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('gedung_lantai')
                            ->label('Gedung & Lantai')
                            ->placeholder('Contoh: Gedung A, Lantai 2')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('luas_ruangan')
                            ->label('Dimensi / Luas Ruangan')
                            ->placeholder('Contoh: 7 x 8 m² (56 m²)')
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_ruangan')
                            ->label('Status Ruangan')
                            ->options([
                                'Aktif / Digunakan' => '🟢 Aktif / Digunakan',
                                'Dalam Perbaikan / Renovasi' => '🟡 Dalam Perbaikan / Renovasi',
                                'Tidak Aktif' => '🔴 Tidak Aktif / Kosong',
                            ])
                            ->default('Aktif / Digunakan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Fasilitas Ruangan')
                            ->placeholder('Catatan fasilitas ruangan, tata tertib pemakaian, kapasitas rombel, atau catatan khusus...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Penanggung Jawab Ruangan & Inventaris')
                    ->description('Pilih guru atau staf yang bertanggung jawab penuh terhadap perawatan, pemeliharaan, dan inventaris di ruangan ini')
                    ->schema([
                        Forms\Components\Select::make('penanggung_jawab_id')
                            ->label('Pilih Guru / Staf Penanggung Jawab (PJ)')
                            ->options(function () {
                                return User::pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('nama_penanggung_jawab', $user->name);
                                        if (!empty($user->jabatan)) {
                                            $set('jabatan_pj', $user->jabatan);
                                        }
                                        if (!empty($user->no_hp)) {
                                            $set('kontak_pj', $user->no_hp);
                                        }
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama_penanggung_jawab')
                            ->label('Nama Lengkap Penanggung Jawab')
                            ->placeholder('Contoh: Budi Santoso, S.Pd / Drs. Syamsul Arifin')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jabatan_pj')
                            ->label('Jabatan / Peran PJ')
                            ->placeholder('Contoh: Wali Kelas 7A / Kepala Lab Komputer')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('kontak_pj')
                            ->label('No. WhatsApp / Telepon PJ')
                            ->placeholder('Contoh: 081234567890')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Dokumentasi Google Drive Ruangan & Kartu Inventaris Ruangan (KIR)')
                    ->description('Dokumentasi foto tata letak fisik ruangan dan berkas KIR bertanda tangan via Google Drive (hemat penyimpanan hosting)')
                    ->schema([
                        Forms\Components\TextInput::make('link_drive_foto')
                            ->label('Foto Tata Letak Fisik Ruangan (Link Google Drive)')
                            ->placeholder('https://drive.google.com/file/d/... atau link folder Google Drive foto')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file foto di hosting server demi efisiensi storage. Tempelkan link Google Drive foto ruangan.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openFotoLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link foto di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('link_drive_kir')
                            ->label('Dokumen / Scan Kartu Inventaris Ruangan (Link Google Drive)')
                            ->placeholder('https://drive.google.com/file/d/... atau https://docs.google.com/...')
                            ->url()
                            ->helperText('Tautkan link berkas Kartu Inventaris Ruangan (KIR) bertanda tangan penanggung jawab dan Kepala Sekolah.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openKirLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link KIR di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Daftar Sarpras yang Ditempatkan di Ruangan Ini (Terkoneksi ke Master Inventaris)')
                    ->description('Pilih barang dari Data Inventaris Sarpras sekolah. Kuantitas, satuan, dan estimasi nilai perolehan ditarik otomatis dari master inventaris.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_id')
                                    ->label('Pilih Sarana / Barang dari Master Inventaris')
                                    ->options(function () {
                                        return Inventory::all()->mapWithKeys(function ($inv) {
                                            return [
                                                $inv->id => "[{$inv->kode_barang}] {$inv->nama_barang} ({$inv->kategori}) • Stok Master: {$inv->jumlah_total} {$inv->satuan} [{$inv->kondisi}]",
                                            ];
                                        })->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $inv = Inventory::find($state);
                                            if ($inv) {
                                                $set('satuan', $inv->satuan ?? 'Unit');
                                                $set('kondisi', $inv->kondisi ?? 'Baik');
                                                $qty = (int) ($get('jumlah') ?: 1);
                                                $harga = (float) ($inv->harga_perolehan ?? 0);
                                                $set('subtotal_nilai_aset', $qty * $harga);
                                            }
                                        }
                                    })
                                    ->columnSpan(5),

                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Qty di Ruangan')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $invId = $get('inventory_id');
                                        if ($invId) {
                                            $inv = Inventory::find($invId);
                                            if ($inv) {
                                                $harga = (float) ($inv->harga_perolehan ?? 0);
                                                $set('subtotal_nilai_aset', ((int) ($state ?: 1)) * $harga);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('satuan')
                                    ->label('Satuan')
                                    ->default('Unit')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('kondisi')
                                    ->label('Kondisi di Ruang')
                                    ->options([
                                        'Baik' => '🟢 Baik (Layak)',
                                        'Rusak Ringan' => '🟡 Rusak Ringan',
                                        'Rusak Berat' => '🔴 Rusak Berat',
                                    ])
                                    ->default('Baik')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('subtotal_nilai_aset')
                                    ->label('Nilai Aset')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('posisi_spesifik')
                                    ->label('Posisi Spesifik Penempatan')
                                    ->placeholder('Contoh: Di meja guru depan / Rak lemari B sudut kiri / Plafon tengah')
                                    ->columnSpan(6),

                                Forms\Components\TextInput::make('keterangan')
                                    ->label('Catatan Tambahan Barang')
                                    ->placeholder('Contoh: Dialokasikan untuk absensi harian / Jadwal pemeliharaan')
                                    ->columnSpan(6),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->cloneable()
                            ->addActionLabel('Tambah Sarpras ke Ruangan Ini')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Ruangan & Lokasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('kode_ruangan')
                            ->label('Kode Ruangan')
                            ->badge()
                            ->color('primary')
                            ->weight('bold')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nama_ruangan')
                            ->label('Nama Ruangan')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('kategori_ruangan')
                            ->label('Kategori Ruangan')
                            ->badge(),

                        Infolists\Components\TextEntry::make('gedung_lantai')
                            ->label('Gedung & Lantai')
                            ->default('-')
                            ->icon('heroicon-m-map-pin'),

                        Infolists\Components\TextEntry::make('luas_ruangan')
                            ->label('Luas Ruangan')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('status_ruangan')
                            ->label('Status Ruangan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Aktif / Digunakan' => 'success',
                                'Dalam Perbaikan / Renovasi' => 'warning',
                                default => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Fasilitas & Keterangan Ruangan')
                            ->default('-')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(3),

                Infolists\Components\Section::make('Penanggung Jawab Ruangan & Inventaris')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_penanggung_jawab')
                            ->label('Nama Penanggung Jawab')
                            ->weight('bold')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('jabatan_pj')
                            ->label('Jabatan / Peran')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('kontak_pj')
                            ->label('Kontak / No. WhatsApp')
                            ->default('-')
                            ->icon('heroicon-m-phone'),

                        Infolists\Components\TextEntry::make('penanggungJawab.email')
                            ->label('Email Akun')
                            ->default('-')
                            ->icon('heroicon-m-envelope'),
                    ])->columns(4),

                Infolists\Components\Section::make('Rekapitulasi Sarpras di Ruangan Ini')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_item_sarpras')
                            ->label('Ragam Jenis Sarpras')
                            ->formatStateUsing(fn ($state) => "{$state} Jenis Barang")
                            ->icon('heroicon-m-archive-box'),

                        Infolists\Components\TextEntry::make('total_unit_sarpras')
                            ->label('Total Unit Fisik')
                            ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Unit')
                            ->weight('bold')
                            ->icon('heroicon-m-cube'),

                        Infolists\Components\TextEntry::make('total_nilai_aset')
                            ->label('Total Nilai Aset di Ruangan')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])->columns(3),

                Infolists\Components\Section::make('Rincian Sarpras yang Ditempatkan di Ruangan Ini')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('Daftar Sarpras di Ruangan')
                            ->schema([
                                Infolists\Components\TextEntry::make('inventory.kode_barang')
                                    ->label('Kode Barang')
                                    ->badge()
                                    ->color('primary')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('inventory.nama_barang')
                                    ->label('Nama Sarpras')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('inventory.kategori')
                                    ->label('Kategori')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Volume di Ruangan')
                                    ->formatStateUsing(fn ($state, $record) => "{$state} {$record->satuan}")
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

                                Infolists\Components\TextEntry::make('subtotal_nilai_aset')
                                    ->label('Subtotal Nilai')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('posisi_spesifik')
                                    ->label('Posisi Spesifik')
                                    ->default('-')
                                    ->columnSpan(3),

                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Catatan Mutasi / Khusus')
                                    ->default('-')
                                    ->columnSpan(3),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Dokumentasi Google Drive')
                    ->schema([
                        Infolists\Components\TextEntry::make('link_drive_foto')
                            ->label('Foto Tata Letak Fisik Ruangan (Google Drive)')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Foto Ruangan di Google Drive ↗' : 'Tidak ada foto terlampir')
                            ->url(fn ($record) => $record->link_drive_foto, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-photo')
                            ->color(fn ($state) => $state ? 'info' : 'gray')
                            ->badge(fn ($state) => (bool) $state),

                        Infolists\Components\TextEntry::make('link_drive_kir')
                            ->label('Scan Kartu Inventaris Ruangan (KIR) (Google Drive)')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Berkas KIR di Google Drive ↗' : 'Tidak ada berkas KIR terlampir')
                            ->url(fn ($record) => $record->link_drive_kir, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-document-text')
                            ->color(fn ($state) => $state ? 'info' : 'gray')
                            ->badge(fn ($state) => (bool) $state),
                    ])->columns(2),

                Infolists\Components\Section::make('Informasi Pencatat & Lembaga')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Dicatat Oleh (Staf Sarpras)'),
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
                Tables\Columns\TextColumn::make('kode_ruangan')
                    ->label('Kode Ruang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_ruangan')
                    ->label('Nama Ruangan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PenempatanSarpras $record) => "{$record->kategori_ruangan}" . ($record->gedung_lantai ? " • {$record->gedung_lantai}" : '')),

                Tables\Columns\TextColumn::make('nama_penanggung_jawab')
                    ->label('Penanggung Jawab (PJ)')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->description(fn (PenempatanSarpras $record) => "{$record->jabatan_pj}" . ($record->kontak_pj ? " • {$record->kontak_pj}" : '')),

                Tables\Columns\TextColumn::make('total_item_sarpras')
                    ->label('Ragam Barang')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state} Jenis")
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_unit_sarpras')
                    ->label('Total Unit')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Unit')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_nilai_aset')
                    ->label('Nilai Aset Ruangan')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_ruangan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif / Digunakan' => 'success',
                        'Dalam Perbaikan / Renovasi' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('link_drive_foto')
                    ->label('Foto')
                    ->formatStateUsing(fn ($state) => $state ? 'Foto ↗' : '-')
                    ->url(fn (PenempatanSarpras $record) => $record->link_drive_foto, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-photo' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('link_drive_kir')
                    ->label('KIR')
                    ->formatStateUsing(fn ($state) => $state ? 'KIR ↗' : '-')
                    ->url(fn (PenempatanSarpras $record) => $record->link_drive_kir, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-document-text' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_ruangan')
                    ->label('Kategori Ruangan')
                    ->options([
                        'Ruang Kelas' => 'Ruang Kelas',
                        'Laboratorium / Praktik' => 'Laboratorium',
                        'Ruang Pendidik / Guru' => 'Ruang Guru',
                        'Ruang Pimpinan & TU' => 'Ruang Pimpinan & TU',
                        'Perpustakaan' => 'Perpustakaan',
                        'Tempat Ibadah / Masjid' => 'Tempat Ibadah / Masjid',
                        'Ruang UKS' => 'Ruang UKS',
                        'Ruang Konseling & BK' => 'Ruang Konseling & BK',
                        'Gudang & Logistik' => 'Gudang & Logistik',
                        'Sarana Olahraga / Terbuka' => 'Sarana Olahraga / Terbuka',
                    ]),
                Tables\Filters\SelectFilter::make('status_ruangan')
                    ->label('Status Ruangan')
                    ->options([
                        'Aktif / Digunakan' => 'Aktif / Digunakan',
                        'Dalam Perbaikan / Renovasi' => 'Dalam Perbaikan / Renovasi',
                        'Tidak Aktif' => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail & KIR')
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
            ->defaultSort('nama_ruangan', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenempatanSarpras::route('/'),
            'create' => Pages\CreatePenempatanSarpras::route('/create'),
            'view' => Pages\ViewPenempatanSarpras::route('/{record}'),
            'edit' => Pages\EditPenempatanSarpras::route('/{record}/edit'),
        ];
    }
}
