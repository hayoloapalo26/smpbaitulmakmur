<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeachingJournalResource\Pages;
use App\Models\TeachingJournal;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Models\Rppm;
use App\Models\TujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
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
                                $set('schedule_ids', []);
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('jam_ke_display', null);
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
                                $set('schedule_ids', []);
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('jam_ke_display', null);
                                $set('rppm_id', null);
                                $set('materi', null);
                                $set('tp_ids', []);
                                $set('student_attendances', []);
                                $set('catatan_karakter', []);
                            })
                            ->required(),

                        Forms\Components\Select::make('schedule_ids')
                            ->label(fn (Forms\Get $get) => 'Pilih Jadwal Mengajar (' . Schedule::getIndonesianDayName($get('tanggal') ?: Carbon::today()) . ') (Multiple Choice)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder(function (Forms\Get $get) {
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);
                                return "Pilih satu atau beberapa jam mengajar hari {$hari}...";
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
                                        ->where('teacher_id', $teacherId)
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
                                            if ($onLeave->isDinasLuar()) {
                                                $invalName = $onLeave->substituteTeacher?->name ?? 'Guru Inval';
                                                $statusText = " ✈️ [DINAS LUAR - Presensi diisi Inval: {$invalName}]" . $statusText;
                                            } else {
                                                $statusText = " 🚫 [Izin ACC: {$onLeave->waktu_izin_label}]";
                                            }
                                        }
                                    } else {
                                        $statusText = " 🔄 [TUGAS INVAL DARI {$s->teacher?->name}]" . $statusText;
                                    }

                                    $jamText = \App\Models\Schedule::getJamLabelWithTime($s->jam_ke);
                                    $options[$s->id] = "{$jamText} | Kelas {$s->classroom?->name} — {$s->subject?->name} (1 JP){$statusText}";
                                }

                                return $options;
                            })
                            ->disableOptionWhen(function (string $value, Forms\Get $get, ?TeachingJournal $record) use ($isAdminOrWaka) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $schedule = Schedule::find($value);
                                if (!$schedule) return false;

                                $isSubstituteForThis = ($schedule->teacher_id != $teacherId);

                                // 1. Kunci opsi jika guru reguler memiliki izin yang telah di-ACC pada jam ini (Kecuali Dinas Luar)
                                if (!$isSubstituteForThis) {
                                    $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $schedule->jam_ke);
                                    if ($leave && !$leave->isDinasLuar()) {
                                        return true;
                                    }
                                }

                                // 2. Kunci opsi jika guru ini sudah mengisi jurnal jadwal ini hari ini
                                if (TeachingJournal::where('schedule_id', $schedule->id)
                                    ->where('teacher_id', $teacherId)
                                    ->whereDate('tanggal', $tanggal)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->exists()) {
                                    return true;
                                }

                                // 3. Kunci opsi jika berbeda kelas atau mata pelajaran dengan jadwal yang telah dipilih
                                $selectedIds = (array) ($get('schedule_ids') ?? []);
                                if (!empty($selectedIds) && !in_array($value, $selectedIds)) {
                                    $firstSelected = Schedule::find(reset($selectedIds));
                                    if ($firstSelected && ($schedule->classroom_id !== $firstSelected->classroom_id || $schedule->subject_id !== $firstSelected->subject_id)) {
                                        return true;
                                    }
                                }

                                return false;
                            })
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $ids = is_array($value) ? $value : (!empty($value) ? [$value] : []);
                                        if (empty($ids)) {
                                            $fail("Silakan pilih minimal satu jadwal mengajar.");
                                            return;
                                        }

                                        $teacherId = $get('teacher_id') ?: Auth::id();
                                        $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                        $hari = Schedule::getIndonesianDayName($tanggal);

                                        $schedules = Schedule::whereIn('id', $ids)->get();
                                        if ($schedules->count() !== count($ids)) {
                                            $fail("Sebagian jadwal mengajar yang dipilih tidak ditemukan.");
                                            return;
                                        }

                                        $first = $schedules->first();
                                        foreach ($schedules as $s) {
                                            if ($s->hari !== $hari) {
                                                $fail("Jadwal jam ke-{$s->jam_ke} merupakan jadwal hari {$s->hari}, bukan jadwal hari {$hari}.");
                                                return;
                                            }

                                            if ($s->classroom_id !== $first->classroom_id || $s->subject_id !== $first->subject_id) {
                                                $fail("Semua jadwal yang dipilih bersamaan harus berada pada kelas dan mata pelajaran yang sama.");
                                                return;
                                            }

                                            if ($s->teacher_id == $teacherId && \App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $s->jam_ke)) {
                                                $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $s->jam_ke);
                                                if ($leave && !$leave->isDinasLuar()) {
                                                    $fail("Tidak dapat mengisi jurnal mengajar! Anda tercatat memiliki perizinan yang telah disetujui (ACC) pada jam ke-{$s->jam_ke} (" . ($leave->waktu_izin_label ?? \App\Models\Schedule::getJamLabel($s->jam_ke)) . ").");
                                                    return;
                                                }
                                            }
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
                                    if ($leaveToday->isDinasLuar()) {
                                        $invalName = $leaveToday->substituteTeacher?->name ?? 'Guru Inval';
                                        return "✈️ PERHATIAN DINAS LUAR: Anda tercatat bertugas dinas luar hari ini. Kehadiran Anda dihitung HADIR. Anda tetap wajib menginputkan materi jurnal mengajar, sedangkan presensi fisik siswa di kelas diisi oleh Guru Inval ({$invalName}).";
                                    }
                                    return "⛔ PERHATIAN: Terdapat perizinan guru yang telah di-ACC pada hari ini ({$leaveToday->waktu_izin_label} — Alasan: {$leaveToday->alasan}). Jadwal jam pelajaran yang bertepatan dengan izin otomatis terkunci.";
                                }

                                $selectedIds = (array) ($get('schedule_ids') ?? []);
                                $selectedCount = count(array_filter($selectedIds));
                                if ($selectedCount > 0) {
                                    $selectedSchedules = Schedule::whereIn('id', $selectedIds)->orderBy('jam_ke')->get();
                                    $labels = $selectedSchedules->map(fn ($s) => Schedule::getJamLabel($s->jam_ke))->implode(', ');
                                    return "✅ Terpilih: {$selectedCount} Jam Pelajaran ({$labels} • Total {$selectedCount} JP). Sistem akan otomatis menyimpan {$selectedCount} jurnal mengajar dan mencatat {$selectedCount} JP kehadiran mengajar guru.";
                                }

                                $count = Schedule::where('teacher_id', $teacherId)->where('hari', $hari)->count();
                                if ($count === 0) {
                                    return "⚠️ Anda tidak memiliki jadwal mengajar pada hari {$hari} berdasarkan Jadwal Pelajaran Kurikulum. Pengisian jurnal hanya dapat dilakukan saat Anda memiliki jadwal pada hari ini.";
                                }
                                return "📋 Terdapat {$count} jam mengajar terjadwal untuk Anda pada hari {$hari}. Anda dapat mencentang/memilih beberapa jam mengajar sekaligus (multiple choice) jika mengajar lebih dari 1 JP di kelas yang sama.";
                            })
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?TeachingJournal $record, Forms\Set $set) {
                                if ($record && $record->schedule_id) {
                                    $component->state([(int) $record->schedule_id]);
                                    $set('schedule_id', (int) $record->schedule_id);
                                    $set('jam_ke', $record->jam_ke);
                                    $set('jam_ke_display', Schedule::getJamLabelWithTime($record->jam_ke) . ' (1 JP)');
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $ids = array_values(array_filter((array) $state));
                                if (empty($ids)) {
                                    $set('schedule_id', null);
                                    $set('classroom_id', null);
                                    $set('subject_id', null);
                                    $set('jam_ke', null);
                                    $set('jam_ke_display', null);
                                    $set('rppm_id', null);
                                    $set('materi', null);
                                    $set('tp_ids', []);
                                    $set('student_attendances', []);
                                    $set('catatan_karakter', []);
                                    return;
                                }

                                $schedules = Schedule::whereIn('id', $ids)->orderBy('jam_ke')->get();
                                $firstSchedule = $schedules->first();
                                if (!$firstSchedule) return;

                                $set('schedule_id', $firstSchedule->id);
                                $set('classroom_id', $firstSchedule->classroom_id);
                                $set('subject_id', $firstSchedule->subject_id);
                                $set('jam_ke', $firstSchedule->jam_ke);

                                $labels = $schedules->map(fn ($s) => Schedule::getJamLabelWithTime($s->jam_ke))->implode(' + ');
                                $totalJp = $schedules->count();
                                $set('jam_ke_display', "{$labels} ({$totalJp} JP)");

                                // Auto pre-fill RPPM & TP jika BUKAN konteks Al-Qur'an dan guru memiliki dokumen RPPM untuk mapel ini
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $teacher = User::find($teacherId);
                                $subjectName = $firstSchedule->subject?->name;
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
                                        ->where(function ($q) use ($firstSchedule, $subjectName) {
                                            if ($firstSchedule->subject_id) {
                                                $q->whereHas('capaianPembelajaran', fn ($cp) => $cp->where('subject_id', $firstSchedule->subject_id));
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

                                    // Cek tautan Google Drive RPPM & Rujukan CP otomatis
                                    $matchingCp = CapaianPembelajaran::where('subject_id', $firstSchedule->subject_id)
                                        ->where(function ($q) {
                                            $q->whereJsonContains('fases', 'D')->orWhere('fase', 'like', '%D%')->orWhereNull('fases');
                                        })
                                        ->first();
                                    if ($matchingCp && empty($get('capaian_pembelajaran_id'))) {
                                        $set('capaian_pembelajaran_id', $matchingCp->id);
                                        if (empty($get('materi')) && !empty($matchingCp->elemen)) {
                                            $set('materi', $matchingCp->elemen);
                                        }
                                    }

                                    $latestRppmWithDrive = Rppm::where('guru_id', $teacherId)
                                        ->where('mata_pelajaran', $firstSchedule->subject?->name)
                                        ->whereNotNull('link_drive')
                                        ->latest()
                                        ->first();
                                    if ($latestRppmWithDrive && empty($get('link_drive_rppm'))) {
                                        $set('link_drive_rppm', $latestRppmWithDrive->link_drive);
                                        $set('rppm_id', $latestRppmWithDrive->id);
                                    } else {
                                        $latestJournalWithDrive = TeachingJournal::where('teacher_id', $teacherId)
                                            ->where('subject_id', $firstSchedule->subject_id)
                                            ->whereNotNull('link_drive_rppm')
                                            ->latest()
                                            ->first();
                                        if ($latestJournalWithDrive && empty($get('link_drive_rppm'))) {
                                            $set('link_drive_rppm', $latestJournalWithDrive->link_drive_rppm);
                                        }
                                    }
                                }

                                $tanggal = $get('tanggal') ?? Carbon::today()->format('Y-m-d');
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $leaveForSchedule = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $firstSchedule->jam_ke);

                                // Jika guru ini sedang dinas luar, cek apakah Guru Inval sudah mengisi presensi siswa
                                $syncedFromInval = false;
                                if ($leaveForSchedule?->isDinasLuar()) {
                                    $invalJournal = TeachingJournal::where('schedule_id', $firstSchedule->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->where('teacher_id', '!=', $teacherId)
                                        ->latest()
                                        ->first();

                                    if ($invalJournal) {
                                        $invalAttendances = \App\Models\SubjectAttendance::where('teaching_journal_id', $invalJournal->id)
                                            ->pluck('status', 'student_id')
                                            ->toArray();
                                        if (!empty($invalAttendances)) {
                                            $set('student_attendances', $invalAttendances);
                                            $syncedFromInval = true;
                                        }
                                    }
                                }

                                $currentAttendance = (array) ($get('student_attendances') ?? []);
                                if (empty($currentAttendance) && !$syncedFromInval) {
                                    $students = \App\Models\Student::where('classroom_id', $firstSchedule->classroom_id)->orderBy('name')->get();
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
                                }
                            })
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('schedule_id')
                            ->dehydrated()
                            ->afterStateHydrated(function ($component, $state, Forms\Set $set, Forms\Get $get) {
                                if ($state && empty($get('schedule_ids'))) {
                                    $set('schedule_ids', [(int) $state]);
                                }
                            }),

                        Forms\Components\Hidden::make('jam_ke')
                            ->dehydrated(),

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

                        Forms\Components\TextInput::make('jam_ke_display')
                            ->label('Alokasi Jam Pelajaran (JP)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Otomatis dari jadwal terpilih...'),
                    ])->columns(3),

                Forms\Components\Section::make('🎪 Status Pelaksanaan Jam Pelajaran & Kegiatan Siswa')
                    ->description('Jika jam mengajar ini bertepatan dengan kegiatan siswa / sekolah (seperti Apel Pelantikan OSIS, Upacara, PHBI, Class Meeting, dll), aktifkan pilihan di bawah ini. Guru TETAP DIANGGAP HADIR MASUK MENGAJAR dan alokasi JP tetap dihitung.')
                    ->schema([
                        Forms\Components\Toggle::make('is_kegiatan_lain')
                            ->label('Ada Kegiatan Siswa / Kegiatan Sekolah Lain pada Jam Mengajar Ini')
                            ->helperText('Aktifkan jika jam pelajaran ini bertepatan dengan kegiatan bersama siswa (misal: Apel Pelantikan OSIS, Upacara, PHBI, Class Meeting, dll). Status guru tetap HADIR MASUK MENGAJAR.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $defaultKegiatan = $get('nama_kegiatan_lain') ?: 'Apel Pelantikan OSIS';
                                    $set('nama_kegiatan_lain', $defaultKegiatan);
                                    $set('materi', "[Kegiatan Siswa] {$defaultKegiatan}");
                                    $set('rencana_kegiatan_guru', "Mendampingi & mengawasi siswa dalam pelaksanaan {$defaultKegiatan}");
                                    $set('kegiatan_siswa', "Siswa mengikuti dan melaksanakan kegiatan {$defaultKegiatan} dengan tertib dan khidmat");
                                    $set('rppm_id', null);
                                    $set('tp_ids', []);

                                    // Seluruh siswa otomatis hadir
                                    $classroomId = $get('classroom_id');
                                    if ($classroomId) {
                                        $students = \App\Models\Student::where('classroom_id', $classroomId)->get();
                                        $att = [];
                                        foreach ($students as $st) {
                                            $att[$st->id] = 'hadir';
                                        }
                                        $set('student_attendances', $att);
                                    }
                                } else {
                                    $set('nama_kegiatan_lain', null);
                                    if (str_starts_with($get('materi') ?? '', '[Kegiatan Siswa]')) {
                                        $set('materi', null);
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('kegiatan_info_banner')
                            ->label('')
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain'))
                            ->content(function (Forms\Get $get) {
                                $kegiatan = $get('nama_kegiatan_lain') ?: 'Kegiatan Siswa';
                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3.5 rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200'>
                                        <div class='flex items-center gap-2 font-bold text-sm'>
                                            <span class='text-lg'>🎖️</span>
                                            <span>Guru Tetap Dianggap Masuk Mengajar (Presensi Hadir Terpenuhi)</span>
                                        </div>
                                        <p class='text-xs text-emerald-700 dark:text-emerald-300 mt-1 leading-relaxed'>
                                            Jam pelajaran ini bertepatan dengan <strong>{$kegiatan}</strong>. Kehadiran guru otomatis tercatat <strong>HADIR</strong> (100% masuk mengajar). Rincian materi, kegiatan guru, dan kegiatan siswa di bawah otomatis disesuaikan.
                                        </p>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('preset_kegiatan')
                            ->label('Pilihan Cepat Kegiatan Siswa')
                            ->placeholder('-- Pilih Kegiatan Populer Sekolah --')
                            ->options([
                                'Apel Pelantikan OSIS'                      => '🎖️ Apel Pelantikan OSIS',
                                'Upacara Bendera / Peringatan Hari Besar'    => '🇮🇩 Upacara Bendera / Hari Besar',
                                'Peringatan Hari Besar Islam (PHBI)'        => '🕌 Peringatan Hari Besar Islam (PHBI)',
                                'Class Meeting / Porseni Siswa'             => '🏆 Class Meeting / Porseni Siswa',
                                'Sosialisasi / Seminar / Workshop Siswa'    => '📢 Sosialisasi / Workshop Siswa',
                                'Pramuka / Outbound / Kemah Siswa'          => '🏕️ Pramuka / Outbound Siswa',
                                'Kegiatan Penilaian / Asesmen Bersama'      => '📝 Asesmen / Ujian Bersama',
                            ])
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain'))
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $set('nama_kegiatan_lain', $state);
                                    $set('materi', "[Kegiatan Siswa] {$state}");
                                    $set('rencana_kegiatan_guru', "Mendampingi & mengawasi siswa dalam pelaksanaan {$state}");
                                    $set('kegiatan_siswa', "Siswa mengikuti dan melaksanakan kegiatan {$state} dengan tertib dan khidmat");
                                }
                            }),

                        Forms\Components\TextInput::make('nama_kegiatan_lain')
                            ->label('Keterangan / Nama Kegiatan Siswa')
                            ->placeholder('Contoh: Apel Pelantikan OSIS / Upacara Bendera')
                            ->required(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain'))
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain'))
                            ->helperText('Tuliskan atau sesuaikan nama kegiatan siswa yang berlangsung pada jam pelajaran ini.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($get('is_kegiatan_lain') && filled($state)) {
                                    $set('materi', "[Kegiatan Siswa] {$state}");
                                    if (empty($get('rencana_kegiatan_guru')) || str_starts_with($get('rencana_kegiatan_guru'), 'Mendampingi & mengawasi')) {
                                        $set('rencana_kegiatan_guru', "Mendampingi & mengawasi siswa dalam pelaksanaan {$state}");
                                    }
                                    if (empty($get('kegiatan_siswa')) || str_starts_with($get('kegiatan_siswa'), 'Siswa mengikuti')) {
                                        $set('kegiatan_siswa', "Siswa mengikuti dan melaksanakan kegiatan {$state} dengan tertib dan khidmat");
                                    }
                                }
                            }),
                    ])->columns(2),

                Forms\Components\Section::make(function (Forms\Get $get) {
                    if ((bool) $get('is_kegiatan_lain')) {
                        return '🎪 Materi / Keterangan Pelaksanaan Kegiatan Siswa';
                    }
                    return static::isQuranContext($get)
                        ? '📖 Lingkup Materi Pembelajaran Al-Qur\'an'
                        : '🎯 Perangkat Pembelajaran: Fase, CP, TP & Link Drive RPPM';
                })
                    ->description(function (Forms\Get $get) {
                        if ((bool) $get('is_kegiatan_lain')) {
                            return 'Jam mengajar bertepatan dengan kegiatan siswa/sekolah. Materi pokok otomatis terhubung dengan kegiatan siswa yang dilaksanakan.';
                        }
                        return static::isQuranContext($get)
                            ? 'Inputkan materi pokok atau capaian pembelajaran Al-Qur\'an (Surat/Ayat/Jilid/Halaman) yang diajarkan pada jam ini tanpa perlu input CP/TP/ATP.'
                            : 'Pilih Fase untuk memfilter Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) yang relevan, serta sertakan link dokumen RPPM Google Drive.';
                    })
                    ->schema([
                        Forms\Components\Select::make('fase')
                            ->label('Fase Kurikulum')
                            ->options(CapaianPembelajaran::FASE_OPTIONS)
                            ->default('D')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('capaian_pembelajaran_id', null);
                                $set('tp_ids', []);
                            })
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get) || (bool) $get('is_kegiatan_lain'))
                            ->helperText('Pilih Fase untuk menyaring Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP) yang sesuai.'),

                        Forms\Components\Select::make('capaian_pembelajaran_id')
                            ->label('Rujukan Capaian Pembelajaran (CP)')
                            ->placeholder('Semua CP di Fase ini / Pilih spesifik CP...')
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $fase = $get('fase') ?: 'D';

                                $query = CapaianPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                $query->where(function ($q) use ($fase) {
                                    $q->whereJsonContains('fases', $fase)
                                      ->orWhere('fase', 'like', "%{$fase}%")
                                      ->orWhereNull('fases');
                                });

                                return $query->get()->mapWithKeys(function ($cp) {
                                    $kode = $cp->kode_cp ? "[CP {$cp->kode_cp}] " : '';
                                    return [$cp->id => "{$kode}[{$cp->elemen}] " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $cp = CapaianPembelajaran::find($state);
                                    if ($cp && empty($get('materi'))) {
                                        $set('materi', $cp->elemen);
                                    }
                                }
                            })
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get) || (bool) $get('is_kegiatan_lain'))
                            ->helperText('Filter daftar TP berdasarkan CP yang dipilih (opsional).'),

                        Forms\Components\Select::make('rppm_id')
                            ->label('Rujukan Dokumen RPPM (Opsional)')
                            ->placeholder('Pilih RPPM yang pernah dibuat (Link Drive akan terisi otomatis)...')
                            ->options(function (Forms\Get $get) {
                                $user = Auth::user();
                                $subjectId = $get('subject_id');
                                $query = Rppm::query();
                                if ($user && !$user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum'])) {
                                    $query->where('guru_id', $user->id);
                                }
                                if ($subjectId) {
                                    $subjName = Subject::where('id', $subjectId)->value('name');
                                    if ($subjName) {
                                        $query->where('mata_pelajaran', $subjName);
                                    }
                                }
                                return $query->latest()->get()->mapWithKeys(function ($rppm) {
                                    $drive = $rppm->link_drive ? ' [Ada Link Drive]' : '';
                                    return [$rppm->id => "[{$rppm->kelas_fase}] {$rppm->topik}{$drive}"];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $rppm = Rppm::find($state);
                                    if ($rppm) {
                                        if ($rppm->link_drive) {
                                            $set('link_drive_rppm', $rppm->link_drive);
                                        }
                                        if ($rppm->capaian_pembelajaran_id && empty($get('capaian_pembelajaran_id'))) {
                                            $set('capaian_pembelajaran_id', $rppm->capaian_pembelajaran_id);
                                        }
                                        if (!empty($rppm->selected_tp_ids) && empty($get('tp_ids'))) {
                                            $set('tp_ids', $rppm->selected_tp_ids);
                                        }
                                        if (!empty($rppm->topik) && empty($get('materi'))) {
                                            $set('materi', $rppm->topik);
                                        }
                                    }
                                }
                            })
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get) || (bool) $get('is_kegiatan_lain'))
                            ->helperText('Pilih RPPM yang sudah dibuat agar tautan Google Drive dan materi terisi otomatis, atau Anda dapat langsung mengisi tautan di bawah.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('link_drive_rppm')
                            ->label('Link Google Drive RPPM / Modul Ajar')
                            ->placeholder('Contoh: https://drive.google.com/file/d/... atau https://docs.google.com/document/d/...')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->hidden(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain'))
                            ->helperText('Masukkan tautan Google Drive dokumen RPPM / Modul Ajar / Perangkat Pembelajaran.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('materi')
                            ->label(function (Forms\Get $get) {
                                if ((bool) $get('is_kegiatan_lain')) {
                                    return 'Topik / Nama Kegiatan Siswa';
                                }
                                return static::isQuranContext($get) ? 'Materi Pokok / Capaian Surat & Ayat' : 'Topik / Materi Pokok Pembelajaran';
                            })
                            ->placeholder(function (Forms\Get $get) {
                                if ((bool) $get('is_kegiatan_lain')) {
                                    return 'Contoh: [Kegiatan Siswa] Apel Pelantikan OSIS';
                                }
                                return static::isQuranContext($get) ? 'Contoh: QS. Al-Baqarah ayat 1-15 / Yanbu\'a Jilid 3 Hal. 10-15 / Makharijul Huruf' : 'Contoh: Operasi Aljabar / Teks Deskripsi / Mengenal Perangkat Komputer';
                            })
                            ->required()
                            ->helperText(function (Forms\Get $get) {
                                if ((bool) $get('is_kegiatan_lain')) {
                                    return 'Nama atau topik kegiatan yang dilaksanakan pada jam pelajaran ini.';
                                }
                                return static::isQuranContext($get) ? 'Lingkup materi atau capaian Al-Qur\'an yang disampaikan pada jam mengajar ini.' : 'Lingkup materi pokok yang disampaikan pada jam mengajar ini.';
                            }),

                        Forms\Components\Select::make('tp_ids')
                            ->label('Tujuan Pembelajaran (TP) yang Diajarkan')
                            ->placeholder('Pilih Tujuan Pembelajaran (TP)...')
                            ->hidden(fn (Forms\Get $get) => static::isQuranContext($get) || (bool) $get('is_kegiatan_lain'))
                            ->dehydrated(fn (Forms\Get $get) => !static::isQuranContext($get) && !(bool) $get('is_kegiatan_lain'))
                            ->multiple()
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (!$value) return null;
                                $tp = TujuanPembelajaran::find($value);
                                if (!$tp) return null;
                                $faseStr = !empty($tp->fases) ? '[Fase ' . implode('&', (array) $tp->fases) . '] ' : '';
                                $kode = $tp->kode_tp ? "[{$tp->kode_tp}] " : '';
                                $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                return "{$faseStr}{$kode}{$prefix}" . trim((string) ($tp->deskripsi_tp ?: "Tujuan Pembelajaran #{$tp->id}"));
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                if (empty($values)) return [];
                                return TujuanPembelajaran::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(function ($tp) {
                                        $faseStr = !empty($tp->fases) ? '[Fase ' . implode('&', (array) $tp->fases) . '] ' : '';
                                        $kode = $tp->kode_tp ? "[{$tp->kode_tp}] " : '';
                                        $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                        $desc = trim((string) ($tp->deskripsi_tp ?: "Tujuan Pembelajaran #{$tp->id}"));
                                        return [(string) $tp->id => "{$faseStr}{$kode}{$prefix}{$desc}"];
                                    })
                                    ->toArray();
                            })
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $classroomId = $get('classroom_id');
                                $fase = $get('fase') ?: 'D';
                                $cpId = $get('capaian_pembelajaran_id');
                                $currentTpIds = (array) ($get('tp_ids') ?? []);

                                $tingkat = null;
                                if ($classroomId) {
                                    $rawTingkat = \App\Models\Classroom::where('id', $classroomId)->value('tingkat');
                                    if ($rawTingkat && preg_match('/(7|8|9)/', (string) $rawTingkat, $m)) {
                                        $tingkat = $m[1];
                                    }
                                }

                                $query = TujuanPembelajaran::query();

                                // Saring berdasarkan Fase Kurikulum yang dipilih guru
                                $query->where(function ($q) use ($fase) {
                                    $q->whereJsonContains('fases', $fase)
                                      ->orWhereHas('capaianPembelajaran', function ($cpQ) use ($fase) {
                                          $cpQ->whereJsonContains('fases', $fase)
                                              ->orWhere('fase', 'like', "%{$fase}%");
                                      })
                                      ->orWhereNull('fases');
                                });

                                if ($cpId) {
                                    $query->where('capaian_pembelajaran_id', $cpId);
                                }

                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }

                                // Rekomendasi kelas sasaran
                                if ($classroomId || $tingkat) {
                                    $query->where(function ($q) use ($classroomId, $tingkat, $currentTpIds) {
                                        if (!empty($currentTpIds)) {
                                            $q->whereIn('id', $currentTpIds)->orWhere(function ($sub) use ($classroomId, $tingkat) {
                                                if ($classroomId) {
                                                    $sub->whereJsonContains('rekomendasi_kelas', (int) $classroomId);
                                                }
                                                if ($tingkat) {
                                                    $sub->orWhereJsonContains('rekomendasi_tingkat', (string) $tingkat)
                                                        ->orWhere('kelas_tingkat', $tingkat)
                                                        ->orWhere('kelas_tingkat', 'like', "%{$tingkat}%");
                                                }
                                                $sub->orWhere(function ($w) {
                                                    $w->whereNull('rekomendasi_kelas')
                                                      ->whereNull('rekomendasi_tingkat')
                                                      ->whereNull('kelas_tingkat');
                                                });
                                            });
                                        } else {
                                            if ($classroomId) {
                                                $q->whereJsonContains('rekomendasi_kelas', (int) $classroomId);
                                            }
                                            if ($tingkat) {
                                                $q->orWhereJsonContains('rekomendasi_tingkat', (string) $tingkat)
                                                  ->orWhere('kelas_tingkat', $tingkat)
                                                  ->orWhere('kelas_tingkat', 'like', "%{$tingkat}%");
                                            }
                                            $q->orWhere(function ($w) {
                                                $w->whereNull('rekomendasi_kelas')
                                                  ->whereNull('rekomendasi_tingkat')
                                                  ->whereNull('kelas_tingkat');
                                            });
                                        }
                                    });
                                }

                                return $query->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                })->mapWithKeys(function ($tp) {
                                    $faseStr = !empty($tp->fases) ? '[Fase ' . implode('&', (array) $tp->fases) . '] ' : '';
                                    $kode = $tp->kode_tp ? "[{$tp->kode_tp}] " : '';
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    $deskripsi = trim((string) ($tp->deskripsi_tp ?: "Tujuan Pembelajaran #{$tp->id}"));
                                    return [(string) $tp->id => "{$faseStr}{$kode}{$prefix}{$deskripsi}"];
                                })->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, Forms\Get $get): array {
                                $subjectId = $get('subject_id');
                                $fase = $get('fase') ?: 'D';
                                $query = TujuanPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                $query->where(function ($q) use ($fase) {
                                    $q->whereJsonContains('fases', $fase)
                                      ->orWhereHas('capaianPembelajaran', function ($cpQ) use ($fase) {
                                          $cpQ->whereJsonContains('fases', $fase)
                                              ->orWhere('fase', 'like', "%{$fase}%");
                                      })
                                      ->orWhereNull('fases');
                                });
                                return $query->where(function ($q) use ($search) {
                                    $q->where('deskripsi_tp', 'like', "%{$search}%")
                                      ->orWhere('materi', 'like', "%{$search}%")
                                      ->orWhere('kode_tp', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($tp) {
                                    $faseStr = !empty($tp->fases) ? '[Fase ' . implode('&', (array) $tp->fases) . '] ' : '';
                                    $kode = $tp->kode_tp ? "[{$tp->kode_tp}] " : '';
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    $deskripsi = trim((string) ($tp->deskripsi_tp ?: "Tujuan Pembelajaran #{$tp->id}"));
                                    return [(string) $tp->id => "{$faseStr}{$kode}{$prefix}{$deskripsi}"];
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
                            ->helperText('💡 Pilih Tujuan Pembelajaran (TP) yang sesuai dengan Fase yang dipilih. Anda dapat memilih lebih dari satu TP.')
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
                            ->label('Kegiatan Guru')
                            ->placeholder(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain') ? 'Contoh: Mendampingi & mengawasi siswa dalam pelaksanaan Apel Pelantikan OSIS' : 'Contoh: Guru menyampaikan Ceramah / Diskusi Terarah')
                            ->required(),
                        Forms\Components\TextInput::make('kegiatan_siswa')
                            ->label('Kegiatan Siswa')
                            ->placeholder(fn (Forms\Get $get) => (bool) $get('is_kegiatan_lain') ? 'Contoh: Siswa mengikuti dan melaksanakan Apel Pelantikan OSIS dengan tertib' : 'Contoh: Siswa Melakukan Presentasi / Diskusi Kelompok')
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

                        $teacherId = $get('teacher_id') ?: Auth::id();
                        $tanggal = $get('tanggal') ?? now()->toDateString();
                        $scheduleId = $get('schedule_id') ?? $record?->schedule_id;
                        $leave = null;
                        $invalTeacherName = null;
                        $invalFilled = false;
                        if ($scheduleId) {
                            $sched = Schedule::find($scheduleId);
                            if ($sched) {
                                $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $sched->jam_ke);
                                if ($leave?->isDinasLuar()) {
                                    $invalTeacherName = $leave->substituteTeacher?->name ?? 'Guru Inval';
                                    $invalJournal = TeachingJournal::where('schedule_id', $sched->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->where('teacher_id', '!=', $teacherId)
                                        ->first();
                                    $invalFilled = (bool) $invalJournal;
                                }
                            }
                        }

                        return [
                            'students' => $students,
                            'classroomId' => $classroomId,
                            'charStatePath' => str_replace('student_attendances', 'catatan_karakter', $component->getStatePath()),
                            'isDinasLuar' => (bool) ($leave?->isDinasLuar()),
                            'invalTeacherName' => $invalTeacherName,
                            'invalFilled' => $invalFilled,
                            'isKegiatanLain' => (bool) $get('is_kegiatan_lain'),
                            'namaKegiatanLain' => $get('nama_kegiatan_lain'),
                        ];
                    })
                    ->dehydrated()
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
                                    'late_penalty_points' => 0,
                                    'keterangan' => 'Tercatat dari Jurnal Mengajar (' . ($record->subject->name ?? 'Mapel') . ')',
                                ]);
                            } elseif ($morning->metode_absen === 'jurnal_mengajar') {
                                $morning->update([
                                    'status' => $status,
                                    'is_late' => false,
                                    'late_penalty_points' => 0,
                                ]);
                            }
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\Section::make('Umpan Balik & Catatan Kepala Sekolah')
                    ->description('Evaluasi, catatan pembinaan, atau apresiasi dari Kepala Sekolah')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_acc_kepsek')
                                    ->label('Status ACC / Verifikasi KS')
                                    ->options([
                                        TeachingJournal::ACC_KEPSEK_PENDING => 'Menunggu ACC',
                                        TeachingJournal::ACC_KEPSEK_APPROVED => 'Disahkan & Di-ACC',
                                        TeachingJournal::ACC_KEPSEK_NEED_REVISION => 'Perlu Revisi',
                                    ])
                                    ->default(TeachingJournal::ACC_KEPSEK_PENDING)
                                    ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])),

                                Forms\Components\Placeholder::make('kepsek_info')
                                    ->label('Diverifikasi Oleh')
                                    ->content(fn (?TeachingJournal $record) => $record?->kepsekApprover 
                                        ? "{$record->kepsekApprover->name} (" . ($record->kepsek_approved_at ? Carbon::parse($record->kepsek_approved_at)->translatedFormat('d M Y H:i') : '-') . ")"
                                        : 'Belum diverifikasi'
                                    ),
                            ]),

                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Evaluasi / Umpan Balik')
                            ->placeholder('Belum ada catatan umpan balik dari Kepala Sekolah...')
                            ->rows(3)
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])),
                    ])
                    ->visible(fn (?TeachingJournal $record) => $record !== null)
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
                        if ($record->is_kegiatan_lain) {
                            return "🎪 Kegiatan: " . ($record->nama_kegiatan_lain ?: 'Kegiatan Siswa');
                        }
                        $count = is_array($record->tp_ids) ? count($record->tp_ids) : 0;
                        $parts = [];
                        if ($count > 0) {
                            $parts[] = "🎯 {$count} TP";
                        }
                        if ($record->link_drive_rppm) {
                            $parts[] = "📁 Link RPPM";
                        } elseif ($record->rppm) {
                            $parts[] = "📄 Dari RPPM Guru";
                        }
                        return !empty($parts) ? implode(' • ', $parts) : null;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('link_drive_rppm')
                    ->label('Link RPPM')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? 'Google Drive' : '-')
                    ->url(fn (TeachingJournal $record) => $record->link_drive_rppm, true)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('presensi_status')
                    ->label('Presensi Guru')
                    ->state(fn (TeachingJournal $record) => $record->is_kegiatan_lain ? 'Hadir (Kegiatan Siswa)' : 'Hadir (Dari Jurnal)')
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
                            $approver = $record->kepsekApprover ? "Oleh: {$record->kepsekApprover->name}" : 'Disahkan KS';
                            return $record->catatan_kepala_sekolah ? "{$approver} • \"{$record->catatan_kepala_sekolah}\"" : $approver;
                        }
                        if ($record->status_acc_kepsek === TeachingJournal::ACC_KEPSEK_NEED_REVISION) {
                            return $record->catatan_kepala_sekolah ? "Catatan KS: \"{$record->catatan_kepala_sekolah}\"" : 'Memerlukan revisi dari guru';
                        }
                        if ($record->is_late && $record->late_status === TeachingJournal::LATE_STATUS_PENDING_WAKA) {
                            return 'Menunggu Persetujuan Waka';
                        }
                        return null;
                    })
                    ->tooltip(fn (TeachingJournal $record) => $record->catatan_kepala_sekolah ? "Catatan KS: {$record->catatan_kepala_sekolah}" : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('catatan_kepala_sekolah')
                    ->label('Catatan KS')
                    ->placeholder('-')
                    ->limit(40)
                    ->tooltip(fn (TeachingJournal $record) => $record->catatan_kepala_sekolah)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
                                        <div><strong>Jadwal Mengajar:</strong> " . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') . " (" . \App\Models\Schedule::getJamLabelWithTime($record->jam_ke) . ")</div>
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
                    ->modalSubmitActionLabel('Simpan & Sahkan')
                    ->modalIcon('heroicon-o-check-circle')
                    ->modalWidth('xl')
                    ->form([
                        Forms\Components\Placeholder::make('info_pembelajaran')
                            ->label('Detail Pembelajaran')
                            ->content(function (TeachingJournal $record) {
                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3.5 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800 text-xs space-y-1.5 text-emerald-900 dark:text-emerald-200'>
                                        <div><strong>Guru Pengampu:</strong> {$record->teacher?->name}</div>
                                        <div><strong>Hari, Tanggal:</strong> " . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') . " (" . \App\Models\Schedule::getJamLabelWithTime($record->jam_ke) . ")</div>
                                        <div><strong>Kelas & Mapel:</strong> {$record->classroom?->name} — {$record->subject?->name}</div>
                                        <div><strong>Materi Pokok:</strong> {$record->materi}</div>
                                        " . ($record->is_late ? "<div class='text-amber-700 font-bold'>⚠️ Terlambat Diisi (Disetujui Waka: " . ($record->wakaApprover?->name ?? 'Waka Kurikulum') . ")</div>" : "<div class='text-emerald-700 font-bold'>✅ Diisi Tepat Waktu</div>") . "
                                    </div>
                                ");
                            }),
                        Forms\Components\Radio::make('status_acc_kepsek')
                            ->label('Status Pengesahan')
                            ->options([
                                TeachingJournal::ACC_KEPSEK_APPROVED => 'ACC / Disetujui (Pembelajaran sah)',
                                TeachingJournal::ACC_KEPSEK_NEED_REVISION => 'Perlu Revisi (Guru perlu melengkapi/memperbaiki)',
                            ])
                            ->default(TeachingJournal::ACC_KEPSEK_APPROVED)
                            ->required(),
                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Apresiasi / Evaluasi Kepala Sekolah')
                            ->placeholder('Contoh: Pembelajaran berjalan tertib sesuai kurikulum, lanjutkan pemantauan karakter murid.')
                            ->default(fn (TeachingJournal $record) => $record->catatan_kepala_sekolah ?: 'Pembelajaran telah terlaksana dengan baik.')
                            ->rows(3),
                    ])
                    ->action(function (TeachingJournal $record, array $data) {
                        $status = $data['status_acc_kepsek'] ?? TeachingJournal::ACC_KEPSEK_APPROVED;
                        $catatan = trim($data['catatan_kepala_sekolah'] ?? '');

                        $record->update([
                            'status_acc_kepsek' => $status,
                            'kepsek_approved_by' => auth()->id(),
                            'kepsek_approved_at' => now(),
                            'catatan_kepala_sekolah' => !empty($catatan) ? $catatan : ($status === TeachingJournal::ACC_KEPSEK_APPROVED ? 'Pembelajaran telah terlaksana dengan baik.' : null),
                        ]);

                        if ($record->teacher) {
                            $statusLabel = $status === TeachingJournal::ACC_KEPSEK_APPROVED ? 'Disahkan & Di-ACC' : 'Perlu Revisi';
                            \Filament\Notifications\Notification::make()
                                ->title("Umpan Balik Jurnal Mengajar ({$statusLabel})")
                                ->body("Kepala Sekolah (" . auth()->user()->name . ") telah memberikan umpan balik pada jurnal Anda (" . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('d M Y') . "): \"" . ($catatan ?: 'Telah ditinjau.') . "\"")
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->warning($status === TeachingJournal::ACC_KEPSEK_NEED_REVISION)
                                ->success($status === TeachingJournal::ACC_KEPSEK_APPROVED)
                                ->sendToDatabase($record->teacher);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Umpan Balik & Status Berhasil Disimpan')
                            ->body("Jurnal mengajar milik {$record->teacher?->name} telah diperbarui status dan catatannya.")
                            ->success()
                            ->send();
                    }),

                // 3. Ubah Catatan / Umpan Balik KS (untuk yang sudah di-ACC)
                Tables\Actions\Action::make('umpan_balik_kepsek')
                    ->label('Umpan Balik KS')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('info')
                    ->visible(fn (TeachingJournal $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum']) && $record->isReadyForKepsek() && $record->status_acc_kepsek === TeachingJournal::ACC_KEPSEK_APPROVED)
                    ->modalHeading('Ubah Catatan & Umpan Balik Kepala Sekolah')
                    ->modalDescription('Perbarui catatan evaluasi, apresiasi, atau status pengesahan untuk jurnal mengajar ini.')
                    ->modalSubmitActionLabel('Perbarui Catatan')
                    ->modalIcon('heroicon-o-chat-bubble-bottom-center-text')
                    ->modalWidth('xl')
                    ->form([
                        Forms\Components\Radio::make('status_acc_kepsek')
                            ->label('Status Pengesahan')
                            ->options([
                                TeachingJournal::ACC_KEPSEK_APPROVED => 'ACC / Disetujui (Pembelajaran sah)',
                                TeachingJournal::ACC_KEPSEK_NEED_REVISION => 'Perlu Revisi (Guru perlu melengkapi/memperbaiki)',
                            ])
                            ->default(fn (TeachingJournal $record) => $record->status_acc_kepsek ?: TeachingJournal::ACC_KEPSEK_APPROVED)
                            ->required(),
                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Apresiasi / Evaluasi Kepala Sekolah')
                            ->default(fn (TeachingJournal $record) => $record->catatan_kepala_sekolah)
                            ->placeholder('Tuliskan catatan evaluasi atau masukan pembelajaran...')
                            ->rows(3),
                    ])
                    ->action(function (TeachingJournal $record, array $data) {
                        $status = $data['status_acc_kepsek'] ?? TeachingJournal::ACC_KEPSEK_APPROVED;
                        $catatan = trim($data['catatan_kepala_sekolah'] ?? '');

                        $record->update([
                            'status_acc_kepsek' => $status,
                            'kepsek_approved_by' => auth()->id(),
                            'kepsek_approved_at' => now(),
                            'catatan_kepala_sekolah' => !empty($catatan) ? $catatan : ($status === TeachingJournal::ACC_KEPSEK_APPROVED ? 'Pembelajaran telah terlaksana dengan baik.' : null),
                        ]);

                        if ($record->teacher) {
                            $statusLabel = $status === TeachingJournal::ACC_KEPSEK_APPROVED ? 'Disahkan & Di-ACC' : 'Perlu Revisi';
                            \Filament\Notifications\Notification::make()
                                ->title("Catatan Jurnal Diperbarui ({$statusLabel})")
                                ->body("Kepala Sekolah (" . auth()->user()->name . ") memperbarui catatan evaluasi pada jurnal Anda (" . \Carbon\Carbon::parse($record->tanggal)->translatedFormat('d M Y') . "): \"" . ($catatan ?: 'Telah ditinjau.') . "\"")
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->warning($status === TeachingJournal::ACC_KEPSEK_NEED_REVISION)
                                ->success($status === TeachingJournal::ACC_KEPSEK_APPROVED)
                                ->sendToDatabase($record->teacher);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Catatan Kepala Sekolah Diperbarui')
                            ->body("Umpan balik untuk {$record->teacher?->name} berhasil diperbarui.")
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
                        $scheduleIds = (array) ($data['schedule_ids'] ?? ($data['schedule_id'] ?? [$record->schedule_id]));
                        $firstId = reset($scheduleIds);
                        $schedule = $firstId ? Schedule::find($firstId) : null;
                        $jamKe = $schedule ? $schedule->jam_ke : ($data['jam_ke'] ?? $record->jam_ke);

                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $jamKe)) {
                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $jamKe);
                            if ($leave && !$leave->isDinasLuar()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Perubahan Jurnal Ditolak')
                                    ->body('Jurnal ini tidak dapat diubah karena guru bersangkutan memiliki perizinan yang telah disetujui (ACC) pada waktu tersebut (' . ($leave?->waktu_izin_label ?? "Jam Ke-{$jamKe}") . ').')
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
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
