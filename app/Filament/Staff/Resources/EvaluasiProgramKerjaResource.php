<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\EvaluasiProgramKerjaResource\Pages;
use App\Models\EvaluasiProgramKerja;
use App\Models\HarmonisasiInternal;
use App\Models\HubunganInstitusi;
use App\Models\HubunganMasyarakat;
use App\Models\HubunganWaliMurid;
use App\Models\ProgramHumas;
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
use Illuminate\Support\Facades\Schema;

class EvaluasiProgramKerjaResource extends Resource
{
    protected static ?string $model = EvaluasiProgramKerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Humas';
    protected static ?string $navigationLabel = 'Evaluasi Program Kerja';
    protected static ?string $modelLabel = 'Evaluasi Program Kerja';
    protected static ?string $pluralModelLabel = 'Evaluasi Program Kerja';
    protected static ?int $navigationSort = 3;

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 6 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\Hidden::make('program_source_type'),
                Forms\Components\Hidden::make('program_source_id'),

                Forms\Components\Section::make('Pilih Program Kerja yang Telah di-ACC Kepala Sekolah')
                    ->description('Silakan pilih salah satu program kerja Humas yang telah berstatus Disetujui (ACC) oleh Kepala Sekolah')
                    ->schema([
                        Forms\Components\Select::make('program_selector')
                            ->label('Pilih Program Kerja (Hanya yang sudah di-ACC)')
                            ->placeholder('-- Klik untuk memilih program kerja yang telah disetujui Kepala Sekolah --')
                            ->options(function () {
                                $user = auth()->user();
                                $instId = $user?->institution_id;
                                $options = [];

                                // 1. Harmonisasi Internal
                                $harmonisasi = HarmonisasiInternal::where('status_persetujuan', 'Disetujui')
                                    ->when($instId && !$user->hasRole('superadmin'), fn($q) => $q->where('institution_id', $instId))
                                    ->get();
                                foreach ($harmonisasi as $item) {
                                    $tgl = $item->tanggal_pelaksanaan ? $item->tanggal_pelaksanaan->format('d/m/Y') : '-';
                                    $options["App\\Models\\HarmonisasiInternal:{$item->id}"] = "[Harmonisasi Internal] {$item->nama_kegiatan} ({$tgl})";
                                }

                                // 2. Hubungan Wali Murid
                                $waliMurid = HubunganWaliMurid::where('status_persetujuan', 'Disetujui')
                                    ->when($instId && !$user->hasRole('superadmin'), fn($q) => $q->where('institution_id', $instId))
                                    ->get();
                                foreach ($waliMurid as $item) {
                                    $tgl = $item->tanggal_pelaksanaan ? $item->tanggal_pelaksanaan->format('d/m/Y') : '-';
                                    $options["App\\Models\\HubunganWaliMurid:{$item->id}"] = "[Wali Murid] {$item->nama_kegiatan} ({$tgl})";
                                }

                                // 3. Hubungan Institusi
                                $institusi = HubunganInstitusi::where('status_persetujuan', 'Disetujui')
                                    ->when($instId && !$user->hasRole('superadmin'), fn($q) => $q->where('institution_id', $instId))
                                    ->get();
                                foreach ($institusi as $item) {
                                    $tgl = $item->tanggal_pelaksanaan ? $item->tanggal_pelaksanaan->format('d/m/Y') : '-';
                                    $options["App\\Models\\HubunganInstitusi:{$item->id}"] = "[Hubungan Institusi] {$item->nama_kegiatan} ({$tgl})";
                                }

                                // 4. Hubungan Masyarakat
                                $masyarakat = HubunganMasyarakat::where('status_persetujuan', 'Disetujui')
                                    ->when($instId && !$user->hasRole('superadmin'), fn($q) => $q->where('institution_id', $instId))
                                    ->get();
                                foreach ($masyarakat as $item) {
                                    $tgl = $item->tanggal_pelaksanaan ? $item->tanggal_pelaksanaan->format('d/m/Y') : '-';
                                    $options["App\\Models\\HubunganMasyarakat:{$item->id}"] = "[Hubungan Masyarakat] {$item->nama_kegiatan} ({$tgl})";
                                }

                                // 5. Program Kerja Humas
                                $prokerHumas = ProgramHumas::query()
                                    ->when(Schema::hasColumn('program_humas', 'status_persetujuan'), fn($q) => $q->where('status_persetujuan', 'Disetujui'))
                                    ->when($instId && !$user->hasRole('superadmin'), function ($q) use ($instId) {
                                        $q->whereHas('user', fn($uq) => $uq->where('institution_id', $instId));
                                    })
                                    ->get();
                                foreach ($prokerHumas as $item) {
                                    $options["App\\Models\\ProgramHumas:{$item->id}"] = "[Program Kerja Humas] {$item->nama_program}";
                                }

                                return $options;
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                [$modelClass, $id] = explode(':', $state);
                                $record = $modelClass::find($id);
                                if (!$record) return;

                                $set('program_source_type', $modelClass);
                                $set('program_source_id', $id);

                                $name = $record->nama_kegiatan ?? $record->nama_program ?? '';
                                $set('nama_program', $name);

                                $category = match ($modelClass) {
                                    'App\\Models\\HarmonisasiInternal' => 'Harmonisasi Internal',
                                    'App\\Models\\HubunganWaliMurid' => 'Hubungan dengan Wali Murid',
                                    'App\\Models\\HubunganInstitusi' => 'Hubungan Institusi',
                                    'App\\Models\\HubunganMasyarakat' => 'Hubungan Masyarakat',
                                    'App\\Models\\ProgramHumas' => 'Program Kerja Humas',
                                    default => 'Humas',
                                };
                                $set('kategori_program', $category);

                                if (isset($record->target_peserta)) {
                                    $set('target_peserta', $record->target_peserta);
                                } elseif (isset($record->sasaran)) {
                                    $set('target_peserta', $record->sasaran);
                                }

                                if (isset($record->tanggal_pelaksanaan)) {
                                    $set('tanggal_pelaksanaan', $record->tanggal_pelaksanaan?->format('Y-m-d'));
                                }

                                if (isset($record->lokasi_kegiatan)) {
                                    $set('lokasi_kegiatan', $record->lokasi_kegiatan);
                                }
                            })
                            ->columnSpanFull()
                            ->hiddenOn('edit'),
                    ]),

