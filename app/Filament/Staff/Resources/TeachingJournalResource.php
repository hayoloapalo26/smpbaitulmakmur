<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeachingJournalResource\Pages;
use App\Models\TeachingJournal;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Models\Rppm;
use App\Models\TujuanPembelajaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TeachingJournalResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge([
            'guru_kelas', 'guru_mapel', 'koordinator_madin', 'wali_kelas',
            'waka_kurikulum', 'kepala_sekolah', 'superadmin'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public static function canCreate(): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user || $user->hasRole('kepala_sekolah')) {
            return false;
        }
        if ($user->hasAnyRole(['waka_kurikulum', 'superadmin'])) {
            return true;
        }
        return ! TeachingJournal::isPastDeadline($record->tanggal, $record->jam_ke);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function isQuranContext(Forms\Get $get): bool
    {
        $user = Auth::user();
        if ($user && $user->isQuranTeacher() && !$user->hasAnyRole(['guru_mapel', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        $teacherId = $get('teacher_id');
        if ($teacherId) {
            $teacher = User::find($teacherId);
            if ($teacher && $teacher->isQuranTeacher() && !$teacher->hasAnyRole(['guru_mapel', 'guru_kelas'])) {
                return true;
            }
        }

        $subjectId = $get('subject_id');
        if ($subjectId) {
            $subject = Subject::find($subjectId);
            if ($subject) {
                $name = strtolower($subject->name);
                if (str_contains($name, 'qur') || str_contains($name, 'tahfidz') || str_contains($name, 'yanbua') || str_contains($name, 'tahsin')) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static ?string $model = TeachingJournal::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Jurnal Mengajar & Presensi';

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Perencanaan Administrasi Pembelajaran';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Jurnal Mengajar';
        }
        return 'Jurnal Mengajar & Presensi';
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 1;
        }
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isAdminOrWaka = $user->hasAnyRole(['waka_kurikulum', 'superadmin']);

        return $form
            ->schema([
                Forms\Components\Section::make('Jadwal & Jam Mengajar (Otomatis Mencatat Presensi Guru)')
                    ->description('Pilih jadwal mengajar hari ini yang telah diatur oleh Waka Kurikulum. Input jurnal ini otomatis mencatat Presensi Kehadiran Guru (Status Masuk) pada jam pelajaran tersebut.')
                    ->schema([
                        Forms\Components\Placeholder::make('jadwal_hari_ini_alert')
                            ->label('')
                            ->content(function (Forms\Get $get) use ($isAdminOrWaka) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);
                                $count = Schedule::where('teacher_id', $teacherId)->where('hari', $hari)->count();

                                if ($count === 0) {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='flex items-center gap-3 p-3.5 text-sm rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-800/60'>
                                            <span class='text-xl'>⚠️</span>
                                            <div>
                                                <div class='font-bold'>Tidak Ada Jadwal Mengajar Hari Ini ({$hari})</div>
                                                <div class='text-xs mt-0.5'>Anda tidak memiliki jadwal mengajar pada hari <strong>{$hari}</strong> berdasarkan Jadwal Pelajaran Kurikulum. Pengisian jurnal mengajar otomatis disesuaikan dengan jadwal aktif hari ini.</div>
                                            </div>
                                        </div>
                                    ");
                                }

                                return new \Illuminate\Support\HtmlString("
                                    <div class='flex items-center justify-between p-3 text-sm rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800/60'>
                                        <div class='flex items-center gap-2.5'>
                                            <span class='text-lg'>📅</span>
                                            <div>
                                                <span class='font-bold'>Jadwal Kurikulum Hari Ini:</span> Hari <strong>{$hari}</strong> ({$count} jam pelajaran terjadwal).
                                            </div>
                                        </div>
                                        <span class='text-xs font-semibold px-2.5 py-1 bg-blue-100 dark:bg-blue-900 rounded-full text-blue-700 dark:text-blue-300'>Otomatis Hari Ini</span>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru Pengajar')
                            ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) use ($user) {
                                if ($user->institution_id && !$user->hasRole('superadmin')) {
                                    $query->where('institution_id', $user->institution_id);
                                }
                                $query->whereHas('roles', fn ($q) => $q->whereIn('name', array_merge(['guru_mapel', 'koordinator_madin', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES)));
                            })
                            ->default(Auth::id())
                            ->visible($isAdminOrWaka)
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('rppm_id', null);
                                $set('materi', null);
                                $set('tp_ids', []);
                                $set('student_attendances', []);
                                $set('catatan_karakter', []);
                            })
                            ->required(),

                        Forms\Components\Hidden::make('teacher_id')
                            ->default(Auth::id())
                            ->visible(!$isAdminOrWaka),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Mengajar')
                            ->default(Carbon::today()->format('Y-m-d'))
                            ->minDate(fn (?TeachingJournal $record) => $isAdminOrWaka ? null : ($record ? $record->tanggal : Carbon::today()->format('Y-m-d')))
                            ->maxDate(fn (?TeachingJournal $record) => $isAdminOrWaka ? null : ($record ? $record->tanggal : Carbon::today()->format('Y-m-d')))
                            ->disabled(fn () => !$isAdminOrWaka)
                            ->dehydrated()
                            ->helperText(function (Forms\Get $get) use ($isAdminOrWaka) {
                                $tanggal = $get('tanggal') ?: Carbon::today();
                                $hari = Schedule::getIndonesianDayName($tanggal);
                                if ($isAdminOrWaka) {
                                    return "📅 Hari: {$hari} (Mode Admin/Waka Kurikulum: Dapat memilih tanggal).";
                                }
                                return "📅 Hari ini: {$hari}, " . Carbon::today()->translatedFormat('d F Y') . " • Pengisian jurnal mengajar otomatis dikunci hanya untuk jadwal hari ini.";
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('rppm_id', null);
                                $set('materi', null);
                                $set('tp_ids', []);
                                $set('student_attendances', []);
                                $set('catatan_karakter', []);
                            })
                            ->required(),

                        Forms\Components\Select::make('schedule_id')
                            ->label('Pilih Jadwal Mengajar Hari Ini (Jadwal Kurikulum)')
                            ->placeholder(function (Forms\Get $get) {
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);
                                return "Pilih jadwal mengajar hari {$hari}...";
                            })
                            ->options(function (Forms\Get $get, ?TeachingJournal $record) use ($isAdminOrWaka) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);

                                // Jadwal reguler kurikulum milik guru yang bersangkutan pada hari tersebut
                                $regularSchedules = Schedule::with(['classroom', 'subject', 'teacher'])
                                    ->where('teacher_id', $teacherId)
                                    ->where('hari', $hari)
                                    ->orderBy('jam_ke')
                                    ->get();

                                // Jadwal di mana guru ini ditugaskan sebagai Guru Pengganti (Inval) pada tanggal/hari tersebut
                                $dayName = Schedule::getIndonesianDayName($tanggal);
                                $isPiketDuty = \App\Models\DutySchedule::where('teacher_id', $teacherId)
                                    ->where(function ($q) use ($tanggal, $dayName) {
                                        $q->where('hari', $dayName)
                                          ->orWhereDate('tanggal', $tanggal);
                                    })->exists()
                                    || auth()->user()?->hasRole('guru_piket');

                                $guruPiketIds = \App\Models\User::withoutGlobalScopes()
                                    ->where(fn ($q) => $q->where('name', 'Guru Piket')->orWhereHas('roles', fn ($r) => $r->where('name', 'guru_piket')))
                                    ->pluck('id')
                                    ->toArray();

                                $targetSubstituteIds = array_unique(array_merge([$teacherId], $isPiketDuty ? $guruPiketIds : []));

                                $substituteScheduleIds = \App\Models\TeacherAttendance::withoutGlobalScopes()
                                    ->whereIn('substitute_teacher_id', $targetSubstituteIds)
                                    ->whereDate('tanggal', $tanggal)
                                    ->whereNotNull('schedule_id')
                                    ->pluck('schedule_id')
                                    ->toArray();

                                $substituteSchedules = !empty($substituteScheduleIds)
                                    ? Schedule::with(['classroom', 'subject', 'teacher'])
                                        ->whereIn('id', $substituteScheduleIds)
                                        ->where('hari', $hari)
                                        ->orderBy('jam_ke')
                                        ->get()
                                    : collect();

                                $schedules = $regularSchedules->merge($substituteSchedules)->sortBy('jam_ke');

                                $options = [];
                                foreach ($schedules as $s) {
                                    $isSubstituteForThis = ($s->teacher_id != $teacherId);
                                    $alreadyFilled = TeachingJournal::where('schedule_id', $s->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->exists();

                                    $statusText = '';
                                    if ($alreadyFilled) {
                                        $statusText = ' ✅ [Sudah Diisi Hari Ini]';
                                    }

                                    if (!$isSubstituteForThis) {
                                        // Cek apakah guru sedang izin yang telah di-ACC pada jam pelajaran ini
                                        $onLeave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $s->jam_ke);
                                        if ($onLeave) {
                                            $statusText = " 🚫 [Izin ACC: {$onLeave->waktu_izin_label}]";
                                        }
                                    } else {
                                        $statusText = " 🔄 [TUGAS INVAL DARI {$s->teacher?->name}]" . $statusText;
                                    }

                                    $jamText = \App\Models\Schedule::getJamLabelWithTime($s->jam_ke);
                                    $options[$s->id] = "{$jamText} | Kelas {$s->classroom?->name} — {$s->subject?->name}{$statusText}";
                                }

                                return $options;
                            })
                            ->disableOptionWhen(function (string $value, Forms\Get $get, ?TeachingJournal $record) use ($isAdminOrWaka) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $schedule = Schedule::find($value);
                                if (!$schedule) return false;

                                $isSubstituteForThis = ($schedule->teacher_id != $teacherId);

                                // 1. Kunci opsi jika guru reguler memiliki izin yang telah di-ACC pada jam ini
                                if (!$isSubstituteForThis && \App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $schedule->jam_ke)) {
                                    return true;
                                }

                                // 2. Kunci opsi jika sudah diisi sebelumnya pada hari ini
                                if (TeachingJournal::where('schedule_id', $schedule->id)
                                    ->whereDate('tanggal', $tanggal)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->exists()) {
                                    return true;
                                }

                                return false;
                            })
                            ->rules([
                                function (Forms\Get $get) use ($isAdminOrWaka) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $isAdminOrWaka) {
                                        $teacherId = $get('teacher_id') ?: Auth::id();
                                        $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                        $hari = Schedule::getIndonesianDayName($tanggal);

                                        if (!$isAdminOrWaka && $tanggal !== Carbon::today()->toDateString()) {
                                            $fail("Pengisian jurnal mengajar hanya dapat dilakukan pada hari ini (" . Carbon::today()->translatedFormat('d F Y') . ").");
                                            return;
                                        }

                                        $schedule = Schedule::find($value);
                                        if (!$schedule) {
                                            $fail("Jadwal mengajar tidak ditemukan.");
                                            return;
                                        }

                                        if ($schedule->hari !== $hari) {
                                            $fail("Jadwal yang dipilih merupakan jadwal hari {$schedule->hari}, bukan jadwal hari {$hari}.");
                                            return;
                                        }

                                        if ($schedule->teacher_id == $teacherId && \App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $schedule->jam_ke)) {
                                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $schedule->jam_ke);
                                            $fail("Tidak dapat mengisi jurnal mengajar! Anda tercatat memiliki perizinan yang telah disetujui (ACC) pada hari ini (" . ($leave?->waktu_izin_label ?? "Jam Ke-{$schedule->jam_ke}") . ").");
                                        }
                                    };
                                }
                            ])
                            ->helperText(function (Forms\Get $get) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);

                                $leaveToday = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal);
                                if ($leaveToday) {
                                    return "⛔ PERHATIAN: Terdapat perizinan guru yang telah di-ACC pada hari ini ({$leaveToday->waktu_izin_label} — Alasan: {$leaveToday->alasan}). Jadwal jam pelajaran yang bertepatan dengan izin otomatis terkunci.";
                                }

                                $count = Schedule::where('teacher_id', $teacherId)->where('hari', $hari)->count();
                                if ($count === 0) {
                                    return "⚠️ Anda tidak memiliki jadwal mengajar pada hari {$hari} berdasarkan Jadwal Pelajaran Kurikulum. Pengisian jurnal hanya dapat dilakukan saat Anda memiliki jadwal pada hari ini.";
                                }
                                return "📋 Terdapat {$count} jam mengajar terjadwal untuk Anda pada hari {$hari}. Silakan pilih jadwal untuk mengisi jurnal mengajar.";
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (!$state) {
                                    $set('classroom_id', null);
                                    $set('subject_id', null);
                                    $set('jam_ke', null);
                                    $set('rppm_id', null);
                                    $set('materi', null);
                                    $set('tp_ids', []);
                                    $set('student_attendances', []);
                                    $set('catatan_karakter', []);
                                    return;
                                }

                                $schedule = Schedule::find($state);
                                if (!$schedule) return;

                                $set('classroom_id', $schedule->classroom_id);
                                $set('subject_id', $schedule->subject_id);
                                $set('jam_ke', $schedule->jam_ke);

                                // Auto pre-fill RPPM & TP jika BUKAN konteks Al-Qur'an dan guru memiliki dokumen RPPM untuk mapel ini
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $teacher = User::find($teacherId);
                                $subjectName = $schedule->subject?->name;
                                $isQuran = ($teacher && $teacher->isQuranTeacher() && !$teacher->hasAnyRole(['guru_mapel', 'guru_kelas']))
                                    || ($subjectName && (
                                        str_contains(strtolower($subjectName), 'qur')
                                        || str_contains(strtolower($subjectName), 'tahfidz')
                                        || str_contains(strtolower($subjectName), 'yanbua')
                                        || str_contains(strtolower($subjectName), 'tahsin')
                                    ));

                                if ($isQuran) {
                                    $set('rppm_id', null);
                                    $set('tp_ids', []);
                                    if (empty($get('materi')) && $subjectName) {
                                        $set('materi', "Pembelajaran {$subjectName}");
                                    }
                                } else {
                                    $matchingRppm = Rppm::query()
                                        ->where('guru_id', $teacherId)
                                        ->where(function ($q) use ($schedule, $subjectName) {
                                            if ($schedule->subject_id) {
                                                $q->whereHas('capaianPembelajaran', fn ($cp) => $cp->where('subject_id', $schedule->subject_id));
                                            }
                                            if ($subjectName) {
                                                $q->orWhere('mata_pelajaran', 'like', "%{$subjectName}%");
                                            }
                                        })
                                        ->latest()
                                        ->first();

                                    if ($matchingRppm) {
                                        $set('rppm_id', $matchingRppm->id);
                                        if (!empty($matchingRppm->topik) && empty($get('materi'))) {
                                            $set('materi', $matchingRppm->topik);
                                        }
                                        if (!empty($matchingRppm->selected_tp_ids) && empty($get('tp_ids'))) {
                                            $set('tp_ids', $matchingRppm->selected_tp_ids);
                                        }
                                    }
                                }

                                $tanggal = $get('tanggal') ?? Carbon::today()->format('Y-m-d');
                                $students = \App\Models\Student::where('classroom_id', $schedule->classroom_id)->orderBy('name')->get();
                                $attendanceData = [];
                                foreach ($students as $student) {
                                    $morning = \App\Models\DailyAttendance::where('student_id', $student->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->where('tipe_absen', 'masuk')
                                        ->first();
                                    if ($morning && in_array($morning->status, ['sakit', 'izin', 'alpa'])) {
                                        $attendanceData[$student->id] = $morning->status;
                                    } else {
                                        $attendanceData[$student->id] = 'hadir';
                                    }
                                }
                                $set('student_attendances', $attendanceData);
                            })
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('jam_ke')
                            ->label('Jam Pelajaran & Waktu')
                            ->options(Schedule::getJamOptions())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make(fn (Forms\Get $get) => static::isQuranContext($get) ? '📖 Lingkup Materi Pembelajaran Al-Qur\'an' : '🎯 Lingkup Materi & Penarikan TP dari RPPM & Perangkat Pembelajaran')
                    ->description(fn (Forms\Get $get) => static::isQuranContext($get)
                        ? 'Inputkan materi pokok atau capaian pembelajaran Al-Qur\'an (Surat/Ayat/Jilid/Halaman) yang diajarkan pada jam ini tanpa perlu input CP/TP/ATP.'
                        : 'Tarik otomatis Tujuan Pembelajaran (TP) yang sudah di-inputkan di RPPM atau pilih langsung dari Tujuan Pembelajaran mapel terkait.')
                    ->schema([
                        Forms\Components\Select::make('rppm_id')
                            ->label('Tarik Dokumen RPPM Guru (Opsional)')
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get))
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $teacherId = $get('teacher_id') ?: Auth::id();

                                $query = Rppm::query();
                                if (!auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
                                    $query->where('guru_id', $teacherId);
                                }
                                if ($subjectId) {
                                    $subjectName = \App\Models\Subject::where('id', $subjectId)->value('name');
                                    if ($subjectName) {
                                        $query->where('mata_pelajaran', 'like', "%{$subjectName}%");
                                    }
                                }
                                return $query->latest()->get()->mapWithKeys(function ($r) {
                                    $tpCount = count($r->selected_tp_ids ?? []);
                                    return [$r->id => "RPPM: {$r->topik} ({$r->kelas_fase} | {$tpCount} TP)"];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if (!$state) return;
                                $rppm = Rppm::find($state);
                                if ($rppm) {
                                    if (!empty($rppm->topik)) {
                                        $set('materi', $rppm->topik);
                                    }
                                    if (!empty($rppm->selected_tp_ids)) {
                                        $set('tp_ids', $rppm->selected_tp_ids);
                                    }
                                }
                            })
                            ->helperText('Pilih RPPM untuk otomatis mengisi Materi Pokok dan mencentang Tujuan Pembelajaran (TP) yang diajarkan.'),

                        Forms\Components\TextInput::make('materi')
                            ->label(fn (Forms\Get $get) => static::isQuranContext($get) ? 'Materi Pokok / Capaian Surat & Ayat' : 'Topik / Materi Pokok Pembelajaran')
                            ->placeholder(fn (Forms\Get $get) => static::isQuranContext($get) ? 'Contoh: QS. Al-Baqarah ayat 1-15 / Yanbu\'a Jilid 3 Hal. 10-15 / Makharijul Huruf' : 'Contoh: Operasi Aljabar / Teks Deskripsi / Mengenal Perangkat Komputer')
                            ->required()
                            ->helperText(fn (Forms\Get $get) => static::isQuranContext($get) ? 'Lingkup materi atau capaian Al-Qur\'an yang disampaikan pada jam mengajar ini.' : 'Lingkup materi pokok yang disampaikan pada jam mengajar ini.'),

                        Forms\Components\Select::make('tp_ids')
                            ->label('Tujuan Pembelajaran (TP) Terkait [Multiple Choice]')
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get))
                            ->dehydrated(fn (Forms\Get $get) => !static::isQuranContext($get))
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $classroomId = $get('classroom_id');
                                $rppmId = $get('rppm_id');

                                $rppmTpIds = [];
                                if ($rppmId) {
                                    $rppm = Rppm::find($rppmId);
                                    $rppmTpIds = $rppm?->selected_tp_ids ?? [];
                                }

                                $query = TujuanPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                if ($classroomId) {
                                    $tingkat = \App\Models\Classroom::where('id', $classroomId)->value('tingkat');
                                    if ($tingkat) {
                                        $query->where('kelas_tingkat', $tingkat);
                                    }
                                }

                                return $query->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                })->mapWithKeys(function ($tp) use ($rppmTpIds) {
                                    $isRppm = in_array($tp->id, $rppmTpIds);
                                    $tag = $isRppm ? '📌 [Dari RPPM] ' : '';
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    $kode = $tp->kode_tp ? "({$tp->kode_tp}) " : '';
                                    return [$tp->id => "{$tag}{$kode}{$prefix}" . Str::limit($tp->deskripsi_tp, 70)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if (empty($get('materi')) && !empty($state) && is_array($state)) {
                                    $materiNames = TujuanPembelajaran::whereIn('id', $state)->pluck('materi')->filter()->unique()->implode(', ');
                                    if ($materiNames) {
                                        $set('materi', $materiNames);
                                    }
                                }
                            })
                            ->helperText('💡 Tujuan Pembelajaran yang ditarik dari dokumen RPPM & Perangkat Pembelajaran guru. Anda juga dapat memilih lebih dari satu TP.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('To Do List Kegiatan Rutin')
                    ->description('Dengan melaksanakan to do list ini, anda berkontribusi terhadap pengembangan kualitas murid')
                    ->schema([
                        Forms\Components\CheckboxList::make('kegiatan_rutin')
                            ->label('')
                            ->options([
                                'Do\'a (Karakter)' => 'Do\'a (Karakter)',
                                'Menanyakan Kabar (Karakter)' => 'Menanyakan Kabar (Karakter)',
                                'Absensi (Akademik)' => 'Absensi (Akademik)',
                                'Review Materi Sebelumnya (Akademik)' => 'Review Materi Sebelumnya (Akademik)',
                                'Pembelajaran Harian (Akademik)' => 'Pembelajaran Harian (Akademik)',
                                'Mengingatkan Tentang Sampah (Karakter)' => 'Mengingatkan Tentang Sampah (Karakter)',
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Jurnal Pembelajaran')
                    ->schema([
                        Forms\Components\TextInput::make('rencana_kegiatan_guru')
                            ->label('Rencana Kegiatan Guru')
                            ->placeholder('Contoh: Guru menyampaikan Ceramah')
                            ->required(),
                        Forms\Components\TextInput::make('kegiatan_siswa')
                            ->label('Kegiatan Siswa')
                            ->placeholder('Contoh: Siswa Melakukan Presentasi')
                            ->required(),
                        Forms\Components\Textarea::make('refleksi_guru')
                            ->label('Refleksi Guru')
                            ->placeholder('Apa yang berjalan baik? Apa yang perlu ditingkatkan?')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('tindak_lanjut')
                            ->label('Tindak Lanjut')
                            ->placeholder('Rencana tindak lanjut / remedial / pengayaan...')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Hidden::make('catatan_karakter')
                    ->default([])
                    ->dehydrated()
                    ->afterStateHydrated(function (Forms\Components\Hidden $component, ?TeachingJournal $record) {
                        if ($record) {
                            $component->state($record->catatan_karakter ?? []);
                        }
                    })
                    ->saveRelationshipsUsing(function (TeachingJournal $record, $state) {
                        if (is_array($state)) {
                            $record->catatan_karakter = $state;
                            $record->saveQuietly();
                        }
                        $record->syncCharacterScoreLogs();
                    }),

                Forms\Components\ViewField::make('student_attendances')
                    ->label('Presensi & Penilaian Karakter Siswa Per Mapel')
                    ->view('filament.staff.forms.components.student-attendance-selector')
                    ->viewData(function (Forms\Get $get, ?TeachingJournal $record, Forms\Components\ViewField $component) {
                        $classroomId = $get('classroom_id') ?? $record?->classroom_id;
                        $students = $classroomId
                            ? \App\Models\Student::where('classroom_id', $classroomId)->orderBy('name')->get()
                            : collect();

                        return [
                            'students' => $students,
                            'classroomId' => $classroomId,
                            'charStatePath' => str_replace('student_attendances', 'catatan_karakter', $component->getStatePath()),
                        ];
                    })
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\ViewField $component, ?TeachingJournal $record) {
                        if ($record) {
                            $existing = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)
                                ->pluck('status', 'student_id')
                                ->toArray();
                            $students = \App\Models\Student::where('classroom_id', $record->classroom_id)->orderBy('name')->get();
                            $attendanceData = [];
                            foreach ($students as $student) {
                                $attendanceData[$student->id] = $existing[$student->id] ?? 'hadir';
                            }
                            $component->state($attendanceData);
                        }
                    })
                    ->saveRelationshipsUsing(function (TeachingJournal $record, $state) {
                        $students = \App\Models\Student::where('classroom_id', $record->classroom_id)->get();
                        $state = is_array($state) ? $state : [];

                        foreach ($students as $student) {
                            $status = $state[$student->id] ?? $state[(string) $student->id] ?? 'hadir';
                            if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpa'])) {
                                $status = 'hadir';
                            }

                            \App\Models\SubjectAttendance::updateOrCreate(
                                [
                                    'teaching_journal_id' => $record->id,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'institution_id' => $student->institution_id,
                                    'status' => $status,
                                ]
                            );

                            $morning = \App\Models\DailyAttendance::where('student_id', $student->id)
                                ->whereDate('tanggal', $record->tanggal)
                                ->where('tipe_absen', 'masuk')
                                ->first();

                            // Jika belum ada rekap presensi harian masuk pada hari tersebut, sinkronkan ke DailyAttendance agar masuk ke Waka Kesiswaan lembaga terkait
                            if (!$morning) {
                                \App\Models\DailyAttendance::create([
                                    'institution_id' => $student->institution_id,
                                    'student_id' => $student->id,
                                    'teacher_id' => $record->teacher_id ?: Auth::id(),
                                    'tanggal' => Carbon::parse($record->tanggal)->toDateString(),
                                    'tipe_absen' => 'masuk',
                                    'metode_absen' => 'jurnal_mengajar',
                                    'status' => $status,
                                    'is_late' => false,
                                    'keterangan' => 'Tercatat dari Jurnal Mengajar (' . ($record->subject->name ?? 'Mapel') . ')',
                                ]);
                            } elseif ($morning->metode_absen === 'jurnal_mengajar') {
                                $morning->update(['status' => $status]);
                            }
                        }
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal & Hari')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('d M Y') . ' (' . Schedule::getIndonesianDayName($state) . ')')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Pelajaran')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => Schedule::getJamLabelWithTime($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->label('Guru'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('materi')
                    ->label('Materi & TP')
                    ->limit(35)
                    ->tooltip(fn ($state) => $state)
                    ->description(function (TeachingJournal $record) {
                        $count = is_array($record->tp_ids) ? count($record->tp_ids) : 0;
                        if ($count > 0) {
                            return "🎯 {$count} Tujuan Pembelajaran (TP)";
                        }
                        if ($record->rppm) {
                            return '📄 Dari RPPM Guru';
                        }
                        return null;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('presensi_status')
                    ->label('Presensi Guru')
                    ->state(fn () => 'Hadir (Dari Jurnal)')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle'),
                Tables\Columns\TextColumn::make('student_attendance_summary')
                    ->label('Presensi Siswa')
                    ->state(function (TeachingJournal $record) {
                        $attendances = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)->get();
                        if ($attendances->isEmpty()) return '-';
                        $hadir = $attendances->where('status', 'hadir')->count();
                        $sakit = $attendances->where('status', 'sakit')->count();
                        $izin = $attendances->where('status', 'izin')->count();
                        $alpa = $attendances->where('status', 'alpa')->count();

                        $parts = [];
                        if ($hadir > 0) $parts[] = "H: {$hadir}";
                        if ($sakit > 0) $parts[] = "S: {$sakit}";
                        if ($izin > 0) $parts[] = "I: {$izin}";
                        if ($alpa > 0) $parts[] = "A: {$alpa}";

                        return implode(' • ', $parts);
                    })
                    ->badge()
                    ->color(function (TeachingJournal $record) {
                        $hasAbsence = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)
                            ->whereIn('status', ['sakit', 'izin', 'alpa'])
                            ->exists();
                        return $hasAbsence ? 'warning' : 'success';
                    }),
                Tables\Columns\TextColumn::make('catatan_karakter')
                    ->label('Karakter Siswa')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state) || count($state) === 0) {
                            return '-';
                        }
                        $baik = collect($state)->where('type', 'baik')->count();
                        $kurang = collect($state)->where('type', 'kurang_baik')->count();
                        $badges = [];
                        if ($baik > 0) $badges[] = "⭐ {$baik} Baik";
                        if ($kurang > 0) $badges[] = "⚠️ {$kurang} Kurang";
                        return implode(' • ', $badges);
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (empty($state) || !is_array($state) || count($state) === 0) return 'gray';
                        $hasKurang = collect($state)->where('type', 'kurang_baik')->count() > 0;
                        return $hasKurang ? 'danger' : 'success';
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (TeachingJournal $record): string => route('academic.journal.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->modalWidth('5xl')
                    ->before(function (Tables\Actions\EditAction $action, TeachingJournal $record, array $data) {
                        $teacherId = $data['teacher_id'] ?? $record->teacher_id;
                        $tanggal = $data['tanggal'] ?? $record->tanggal;
                        $schedule = isset($data['schedule_id']) ? Schedule::find($data['schedule_id']) : null;
                        $jamKe = $schedule ? $schedule->jam_ke : ($data['jam_ke'] ?? $record->jam_ke);

                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $jamKe)) {
                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $jamKe);
                            \Filament\Notifications\Notification::make()
                                ->title('Perubahan Jurnal Ditolak')
                                ->body('Jurnal ini tidak dapat diubah karena guru bersangkutan memiliki perizinan yang telah di-ACC pada waktu tersebut (' . ($leave?->waktu_izin_label ?? "Jam Ke-{$jamKe}") . ').')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeachingJournals::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
