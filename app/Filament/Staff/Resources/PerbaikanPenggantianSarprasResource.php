<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Pages;
use App\Models\Inventory;
use App\Models\PenempatanSarpras;
use App\Models\PerbaikanPenggantianSarpras;
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

class PerbaikanPenggantianSarprasResource extends Resource
{
    protected static ?string $model = PerbaikanPenggantianSarpras::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Pemeliharaan';
    protected static ?string $navigationLabel = 'Perbaikan & Penggantian';
    protected static ?string $modelLabel = 'Perbaikan & Penggantian Sarpras';
    protected static ?string $pluralModelLabel = 'Perbaikan & Penggantian Sarpras';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'nomor_pengurusan';

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Sarpras'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 6 : static::$navigationSort;
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

                Forms\Components\Section::make('Informasi Kasus & Aset Sarpras Terdampak')
                    ->description('Pilih barang inventaris yang mengalami kerusakan fisik atau kehilangan')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_pengurusan')
                            ->label('Nomor Tiket / Pengurusan')
                            ->placeholder('Dibuat otomatis oleh sistem (contoh: PPG/2026/10/0001)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_laporan')
                            ->label('Tanggal Laporan Kejadian')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('inventory_id')
                            ->label('Pilih Sarpras / Inventaris yang Rusak/Hilang')
                            ->options(function () {
                                return Inventory::all()->mapWithKeys(function ($inv) {
                                    return [
                                        $inv->id => "[{$inv->kode_barang}] {$inv->nama_barang} ({$inv->kategori}) • Kondisi: [{$inv->kondisi}] • Lokasi: " . ($inv->ruangan_lokasi ?: 'Belum dispesifikasi'),
                                    ];
                                })->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $inv = Inventory::find($state);
                                    if ($inv && !empty($inv->ruangan_lokasi)) {
                                        $set('lokasi_kejadian', $inv->ruangan_lokasi);
                                    }
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\Select::make('penempatan_sarpras_id')
                            ->label('Ruangan / Fasilitas Asal (Opsional)')
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
                                        $set('lokasi_kejadian', $room->nama_ruangan);
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('lokasi_kejadian')
                            ->label('Lokasi Kejadian Kerusakan / Kehilangan')
                            ->placeholder('Contoh: Ruang Kelas 7A / Lab Komputer / Masjid Sekolah')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('jumlah_unit')
                            ->label('Jumlah Unit Terdampak')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_kasus')
                            ->label('Jenis Masalah / Kasus')
                            ->options([
                                'Rusak Ringan' => '🟡 Rusak Ringan (Bisa Diperbaiki Cepat)',
                                'Rusak Berat' => '🔴 Rusak Berat (Mati Total / Tidak Berfungsi)',
                                'Hilang / Tidak Ditemukan' => '⚫ Hilang / Tidak Ditemukan',
                                'Aus Akibat Usia Teknis' => '🟠 Aus Akibat Melewati Usia Teknis',
                                'Keadaan Kahar / Musibah' => '🟣 Keadaan Kahar / Musibah (Banjir, Korsleting Listrik, dll)',
                            ])
                            ->default('Rusak Ringan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('kronologi_penyebab')
                            ->label('Kronologi & Penyebab Kerusakan / Kehilangan')
                            ->placeholder('Jelaskan detail waktu kejadian, kronologi kerusakan atau kehilangan, serta dugaan penyebab utama...')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Identitas Pelapor & Penanggung Jawab')
                    ->description('Data guru atau staf yang menemukan dan melaporkan masalah aset sarpras')
                    ->schema([
                        Forms\Components\TextInput::make('pelapor_nama')
                            ->label('Nama Lengkap Pelapor')
                            ->placeholder('Contoh: Bpk. Bambang Sutrisno, S.Pd (Wali Kelas 8A)')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('pelapor_kontak')
                            ->label('Kontak / WhatsApp Pelapor')
                            ->placeholder('Contoh: 081234567890')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Rencana Tindakan Penyelesaian, Pelaksana & Biaya')
                    ->description('Tentukan metode perbaikan atau penggantian, pelaksana teknisi, serta estimasi/realisasi biaya')
                    ->schema([
                        Forms\Components\Select::make('rencana_tindakan')
                            ->label('Rencana Tindakan Penanganan')
                            ->options([
                                'Perbaikan Mandiri' => '🛠️ Perbaikan Mandiri (Teknisi Sarpras Sekolah)',
                                'Servis Vendor / Garansi Resmi' => '🏭 Servis Vendor Eksternal / Garansi Resmi',
                                'Penggantian Komponen / Sparepart' => '⚙️ Penggantian Komponen / Suku Cadang',
                                'Penggantian Unit Baru (Replacement)' => '📦 Penggantian Unit Baru (Beli Baru Pengganti)',
                                'Penghapusan Aset / Afkir (Write-off)' => '🗑️ Penghapusan Aset / Usulan Afkir (Tidak Ekonomis)',
                                'Klaim Ganti Rugi Pihak Terkait' => '⚖️ Klaim Ganti Rugi Pihak Terkait (Kelalaian)',
                            ])
                            ->default('Perbaikan Mandiri')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_proses')
                            ->label('Status Penanganan')
                            ->options([
                                'Dilaporkan' => '🟡 Dilaporkan (Menunggu Penanganan)',
                                'Sedang Diperbaiki' => '🔵 Sedang Dalam Pengerjaan Servis',
                                'Menunggu Penggantian Unit' => '🟠 Menunggu Pengadaan Unit Pengganti',
                                'Selesai Diperbaiki' => '🟢 Selesai Diperbaiki (Aset Kembali Berfungsi)',
                                'Selesai Diganti Unit Baru' => '🟢 Selesai Diganti Unit Baru (Aset Diperbarui)',
                                'Dihapuskan / Afkir' => '⚫ Aset Resmi Dihapuskan / Afkir',
                            ])
                            ->default('Dilaporkan')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('estimasi_biaya')
                            ->label('Estimasi Biaya (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('realisasi_biaya')
                            ->label('Realisasi Biaya Akhir (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('sumber_dana')
                            ->label('Sumber Anggaran Pembiayaan')
                            ->options([
                                'Anggaran Rutin Sarpras' => 'Anggaran Rutin Sarpras Sekolah',
                                'Yayasan Baitul Makmur' => 'Anggaran Yayasan / SPP',
                                'SPP / Iuran Komite' => 'SPP / Iuran Komite / Infaq',
                                'Ganti Rugi Pihak Terkait' => 'Ganti Rugi Pihak Terkait / Peminjam',
                                'Klaim Garansi (Gratis)' => 'Klaim Garansi Resmi (Tanpa Biaya)',
                                'Wakaf / Donatur' => 'Wakaf / Donatur',
                                'Bantuan Hibah' => 'Bantuan Hibah',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Anggaran Rutin Sarpras')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai Penanganan')
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('pelaksana_teknisi')
                            ->label('Teknisi / Toko / Vendor Pelaksana')
                            ->placeholder('Contoh: Bpk. Junaedi (Teknisi Elektronik) / CV Multi Servisindo')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('kontak_pelaksana')
                            ->label('Kontak / No. Telp Pelaksana')
                            ->placeholder('Contoh: 081298765432')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan_penyelesaian')
                            ->label('Catatan Hasil Penanganan & Garansi')
                            ->placeholder('Tuliskan rincian komponen yang diganti, nomor seri baru jika ganti unit, dan masa garansi servis...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Dokumentasi Google Drive (Hemat Penyimpanan Hosting)')
                    ->description('Tautkan folder Google Drive foto kondisi kerusakan, Berita Acara (BA) Kehilangan, atau kuitansi pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('link_drive_bukti')
                            ->label('Link Google Drive Dokumentasi & Kuitansi')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://drive.google.com/file/d/...')
                            ->url()
                            ->helperText('Sistem tidak menyimpan file foto/dokumen pada hosting server demi efisiensi storage. Tautkan link Google Drive yang memuat foto fisik kerusakan barang, scan Berita Acara Kehilangan, atau nota kuitansi perbaikan/pembelian unit baru.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openDriveLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link Google Drive di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Verifikasi & Disposisi Kepala Sekolah (Approval)')
                    ->description('Persetujuan tindakan dan alokasi anggaran penanganan oleh Kepala Sekolah')
                    ->schema([
                        Forms\Components\Select::make('status_persetujuan')
                            ->label('Status Persetujuan Kepala Sekolah')
                            ->options([
                                'Menunggu Persetujuan' => '⏳ Menunggu Persetujuan',
                                'Disetujui' => '✅ Disetujui (ACC Tindakan & Anggaran)',
                                'Perlu Evaluasi Ulang' => '⚠️ Perlu Evaluasi Ulang',
                                'Ditolak' => '❌ Ditolak',
                            ])
                            ->default('Menunggu Persetujuan')
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Instruksi Kepala Sekolah')
                            ->placeholder('Tuliskan arahan disposisi kepala sekolah terkait persetujuan biaya, klaim ganti rugi, atau jadwal penggantian unit...')
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
                Tables\Columns\TextColumn::make('nomor_pengurusan')
                    ->label('No. Tiket')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('inventory.nama_barang')
                    ->label('Sarpras / Barang')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (PerbaikanPenggantianSarpras $record): string => 
                        "[{$record->inventory?->kode_barang}] • Lokasi: " . ($record->lokasi_kejadian ?: 'Sekolah')
                    ),

                Tables\Columns\TextColumn::make('jenis_kasus')
                    ->label('Jenis Kasus')
                    ->badge()
                    ->colors([
                        'warning' => 'Rusak Ringan',
                        'danger' => 'Rusak Berat',
                        'gray' => 'Hilang / Tidak Ditemukan',
                        'info' => 'Aus Akibat Usia Teknis',
                        'primary' => 'Keadaan Kahar / Musibah',
                    ]),

                Tables\Columns\TextColumn::make('jumlah_unit')
                    ->label('Unit')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('rencana_tindakan')
                    ->label('Rencana Tindakan')
                    ->badge()
                    ->color('info')
                    ->limit(25),

                Tables\Columns\TextColumn::make('realisasi_biaya')
                    ->label('Biaya Realisasi')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_proses')
                    ->label('Status Pengerjaan')
                    ->badge()
                    ->colors([
                        'warning' => 'Dilaporkan',
                        'info' => 'Sedang Diperbaiki',
                        'primary' => 'Menunggu Penggantian Unit',
                        'success' => 'Selesai Diperbaiki',
                        'success' => 'Selesai Diganti Unit Baru',
                        'danger' => 'Dihapuskan / Afkir',
                    ]),

                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status KS')
                    ->badge()
                    ->colors([
                        'warning' => 'Menunggu Persetujuan',
                        'success' => 'Disetujui',
                        'danger' => 'Perlu Evaluasi Ulang',
                        'gray' => 'Ditolak',
                    ]),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->catatan_kepsek),

                Tables\Columns\TextColumn::make('link_drive_bukti')
                    ->label('Dokumen / Foto')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->getStateUsing(fn ($record) => !empty($record->link_drive_bukti) ? 'Buka Drive' : '-')
                    ->url(fn ($record) => $record->link_drive_bukti, shouldOpenInNewTab: true)
                    ->color(fn ($state) => $state === 'Buka Drive' ? 'primary' : 'gray')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kasus')
                    ->label('Jenis Kasus')
                    ->options([
                        'Rusak Ringan' => 'Rusak Ringan',
                        'Rusak Berat' => 'Rusak Berat',
                        'Hilang / Tidak Ditemukan' => 'Hilang / Tidak Ditemukan',
                        'Aus Akibat Usia Teknis' => 'Aus Akibat Usia Teknis',
                        'Keadaan Kahar / Musibah' => 'Keadaan Kahar / Musibah',
                    ]),

                Tables\Filters\SelectFilter::make('rencana_tindakan')
                    ->label('Tindakan')
                    ->options([
                        'Perbaikan Mandiri' => 'Perbaikan Mandiri',
                        'Servis Vendor / Garansi Resmi' => 'Servis Vendor / Garansi',
                        'Penggantian Komponen / Sparepart' => 'Penggantian Komponen',
                        'Penggantian Unit Baru (Replacement)' => 'Penggantian Unit Baru',
                        'Penghapusan Aset / Afkir (Write-off)' => 'Penghapusan / Afkir',
                        'Klaim Ganti Rugi Pihak Terkait' => 'Klaim Ganti Rugi',
                    ]),

                Tables\Filters\SelectFilter::make('status_proses')
                    ->label('Status Pengerjaan')
                    ->options([
                        'Dilaporkan' => 'Dilaporkan',
                        'Sedang Diperbaiki' => 'Sedang Diperbaiki',
                        'Menunggu Penggantian Unit' => 'Menunggu Penggantian Unit',
                        'Selesai Diperbaiki' => 'Selesai Diperbaiki',
                        'Selesai Diganti Unit Baru' => 'Selesai Diganti Unit Baru',
                        'Dihapuskan / Afkir' => 'Dihapuskan / Afkir',
                    ]),

                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status KS')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Evaluasi Ulang' => 'Perlu Evaluasi Ulang',
                        'Ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !auth()->user()?->hasRole('kepala_sekolah')),

                // Aksi Cepat Kepala Sekolah: ACC Tindakan & Anggaran
                Tables\Actions\Action::make('setujui_cepat')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (PerbaikanPenggantianSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        $record->status_persetujuan !== 'Disetujui'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('ACC Tindakan Perbaikan / Penggantian Sarpras')
                    ->modalDescription(fn ($record) => "Apakah Anda menyetujui penanganan {$record->rencana_tindakan} untuk {$record->inventory?->nama_barang} ({$record->nomor_pengurusan})?")
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Arahan Tindak Lanjut (Opsional)')
                            ->placeholder('Tuliskan instruksi atau arahan pembiayaan jika ada...'),
                    ])
                    ->action(function (PerbaikanPenggantianSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        Notification::make()
                            ->title('Penanganan Sarpras Berhasil Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Cepat Kepala Sekolah: Minta Evaluasi Ulang
                Tables\Actions\Action::make('minta_evaluasi')
                    ->label('Evaluasi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn (PerbaikanPenggantianSarpras $record) => 
                        auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && 
                        in_array($record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Evaluasi / Arahan')
                            ->required()
                            ->placeholder('Tuliskan pertimbangan biaya, opsi perbaikan alternatif, atau investigasi penyebab kerusakan/kehilangan...'),
                    ])
                    ->action(function (PerbaikanPenggantianSarpras $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Evaluasi Ulang',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'approved_by' => auth()->id(),
                            'tanggal_persetujuan' => now(),
                        ]);

                        Notification::make()
                            ->title('Catatan Evaluasi Terkirim ke Bagian Sarpras')
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
                Infolists\Components\Section::make('Identitas Tiket & Informasi Kasus Sarpras')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_pengurusan')
                            ->label('Nomor Tiket')
                            ->weight('bold')
                            ->color('primary')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('inventory.kode_barang')
                            ->label('Kode Barang')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('inventory.nama_barang')
                            ->label('Nama Sarpras')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('tanggal_laporan')
                            ->label('Tanggal Laporan')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('lokasi_kejadian')
                            ->label('Lokasi Kejadian')
                            ->icon('heroicon-m-map-pin'),

                        Infolists\Components\TextEntry::make('jumlah_unit')
                            ->label('Unit Terdampak')
                            ->suffix(' unit')
                            ->weight('bold'),

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

                        Infolists\Components\TextEntry::make('kronologi_penyebab')
                            ->label('Kronologi & Penyebab Kerusakan / Kehilangan')
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Pelapor, Rencana Tindakan & Realisasi Biaya')
                    ->schema([
                        Infolists\Components\TextEntry::make('pelapor_nama')
                            ->label('Pelapor Kerusakan / Masalah')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('pelapor_kontak')
                            ->label('Kontak Pelapor')
                            ->placeholder('-')
                            ->icon('heroicon-m-phone'),

                        Infolists\Components\TextEntry::make('rencana_tindakan')
                            ->label('Metode Penanganan')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('sumber_dana')
                            ->label('Sumber Anggaran')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('estimasi_biaya')
                            ->label('Estimasi Biaya')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('realisasi_biaya')
                            ->label('Realisasi Biaya Akhir')
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('pelaksana_teknisi')
                            ->label('Teknisi / Vendor Pelaksana')
                            ->placeholder('Teknisi Internal'),

                        Infolists\Components\TextEntry::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->date('d F Y')
                            ->placeholder('Masih dalam proses'),

                        Infolists\Components\TextEntry::make('keterangan_penyelesaian')
                            ->label('Catatan Penyelesaian Akhir & Garansi')
                            ->placeholder('Belum ada catatan penyelesaian')
                            ->columnSpanFull(),
                    ])->columns(4),

                Infolists\Components\Section::make('Dokumentasi Google Drive')
                    ->schema([
                        Infolists\Components\TextEntry::make('link_drive_bukti')
                            ->label('Tautan Berkas Google Drive (Foto Kerusakan, Berita Acara, & Kuitansi)')
                            ->getStateUsing(fn ($record) => !empty($record->link_drive_bukti) ? $record->link_drive_bukti : 'Belum ditautkan link Google Drive')
                            ->url(fn ($record) => $record->link_drive_bukti, shouldOpenInNewTab: true)
                            ->color(fn ($record) => !empty($record->link_drive_bukti) ? 'primary' : 'gray')
                            ->icon(fn ($record) => !empty($record->link_drive_bukti) ? 'heroicon-m-arrow-top-right-on-square' : null),
                    ]),

                Infolists\Components\Section::make('Verifikasi & Disposisi Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_persetujuan')
                            ->label('Status Persetujuan KS')
                            ->badge()
                            ->colors([
                                'warning' => 'Menunggu Persetujuan',
                                'success' => 'Disetujui',
                                'danger' => 'Perlu Evaluasi Ulang',
                                'gray' => 'Ditolak',
                            ]),

                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Kepala Sekolah Penyetuju')
                            ->placeholder('Belum ditinjau'),

                        Infolists\Components\TextEntry::make('tanggal_persetujuan')
                            ->label('Tanggal Persetujuan')
                            ->dateTime('d F Y, H:i')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan Disposisi / Arahan Kepala Sekolah')
                            ->placeholder('Tidak ada catatan khusus dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(3),
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
            'index' => Pages\ListPerbaikanPenggantianSarpras::route('/'),
            'create' => Pages\CreatePerbaikanPenggantianSarpras::route('/create'),
            'view' => Pages\ViewPerbaikanPenggantianSarpras::route('/{record}'),
            'edit' => Pages\EditPerbaikanPenggantianSarpras::route('/{record}/edit'),
        ];
    }
}
