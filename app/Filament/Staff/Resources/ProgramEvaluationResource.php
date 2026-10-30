<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ProgramEvaluationResource\Pages;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use App\Models\ProgramEvaluation;
use App\Models\ProgramHumas;
use App\Models\ProgramKepsek;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ProgramEvaluationResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isQuranTeacher()) return false;
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isQuranTeacher()) return false;

        return $user->hasAnyRole([
            'kepala_sekolah',
            'superadmin',
            'waka_kurikulum',
            'waka_kesiswaan',
            'sarpras',
            'humas',
            'bk',
            'wali_kelas',
            'guru_kelas',
            'ketua_yayasan',
            'pembina_yayasan',
        ]);
    }

    protected static ?string $model = ProgramEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Evaluasi Program';
    protected static ?string $modelLabel = 'Evaluasi Program';
    protected static ?string $pluralModelLabel = 'Evaluasi Program';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('kepala_sekolah') || $user?->hasRole('superadmin')) {
            return 'Manajemen Sekolah';
        }
        if ($user?->hasRole('waka_kurikulum')) {
            return 'Akademik & Kurikulum';
        }
        if ($user?->hasRole('waka_kesiswaan') || $user?->hasRole('bk')) {
            return 'Kesiswaan & Karakter';
        }
        if ($user?->hasRole('sarpras')) {
            return 'Sarpras';
        }
        if ($user?->hasRole('humas')) {
            return 'Humas';
        }
        if ($user?->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Manajemen Sekolah';
    }

    public static function getNavigationSort(): ?int
    {
        $user = auth()->user();
        if ($user?->hasRole('kepala_sekolah')) {
            return 2;
        }
        return 6;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('kepala_sekolah')) {
            $query = static::getModel()::whereIn('status', ['Menunggu Review KS', 'Draft']);
            if ($user->institution_id && $user->institution_id != 1) {
                $query->where('institution_id', $user->institution_id);
            }
            $count = $query->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Evaluasi program kerja yang menunggu review Kepala Sekolah';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) return $query;

        // Isolasi lembaga
        if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
            $query->where('institution_id', $user->institution_id);
        }

        // Pimpinan & yayasan melihat seluruh evaluasi lintas divisi
        if ($user->hasAnyRole(['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan'])) {
            return $query;
        }

        // Divisi memantau evaluasi divisi masing-masing
        if ($user->hasRole('waka_kurikulum')) {
            return $query->where('divisi', 'Kurikulum');
        }
        if ($user->hasRole('waka_kesiswaan')) {
            return $query->where('divisi', 'Kesiswaan');
        }
        if ($user->hasRole('sarpras')) {
            return $query->where('divisi', 'Sarpras');
        }
        if ($user->hasRole('humas')) {
            return $query->where('divisi', 'Humas');
        }
        if ($user->hasRole('bk')) {
            return $query->where('divisi', 'Bimbingan Konseling');
        }
        if ($user->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            return $query->where('divisi', 'Wali Kelas');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Hidden::make('institution_id')
                    ->default(fn () => auth()->user()?->institution_id),

                Forms\Components\Hidden::make('program_source_type'),
                Forms\Components\Hidden::make('program_source_id'),

                Forms\Components\Section::make('Identitas Program Kerja & Divisi')
                    ->description('Pilih divisi dan program kerja yang dievaluasi. Data program terintegrasi dengan database program kerja masing-masing divisi.')
                    ->schema([
                        Forms\Components\Select::make('divisi')
                            ->label('Divisi / Bidang Penanggung Jawab')
                            ->options([
                                'Kurikulum' => 'Waka Kurikulum (Akademik & Pembelajaran)',
                                'Kesiswaan' => 'Waka Kesiswaan (Kesiswaan, OSIS & Karakter)',
                                'Sarpras' => 'Sarana & Prasarana (Pengadaan & Fasilitas)',
                                'Humas' => 'Hubungan Masyarakat (Kemitraan & Wali Murid)',
                                'Bimbingan Konseling' => 'Bimbingan Konseling (BK)',
                                'Wali Kelas' => 'Program Wali Kelas (Pengelolaan Kelas)',
                                'Kepala Sekolah' => 'Kepala Sekolah (Manajemen & Proker Sekolah)',
                                'Umum' => 'Program Umum / Lintas Divisi',
                            ])
                            ->default(function () {
                                $user = auth()->user();
                                if ($user?->hasRole('waka_kurikulum')) return 'Kurikulum';
                                if ($user?->hasRole('waka_kesiswaan')) return 'Kesiswaan';
                                if ($user?->hasRole('sarpras')) return 'Sarpras';
                                if ($user?->hasRole('humas')) return 'Humas';
                                if ($user?->hasRole('bk')) return 'Bimbingan Konseling';
                                if ($user?->hasAnyRole(['wali_kelas', 'guru_kelas'])) return 'Wali Kelas';
                                return 'Kepala Sekolah';
                            })
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('selected_program_ref')
                            ->label('Pilih Program Kerja Terdaftar (Otomatis)')
                            ->placeholder('-- Pilih Program Kerja dari Database Divisi --')
                            ->options(function (Forms\Get $get) {
                                $divisi = $get('divisi');
                                $user = auth()->user();
                                $instId = $user?->institution_id;

                                $opts = [];
                                switch ($divisi) {
                                    case 'Kurikulum':
                                        $query = \App\Models\ProgramKurikulum::query();
                                        if ($instId && $instId != 1) $query->where('institution_id', $instId);
                                        foreach ($query->get() as $p) {
                                            $opts['Kurikulum:' . $p->id] = "[Kurikulum] {$p->nama_program} ({$p->status_pelaksanaan})";
                                        }
                                        break;
                                    case 'Kesiswaan':
                                        $query = \App\Models\ProgramKesiswaan::query();
                                        if ($instId && $instId != 1) $query->where('institution_id', $instId);
                                        foreach ($query->get() as $p) {
                                            $opts['Kesiswaan:' . $p->id] = "[Kesiswaan] {$p->nama_program} ({$p->status_pelaksanaan})";
                                        }
                                        break;
                                    case 'Sarpras':
                                        $query = \App\Models\ProgramSarpras::query();
                                        if ($instId && $instId != 1) $query->where('institution_id', $instId);
                                        foreach ($query->get() as $p) {
                                            $opts['Sarpras:' . $p->id] = "[Sarpras] {$p->nama_program} ({$p->status_pelaksanaan})";
                                        }
                                        break;
                                    case 'Humas':
                                        $query = \App\Models\ProgramHumas::query();
                                        if ($instId && $instId != 1) {
                                            $query->whereHas('user', fn($q) => $q->where('institution_id', $instId));
                                        }
                                        foreach ($query->get() as $p) {
                                            $opts['Humas:' . $p->id] = "[Humas] {$p->nama_program} ({$p->status_pelaksanaan})";
                                        }
                                        break;
                                    case 'Bimbingan Konseling':
                                        $query = \App\Models\BkWorkProgram::query();
                                        if ($instId && $instId != 1) $query->where('institution_id', $instId);
                                        foreach ($query->get() as $p) {
                                            $opts['BK:' . $p->id] = "[BK] {$p->nama_program} ({$p->status})";
                                        }
                                        break;
                                    case 'Wali Kelas':
                                        $query = \App\Models\ClassProgram::with('classroom');
                                        if ($instId && $instId != 1) {
                                            $query->whereHas('classroom', fn($q) => $q->where('institution_id', $instId));
                                        }
                                        foreach ($query->get() as $p) {
                                            $kls = $p->classroom?->name ?? 'Kelas';
                                            $opts['WaliKelas:' . $p->id] = "[Wali Kelas - {$kls}] {$p->nama_program} ({$p->status})";
                                        }
                                        break;
                                    case 'Kepala Sekolah':
                                        $query = \App\Models\ProgramKepsek::query();
                                        if ($instId && $instId != 1) $query->where('institution_id', $instId);
                                        foreach ($query->get() as $p) {
                                            $opts['Kepsek:' . $p->id] = "[Kepsek] {$p->nama_program} ({$p->status_pelaksanaan})";
                                        }
                                        break;
                                }
                                return $opts;
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!empty($state) && str_contains($state, ':')) {
                                    [$type, $id] = explode(':', $state, 2);
                                    $modelClass = match ($type) {
                                        'Kurikulum' => \App\Models\ProgramKurikulum::class,
                                        'Kesiswaan' => \App\Models\ProgramKesiswaan::class,
                                        'Sarpras' => \App\Models\ProgramSarpras::class,
                                        'Humas' => \App\Models\ProgramHumas::class,
                                        'BK' => \App\Models\BkWorkProgram::class,
                                        'WaliKelas' => \App\Models\ClassProgram::class,
                                        'Kepsek' => \App\Models\ProgramKepsek::class,
                                        default => null,
                                    };
                                    if ($modelClass) {
                                        $item = $modelClass::find($id);
                                        if ($item) {
                                            $set('nama_program', $item->nama_program ?? $item->judul_program ?? '');
                                            $set('program_source_type', $modelClass);
                                            $set('program_source_id', $item->id);
                                            if (!empty($item->tahun_ajaran)) {
                                                $set('tahun_ajaran', $item->tahun_ajaran);
                                            }
                                            $rawStatus = strtolower($item->status_pelaksanaan ?? $item->status ?? '');
                                            if (str_contains($rawStatus, 'selesai') || str_contains($rawStatus, 'terlaksana')) {
                                                $set('persentase_ketercapaian', 100);
                                                $set('predikat', 'Sangat Baik');
                                            } elseif (str_contains($rawStatus, 'jalan') || str_contains($rawStatus, 'proses')) {
                                                $set('persentase_ketercapaian', 60);
                                                $set('predikat', 'Baik');
                                            }
                                        }
                                    }
                                }
                            })
                            ->helperText('Memilih program kerja akan otomatis mengisi nama dan status program.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program Kerja yang Dievaluasi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Periode Evaluasi')
                            ->options([
                                'Semester Ganjil' => 'Semester Ganjil',
                                'Semester Genap' => 'Semester Genap',
                                'Tahunan' => 'Tahunan / Akhir Tahun',
                            ])
                            ->default('Semester Ganjil')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Ketercapaian & Predikat Kinerja')
                    ->schema([
                        Forms\Components\TextInput::make('persentase_ketercapaian')
                            ->label('Persentase Ketercapaian (%)')
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
                                'Sangat Baik' => 'Sangat Baik (>=90%)',
                                'Baik' => 'Baik (75% - 89%)',
                                'Cukup' => 'Cukup (60% - 74%)',
                                'Kurang' => 'Kurang / Perlu Perbaikan (<60%)',
                            ])
                            ->default('Baik')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status Evaluasi')
                            ->options([
                                'Menunggu Review KS' => 'Menunggu Review KS',
                                'Disetujui KS' => 'Disetujui KS (Final)',
                                'Perlu Perbaikan' => 'Perlu Perbaikan',
                                'Draft' => 'Draft',
                            ])
                            ->default('Menunggu Review KS')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Analisis Pelaksanaan, Kendala & Solusi Tindak Lanjut')
                    ->schema([
                        Forms\Components\Textarea::make('realisasi_kegiatan')
                            ->label('Realisasi & Bukti Pelaksanaan Faktual')
                            ->placeholder('Jelaskan bagaimana program ini terlaksana secara nyata di lapangan...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('kendala')
                            ->label('Kendala & Hambatan yang Dihadapi')
                            ->placeholder('Hambatan anggaran, sumber daya, teknis, atau jadwal yang dialami...')
                            ->rows(3),

                        Forms\Components\Textarea::make('solusi_tindak_lanjut')
                            ->label('Solusi & Rekomendasi Tindak Lanjut')
                            ->placeholder('Saran perbaikan, kelanjutan, atau modifikasi untuk periode berikutnya...')
                            ->rows(3),

                        Forms\Components\TextInput::make('file_evaluasi')
                            ->label('Link Google Drive LPJ / Bukti Dokumentasi')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan folder Google Drive berisi berkas LPJ, foto kegiatan, atau kuitansi untuk menghemat kapasitas hosting.')
                            ->maxLength(2048)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan & Arahan Kepala Sekolah')
                            ->placeholder('Catatan, apresiasi, atau instruksi tindak lanjut dari Kepala Sekolah...')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin'])),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('divisi')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Kurikulum' => 'info',
                        'Kesiswaan' => 'success',
                        'Sarpras' => 'warning',
                        'Humas' => 'danger',
                        'Bimbingan Konseling' => 'gray',
                        'Wali Kelas' => 'primary',
                        'Kepala Sekolah' => 'success',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program Kerja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ProgramEvaluation $record) => $record->tahun_ajaran . ' • ' . $record->semester),

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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status KS')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Disetujui KS', 'Final' => 'success',
                        'Menunggu Review KS' => 'warning',
                        'Perlu Perbaikan' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'Disetujui KS', 'Final' => '✅ Disetujui',
                        'Menunggu Review KS' => '⏳ Menunggu Review',
                        'Perlu Perbaikan' => '⚠️ Perlu Perbaikan',
                        default => ucfirst($state ?? 'Draft'),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->placeholder('-')
                    ->limit(35)
                    ->tooltip(fn (ProgramEvaluation $record) => $record->catatan_kepsek ?? '-'),

                Tables\Columns\TextColumn::make('file_evaluasi')
                    ->label('LPJ Drive')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka LPJ' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state)),

                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('Penginput')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Evaluasi')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('divisi')
                    ->options([
                        'Kurikulum' => 'Kurikulum',
                        'Kesiswaan' => 'Kesiswaan',
                        'Sarpras' => 'Sarpras',
                        'Humas' => 'Humas',
                        'Bimbingan Konseling' => 'Bimbingan Konseling',
                        'Wali Kelas' => 'Wali Kelas',
                        'Kepala Sekolah' => 'Kepala Sekolah',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Menunggu Review KS' => 'Menunggu Review KS',
                        'Disetujui KS' => 'Disetujui KS',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Draft' => 'Draft',
                    ]),

                Tables\Filters\SelectFilter::make('predikat')
                    ->options([
                        'Sangat Baik' => 'Sangat Baik',
                        'Baik' => 'Baik',
                        'Cukup' => 'Cukup',
                        'Kurang' => 'Kurang',
                    ]),

                Tables\Filters\SelectFilter::make('tahun_ajaran')
                    ->options([
                        '2026/2027' => '2026/2027',
                        '2025/2026' => '2025/2026',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review_evaluasi')
                    ->label('Tinjau KS')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->button()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->modalHeading(fn (ProgramEvaluation $record) => "Tinjauan Kepala Sekolah: {$record->nama_program}")
                    ->modalDescription('Tinjau laporan evaluasi program kerja dan berikan arahan atau apresiasi.')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Tinjauan')
                            ->options([
                                'Disetujui KS' => '✅ Disetujui (Evaluasi Diterima & Disahkan)',
                                'Perlu Perbaikan' => '⚠️ Perlu Perbaikan (Laporan Perlu Direvisi)',
                                'Menunggu Review KS' => '⏳ Menunggu Review',
                            ])
                            ->default(fn (ProgramEvaluation $record) => in_array($record->status, ['Disetujui KS', 'Perlu Perbaikan', 'Menunggu Review KS']) ? $record->status : 'Disetujui KS')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan & Arahan Kepala Sekolah')
                            ->placeholder('Tuliskan tanggapan, apresiasi ketercapaian, atau instruksi perbaikan...')
                            ->default(fn (ProgramEvaluation $record) => $record->catatan_kepsek)
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ProgramEvaluation $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'reviewed_by' => auth()->id(),
                            'tanggal_review' => now(),
                        ]);

                        Notification::make()
                            ->title('Evaluasi Program Berhasil Ditinjau')
                            ->body("Tinjauan dan arahan untuk {$record->nama_program} ({$record->divisi}) telah tersimpan.")
                            ->success()
                            ->send();
                    }),

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
            'index' => Pages\ManageProgramEvaluations::route('/'),
        ];
    }
}