                Forms\Components\Section::make('Identitas Program Kerja')
                    ->description('Data terambil otomatis dari program kerja yang telah disetujui')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program / Kegiatan')
                            ->required()
                            ->readOnly()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('kategori_program')
                            ->label('Kategori Program')
                            ->required()
                            ->readOnly(),

                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->readOnly(),

                        Forms\Components\TextInput::make('target_peserta')
                            ->label('Target Peserta / Sasaran')
                            ->readOnly(),

                        Forms\Components\TextInput::make('lokasi_kegiatan')
                            ->label('Lokasi Kegiatan')
                            ->readOnly(),
                    ])->columns(2),

                Forms\Components\Section::make('Hasil Evaluasi Pelaksanaan Kegiatan')
                    ->description('Isi evaluasi faktual atas pelaksanaan program kerja yang telah dijalankan')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_evaluasi')
                            ->label('Tanggal Evaluasi')
                            ->default(now()->toDateString())
                            ->required(),

                        Forms\Components\Select::make('status_keterlaksanaan')
                            ->label('Status Keterlaksanaan')
                            ->options([
                                'Terlaksana Sepenuhnya' => 'Terlaksana Sepenuhnya',
                                'Terlaksana Sebagian' => 'Terlaksana Sebagian',
                                'Tidak Terlaksana' => 'Tidak Terlaksana',
                            ])
                            ->default('Terlaksana Sepenuhnya')
                            ->required(),

                        Forms\Components\TextInput::make('persentase_ketercapaian')
                            ->label('Persentase Ketercapaian (0-100%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(100)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $pct = (int) $state;
                                if ($pct >= 90) {
                                    $set('predikat', 'Sangat Baik');
                                } elseif ($pct >= 75) {
                                    $set('predikat', 'Baik');
                                } elseif ($pct >= 60) {
                                    $set('predikat', 'Cukup');
                                } else {
                                    $set('predikat', 'Kurang');
                                }
                            }),

                        Forms\Components\Select::make('predikat')
                            ->label('Predikat Capaian')
                            ->options([
                                'Sangat Baik' => 'Sangat Baik',
                                'Baik' => 'Baik',
                                'Cukup' => 'Cukup',
                                'Kurang' => 'Kurang / Perlu Perbaikan',
                            ])
                            ->default('Sangat Baik')
                            ->required(),

                        Forms\Components\TextInput::make('link_drive_lpj')
                            ->label('Link Dokumen LPJ / Evaluasi (Google Drive)')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://docs.google.com/...')
                            ->url()
                            ->helperText('Sistem hanya menampung tautan/link Google Drive dari dokumen LPJ, bukti presensi, dokumentasi kegiatan, atau form feedback.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('realisasi_kegiatan')
                            ->label('Realisasi & Bukti Pelaksanaan')
                            ->placeholder('Jelaskan bagaimana kegiatan ini terlaksana secara faktual, jumlah peserta hadir vs target, output yang tercapai...')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('kendala')
                            ->label('Kendala & Hambatan')
                            ->placeholder('Hambatan teknis, logistik, komunikasi, waktu, atau anggaran yang dihadapi...')
                            ->rows(3),

                        Forms\Components\Textarea::make('solusi_tindak_lanjut')
                            ->label('Solusi & Rekomendasi Tindak Lanjut')
                            ->placeholder('Saran perbaikan dan rencana modifikasi untuk kegiatan periode berikutnya...')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Tanggapan & Arahan Kepala Sekolah')
                    ->schema([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->placeholder('Catatan atau arahan pimpinan atas hasil evaluasi...')
                            ->disabled(fn () => !auth()->user()?->hasRole('kepala_sekolah'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identitas Program Kerja yang Dievaluasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_program')
                            ->label('Nama Program / Kegiatan')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('kategori_program')
                            ->label('Kategori Program')
                            ->badge()
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->date('d F Y')
                            ->icon('heroicon-m-calendar-days'),

                        Infolists\Components\TextEntry::make('target_peserta')
                            ->label('Target Peserta')
                            ->icon('heroicon-m-users'),

                        Infolists\Components\TextEntry::make('lokasi_kegiatan')
                            ->label('Lokasi Kegiatan')
                            ->icon('heroicon-m-map-pin'),
                    ])->columns(2),

                Infolists\Components\Section::make('Hasil Evaluasi Pelaksanaan')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal_evaluasi')
                            ->label('Tanggal Evaluasi')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('status_keterlaksanaan')
                            ->label('Status Keterlaksanaan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Terlaksana Sepenuhnya' => 'success',
                                'Terlaksana Sebagian' => 'warning',
                                'Tidak Terlaksana' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('persentase_ketercapaian')
                            ->label('Ketercapaian')
                            ->formatStateUsing(fn ($state) => $state . '%')
                            ->badge()
                            ->color(fn ($state): string => match (true) {
                                $state >= 90 => 'success',
                                $state >= 75 => 'info',
                                $state >= 60 => 'warning',
                                default => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('predikat')
                            ->label('Predikat Capaian')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Sangat Baik' => 'success',
                                'Baik' => 'info',
                                'Cukup' => 'warning',
                                default => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('link_drive_lpj')
                            ->label('Dokumen LPJ / Evaluasi di Google Drive')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Dokumen LPJ di Google Drive ↗' : 'Tidak ada link dokumen')
                            ->url(fn ($record) => $record->link_drive_lpj, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color(fn ($state) => $state ? 'info' : 'gray'),

                        Infolists\Components\TextEntry::make('realisasi_kegiatan')
                            ->label('Realisasi & Bukti Pelaksanaan')
                            ->columnSpanFull()
                            ->prose(),

                        Infolists\Components\TextEntry::make('kendala')
                            ->label('Kendala & Hambatan')
                            ->default('Tidak ada kendala berarti')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('solusi_tindak_lanjut')
                            ->label('Solusi & Rekomendasi Tindak Lanjut')
                            ->default('Pertahankan dan tingkatkan kualitas kegiatan')
                            ->columnSpan(1),
                    ])->columns(2),

                Infolists\Components\Section::make('Tanggapan & Catatan Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('reviewer.name')
                            ->label('Ditinjau Oleh')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('tanggal_review')
                            ->label('Waktu Peninjauan')
                            ->dateTime('d F Y, H:i WIB')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan / Arahan Kepala Sekolah')
                            ->default('Belum ada catatan dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(2),
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
                    ->weight('bold')
                    ->description(fn (EvaluasiProgramKerja $record) => $record->kategori_program),

                Tables\Columns\TextColumn::make('tanggal_pelaksanaan')
                    ->label('Tgl Pelaksanaan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_keterlaksanaan')
                    ->label('Keterlaksanaan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Terlaksana Sepenuhnya' => 'success',
                        'Terlaksana Sebagian' => 'warning',
                        'Tidak Terlaksana' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('persentase_ketercapaian')
                    ->label('Capaian')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'info',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Baik' => 'success',
                        'Baik' => 'info',
                        'Cukup' => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('link_drive_lpj')
                    ->label('Dokumen LPJ')
                    ->formatStateUsing(fn ($state) => $state ? 'Buka LPJ' : '-')
                    ->url(fn (EvaluasiProgramKerja $record) => $record->link_drive_lpj, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->catatan_kepsek),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_program')
                    ->label('Kategori Program')
                    ->options([
                        'Harmonisasi Internal' => 'Harmonisasi Internal',
                        'Hubungan dengan Wali Murid' => 'Hubungan dengan Wali Murid',
                        'Hubungan Institusi' => 'Hubungan Institusi',
                        'Hubungan Masyarakat' => 'Hubungan Masyarakat',
                        'Program Kerja Humas' => 'Program Kerja Humas',
                    ]),
                Tables\Filters\SelectFilter::make('status_keterlaksanaan')
                    ->options([
                        'Terlaksana Sepenuhnya' => 'Terlaksana Sepenuhnya',
                        'Terlaksana Sebagian' => 'Terlaksana Sebagian',
                        'Tidak Terlaksana' => 'Tidak Terlaksana',
                    ]),
                Tables\Filters\SelectFilter::make('predikat')
                    ->options([
                        'Sangat Baik' => 'Sangat Baik',
                        'Baik' => 'Baik',
                        'Cukup' => 'Cukup',
                        'Kurang' => 'Kurang',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye'),

                // Aksi Khusus Kepala Sekolah: Beri Catatan / Review Evaluasi
                Tables\Actions\Action::make('tinjauKepsek')
                    ->label('Beri Arahan KS')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Tanggapan & Arahan Kepala Sekolah')
                            ->required()
                            ->placeholder('Tuliskan masukan, apresiasi, atau arahan perbaikan terhadap hasil evaluasi program ini...')
                            ->default(fn (EvaluasiProgramKerja $record) => $record->catatan_kepsek),
                    ])
                    ->action(function (EvaluasiProgramKerja $record, array $data) {
                        $record->update([
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'reviewed_by' => auth()->id(),
                            'tanggal_review' => now(),
                        ]);
                        Notification::make()
                            ->title('Catatan Kepala Sekolah Berhasil Disimpan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
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
            'index' => Pages\ListEvaluasiProgramKerjas::route('/'),
            'create' => Pages\CreateEvaluasiProgramKerja::route('/create'),
            'view' => Pages\ViewEvaluasiProgramKerja::route('/{record}'),
            'edit' => Pages\EditEvaluasiProgramKerja::route('/{record}/edit'),
        ];
    }
}
