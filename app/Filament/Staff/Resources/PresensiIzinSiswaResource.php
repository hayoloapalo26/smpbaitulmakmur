<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\PrayerAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PresensiIzinSiswaResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Presensi & Izin Harian Siswa';
    protected static ?string $modelLabel = 'Siswa';
    protected static ?string $pluralModelLabel = 'Presensi & Izin Harian Siswa';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user?->hasRole('kepala_sekolah')) {
            return 'Perencanaan Administrasi Pembelajaran';
        }
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return 'Rekap Presensi Siswa';
        }
        return 'Presensi & Izin Harian Siswa';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isPureQuranTeacher()) return false;
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isPureQuranTeacher()) return false;

        // Pimpinan & Penanggung Jawab Kesiswaan: Selalu berhak mengakses penuh
        if ($user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kesiswaan'])) {
            return true;
        }

        // Wali Kelas / Guru Kelas: Berhak memantau siswa kelas binaannya
        if ($user->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            return true;
        }

        // Guru Piket: HANYA berhak mengakses dan melihat menu ketika HARI H dia sedang bertugas piket
        if ($user->hasRole('guru_piket') || $user->isPiketToday()) {
            return $user->isPiketToday();
        }

        return false;
    }

    public static function getActiveMode($livewire = null): string
    {
        return ($livewire && isset($livewire->mode)) ? $livewire->mode : 'harian';
    }

    public static function getActiveDate($livewire = null): string
    {
        if ($livewire && isset($livewire->filterTanggal) && !empty($livewire->filterTanggal)) {
            return $livewire->filterTanggal;
        }
        return Carbon::today()->toDateString();
    }

    public static function getActiveDateRange($livewire = null): array
    {
        $start = ($livewire && !empty($livewire->filterTanggalMulai)) ? $livewire->filterTanggalMulai : Carbon::today()->startOfMonth()->toDateString();
        $end = ($livewire && !empty($livewire->filterTanggalSelesai)) ? $livewire->filterTanggalSelesai : Carbon::today()->toDateString();
        return [$start, $end];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where('status_siswa', 'aktif')
            ->with([
                'classroom',
                'dailyAttendances' => function ($q) {
                    $q->withoutGlobalScopes();
                },
            ]);

        $user = Auth::user();

        // Pastikan seluruh data presensi siswa terfilter murni sesuai lembaga aktif (SD vs SMP terisolasi 100%)
        $instId = \App\Models\Institution::getActiveInstitutionId();
        $query->where('institution_id', $instId);

        if ($user && ($user->hasAnyRole(['kepala_sekolah', 'waka_kesiswaan', 'guru_piket', 'superadmin']) || $user->isPiketToday())) {
            return $query;
        }

        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereIn('classroom_id', $classroomIds);
        }

        return $query;
    }

    /**
     * Skema Form utama (mempertahankan bukti_izin sebagai TextInput URL Google Drive).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Nama Siswa')
                    ->options(function () {
                        $instId = Auth::user()?->institution_id ?: 2;
                        return Student::withoutGlobalScopes()
                            ->where('status_siswa', 'aktif')
                            ->where('institution_id', $instId)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Presensi')
                    ->default(Carbon::today())
                    ->required(),

                Forms\Components\Select::make('tipe_absen')
                    ->label('Kategori Sesi')
                    ->options([
                        'masuk'         => '🏫 Kehadiran di Sekolah (Masuk Pagi)',
                        'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                        'keputrian'     => '🌸 Kegiatan Keputrian (Jumat Putri)',
                        'sholat_ashar'  => '🕌 Sholat Ashar & Pulang',
                        'pulang'        => '🏠 Jam Pulang',
                    ])
                    ->default('masuk')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran / Izin')
                    ->options([
                        'hadir'    => '✅ Hadir',
                        'sakit'    => '🤒 Sakit',
                        'izin'     => '📋 Izin',
                        'alpa'     => '❌ Alpa / Tanpa Keterangan',
                        'halangan' => '🌸 Halangan (Khusus Sholat Siswi)',
                    ])
                    ->default('sakit')
                    ->required(),

                Forms\Components\TimePicker::make('waktu_kedatangan')
                    ->label('Jam Scan / Masuk')
                    ->default('06:40'),

                Forms\Components\Toggle::make('is_late')
                    ->label('Terlambat?')
                    ->default(false),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Alasan / Keterangan')
                    ->placeholder('Contoh: Sakit demam berobat ke dokter / Izin acara keluarga...')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('bukti_izin')
                    ->label('Link Google Drive Bukti Surat Izin / Sakit / Foto')
                    ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                    ->url()
                    ->prefixIcon('heroicon-o-link')
                    ->helperText('Tautkan link Google Drive bukti surat izin/sakit siswa untuk menghemat ruang penyimpanan hosting.')
                    ->maxLength(2048)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS / Pin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Student $record) => ($state ?: '-') . ' / ' . ($record->fingerspot_pin ?: '-')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Student $record) => ($record->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') . ' • Kelas ' . ($record->classroom?->name ?? '-')),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->placeholder('-'),

                // 1. Kedatangan Waktu di Sekolah (Finger)
                Tables\Columns\TextColumn::make('kedatangan_finger')
                    ->label('1. Kedatangan (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $mode = static::getActiveMode($livewire);

                        if ($mode === 'rentang') {
                            [$start, $end] = static::getActiveDateRange($livewire);
                            $atts = $record->dailyAttendances->filter(function ($a) use ($start, $end) {
                                $d = Carbon::parse($a->tanggal)->toDateString();
                                return $d >= $start && $d <= $end && $a->tipe_absen === 'masuk';
                            })->sortBy(fn ($a) => ($a->waktu_kedatangan ?: '99:99:99') . '_' . $a->id)
                              ->unique(fn ($a) => Carbon::parse($a->tanggal)->toDateString());

                            $tepat = $atts->where('status', 'hadir')->where('is_late', false)->count();
                            $telat = $atts->where('status', 'hadir')->where('is_late', true)->count();
                            $sakit = $atts->where('status', 'sakit')->count();
                            $izin  = $atts->where('status', 'izin')->count();
                            $alpa  = $atts->where('status', 'alpa')->count();

                            $out = '<div class="flex flex-col gap-1">';
                            $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 dark:text-emerald-300 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">✅ ' . $tepat . 'x Tepat</span>';
                            if ($telat > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 dark:text-rose-300 dark:bg-rose-950/40 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">⏰ ' . $telat . 'x Telat</span>';
                            }
                            if ($sakit > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">🤒 ' . $sakit . 'x Sakit</span>';
                            }
                            if ($izin > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700 bg-sky-50 dark:text-sky-300 dark:bg-sky-950/40 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800">📋 ' . $izin . 'x Izin</span>';
                            }
                            if ($alpa > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 dark:text-rose-300 dark:bg-rose-950/40 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">❌ ' . $alpa . 'x Alpha</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // Mode Harian
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, 'masuk');

                        // 1. Jika Siswa Sakit, Izin, atau Alpha khusus kedatangan pagi
                        if ($att && in_array($att->status, ['sakit', 'izin', 'alpa'])) {
                            $statusLabel = match ($att->status) {
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                default => '❌ Alpha',
                            };
                            $badgeColor = match ($att->status) {
                                'sakit' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                'izin'  => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                                default => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                            };
                            $ket = $att->keterangan;
                            $out = '<div class="flex flex-col gap-0.5">';
                            $out .= '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border ' . $badgeColor . '">' . $statusLabel . '</span>';
                            if (!empty($ket)) {
                                $out .= '<span class="text-[11px] text-slate-500 dark:text-slate-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // 2. Jika Hadir Scan / Input Manual di Pagi Hari
                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $isLate = $att->waktu_kedatangan ? (Carbon::parse($att->waktu_kedatangan)->format('H:i') >= '06:46') : (bool) $att->is_late;

                            // Cek apakah siswa izin pulang siang (sakit / izin di sesi dhuhur atau ashar)
                            $isFriday = Carbon::parse($tanggal)->isFriday();
                            $targetTipeSiang = ($isFriday && $record->jenis_kelamin === 'P') ? ['keputrian', 'sholat_dhuhur'] : ['sholat_dhuhur', 'keputrian'];
                            $attSiang = $record->getAttendanceOnDate($tanggal, $targetTipeSiang);
                            $badgePulangSiang = '';
                            if ($attSiang && in_array($attSiang->status, ['sakit', 'izin'])) {
                                $pulangLabel = $attSiang->status === 'sakit' ? 'Pulang Sakit' : 'Pulang Izin';
                                $badgePulangSiang = '<span class="text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 px-1.5 py-0.5 rounded font-bold border border-amber-300 dark:border-amber-700" title="' . e($attSiang->keterangan ?? '') . '">🚪 ' . $pulangLabel . '</span>';
                            }

                            if ($isLate) {
                                $accStatus = $att->late_approval_status ?? 'pending';
                                $badgeAcc = match ($accStatus) {
                                    'approved' => '<span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">Terlambat (ACC +5)</span>',
                                    'rejected' => '<span class="text-[10px] bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200 px-1.5 py-0.5 rounded font-bold">Terlambat (Dispensasi)</span>',
                                    default    => '<span class="text-[10px] bg-amber-200 text-amber-900 dark:bg-amber-900 dark:text-amber-200 px-1.5 py-0.5 rounded font-bold animate-pulse">Terlambat (Menunggu ACC Waka)</span>',
                                };

                                return '<div class="inline-flex flex-wrap items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    ' . $badgeAcc . '
                                    ' . $badgePulangSiang . '
                                </div>';
                            }

                            return '<div class="inline-flex flex-wrap items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">Tepat Waktu</span>
                                ' . $badgePulangSiang . '
                            </div>';
                        }

                        // 3. Jika tidak ada record 'masuk', tapi ada status izin seharian
                        $excused = $record->getGeneralExcusedOnDate($tanggal);
                        if ($excused) {
                            $statusLabel = match ($excused->status) {
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                default => '❌ Alpha',
                            };
                            $badgeColor = match ($excused->status) {
                                'sakit' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                'izin'  => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                                default => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                            };
                            $ket = $excused->keterangan;
                            $out = '<div class="flex flex-col gap-0.5">';
                            $out .= '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border ' . $badgeColor . '">' . $statusLabel . '</span>';
                            if (!empty($ket)) {
                                $out .= '<span class="text-[11px] text-slate-500 dark:text-slate-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // 4. Belum Scan Kedatangan
                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Belum Scan
                        </span>';
                    }),

                // 2. Sholat Dhuhur / Jum'at / Kegiatan Keputrian (Finger)
                Tables\Columns\TextColumn::make('dhuhur_finger')
                    ->label(fn ($livewire) => (static::getActiveMode($livewire) === 'harian' && Carbon::parse(static::getActiveDate($livewire))->isFriday()) ? '2. Sholat Jum\'at (Putra) / Keputrian (Putri)' : '2. Sholat Dhuhur (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $mode = static::getActiveMode($livewire);

                        if ($mode === 'rentang') {
                            [$start, $end] = static::getActiveDateRange($livewire);
                            $atts = $record->dailyAttendances->filter(function ($a) use ($start, $end) {
                                $d = Carbon::parse($a->tanggal)->toDateString();
                                return $d >= $start && $d <= $end && in_array($a->tipe_absen, ['sholat_dhuhur', 'keputrian']);
                            })->sortBy(fn ($a) => ($a->waktu_kedatangan ?: '99:99:99') . '_' . $a->id)
                              ->unique(fn ($a) => Carbon::parse($a->tanggal)->toDateString());

                            $tepat = $atts->where('status', 'hadir')->where('is_late', false)->count();
                            $telat = $atts->where('status', 'hadir')->where('is_late', true)->count();
                            $halangan = $atts->where('status', 'halangan')->count();
                            $sakit = $atts->where('status', 'sakit')->count();
                            $izin  = $atts->where('status', 'izin')->count();
                            $alpa  = $atts->where('status', 'alpa')->count();

                            $out = '<div class="flex flex-col gap-1">';
                            $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 bg-blue-50 dark:text-blue-300 dark:bg-blue-950/40 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800">✅ ' . $tepat . 'x Tepat</span>';
                            if ($telat > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">⏰ ' . $telat . 'x Telat</span>';
                            }
                            if ($halangan > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-950/40 px-2 py-0.5 rounded border border-purple-200 dark:border-purple-800">🌸 ' . $halangan . 'x Halangan</span>';
                            }
                            if ($sakit > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">🤒 ' . $sakit . 'x Sakit</span>';
                            }
                            if ($izin > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700 bg-sky-50 dark:text-sky-300 dark:bg-sky-950/40 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800">📋 ' . $izin . 'x Izin</span>';
                            }
                            if ($alpa > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 dark:text-rose-300 dark:bg-rose-950/40 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">❌ ' . $alpa . 'x Alpha</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // Mode Harian
                        $tanggal = static::getActiveDate($livewire);
                        $isFriday = Carbon::parse($tanggal)->isFriday();
                        $isKeputrian = $isFriday && $record->jenis_kelamin === 'P';
                        $targetTipe = $isKeputrian ? ['keputrian', 'sholat_dhuhur'] : ['sholat_dhuhur', 'keputrian'];
                        $att = $record->getAttendanceOnDate($tanggal, $targetTipe);
                        $isHalangan = $record->jenis_kelamin === 'P' && (StudentHalangan::isHalangan($record->id, $tanggal) || ($att && $att->status === 'halangan'));
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        // 1. Siswi Putri Halangan / Udzur Syar'i (jika bukan hadir keputrian)
                        if ($isHalangan && (!$att || $att->status !== 'hadir')) {
                            $ket = ($att && $att->keterangan) ? $att->keterangan : "Sedang halangan / udzur syar'i";
                            return '<div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800">
                                    🌸 Sedang Halangan
                                </span>
                                <span class="text-[11px] text-purple-600 dark:text-purple-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>
                            </div>';
                        }

                        // 2. Siswa Sakit / Izin / Alpha
                        $statusIzin = ($att && in_array($att->status, ['sakit', 'izin', 'alpa'])) ? $att->status : ($excused ? $excused->status : null);
                        if ($statusIzin) {
                            $statusLabel = match ($statusIzin) {
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                default => '❌ Alpha',
                            };
                            $badgeColor = match ($statusIzin) {
                                'sakit' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                'izin'  => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                                default => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                            };
                            $ket = ($att && $att->keterangan) ? $att->keterangan : ($excused ? $excused->keterangan : null);
                            $out = '<div class="flex flex-col gap-0.5">';
                            $out .= '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border ' . $badgeColor . '">' . $statusLabel . '</span>';
                            if (!empty($ket)) {
                                $out .= '<span class="text-[11px] text-slate-500 dark:text-slate-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // 3. Hadir Scan
                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $cutoff = $isKeputrian ? '12:30' : '12:15';
                            $isLate = $att->is_late || ($att->waktu_kedatangan && Carbon::parse($att->waktu_kedatangan)->format('H:i') > $cutoff);

                            if ($isLate) {
                                $lateLabel = $isKeputrian ? 'Terlambat Keputrian' : ($isFriday ? 'Terlambat Jum\'at' : 'Terlambat');
                                return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    <span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">' . $lateLabel . '</span>
                                </div>';
                            }

                            $onTimeLabel = $isKeputrian ? 'Keputrian Tepat' : ($isFriday ? 'Jum\'at Tepat' : 'Tepat Waktu');
                            return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">' . $onTimeLabel . '</span>
                            </div>';
                        }

                        // 4. Belum Scan
                        $belumLabel = $isKeputrian ? 'Belum Scan Keputrian' : ($isFriday ? 'Belum Sholat Jum\'at' : 'Belum Scan');
                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            ' . $belumLabel . '
                        </span>';
                    }),

                // 3. Sholat Ashar (Finger)
                Tables\Columns\TextColumn::make('ashar_finger')
                    ->label('3. Sholat Ashar (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $mode = static::getActiveMode($livewire);

                        if ($mode === 'rentang') {
                            [$start, $end] = static::getActiveDateRange($livewire);
                            $atts = $record->dailyAttendances->filter(function ($a) use ($start, $end) {
                                $d = Carbon::parse($a->tanggal)->toDateString();
                                return $d >= $start && $d <= $end && in_array($a->tipe_absen, ['sholat_ashar', 'pulang']);
                            })->sortBy(fn ($a) => ($a->waktu_kedatangan ?: '99:99:99') . '_' . $a->id)
                              ->unique(fn ($a) => Carbon::parse($a->tanggal)->toDateString());

                            $tepat = $atts->where('status', 'hadir')->where('is_late', false)->count();
                            $telat = $atts->where('status', 'hadir')->where('is_late', true)->count();
                            $halangan = $atts->where('status', 'halangan')->count();
                            $sakit = $atts->where('status', 'sakit')->count();
                            $izin  = $atts->where('status', 'izin')->count();
                            $alpa  = $atts->where('status', 'alpa')->count();

                            $out = '<div class="flex flex-col gap-1">';
                            $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">✅ ' . $tepat . 'x Tepat</span>';
                            if ($telat > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 dark:text-rose-300 dark:bg-rose-950/40 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">⏰ ' . $telat . 'x Telat</span>';
                            }
                            if ($halangan > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-950/40 px-2 py-0.5 rounded border border-purple-200 dark:border-purple-800">🌸 ' . $halangan . 'x Halangan</span>';
                            }
                            if ($sakit > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">🤒 ' . $sakit . 'x Sakit</span>';
                            }
                            if ($izin > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700 bg-sky-50 dark:text-sky-300 dark:bg-sky-950/40 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800">📋 ' . $izin . 'x Izin</span>';
                            }
                            if ($alpa > 0) {
                                $out .= '<span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 dark:text-rose-300 dark:bg-rose-950/40 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-800">❌ ' . $alpa . 'x Alpha</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // Mode Harian
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $isHalangan = $record->jenis_kelamin === 'P' && (StudentHalangan::isHalangan($record->id, $tanggal) || ($att && $att->status === 'halangan'));
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        // 1. Siswi Putri Halangan / Udzur Syar'i
                        if ($isHalangan) {
                            $ket = ($att && $att->keterangan) ? $att->keterangan : "Sedang halangan / udzur syar'i";
                            return '<div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800">
                                    🌸 Sedang Halangan
                                </span>
                                <span class="text-[11px] text-purple-600 dark:text-purple-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>
                            </div>';
                        }

                        // 2. Siswa Sakit / Izin / Alpha
                        $statusIzin = ($att && in_array($att->status, ['sakit', 'izin', 'alpa'])) ? $att->status : ($excused ? $excused->status : null);
                        if ($statusIzin) {
                            $statusLabel = match ($statusIzin) {
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                default => '❌ Alpha',
                            };
                            $badgeColor = match ($statusIzin) {
                                'sakit' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                'izin'  => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                                default => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                            };
                            $ket = ($att && $att->keterangan) ? $att->keterangan : ($excused ? $excused->keterangan : null);
                            $out = '<div class="flex flex-col gap-0.5">';
                            $out .= '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border ' . $badgeColor . '">' . $statusLabel . '</span>';
                            if (!empty($ket)) {
                                $out .= '<span class="text-[11px] text-slate-500 dark:text-slate-400 italic max-w-[170px] truncate" title="' . e($ket) . '">' . e($ket) . '</span>';
                            }
                            $out .= '</div>';
                            return $out;
                        }

                        // 3. Hadir Scan
                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $isLate = $att->is_late || ($att->waktu_kedatangan && Carbon::parse($att->waktu_kedatangan)->format('H:i') > '15:15');

                            if ($isLate) {
                                return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    <span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">Terlambat</span>
                                </div>';
                            }

                            return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">Tepat Waktu</span>
                            </div>';
                        }

                        // 4. Belum Scan
                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Belum Scan
                        </span>';
                    }),

                // Ringkasan Status / Rekap Periode
                Tables\Columns\TextColumn::make('status_harian')
                    ->label(fn ($livewire) => static::getActiveMode($livewire) === 'rentang' ? 'Rekap Kehadiran' : 'Status Hari Ini')
                    ->badge()
                    ->state(function (Student $record, $livewire): string {
                        $mode = static::getActiveMode($livewire);

                        if ($mode === 'rentang') {
                            [$start, $end] = static::getActiveDateRange($livewire);
                            $atts = $record->dailyAttendances->filter(function ($a) use ($start, $end) {
                                $d = Carbon::parse($a->tanggal)->toDateString();
                                return $d >= $start && $d <= $end && $a->tipe_absen === 'masuk';
                            })->sortBy(fn ($a) => ($a->waktu_kedatangan ?: '99:99:99') . '_' . $a->id)
                              ->unique(fn ($a) => Carbon::parse($a->tanggal)->toDateString());

                            $s = $atts->where('status', 'sakit')->count();
                            $i = $atts->where('status', 'izin')->count();
                            $a = $atts->where('status', 'alpa')->count();
                            $h = $atts->where('status', 'hadir')->count();

                            $totalHari = max(1, $h + $s + $i + $a);
                            $persen = round(($h / $totalHari) * 100);

                            return "{$h} Hadir ({$persen}%) • {$s}S {$i}I {$a}A";
                        }

                        // Mode Harian
                        $tanggal = static::getActiveDate($livewire);
                        $attMasuk = $record->getAttendanceOnDate($tanggal, 'masuk');
                        $attDhuhur = $record->getAttendanceOnDate($tanggal, 'sholat_dhuhur');
                        $attAshar = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        // 1. Jika Sakit / Izin / Alpha
                        $statusIzin = ($attMasuk && in_array($attMasuk->status, ['sakit', 'izin', 'alpa'])) ? $attMasuk->status : ($excused ? $excused->status : null);
                        if ($statusIzin) {
                            return match ($statusIzin) {
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                default => '❌ Alpha',
                            };
                        }

                        // 2. Siswi Halangan
                        $isHalangan = $record->jenis_kelamin === 'P' && (
                            StudentHalangan::isHalangan($record->id, $tanggal)
                            || ($attDhuhur && $attDhuhur->status === 'halangan')
                            || ($attAshar && $attAshar->status === 'halangan')
                        );

                        $dhuhurOk = ($attDhuhur && $attDhuhur->status === 'hadir') || $isHalangan;
                        $asharOk = ($attAshar && $attAshar->status === 'hadir') || $isHalangan;

                        if ($attMasuk && $attMasuk->status === 'hadir') {
                            if ($isHalangan) {
                                return '🌸 Hadir (Sedang Halangan)';
                            }
                            if ($dhuhurOk && $asharOk) {
                                return '✅ Lengkap (3/3)';
                            }
                            if ($dhuhurOk) {
                                return (Carbon::parse($tanggal)->isFriday() && $record->jenis_kelamin === 'P')
                                    ? '🔹 Masuk & Keputrian (2/3)'
                                    : '🔹 Masuk & Dhuhur (2/3)';
                            }
                            return '🔸 Masuk Saja (1/3)';
                        }

                        if ($isHalangan) {
                            return '🌸 Sedang Halangan';
                        }

                        return 'Belum Ada Data';
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Lengkap') || str_contains($state, '100%') || str_contains($state, '9') => 'success',
                        str_contains($state, 'Halangan') => 'purple',
                        str_contains($state, '2/3') || str_contains($state, 'Hadir') => 'info',
                        str_contains($state, '1/3') || str_contains($state, 'Sakit') => 'warning',
                        str_contains($state, 'Izin') => 'info',
                        str_contains($state, 'Alpa') || str_contains($state, 'Alpha') => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Filter Kelas / Rombel')
                    ->options(fn () => Classroom::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
            ])
            ->actions([
                // 1. Izin Pulang Siang (Sakit / Izin) - Presensi Kedatangan Pagi Tetap Hadir
                Tables\Actions\Action::make('izin_pulang_siang')
                    ->label('Izin Pulang Siang')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('warning')
                    ->tooltip('Ubah status siswa saat siang menjadi Sakit atau Izin (presensi pagi tetap Hadir)')
                    ->modalHeading(fn (Student $record) => "Izin Pulang Siang: {$record->name}")
                    ->modalDescription('Fitur khusus guru piket untuk mencatat kepulangan siswa di siang hari karena sakit atau izin. Presensi kedatangan pagi TETAP Hadir, sedangkan sesi sholat Dhuhur/Jum\'at/Keputrian dan Ashar otomatis tercatat Sakit/Izin.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Status Pulang Siang')
                                ->options([
                                    'sakit' => '🤒 Sakit (Pulang Siang)',
                                    'izin'  => '📋 Izin (Pulang Siang)',
                                ])
                                ->default('sakit')
                                ->required(),

                            Forms\Components\TimePicker::make('jam_pulang')
                                ->label('Jam Pulang / Dijemput')
                                ->default(now()->format('H:i'))
                                ->required(),
                        ]),

                        Forms\Components\TextInput::make('penjemput')
                            ->label('Penjemput / Pendamping')
                            ->placeholder('Contoh: Dijemput Ibu / Pulang Mandiri / Dijemput Ayah')
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan / Gejala Pulang Siang')
                            ->placeholder('Contoh: Mengalami demam / pusing di UKS / Ada keperluan keluarga mendesak')
                            ->rows(2)
                            ->required(),

                        Forms\Components\CheckboxList::make('sesi_yang_diubah')
                            ->label('Sesi yang Diubah Menjadi Sakit / Izin')
                            ->options(function (Student $record, Forms\Get $get, $livewire) {
                                $tgl = $get('tanggal') ?: static::getActiveDate($livewire);
                                $isFriday = Carbon::parse($tgl)->isFriday();
                                $labelSiang = ($isFriday && $record->jenis_kelamin === 'P')
                                    ? '🌸 Kegiatan Keputrian (Jumat Putri)'
                                    : ($isFriday ? '🕌 Sholat Jum\'at (Jumat Putra)' : '🕌 Sholat Dhuhur');

                                return [
                                    'dhuhur' => $labelSiang,
                                    'ashar'  => '🕌 Sholat Ashar',
                                ];
                            })
                            ->default(['dhuhur', 'ashar'])
                            ->required()
                            ->helperText('Presensi kedatangan pagi TIDAK akan diubah (tetap Hadir).'),

                        Forms\Components\TextInput::make('bukti_izin')
                            ->label('Link Google Drive Bukti Surat / Resep Dokter (Opsional)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->maxLength(2048),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = $data['tanggal'];
                        $status = $data['status'];
                        $jamPulang = $data['jam_pulang'] ?? now()->format('H:i');
                        $penjemput = trim($data['penjemput'] ?? '-');
                        $alasan = trim($data['keterangan'] ?? '-');
                        $sesiUbah = (array) ($data['sesi_yang_diubah'] ?? ['dhuhur', 'ashar']);

                        $isFriday = Carbon::parse($tanggal)->isFriday();
                        $sesiSiang = ($isFriday && $record->jenis_kelamin === 'P') ? 'keputrian' : 'sholat_dhuhur';
                        $statusLabel = match ($status) {
                            'sakit' => 'Sakit',
                            'izin'  => 'Izin',
                            default => 'Alpha',
                        };

                        $keteranganLengkap = "Izin Pulang Siang ({$statusLabel}) pkl {$jamPulang} WIB. Penjemput: {$penjemput}. Alasan: {$alasan}";

                        // 1. Sesi Siang (Dhuhur / Jum'at / Keputrian)
                        if (in_array('dhuhur', $sesiUbah)) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $record->id,
                                    'tanggal'    => $tanggal,
                                    'tipe_absen' => $sesiSiang,
                                ],
                                [
                                    'institution_id'   => $record->institution_id ?: 2,
                                    'teacher_id'       => Auth::id() ?: 1,
                                    'status'           => $status,
                                    'metode_absen'     => 'manual',
                                    'keterangan'       => $keteranganLengkap,
                                    'bukti_izin'       => $data['bukti_izin'] ?? null,
                                    'waktu_kedatangan' => null,
                                    'is_late'          => false,
                                    'late_penalty_points' => 0,
                                ]
                            );

                            // Sinkronkan ke PrayerAttendance Dhuhur
                            PrayerAttendance::$isSyncing = true;
                            try {
                                PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                    [
                                        'student_id'   => $record->id,
                                        'tanggal'      => $tanggal,
                                        'jenis_sholat' => 'Dhuhur',
                                    ],
                                    [
                                        'institution_id' => $record->institution_id ?: 2,
                                        'teacher_id'     => Auth::id() ?: 1,
                                        'status'         => $status,
                                        'is_khusyu'      => true,
                                    ]
                                );
                            } finally {
                                PrayerAttendance::$isSyncing = false;
                            }
                        }

                        // 2. Sesi Ashar
                        if (in_array('ashar', $sesiUbah)) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $record->id,
                                    'tanggal'    => $tanggal,
                                    'tipe_absen' => 'sholat_ashar',
                                ],
                                [
                                    'institution_id'   => $record->institution_id ?: 2,
                                    'teacher_id'       => Auth::id() ?: 1,
                                    'status'           => $status,
                                    'metode_absen'     => 'manual',
                                    'keterangan'       => $keteranganLengkap,
                                    'bukti_izin'       => $data['bukti_izin'] ?? null,
                                    'waktu_kedatangan' => null,
                                    'is_late'          => false,
                                    'late_penalty_points' => 0,
                                ]
                            );

                            // Sinkronkan ke PrayerAttendance Ashar
                            PrayerAttendance::$isSyncing = true;
                            try {
                                PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                    [
                                        'student_id'   => $record->id,
                                        'tanggal'      => $tanggal,
                                        'jenis_sholat' => 'Ashar',
                                    ],
                                    [
                                        'institution_id' => $record->institution_id ?: 2,
                                        'teacher_id'     => Auth::id() ?: 1,
                                        'status'         => $status,
                                        'is_khusyu'      => true,
                                    ]
                                );
                            } finally {
                                PrayerAttendance::$isSyncing = false;
                            }
                        }

                        Notification::make()
                            ->title('Izin Pulang Siang Berhasil Dicatat')
                            ->body("Status {$statusLabel} untuk {$record->name} jam {$jamPulang} WIB tersimpan. Presensi kedatangan pagi tetap Hadir.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 2. Catat Izin / Sakit / Alpa
                Tables\Actions\Action::make('catat_izin')
                    ->label('Catat Izin')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->modalHeading(fn (Student $record) => "Catat Izin / Sakit: {$record->name}")
                    ->modalDescription('Catatan izin, sakit, atau alpha dapat diterapkan ke seluruh sesi atau sesi kepulangan siang tertentu.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Radio::make('cakupan_sesi')
                            ->label('Cakupan Sesi Presensi')
                            ->options([
                                'seharian'     => '📅 Seharian Penuh (Kedatangan, Dhuhur, Ashar)',
                                'pulang_siang' => '⛅ Izin Pulang Siang (Dhuhur & Ashar Saja - Kedatangan Pagi Tetap Hadir)',
                                'dhuhur_saja'  => '🕌 Sesi Siang Saja (Dhuhur / Jum\'at / Keputrian)',
                                'ashar_saja'   => '🕌 Sesi Sore Saja (Ashar)',
                            ])
                            ->default('seharian')
                            ->required()
                            ->helperText('Pilih "Izin Pulang Siang" jika siswa sudah hadir pagi lalu izin pulang saat siang.'),

                        Forms\Components\Select::make('status')
                            ->label('Pilihan Status')
                            ->options([
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                'alpa'  => '❌ Alpha / Tanpa Keterangan',
                            ])
                            ->default('sakit')
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan / Keterangan')
                            ->placeholder('Contoh: Sakit demam berobat ke klinik / Izin ada acara keluarga...')
                            ->rows(3)
                            ->required(),

                        Forms\Components\TextInput::make('bukti_izin')
                            ->label('Link Google Drive Bukti Surat Izin / Sakit')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan bukti surat izin atau resep dokter via link Google Drive publik.')
                            ->maxLength(2048),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = $data['tanggal'];
                        $isFriday = Carbon::parse($tanggal)->isFriday();
                        $sesiSiang = ($isFriday && $record->jenis_kelamin === 'P') ? 'keputrian' : 'sholat_dhuhur';
                        $cakupan = $data['cakupan_sesi'] ?? 'seharian';

                        $sesiList = match ($cakupan) {
                            'pulang_siang' => [$sesiSiang, 'sholat_ashar'],
                            'dhuhur_saja'  => [$sesiSiang],
                            'ashar_saja'   => ['sholat_ashar'],
                            default        => ['masuk', $sesiSiang, 'sholat_ashar'],
                        };

                        foreach ($sesiList as $sesi) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $record->id,
                                    'tanggal'    => $tanggal,
                                    'tipe_absen' => $sesi,
                                ],
                                [
                                    'institution_id'   => $record->institution_id ?: 2,
                                    'teacher_id'       => Auth::id() ?: 1,
                                    'status'           => $data['status'],
                                    'metode_absen'     => 'manual',
                                    'keterangan'       => $data['keterangan'] ?? null,
                                    'bukti_izin'       => $data['bukti_izin'] ?? null,
                                    'waktu_kedatangan' => null,
                                    'is_late'          => false,
                                    'late_penalty_points' => 0,
                                ]
                            );
                        }

                        // Sinkronkan ke PrayerAttendance (Dhuhur & Ashar jika relevan)
                        $prayerList = [];
                        if (in_array($sesiSiang, $sesiList)) $prayerList[] = 'Dhuhur';
                        if (in_array('sholat_ashar', $sesiList)) $prayerList[] = 'Ashar';

                        foreach ($prayerList as $prayerName) {
                            \App\Models\PrayerAttendance::$isSyncing = true;
                            try {
                                \App\Models\PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                    [
                                        'student_id'   => $record->id,
                                        'tanggal'      => $tanggal,
                                        'jenis_sholat' => $prayerName,
                                    ],
                                    [
                                        'institution_id' => $record->institution_id ?: 2,
                                        'teacher_id'     => Auth::id() ?: 1,
                                        'status'         => $data['status'],
                                        'is_khusyu'      => true,
                                    ]
                                );
                            } finally {
                                \App\Models\PrayerAttendance::$isSyncing = false;
                            }
                        }

                        $statusLabel = match ($data['status']) {
                            'sakit' => 'Sakit',
                            'izin'  => 'Izin',
                            default => 'Alpha',
                        };

                        $cakupanText = match ($cakupan) {
                            'pulang_siang' => 'sesi Dhuhur & Ashar (kedatangan pagi tetap Hadir)',
                            'dhuhur_saja'  => 'sesi Dhuhur',
                            'ashar_saja'   => 'sesi Ashar',
                            default        => 'seluruh sesi (kedatangan, Dhuhur, Ashar)',
                        };

                        Notification::make()
                            ->title('Status Berhasil Dicatat')
                            ->body("Status {$statusLabel} untuk {$record->name} pada {$tanggal} berhasil disimpan untuk {$cakupanText}.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 2. Tandai Halangan (Khusus Siswi Putri - Berada di Sebelah Tombol Catat Izin)
                Tables\Actions\Action::make('tandai_halangan')
                    ->label('Tandai Halangan')
                    ->icon('heroicon-o-sparkles')
                    ->color('purple')
                    ->visible(fn (Student $record) => $record->jenis_kelamin === 'P')
                    ->modalHeading(fn (Student $record) => "Tandai Halangan Sholat: {$record->name}")
                    ->modalDescription("Tandai siswi sedang berhalangan syar'i (haid). Otomatis absensi Sholat Dhuhur & Ashar dicatat 'Sedang Halangan', sedangkan absensi kedatangan tetap tercatat normal.")
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai Halangan')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Select::make('durasi_hari')
                            ->label('Estimasi Lama Periode Halangan')
                            ->options([
                                1  => '1 Hari (Hari Ini Saja)',
                                2  => '2 Hari',
                                3  => '3 Hari',
                                5  => '5 Hari',
                                7  => '7 Hari (Standar Siklus)',
                                10 => '10 Hari',
                                14 => '14 Hari (Maksimal)',
                            ])
                            ->default(7)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $start = $get('tanggal_mulai') ?: now()->toDateString();
                                $days = (int) ($state ?: 7);
                                $set('tanggal_selesai', Carbon::parse($start)->addDays($days - 1)->toDateString());
                            })
                            ->helperText('Pilih durasi estimasi siswi berhalangan sholat.'),

                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai Halangan')
                            ->default(fn ($livewire) => Carbon::parse(static::getActiveDate($livewire))->addDays(6)->toDateString())
                            ->required(),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan / Catatan')
                            ->default("Sedang halangan / udzur syar'i")
                            ->maxLength(255),

                        Forms\Components\Toggle::make('batal_halangan')
                            ->label('Hapus / Batalkan Tanda Halangan')
                            ->helperText('Aktifkan pilihan ini jika siswi sudah suci lebih awal atau tanda halangan keliru dimasukkan.')
                            ->default(false)
                            ->visible(fn (Student $record, $livewire) => StudentHalangan::isHalangan($record->id, static::getActiveDate($livewire))),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $start = $data['tanggal_mulai'];
                        $end = $data['tanggal_selesai'] ?? $start;

                        if (!empty($data['batal_halangan'])) {
                            // Hapus rekaman StudentHalangan yang beririsan
                            StudentHalangan::withoutGlobalScopes()
                                ->where('student_id', $record->id)
                                ->where('tanggal_mulai', '<=', $end)
                                ->where(function ($q) use ($start) {
                                    $q->whereNull('tanggal_selesai')
                                      ->orWhere('tanggal_selesai', '>=', $start);
                                })
                                ->delete();

                            // Reset DailyAttendance sholat yang statusnya halangan pada rentang tersebut
                            DailyAttendance::withoutGlobalScopes()
                                ->where('student_id', $record->id)
                                ->whereBetween('tanggal', [$start, $end])
                                ->whereIn('tipe_absen', ['sholat_dhuhur', 'sholat_ashar'])
                                ->where('status', 'halangan')
                                ->delete();

                            Notification::make()
                                ->title('Tanda Halangan Dibatalkan')
                                ->body("Status halangan untuk {$record->name} berhasil dihapus/dibatalkan.")
                                ->info()
                                ->send();
                        } else {
                            // Catat di tabel StudentHalangan
                            StudentHalangan::create([
                                'student_id'      => $record->id,
                                'institution_id'  => $record->institution_id ?: 2,
                                'tanggal_mulai'   => $start,
                                'tanggal_selesai' => $end,
                                'catatan'         => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                                'recorded_by'     => Auth::id() ?: 1,
                            ]);

                            // Pastikan DailyAttendance untuk Sholat Dhuhur & Ashar tercatat sebagai halangan pada rentang tersebut
                            // PENTING: Absensi kedatangan (masuk) TIDAK diubah sama sekali agar kehadiran tetap tercatat
                            $period = \Carbon\CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end));
                            foreach ($period as $dt) {
                                $curDate = $dt->toDateString();
                                foreach (['sholat_dhuhur', 'sholat_ashar'] as $sesi) {
                                    DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                        [
                                            'student_id' => $record->id,
                                            'tanggal'    => $curDate,
                                            'tipe_absen' => $sesi,
                                        ],
                                        [
                                            'institution_id'   => $record->institution_id ?: 2,
                                            'teacher_id'       => Auth::id() ?: 1,
                                            'status'           => 'halangan',
                                            'metode_absen'     => 'manual',
                                            'waktu_kedatangan' => null,
                                            'is_late'          => false,
                                            'keterangan'       => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                                        ]
                                    );
                                }
                            }

                            Notification::make()
                                ->title('Siswi Berhasil Ditandai Halangan')
                                ->body("Siswi {$record->name} ditandai halangan dari {$start} s.d {$end}. Absensi sholat Dhuhur & Ashar otomatis tercatat 'Sedang Halangan', sedangkan absensi kedatangan tetap tercatat normal.")
                                ->success()
                                ->send();
                        }

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 3. Input Manual / Koreksi Jam (Khusus Mode Harian)
                Tables\Actions\Action::make('input_manual_jam')
                    ->label('Koreksi Jam')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->visible(fn ($livewire) => static::getActiveMode($livewire) === 'harian')
                    ->modalHeading(fn (Student $record) => "Input / Koreksi Jam Scan: {$record->name}")
                    ->modalDescription('Gunakan fitur ini jika siswa lupa scan jari pada mesin fingerspot, saat mesin offline, atau ingin menyesuaikan status kehadiran sesi tertentu.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Select::make('tipe_absen')
                            ->label('Sesi Presensi')
                            ->options(function (Student $record, $livewire) {
                                $isFriday = Carbon::parse(static::getActiveDate($livewire))->isFriday();
                                $dhuhurLabel = ($isFriday && $record->jenis_kelamin === 'P')
                                    ? '🌸 Kegiatan Keputrian (Jumat Putri)'
                                    : ($isFriday ? '🕌 Sholat Jum\'at (Jumat Putra)' : '🕌 Sholat Dhuhur');

                                return [
                                    'masuk'         => '🏫 Kedatangan Sekolah (Masuk Pagi)',
                                    'sholat_dhuhur' => $dhuhurLabel,
                                    'sholat_ashar'  => '🕌 Sholat Ashar',
                                ];
                            })
                            ->default('masuk')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status Kehadiran')
                            ->options([
                                'hadir' => '✅ Hadir',
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                'alpa'  => '❌ Alpha / Tanpa Keterangan',
                            ])
                            ->default('hadir')
                            ->required()
                            ->live(),

                        Forms\Components\TimePicker::make('waktu_kedatangan')
                            ->label('Jam Kehadiran / Sholat')
                            ->default(now()->format('H:i'))
                            ->visible(fn (Forms\Get $get) => ($get('status') ?? 'hadir') === 'hadir'),

                        Forms\Components\Toggle::make('is_late')
                            ->label('Tandai Terlambat')
                            ->helperText('Otomatis: Masuk >= 06:46 (06:45 tepat waktu), Jum\'at/Dhuhur > 12:15 (Putra mulai 11:00), Keputrian > 12:30, Ashar > 15:15')
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => ($get('status') ?? 'hadir') === 'hadir'),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Catatan Petugas Piket')
                            ->placeholder('Contoh: Hadir di sekolah, lupa scan jari / Izin pulang siang')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = $data['tanggal'];
                        $sesi = $data['tipe_absen'];
                        $status = $data['status'] ?? 'hadir';
                        $jam = ($status === 'hadir') ? ($data['waktu_kedatangan'] ?? null) : null;

                        $isFriday = Carbon::parse($tanggal)->isFriday();
                        $isKeputrian = $isFriday && $record->jenis_kelamin === 'P' && in_array($sesi, ['sholat_dhuhur', 'keputrian']);
                        if ($isKeputrian) {
                            $sesi = 'keputrian';
                        }

                        $isLate = false;
                        if ($status === 'hadir') {
                            $isLate = (bool) ($data['is_late'] ?? false);
                            if (!$isLate && $jam) {
                                if ($sesi === 'masuk' && $jam >= '06:46') $isLate = true;
                                if ($sesi === 'keputrian' && $jam > '12:30') $isLate = true;
                                if ($sesi === 'sholat_dhuhur' && $jam > '12:15') $isLate = true;
                                if ($sesi === 'sholat_ashar' && $jam > '15:15') $isLate = true;
                            }
                        }

                        $defaultKet = match ($status) {
                            'hadir' => ($isKeputrian ? 'Kegiatan Keputrian (diinput petugas)' : ($isFriday ? 'Sholat Jum\'at (diinput petugas)' : 'Diinput manual oleh petugas piket')),
                            'sakit' => 'Sakit (diinput manual petugas piket)',
                            'izin'  => 'Izin (diinput manual petugas piket)',
                            default => 'Alpha (diinput manual petugas piket)',
                        };

                        DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                            [
                                'student_id' => $record->id,
                                'tanggal'    => $tanggal,
                                'tipe_absen' => $sesi,
                            ],
                            [
                                'institution_id'   => $record->institution_id ?: 2,
                                'teacher_id'       => Auth::id(),
                                'status'           => $status,
                                'metode_absen'     => 'manual',
                                'waktu_kedatangan' => $jam,
                                'is_late'          => $isLate,
                                'late_penalty_points' => 0,
                                'keterangan'       => $data['keterangan'] ?? $defaultKet,
                            ]
                        );

                        // Sync ke PrayerAttendance jika sesi sholat
                        if (in_array($sesi, ['sholat_dhuhur', 'keputrian', 'sholat_ashar'])) {
                            $prayerName = in_array($sesi, ['sholat_dhuhur', 'keputrian']) ? 'Dhuhur' : 'Ashar';
                            PrayerAttendance::$isSyncing = true;
                            try {
                                PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                    [
                                        'student_id'   => $record->id,
                                        'tanggal'      => $tanggal,
                                        'jenis_sholat' => $prayerName,
                                    ],
                                    [
                                        'institution_id' => $record->institution_id ?: 2,
                                        'teacher_id'     => Auth::id() ?: 1,
                                        'status'         => $status,
                                        'is_khusyu'      => true,
                                    ]
                                );
                            } finally {
                                PrayerAttendance::$isSyncing = false;
                            }
                        }

                        Notification::make()
                            ->title('Presensi Berhasil Disimpan')
                            ->body("Data sesi {$sesi} ({$status}) untuk {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 4. Kirim WA Laporan ke Orang Tua
                Tables\Actions\Action::make('kirim_wa')
                    ->label('Kirim WA Ortu')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (Student $record, $livewire): ?string {
                        $phone = $record->telepon_ayah ?: $record->telepon_ibu ?: $record->telepon_wali;
                        if (!$phone) {
                            return null;
                        }
                        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }

                        $tanggal = static::getActiveDate($livewire);
                        $tglFmt = Carbon::parse($tanggal)->translatedFormat('l, d F Y');

                        $isFriday = Carbon::parse($tanggal)->isFriday();
                        $targetTipeSiang = ($isFriday && $record->jenis_kelamin === 'P') ? ['keputrian', 'sholat_dhuhur'] : ['sholat_dhuhur', 'keputrian'];

                        $attMasuk = $record->getAttendanceOnDate($tanggal, 'masuk');
                        $attDhuhur = $record->getAttendanceOnDate($tanggal, $targetTipeSiang);
                        $attAshar = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $excused = $record->getGeneralExcusedOnDate($tanggal);
                        $isHalangan = $record->jenis_kelamin === 'P' && (
                            StudentHalangan::isHalangan($record->id, $tanggal)
                            || ($attDhuhur && $attDhuhur->status === 'halangan')
                            || ($attAshar && $attAshar->status === 'halangan')
                        );

                        // Masuk Text
                        if ($attMasuk && $attMasuk->status === 'hadir') {
                            $masukText = ($attMasuk->waktu_kedatangan ? Carbon::parse($attMasuk->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attMasuk->is_late ? ' (Terlambat)' : ' (Tepat Waktu)');
                            if ($attDhuhur && in_array($attDhuhur->status, ['sakit', 'izin'])) {
                                $pulangStatus = ucfirst($attDhuhur->status);
                                $masukText .= " [Izin Pulang Siang ({$pulangStatus})]";
                            }
                        } elseif ($attMasuk && in_array($attMasuk->status, ['sakit', 'izin', 'alpa'])) {
                            $masukText = ucfirst($attMasuk->status) . ($attMasuk->keterangan ? " ({$attMasuk->keterangan})" : '');
                        } elseif ($excused) {
                            $masukText = ucfirst($excused->status) . ($excused->keterangan ? " ({$excused->keterangan})" : '');
                        } else {
                            $masukText = 'Belum Scan Masuk';
                        }

                        // Sesi Siang Text
                        $labelSiang = ($isFriday && $record->jenis_kelamin === 'P') ? 'Kegiatan Keputrian' : ($isFriday ? 'Sholat Jum\'at' : 'Sholat Dhuhur');
                        if ($isHalangan && (!$attDhuhur || $attDhuhur->status !== 'hadir')) {
                            $dhuhurText = '🌸 Sedang Halangan / Udzur Syar\'i';
                        } elseif ($attDhuhur && $attDhuhur->status === 'hadir') {
                            $dhuhurText = ($attDhuhur->waktu_kedatangan ? Carbon::parse($attDhuhur->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attDhuhur->is_late ? ' (Terlambat)' : ' (Tepat Waktu)');
                        } elseif ($attDhuhur && in_array($attDhuhur->status, ['sakit', 'izin', 'alpa'])) {
                            $dhuhurText = ucfirst($attDhuhur->status) . ($attDhuhur->keterangan ? " ({$attDhuhur->keterangan})" : '');
                        } elseif ($excused) {
                            $dhuhurText = ucfirst($excused->status) . ($excused->keterangan ? " ({$excused->keterangan})" : '');
                        } else {
                            $dhuhurText = 'Belum Sholat / Scan';
                        }

                        // Sesi Ashar Text
                        if ($isHalangan) {
                            $asharText = '🌸 Sedang Halangan / Udzur Syar\'i';
                        } elseif ($attAshar && $attAshar->status === 'hadir') {
                            $asharText = ($attAshar->waktu_kedatangan ? Carbon::parse($attAshar->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attAshar->is_late ? ' (Terlambat)' : ' (Tepat Waktu)');
                        } elseif ($attAshar && in_array($attAshar->status, ['sakit', 'izin', 'alpa'])) {
                            $asharText = ucfirst($attAshar->status) . ($attAshar->keterangan ? " ({$attAshar->keterangan})" : '');
                        } elseif ($excused) {
                            $asharText = ucfirst($excused->status) . ($excused->keterangan ? " ({$excused->keterangan})" : '');
                        } else {
                            $asharText = 'Belum Sholat / Scan';
                        }

                        $kelas = $record->classroom?->name ?? '-';
                        $msg = "Assalamu'alaikum Wr. Wb.\n\n"
                            . "Yth. Bapak/Ibu Wali dari ananda *{$record->name}* (Kelas {$kelas}).\n"
                            . "Berikut ini kami sampaikan laporan kedatangan dan pelaksanaan sholat ananda pada hari *{$tglFmt}*:\n\n"
                            . "1. 🏫 *Kedatangan di Sekolah*: {$masukText}\n"
                            . "2. 🕌 *{$labelSiang}*: {$dhuhurText}\n"
                            . "3. 🕌 *Sholat Ashar*: {$asharText}\n\n"
                            . "Terima kasih atas perhatian dan kerjasamanya.\nWassalamu'alaikum Wr. Wb.\n"
                            . "_Tim Kesiswaan & Guru Piket SMP Islam Baitul Makmur_";

                        return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($msg);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Student $record) => (bool) ($record->telepon_ayah ?: $record->telepon_ibu ?: $record->telepon_wali)),

                // 5. ACC Poin Keterlambatan (+5 Poin) oleh Waka Kesiswaan
                Tables\Actions\Action::make('acc_poin_terlambat')
                    ->label('ACC Poin (+5)')
                    ->icon('heroicon-o-check-badge')
                    ->color('danger')
                    ->visible(function (Student $record, $livewire): bool {
                        if (!Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])) {
                            return false;
                        }
                        if (static::getActiveMode($livewire) !== 'harian') {
                            return false;
                        }
                        $att = $record->getAttendanceOnDate(static::getActiveDate($livewire), 'masuk');
                        if (!$att) {
                            return false;
                        }
                        $isLate = $att->waktu_kedatangan ? (Carbon::parse($att->waktu_kedatangan)->format('H:i') >= '06:46') : (bool) $att->is_late;
                        return $isLate && $att->late_approval_status !== 'approved';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('ACC Poin Keterlambatan (+5 Poin)?')
                    ->modalDescription(fn (Student $record) => "Siswa ananda {$record->name} tercatat terlambat masuk sekolah. Dengan menyetujui (ACC), poin pelanggaran 5 poin akan resmi dibukukan ke rekap pelanggaran & mengurangi skor karakter siswa.")
                    ->modalSubmitActionLabel('Ya, ACC Poin (+5)')
                    ->action(function (Student $record, $livewire): void {
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, 'masuk');
                        if ($att) {
                            $att->approveLatePenalty(Auth::id());
                            Notification::make()
                                ->title('✅ Poin Keterlambatan Berhasil Di-ACC')
                                ->body("Poin keterlambatan (+5 Poin) untuk {$record->name} resmi dibukukan.")
                                ->success()
                                ->send();
                            $livewire->dispatch('refresh-presensi-table');
                        }
                    }),

                // 6. Dispensasi Keterlambatan (0 Poin) oleh Waka Kesiswaan
                Tables\Actions\Action::make('dispensasi_terlambat')
                    ->label('Dispensasi')
                    ->icon('heroicon-o-shield-check')
                    ->color('gray')
                    ->visible(function (Student $record, $livewire): bool {
                        if (!Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])) {
                            return false;
                        }
                        if (static::getActiveMode($livewire) !== 'harian') {
                            return false;
                        }
                        $att = $record->getAttendanceOnDate(static::getActiveDate($livewire), 'masuk');
                        if (!$att) {
                            return false;
                        }
                        $isLate = $att->waktu_kedatangan ? (Carbon::parse($att->waktu_kedatangan)->format('H:i') >= '06:46') : (bool) $att->is_late;
                        return $isLate && $att->late_approval_status !== 'rejected';
                    })
                    ->modalHeading(fn (Student $record) => "Beri Dispensasi Keterlambatan: {$record->name}")
                    ->modalDescription('Poin keterlambatan tidak akan dibebankan kepada siswa (0 poin).')
                    ->modalSubmitActionLabel('Simpan Dispensasi')
                    ->form([
                        Forms\Components\TextInput::make('alasan_dispensasi')
                            ->label('Alasan Dispensasi')
                            ->default('Dispensasi udzur resmi dari Waka Kesiswaan')
                            ->required(),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, 'masuk');
                        if ($att) {
                            $att->rejectLatePenalty($data['alasan_dispensasi'], Auth::id());
                            Notification::make()
                                ->title('🛡️ Dispensasi Keterlambatan Diberikan')
                                ->body("Keterlambatan {$record->name} diberikan dispensasi (0 Poin).")
                                ->info()
                                ->send();
                            $livewire->dispatch('refresh-presensi-table');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Action: ACC Poin Keterlambatan Siswa Terpilih
                    Tables\Actions\BulkAction::make('acc_poin_terlambat_bulk')
                        ->label('ACC Poin Keterlambatan Terpilih (+5 Poin)')
                        ->icon('heroicon-o-check-badge')
                        ->color('danger')
                        ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']))
                        ->requiresConfirmation()
                        ->modalHeading('ACC Poin Keterlambatan Siswa Terpilih?')
                        ->modalDescription('Semua siswa terpilih yang terlambat pada tanggal ini akan disetujui poin keterlambatannya (+5 Poin Pelanggaran) dan dibukukan ke skor karakter.')
                        ->modalSubmitActionLabel('Ya, ACC Semua Terpilih')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, $livewire): void {
                            $tanggal = static::getActiveDate($livewire);
                            $count = 0;
                            foreach ($records as $record) {
                                $att = $record->getAttendanceOnDate($tanggal, 'masuk');
                                $isLate = $att && ($att->waktu_kedatangan ? (Carbon::parse($att->waktu_kedatangan)->format('H:i') >= '06:46') : $att->is_late);
                                if ($isLate && $att->late_approval_status !== 'approved') {
                                    $att->approveLatePenalty(Auth::id());
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title('✅ Poin Keterlambatan Berhasil Di-ACC')
                                ->body("Sebanyak {$count} siswa berhasil di-ACC poin keterlambatannya (+5 Poin).")
                                ->success()
                                ->send();
                            $livewire->dispatch('refresh-presensi-table');
                        }),

                    // Bulk Action: Beri Dispensasi Siswa Terpilih
                    Tables\Actions\BulkAction::make('dispensasi_terlambat_bulk')
                        ->label('Beri Dispensasi Terpilih (0 Poin)')
                        ->icon('heroicon-o-shield-check')
                        ->color('gray')
                        ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']))
                        ->modalHeading('Beri Dispensasi Keterlambatan Terpilih')
                        ->modalDescription('Poin keterlambatan tidak akan dibebankan ke seluruh siswa terpilih.')
                        ->form([
                            Forms\Components\TextInput::make('alasan_dispensasi')
                                ->label('Alasan Dispensasi')
                                ->default('Dispensasi udzur resmi dari Waka Kesiswaan')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, $livewire): void {
                            $tanggal = static::getActiveDate($livewire);
                            $count = 0;
                            foreach ($records as $record) {
                                $att = $record->getAttendanceOnDate($tanggal, 'masuk');
                                $isLate = $att && ($att->waktu_kedatangan ? (Carbon::parse($att->waktu_kedatangan)->format('H:i') >= '06:46') : $att->is_late);
                                if ($isLate) {
                                    $att->rejectLatePenalty($data['alasan_dispensasi'], Auth::id());
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title('🛡️ Dispensasi Berhasil Diberikan')
                                ->body("Sebanyak {$count} siswa berhasil diberikan dispensasi keterlambatan.")
                                ->info()
                                ->send();
                            $livewire->dispatch('refresh-presensi-table');
                        }),
                    Tables\Actions\BulkAction::make('tandai_halangan_bulk')
                        ->label('Tandai Halangan Siswi (Pilihan)')
                        ->icon('heroicon-o-heart')
                        ->color('purple')
                        ->modalHeading('Tandai Halangan Sholat Siswi Terpilih')
                        ->modalDescription("Tandai siswi-siswi terpilih sedang berhalangan syar'i (haid). Otomatis sholat Dhuhur & Ashar dicatat 'Sedang Halangan', sedangkan absensi kedatangan tetap tercatat normal.")
                        ->form([
                            Forms\Components\DatePicker::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->default(fn ($livewire) => static::getActiveDate($livewire))
                                ->required(),
                            Forms\Components\DatePicker::make('tanggal_selesai')
                                ->label('Tanggal Selesai')
                                ->default(fn ($livewire) => Carbon::parse(static::getActiveDate($livewire))->addDays(6)->toDateString())
                                ->required(),
                            Forms\Components\TextInput::make('keterangan')
                                ->label('Keterangan')
                                ->default("Sedang halangan / udzur syar'i")
                                ->maxLength(255),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, $livewire): void {
                            $start = $data['tanggal_mulai'];
                            $end = $data['tanggal_selesai'] ?? $start;
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->jenis_kelamin !== 'P') {
                                    continue;
                                }

                                StudentHalangan::create([
                                    'student_id'      => $record->id,
                                    'institution_id'  => $record->institution_id ?: 2,
                                    'tanggal_mulai'   => $start,
                                    'tanggal_selesai' => $end,
                                    'catatan'         => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                                    'recorded_by'     => Auth::id() ?: 1,
                                ]);

                                $period = \Carbon\CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end));
                                foreach ($period as $dt) {
                                    $curDate = $dt->toDateString();
                                    foreach (['sholat_dhuhur', 'sholat_ashar'] as $sesi) {
                                        DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                            [
                                                'student_id' => $record->id,
                                                'tanggal'    => $curDate,
                                                'tipe_absen' => $sesi,
                                            ],
                                            [
                                                'institution_id'   => $record->institution_id ?: 2,
                                                'teacher_id'       => Auth::id() ?: 1,
                                                'status'           => 'halangan',
                                                'metode_absen'     => 'manual',
                                                'waktu_kedatangan' => null,
                                                'is_late'          => false,
                                                'keterangan'       => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                                            ]
                                        );
                                    }
                                }
                                $count++;
                            }

                            Notification::make()
                                ->title('Halangan Siswi Berhasil Ditandai')
                                ->body("Berhasil menandai {$count} siswi perempuan sedang halangan sholat.")
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-presensi-table');
                        }),

                    Tables\Actions\BulkAction::make('catat_izin_bulk')
                        ->label('Catat Izin / Sakit (Pilihan)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->modalHeading('Catat Izin / Sakit Siswa Terpilih')
                        ->modalDescription('Catatan izin/sakit/alpa dapat diterapkan ke seluruh sesi atau kepulangan siang saja.')
                        ->form([
                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->default(fn ($livewire) => static::getActiveDate($livewire))
                                ->required(),

                            Forms\Components\Radio::make('cakupan_sesi')
                                ->label('Cakupan Sesi Presensi')
                                ->options([
                                    'seharian'     => '📅 Seharian Penuh (Kedatangan, Dhuhur, Ashar)',
                                    'pulang_siang' => '⛅ Izin Pulang Siang (Dhuhur & Ashar Saja - Kedatangan Pagi Tetap Hadir)',
                                ])
                                ->default('seharian')
                                ->required(),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'sakit' => '🤒 Sakit',
                                    'izin'  => '📋 Izin',
                                    'alpa'  => '❌ Alpha',
                                ])
                                ->default('sakit')
                                ->required(),

                            Forms\Components\TextInput::make('keterangan')
                                ->label('Alasan / Keterangan')
                                ->placeholder('Contoh: Mengikuti kegiatan / izin keluarga / pulang siang sakit')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, $livewire): void {
                            $tanggal = $data['tanggal'];
                            $isFriday = Carbon::parse($tanggal)->isFriday();
                            $cakupan = $data['cakupan_sesi'] ?? 'seharian';
                            $count = 0;

                            foreach ($records as $record) {
                                $sesiSiang = ($isFriday && $record->jenis_kelamin === 'P') ? 'keputrian' : 'sholat_dhuhur';
                                $sesiList = ($cakupan === 'pulang_siang') ? [$sesiSiang, 'sholat_ashar'] : ['masuk', $sesiSiang, 'sholat_ashar'];

                                foreach ($sesiList as $sesi) {
                                    DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                        [
                                            'student_id' => $record->id,
                                            'tanggal'    => $tanggal,
                                            'tipe_absen' => $sesi,
                                        ],
                                        [
                                            'institution_id'   => $record->institution_id ?: 2,
                                            'teacher_id'       => Auth::id() ?: 1,
                                            'status'           => $data['status'],
                                            'metode_absen'     => 'manual',
                                            'keterangan'       => $data['keterangan'] ?? null,
                                            'waktu_kedatangan' => null,
                                            'is_late'          => false,
                                            'late_penalty_points' => 0,
                                        ]
                                    );
                                }

                                // Sync ke PrayerAttendance jika sesi sholat ikut diubah
                                foreach (['Dhuhur', 'Ashar'] as $prayerName) {
                                    \App\Models\PrayerAttendance::$isSyncing = true;
                                    try {
                                        \App\Models\PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                            [
                                                'student_id'   => $record->id,
                                                'tanggal'      => $tanggal,
                                                'jenis_sholat' => $prayerName,
                                            ],
                                            [
                                                'institution_id' => $record->institution_id ?: 2,
                                                'teacher_id'     => Auth::id() ?: 1,
                                                'status'         => $data['status'],
                                                'is_khusyu'      => true,
                                            ]
                                        );
                                    } finally {
                                        \App\Models\PrayerAttendance::$isSyncing = false;
                                    }
                                }

                                $count++;
                            }

                            $cakupanNote = ($cakupan === 'pulang_siang') ? ' (Sesi Siang & Ashar, presensi pagi tetap Hadir)' : '';

                            Notification::make()
                                ->title('Status Berhasil Dicatat')
                                ->body("Berhasil mencatat status {$data['status']} untuk {$count} siswa{$cakupanNote}.")
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-presensi-table');
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePresensiIzinSiswa::route('/'),
        ];
    }
}
