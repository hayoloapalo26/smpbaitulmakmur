<?php

namespace App\Livewire\Portal;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\DailyAttendance;
use App\Models\PrayerAttendance;
use App\Models\SubjectAttendance;
use App\Models\CharacterScoreLog;
use App\Models\StudentViolationLog;
use App\Models\Grade;
use App\Models\QuranAchievement;
use App\Models\ParentDailyChecklist;
use Carbon\Carbon;

class ParentDashboard extends Component
{
    public $students;
    public $selectedStudentId;
    public $activeStudent;
    public $selectedDate;
    public $activeTab = 'semua';

    // Checklists Amaliyah Rumah
    public $morning_sholat_subuh = false;
    public $morning_merapikan_tempat_tidur = false;
    public $morning_meminta_doa = false;
    public $evening_sholat_maghrib = false;
    public $evening_sholat_isya = false;
    public $evening_murajaah = false;
    public $evening_mengaji = false;
    public $evening_membantu_ortu = false;
    public $evening_persiapan_sekolah = false;

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        $this->selectedDate = Carbon::today()->toDateString();

        // Pastikan akun memiliki record guardian
        if (!$user->guardian_id) {
            $user->ensureGuardianRecord();
        }

        // Ambil seluruh anak / siswa yang terhubung dengan orang tua ini (baik via user_id maupun guardian_id)
        $this->students = Student::withoutGlobalScopes()
            ->whereIn('id', function ($q) use ($user) {
                $q->select('student_id')
                  ->from('student_guardians')
                  ->where('user_id', $user->id)
                  ->when($user->guardian_id, fn ($sq) => $sq->orWhere('guardian_id', $user->guardian_id));
            })
            ->with(['classroom'])
            ->get();

