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
        $user = auth()->user();
        if (!$user) return false;

        $allowedRoles = array_merge([
            'guru_kelas', 'guru_mapel', 'koordinator_madin', 'wali_kelas',
            'waka_kurikulum', 'kepala_sekolah', 'superadmin'
        ], \App\Models\User::QURAN_TEACHER_ROLES);

        try {
            return $user->hasAnyRole($allowedRoles);
        } catch (\Throwable $e) {
            return $user->roles->pluck('name')->intersect($allowedRoles)->isNotEmpty();
        }
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
        // Guru tidak dapat mengubah jurnal jika sudah disetujui (ACC) oleh Kepala Sekolah
        if ($record instanceof TeachingJournal && $record->status_acc_kepsek === TeachingJournal::ACC_KEPSEK_APPROVED) {
            return false;
        }
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function isQuranContext(Forms\Get $get): bool
    {
        $user = Auth::user();
        if ($user && $user->isPureQuranTeacher()) {
            return true;
        }

        $teacherId = $get('teacher_id');
        if ($teacherId) {
            $teacher = User::find($teacherId);
            if ($teacher && $teacher->isPureQuranTeacher()) {
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
        $user = Auth::user();
        if ($user && $user->hasRole('kepala_sekolah')) {
            // Kepala Sekolah hanya melihat jurnal yang siap (tepat waktu atau terlambat yang sudah disetujui Waka)
            return $query->readyForKepsek();
        }
        if ($user && $user->hasAnyRole(['waka_kurikulum', 'superadmin'])) {
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
                                $count = Schedule::withoutGlobalScopes()->where('teacher_id', $teacherId)->where('hari', $hari)->count();

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
                            ->maxDate(Carbon::today()->format('Y-m-d'))
                            ->dehydrated()
                            ->helperText(function (Forms\Get $get) {
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);
                                $isToday = ($tanggal === Carbon::today()->format('Y-m-d'));
                                if ($isToday) {
                                    return "📅 Hari ini: {$hari}, " . Carbon::today()->translatedFormat('d F Y') . " • Pengisian tepat waktu langsung diteruskan ke Kepala Sekolah.";
                                }
                                return "⚠️ Pengisian Terlambat: Tanggal mengajar ({$hari}, " . Carbon::parse($tanggal)->translatedFormat('d F Y') . ") telah melewati 23:59. Jurnal ini akan tercatat terlambat dan masuk ke Persetujuan Waka Kurikulum terlebih dahulu sebelum masuk ke laporan Kepala Sekolah.";
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
                            ->label(fn (Forms\Get $get) => 'Pilih Jadwal Mengajar (' . Schedule::getIndonesianDayName($get('tanggal') ?: Carbon::today()) . ')')
                            ->searchable()
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
                                $regularSchedules = Schedule::withoutGlobalScopes()
                                    ->with(['classroom', 'subject', 'teacher'])
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
                                    ? Schedule::withoutGlobalScopes()
                                        ->with(['classroom', 'subject', 'teacher'])
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
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $teacherId = $get('teacher_id') ?: Auth::id();
                                        $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                        $hari = Schedule::getIndonesianDayName($tanggal);

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
                                $isQuran = ($teacher && $teacher->isPureQuranTeacher())
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
                                        $matchingTpIds = !empty($matchingRppm->selected_tp_ids)
                                            ? array_values(array_map('intval', (array) $matchingRppm->selected_tp_ids))
                                            : [];
                                        if (empty($matchingTpIds) && !empty($matchingRppm->content['tujuan_pembelajaran']) && is_array($matchingRppm->content['tujuan_pembelajaran'])) {
                                            foreach ($matchingRppm->content['tujuan_pembelajaran'] as $tpItem) {
                                                $rumusan = is_array($tpItem) ? ($tpItem['rumusan'] ?? '') : (string) $tpItem;
                                                if ($rumusan) {
                                                    $cleanRumusan = trim(preg_replace('/^\[.*?\]\s*/', '', $rumusan));
                                                    $matched = TujuanPembelajaran::where('deskripsi_tp', 'like', "%{$cleanRumusan}%")->first();
                                                    if ($matched) {
                                                        $matchingTpIds[] = (int) $matched->id;
                                                    }
                                                }
                                            }
                                            $matchingTpIds = array_values(array_unique($matchingTpIds));
                                        }
                                        if (!empty($matchingTpIds) && empty($get('tp_ids'))) {
                                            $set('tp_ids', $matchingTpIds);
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
                                    $tpIds = !empty($rppm->selected_tp_ids)
                                        ? array_values(array_map('intval', (array) $rppm->selected_tp_ids))
                                        : [];
                                    if (empty($tpIds) && !empty($rppm->content['tujuan_pembelajaran']) && is_array($rppm->content['tujuan_pembelajaran'])) {
                                        foreach ($rppm->content['tujuan_pembelajaran'] as $tpItem) {
                                            $rumusan = is_array($tpItem) ? ($tpItem['rumusan'] ?? '') : (string) $tpItem;
                                            if ($rumusan) {
                                                $cleanRumusan = trim(preg_replace('/^\[.*?\]\s*/', '', $rumusan));
                                                $matched = TujuanPembelajaran::where('deskripsi_tp', 'like', "%{$cleanRumusan}%")->first();
                                                if ($matched) {
                                                    $tpIds[] = (int) $matched->id;
                                                }
                                            }
                                        }
                                        $tpIds = array_values(array_unique($tpIds));
                                    }
                                    if (!empty($tpIds)) {
                                        $set('tp_ids', $tpIds);
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
                            ->label('Tujuan Pembelajaran (TP) yang Diajarkan')
                            ->placeholder('Pilih Tujuan Pembelajaran (TP)...')
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get))
                            ->dehydrated(fn (Forms\Get $get) => !static::isQuranContext($get))
                            ->multiple()
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (!$value) return null;
                                $tp = TujuanPembelajaran::find($value);
                                if (!$tp) return null;
                                return trim((string) ($tp->deskripsi_tp ?: ($tp->materi ?: "Tujuan Pembelajaran #{$tp->id}")));
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                if (empty($values)) return [];
                                return TujuanPembelajaran::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(function ($tp) {
                                        $desc = trim((string) ($tp->deskripsi_tp ?: ($tp->materi ?: "Tujuan Pembelajaran #{$tp->id}")));
                                        return [(string) $tp->id => $desc];
                                    })
                                    ->toArray();
                            })
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $classroomId = $get('classroom_id');
                                $rppmId = $get('rppm_id');
                                $currentTpIds = (array) ($get('tp_ids') ?? []);

                                $rppmTpIds = [];
                                if ($rppmId) {
                                    $rppm = Rppm::find($rppmId);
                                    $rppmTpIds = $rppm?->selected_tp_ids ?? [];
                                }

                                $priorityIds = array_values(array_filter(array_unique(array_merge(
                                    array_map('intval', (array) $rppmTpIds),
                                    array_map('intval', $currentTpIds)
                                ))));

                                $tingkat = null;
                                if ($classroomId) {
                                    $rawTingkat = \App\Models\Classroom::where('id', $classroomId)->value('tingkat');
                                    if ($rawTingkat && preg_match('/(7|8|9)/', (string) $rawTingkat, $m)) {
                                        $tingkat = $m[1];
                                    }
                                }

                                $query = TujuanPembelajaran::query();

                                if (!empty($priorityIds)) {
                                    $query->where(function ($q) use ($priorityIds, $subjectId, $tingkat) {
                                        $q->whereIn('id', $priorityIds);
                                        $q->orWhere(function ($sub) use ($subjectId, $tingkat) {
                                            if ($subjectId) {
                                                $sub->where('subject_id', $subjectId);
                                            }
                                            if ($tingkat) {
                                                $sub->where(function ($w) use ($tingkat) {
                                                    $w->where('kelas_tingkat', $tingkat)
                                                      ->orWhere('kelas_tingkat', 'like', "%{$tingkat}%")
                                                      ->orWhereNull('kelas_tingkat');
                                                });
                                            }
                                        });
                                    });
                                } else {
                                    if ($subjectId) {
                                        $query->where('subject_id', $subjectId);
                                    }
                                    if ($tingkat) {
                                        $query->where(function ($w) use ($tingkat) {
                                            $w->where('kelas_tingkat', $tingkat)
                                              ->orWhere('kelas_tingkat', 'like', "%{$tingkat}%")
                                              ->orWhereNull('kelas_tingkat');
                                        });
                                    }
                                }

                                return $query->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                })->mapWithKeys(function ($tp) use ($rppmTpIds) {
                                    $isRppm = in_array((int) $tp->id, array_map('intval', (array) $rppmTpIds));
                                    $tag = $isRppm ? '📌 [Dari RPPM] ' : '';
                                    $deskripsi = trim((string) ($tp->deskripsi_tp ?: ($tp->materi ?: "Tujuan Pembelajaran #{$tp->id}")));
                                    return [(string) $tp->id => "{$tag}{$deskripsi}"];
                                })->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, Forms\Get $get): array {
                                $subjectId = $get('subject_id');
                                $query = TujuanPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                return $query->where(function ($q) use ($search) {
                                    $q->where('deskripsi_tp', 'like', "%{$search}%")
                                      ->orWhere('materi', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($tp) {
                                    $deskripsi = trim((string) ($tp->deskripsi_tp ?: ($tp->materi ?: "Tujuan Pembelajaran #{$tp->id}")));
                                    return [(string) $tp->id => $deskripsi];
                                })
                                ->toArray();
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
                            ->helperText('💡 Pilih Tujuan Pembelajaran (TP) berdasarkan rumusan deskripsinya. Anda dapat memilih lebih dari satu TP.')
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
                Tables\Columns\TextColumn::make('late_status')
                    ->label('Status Pengisian')
                    ->badge()
                    ->formatStateUsing(fn ($state, TeachingJournal $record) => match ($state) {
                        TeachingJournal::LATE_STATUS_ON_TIME => 'Tepat Waktu',
                        TeachingJournal::LATE_STATUS_PENDING_WAKA => '⚠️ Terlambat (Menunggu Waka)',
                        TeachingJournal::LATE_STATUS_APPROVED_WAKA => '✅ Terlambat (ACC Waka)',
                        TeachingJournal::LATE_STATUS_REJECTED_WAKA => '❌ Ditolak Waka',
                        default => $record->is_late ? '⚠️ Terlambat' : 'Tepat Waktu',
                    })
                    ->color(fn ($state, TeachingJournal $record) => match ($state) {
                        TeachingJournal::LATE_STATUS_ON_TIME => 'success',
                        TeachingJournal::LATE_STATUS_PENDING_WAKA => 'warning',
                        TeachingJournal::LATE_STATUS_APPROVED_WAKA => 'info',
                        TeachingJournal::LATE_STATUS_REJECTED_WAKA => 'danger',
                        default => $record->is_late ? 'warning' : 'success',
                    })
                    ->description(fn (TeachingJournal $record) => $record->wakaApprover ? "ACC Waka: {$record->wakaApprover->name}" : null),

                Tables\Columns\TextColumn::make('status_acc_kepsek')
                    ->label('ACC Kepala Sekolah')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        TeachingJournal::ACC_KEPSEK_APPROVED => '✅ Disetujui KS',
                        TeachingJournal::ACC_KEPSEK_NEED_REVISION => '⚠️ Perlu Revisi',
                        default => '⏳ Menunggu ACC',
                    })
                    ->color(fn ($state) => match ($state) {
                        TeachingJournal::ACC_KEPSEK_APPROVED => 'success',
                        TeachingJournal::ACC_KEPSEK_NEED_REVISION => 'danger',
                        default => 'gray',
                    })
                    ->description(function (TeachingJournal $record) {
                        if ($record->status_acc_kepsek === TeachingJournal::ACC_KEPSEK_APPROVED) {
                            return $record->kepsekApprover ? "Oleh: {$record->kepsekApprover->name}" : 'Disahkan KS';
                        }
                        if ($record->is_late && $record->late_status === TeachingJournal::LATE_STATUS_PENDING_WAKA) {
                            return 'Menunggu Persetujuan Waka';
                        }
                        return null;
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                // 1. Persetujuan Terlambat oleh Waka Kurikulum
                Tables\Actions\Action::make('approve_late_waka')
                    ->label('Persetujuan Terlambat')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (TeachingJournal $record) => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) && $record->is_late && $record->late_status === TeachingJournal::LATE_STATUS_PENDING_WAKA)
                    ->modalHeading('Persetujuan Pengisian Jurnal Mengajar Terlambat (Waka Kurikulum)')
                    ->modalDescription('Verifikasi dan setujui dispensasi keterlambatan pengisian jurnal mengajar guru. Setelah Anda menyetujui, jurnal ini otomatis muncul di Laporan Monitoring Kepala Sekolah untuk di-ACC.')
                    ->modalSubmitActionLabel('Setujui Pengisian Terlambat')
                    ->modalIcon('heroicon-o-check-badge')
                    ->modalWidth('xl')
                    ->form([
                        Forms\Components\Placeholder::make('info_keterlambatan')
                            ->label('Detail Jurnal Terlambat')
                            ->content(function (TeachingJournal $record) {
                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-xs space-y-1.5 text-amber-900 dark:text-amber-200'>
                                        <div><strong>Guru Pengampu:</strong> {$record->teacher?->name}</div>
                                        <div><strong>Jadwal Mengajar:</strong> " . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') . " (Jam Ke-{$record->jam_ke})</div>
                                        <div><strong>Kelas & Mapel:</strong> {$record->classroom?->name} — {$record->subject?->name}</div>
                                        <div><strong>Materi:</strong> {$record->materi}</div>
                                        <div class='text-amber-700 font-bold mt-1'>⚠️ Keterlambatan: Melewati batas 23:59 pada hari mengajar.</div>
                                    </div>
                                ");
                            }),
                        Forms\Components\Textarea::make('waka_notes')
                            ->label('Catatan / Arahan Waka Kurikulum (Opsional)')
                            ->placeholder('Contoh: Dispensasi disetujui karena ada kendala jaringan / kegiatan sekolah. Harap ke depan tertib waktu.')
                            ->rows(3),
                    ])
                    ->action(function (TeachingJournal $record, array $data) {
                        $record->update([
                            'late_status' => TeachingJournal::LATE_STATUS_APPROVED_WAKA,
                            'waka_approved_by' => auth()->id(),
                            'waka_approved_at' => now(),
                            'waka_notes' => $data['waka_notes'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Terlambat Berhasil Disetujui')
                            ->body("Pengisian jurnal terlambat milik {$record->teacher?->name} telah disetujui dan kini resmi muncul di Laporan Monitoring Kepala Sekolah.")
                            ->success()
                            ->send();
                    }),

                // 2. ACC Kepala Sekolah
                Tables\Actions\Action::make('acc_kepsek')
                    ->label('ACC Kepala Sekolah')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TeachingJournal $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum']) && $record->isReadyForKepsek() && $record->status_acc_kepsek !== TeachingJournal::ACC_KEPSEK_APPROVED)
                    ->modalHeading('ACC & Pengesahan Jurnal Mengajar (Kepala Sekolah)')
                    ->modalDescription('Sahkan keterlaksanaan pembelajaran guru di kelas ini. Anda dapat menyertakan catatan evaluasi atau apresiasi tertulis.')
                    ->modalSubmitActionLabel('Sahkan & ACC Jurnal')
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalWidth('xl')
                    ->form([
                        Forms\Components\Placeholder::make('info_pembelajaran')
                            ->label('Detail Pembelajaran')
                            ->content(function (TeachingJournal $record) {
                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3.5 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800 text-xs space-y-1.5 text-emerald-900 dark:text-emerald-200'>
                                        <div><strong>Guru Pengampu:</strong> {$record->teacher?->name}</div>
                                        <div><strong>Hari, Tanggal:</strong> " . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') . " (Jam Ke-{$record->jam_ke})</div>
                                        <div><strong>Kelas & Mapel:</strong> {$record->classroom?->name} — {$record->subject?->name}</div>
                                        <div><strong>Materi Pokok:</strong> {$record->materi}</div>
                                        " . ($record->is_late ? "<div class='text-amber-700 font-bold'>⚠️ Terlambat Diisi (Disetujui Waka: " . ($record->wakaApprover?->name ?? 'Waka Kurikulum') . ")</div>" : "<div class='text-emerald-700 font-bold'>✅ Diisi Tepat Waktu</div>") . "
                                    </div>
                                ");
                            }),
                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Apresiasi / Evaluasi Kepala Sekolah')
                            ->placeholder('Contoh: Pembelajaran berjalan tertib sesuai kurikulum, lanjutkan pemantauan karakter murid.')
                            ->default(fn (TeachingJournal $record) => $record->catatan_kepala_sekolah ?: 'Pembelajaran telah terlaksana dengan baik.')
                            ->rows(3),
                    ])
                    ->action(function (TeachingJournal $record, array $data) {
                        $record->update([
                            'status_acc_kepsek' => TeachingJournal::ACC_KEPSEK_APPROVED,
                            'kepsek_approved_by' => auth()->id(),
                            'kepsek_approved_at' => now(),
                            'catatan_kepala_sekolah' => $data['catatan_kepala_sekolah'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Mengajar Telah Di-ACC')
                            ->body("Jurnal mengajar milik {$record->teacher?->name} telah berhasil disahkan.")
                            ->success()
                            ->send();
                    }),

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