        if ($this->students->isNotEmpty()) {
            $this->selectedStudentId = $this->students->first()->id;
            $this->loadStudentData();
        } else {
            $this->selectedStudentId = null;
            $this->activeStudent = null;
        }
    }

    public function setDateToday()
    {
        $this->selectedDate = Carbon::today()->toDateString();
        $this->loadTodayChecklists();
    }

    public function setDateYesterday()
    {
        $this->selectedDate = Carbon::yesterday()->toDateString();
        $this->loadTodayChecklists();
    }

    public function updatedSelectedDate()
    {
        $this->loadTodayChecklists();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedSelectedStudentId()
    {
        $this->loadStudentData();
    }

    public function loadStudentData()
    {
        if (!$this->selectedStudentId) {
            $this->activeStudent = null;
            return;
        }
        $this->activeStudent = Student::withoutGlobalScopes()->with(['classroom.waliKelas'])->find($this->selectedStudentId);
        $this->resetChecklists();
        $this->loadTodayChecklists();
    }

    public function resetChecklists()
    {
        $this->morning_sholat_subuh = false;
        $this->morning_merapikan_tempat_tidur = false;
        $this->morning_meminta_doa = false;
        $this->evening_sholat_maghrib = false;
        $this->evening_sholat_isya = false;
        $this->evening_murajaah = false;
        $this->evening_mengaji = false;
        $this->evening_membantu_ortu = false;
        $this->evening_persiapan_sekolah = false;
    }

    public function loadTodayChecklists()
    {
        if (!$this->selectedStudentId) return;

        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();
        $checks = ParentDailyChecklist::where('student_id', $this->selectedStudentId)
            ->where('tanggal', $targetDate)
            ->get();

        $this->resetChecklists();
        foreach ($checks as $c) {
            if ($c->activity == 'Sholat Subuh') $this->morning_sholat_subuh = (bool) $c->is_done;
            if ($c->activity == 'Merapikan Tempat Tidur') $this->morning_merapikan_tempat_tidur = (bool) $c->is_done;
            if ($c->activity == 'Meminta Doa') $this->morning_meminta_doa = (bool) $c->is_done;
            if ($c->activity == 'Sholat Maghrib') $this->evening_sholat_maghrib = (bool) $c->is_done;
            if ($c->activity == 'Sholat Isya') $this->evening_sholat_isya = (bool) $c->is_done;
            if ($c->activity == 'Murajaah') $this->evening_murajaah = (bool) $c->is_done;
            if ($c->activity == 'Mengaji') $this->evening_mengaji = (bool) $c->is_done;
            if ($c->activity == 'Membantu Ortu') $this->evening_membantu_ortu = (bool) $c->is_done;
            if ($c->activity == 'Persiapan Sekolah') $this->evening_persiapan_sekolah = (bool) $c->is_done;
        }
    }

    public function saveMorning()
    {
        $this->saveChecklist('pagi', 'Sholat Subuh', $this->morning_sholat_subuh);
        $this->saveChecklist('pagi', 'Merapikan Tempat Tidur', $this->morning_merapikan_tempat_tidur);
        $this->saveChecklist('pagi', 'Meminta Doa', $this->morning_meminta_doa);
        session()->flash('message_morning', 'Tugas amaliyah pagi berhasil disimpan.');
    }

    public function saveEvening()
    {
        $this->saveChecklist('malam', 'Sholat Maghrib', $this->evening_sholat_maghrib);
        $this->saveChecklist('malam', 'Sholat Isya', $this->evening_sholat_isya);
        $this->saveChecklist('malam', 'Murajaah', $this->evening_murajaah);
        $this->saveChecklist('malam', 'Mengaji', $this->evening_mengaji);
        $this->saveChecklist('malam', 'Membantu Ortu', $this->evening_membantu_ortu);
        $this->saveChecklist('malam', 'Persiapan Sekolah', $this->evening_persiapan_sekolah);
        session()->flash('message_evening', 'Tugas amaliyah sore/malam berhasil disimpan.');
    }

    private function saveChecklist($timeSlot, $activity, $isDone)
    {
        if (!$this->selectedStudentId) {
            return;
        }

        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();
        $record = ParentDailyChecklist::where('student_id', $this->selectedStudentId)
            ->where('tanggal', $targetDate)
            ->where('activity', $activity)
            ->where('time_slot', $timeSlot)
            ->first();

        if (!$record && $isDone) {
            ParentDailyChecklist::create([
                'student_id' => $this->selectedStudentId,
                'tanggal' => $targetDate,
                'time_slot' => $timeSlot,
                'activity' => $activity,
                'is_done' => true,
            ]);

            CharacterScoreLog::create([
                'student_id' => $this->selectedStudentId,
                'source' => 'orang_tua',
                'category' => 'kepribadian',
                'description' => 'Amaliyah Rumah: ' . $activity,
                'point_change' => 1,
                'tanggal' => $targetDate,
                'submitted_by' => Auth::id(),
            ]);

            if ($this->activeStudent) {
                $this->activeStudent->increment('character_score', 1);
                $this->activeStudent->refresh();
            }
        } elseif ($record && !$isDone) {
            $record->delete();
        }
    }

    // ==========================================
    // 7 DATA GETTERS UNTUK ORANG TUA
    // ==========================================

    /**
     * 1. Kehadiran Masuk Sekolah & Waktu Kedatangan
     */
    public function getArrivalAttendanceProperty()
    {
        if (!$this->selectedStudentId) return null;
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('tipe_absen', 'masuk')
            ->whereDate('tanggal', $targetDate)
            ->first();
    }

    public function getLatestArrivalProperty()
    {
        if (!$this->selectedStudentId) return null;

        return DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('tipe_absen', 'masuk')
            ->orderBy('tanggal', 'desc')
            ->first();
    }

    public function getRecentWeeklyAttendancesProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('tipe_absen', 'masuk')
            ->orderBy('tanggal', 'desc')
            ->take(7)
            ->get();
    }

    /**
     * 2. Absensi Sholat Dhuhur & Ashar (Ketepatan Waktu)
     */
    public function getDhuhurPrayerProperty()
    {
        if (!$this->selectedStudentId) return null;
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        $prayer = PrayerAttendance::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->where('jenis_sholat', 'Dhuhur')
            ->whereDate('tanggal', $targetDate)
            ->first();

        if ($prayer) return $prayer;

        // Fallback jika tercatat via DailyAttendance tipe_absen = sholat_dhuhur
        $daily = DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('tipe_absen', 'sholat_dhuhur')
            ->whereDate('tanggal', $targetDate)
            ->first();

        if ($daily) {
            return (object) [
                'status' => $daily->status,
                'is_khusyu' => in_array($daily->status, ['hadir', 'masuk']),
                'keterangan' => $daily->keterangan ?: ($daily->is_late ? 'Masbuk / Terlambat' : 'Tepat Waktu'),
                'teacher' => null,
            ];
        }

        return null;
    }

    public function getAsharPrayerProperty()
    {
        if (!$this->selectedStudentId) return null;
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        $prayer = PrayerAttendance::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->where('jenis_sholat', 'Ashar')
            ->whereDate('tanggal', $targetDate)
            ->first();

        if ($prayer) return $prayer;

        // Fallback jika tercatat via DailyAttendance tipe_absen = sholat_ashar
        $daily = DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('tipe_absen', 'sholat_ashar')
            ->whereDate('tanggal', $targetDate)
            ->first();

        if ($daily) {
            return (object) [
                'status' => $daily->status,
                'is_khusyu' => in_array($daily->status, ['hadir', 'masuk']),
                'keterangan' => $daily->keterangan ?: ($daily->is_late ? 'Masbuk / Terlambat' : 'Tepat Waktu'),
                'teacher' => null,
            ];
        }

        return null;
    }

    /**
     * 3. Kehadiran per Mata Pelajaran Hari Itu (dari Jurnal Mengajar)
     */
    public function getSubjectAttendancesProperty()
    {
        if (!$this->selectedStudentId) return collect();
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return SubjectAttendance::withoutGlobalScopes()
            ->with(['teachingJournal.subject', 'teachingJournal.teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->whereHas('teachingJournal', fn ($q) => $q->whereDate('tanggal', $targetDate))
            ->get();
    }

    public function getRecentSubjectAttendancesProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return SubjectAttendance::withoutGlobalScopes()
            ->with(['teachingJournal.subject', 'teachingJournal.teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->whereHas('teachingJournal')
            ->latest('id')
            ->take(6)
            ->get();
    }

    /**
     * 4. Skor Karakter dari Guru di Jurnal Mengajar
     */
    public function getCharacterLogsTodayProperty()
    {
        if (!$this->selectedStudentId) return collect();
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return CharacterScoreLog::withoutGlobalScopes()
            ->with(['submitter'])
            ->where('student_id', $this->selectedStudentId)
            ->where('source', '!=', 'orang_tua')
            ->whereDate('tanggal', $targetDate)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getRecentCharacterLogsProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return CharacterScoreLog::withoutGlobalScopes()
            ->with(['submitter'])
            ->where('student_id', $this->selectedStudentId)
            ->where('source', '!=', 'orang_tua')
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * 5. Pelanggaran Siswa & Predikat Kedisiplinan
     */
    public function getViolationsTodayProperty()
    {
        if (!$this->selectedStudentId) return collect();
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return StudentViolationLog::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->whereDate('tanggal', $targetDate)
            ->get();
    }

    public function getAllViolationsProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return StudentViolationLog::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();
    }

    public function getSikapPredikatProperty(): array
    {
        if (!$this->selectedStudentId) {
            return ['predikat' => 'A', 'label' => 'Sangat Baik (A)', 'color' => 'success', 'desc' => 'Sangat disiplin dan berakhlak mulia'];
        }

        $totalPoin = (int) (StudentViolationLog::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->sum('poin') ?? 0);

        return StudentViolationLog::getPredikatNilaiSikap($totalPoin);
    }

    /**
     * 6. Nilai Siswa per Mapel (Penilaian Harian)
     */
    public function getDailyGradesTodayProperty()
    {
        if (!$this->selectedStudentId) return collect();
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return Grade::withoutGlobalScopes()
            ->with(['subject', 'teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->whereDate('tanggal', $targetDate)
            ->get();
    }

    public function getRecentGradesProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return Grade::withoutGlobalScopes()
            ->with(['subject', 'teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->orderBy('tanggal', 'desc')
            ->take(8)
            ->get();
    }

    /**
     * 7. Capaian Penilaian Qur'an
     */
    public function getQuranAchievementsTodayProperty()
    {
        if (!$this->selectedStudentId) return collect();
        $targetDate = $this->selectedDate ?: Carbon::today()->toDateString();

        return QuranAchievement::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->whereDate('tanggal', $targetDate)
            ->get();
    }

    public function getRecentQuranAchievementsProperty()
    {
        if (!$this->selectedStudentId) return collect();

        return QuranAchievement::withoutGlobalScopes()
            ->with(['teacher'])
            ->where('student_id', $this->selectedStudentId)
            ->orderBy('tanggal', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        if (!$this->selectedStudentId) {
            return view('livewire.portal.parent-dashboard', [
                'schoolScoreTotal' => 0,
                'homeScoreTotal' => 0,
                'academicPct' => 0,
                'nonAcademicPct' => 0,
                'characterPct' => 0,
            ])->layout('components.layouts.app');
        }

        // 1. Calculate Home vs School Scores for the Visualizer
        $schoolScoreTotal = (int) (CharacterScoreLog::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('source', '!=', 'orang_tua')
            ->sum('point_change') ?? 0);

        $homeScoreTotal = (int) (CharacterScoreLog::withoutGlobalScopes()
            ->where('student_id', $this->selectedStudentId)
            ->where('source', 'orang_tua')
            ->sum('point_change') ?? 0);

        // 2. Kalkulasi Persentase Akademik
        $totalDaily = DailyAttendance::withoutGlobalScopes()->where('student_id', $this->selectedStudentId)->where('tipe_absen', 'masuk')->count();
        $hadirDaily = DailyAttendance::withoutGlobalScopes()->where('student_id', $this->selectedStudentId)->where('tipe_absen', 'masuk')->where('status', 'hadir')->count();
        $attendancePct = $totalDaily > 0 ? ($hadirDaily / $totalDaily) * 100 : 100;

        $avgGrade = Grade::withoutGlobalScopes()->where('student_id', $this->selectedStudentId)->avg('score');
        $academicPct = $avgGrade !== null ? ($attendancePct + $avgGrade) / 2 : $attendancePct;

        // 3. Kalkulasi Persentase Non-Akademik
        $totalExtra = \App\Models\ExtracurricularAttendance::withoutGlobalScopes()->where('student_id', $this->selectedStudentId)->count();
        $hadirExtra = \App\Models\ExtracurricularAttendance::withoutGlobalScopes()->where('student_id', $this->selectedStudentId)->where('status', 'hadir')->count();
        $nonAcademicPct = $totalExtra > 0 ? ($hadirExtra / $totalExtra) * 100 : 0;

        // 4. Kalkulasi Persentase Karakter
        $targetCharacterPoints = 100;
        $totalCharacterPoints = $this->activeStudent?->character_score ?? ($schoolScoreTotal + $homeScoreTotal);
        $characterPct = $targetCharacterPoints > 0 ? ($totalCharacterPoints / $targetCharacterPoints) * 100 : 0;
        $characterPct = max(0, min($characterPct, 100));

        return view('livewire.portal.parent-dashboard', [
            'schoolScoreTotal' => $schoolScoreTotal,
            'homeScoreTotal' => $homeScoreTotal,
            'academicPct' => round($academicPct, 1),
            'nonAcademicPct' => round($nonAcademicPct, 1),
            'characterPct' => round($characterPct, 1),
        ])->layout('components.layouts.app');
    }
}
