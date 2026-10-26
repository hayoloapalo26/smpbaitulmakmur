<?php

namespace App\Livewire\Portal;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\ParentDailyChecklist;
use App\Models\CharacterScoreLog;
use Carbon\Carbon;

class ParentDashboard extends Component
{
    public $students;
    public $selectedStudentId;
    public $activeStudent;

    // Checklists
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
        if (!$user->guardian_id) {
            abort(403, 'Akses ditolak: Akun tidak terhubung dengan orang tua/wali.');
        }

        // Get students linked to this guardian
        $this->students = Student::join('student_guardians', 'students.id', '=', 'student_guardians.student_id')
            ->where('student_guardians.guardian_id', $user->guardian_id)
            ->select('students.*')
            ->get();

        if ($this->students->isEmpty()) {
            abort(403, 'Tidak ada data anak yang terhubung.');
        }

        $this->selectedStudentId = $this->students->first()->id;
        $this->loadStudentData();
    }

    public function updatedSelectedStudentId()
    {
        $this->loadStudentData();
    }

    public function loadStudentData()
    {
        $this->activeStudent = Student::find($this->selectedStudentId);
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
        $today = Carbon::today()->toDateString();
        $checks = ParentDailyChecklist::where('student_id', $this->selectedStudentId)
            ->where('tanggal', $today)
            ->get();
        
        foreach ($checks as $c) {
            
            // Match our property names exactly
            if ($c->activity == 'Sholat Subuh') $this->morning_sholat_subuh = $c->is_done;
            if ($c->activity == 'Merapikan Tempat Tidur') $this->morning_merapikan_tempat_tidur = $c->is_done;
            if ($c->activity == 'Meminta Doa') $this->morning_meminta_doa = $c->is_done;
            if ($c->activity == 'Sholat Maghrib') $this->evening_sholat_maghrib = $c->is_done;
            if ($c->activity == 'Sholat Isya') $this->evening_sholat_isya = $c->is_done;
            if ($c->activity == 'Murajaah') $this->evening_murajaah = $c->is_done;
            if ($c->activity == 'Mengaji') $this->evening_mengaji = $c->is_done;
            if ($c->activity == 'Membantu Ortu') $this->evening_membantu_ortu = $c->is_done;
            if ($c->activity == 'Persiapan Sekolah') $this->evening_persiapan_sekolah = $c->is_done;
        }
    }

    public function saveMorning()
    {
        $this->saveChecklist('pagi', 'Sholat Subuh', $this->morning_sholat_subuh);
        $this->saveChecklist('pagi', 'Merapikan Tempat Tidur', $this->morning_merapikan_tempat_tidur);
        $this->saveChecklist('pagi', 'Meminta Doa', $this->morning_meminta_doa);
        session()->flash('message_morning', 'Tugas pagi berhasil disimpan.');
    }

    public function saveEvening()
    {
        $this->saveChecklist('malam', 'Sholat Maghrib', $this->evening_sholat_maghrib);
        $this->saveChecklist('malam', 'Sholat Isya', $this->evening_sholat_isya);
        $this->saveChecklist('malam', 'Murajaah', $this->evening_murajaah);
        $this->saveChecklist('malam', 'Mengaji', $this->evening_mengaji);
        $this->saveChecklist('malam', 'Membantu Ortu', $this->evening_membantu_ortu);
        $this->saveChecklist('malam', 'Persiapan Sekolah', $this->evening_persiapan_sekolah);
        session()->flash('message_evening', 'Tugas sore/malam berhasil disimpan.');
    }

    private function saveChecklist($timeSlot, $activity, $isDone)
    {
        $today = Carbon::today()->toDateString();
        $record = ParentDailyChecklist::where('student_id', $this->selectedStudentId)
            ->where('tanggal', $today)
            ->where('activity', $activity)
            ->where('time_slot', $timeSlot)
            ->first();
        
        if (!$record && $isDone) {
            ParentDailyChecklist::create([
                'student_id' => $this->selectedStudentId,
                'tanggal' => $today,
                'time_slot' => $timeSlot,
                'activity' => $activity,
                'is_done' => true
            ]);
            
            // Add +1 character score
            CharacterScoreLog::create([
                'student_id' => $this->selectedStudentId,
                'source' => 'orang_tua',
                'category' => 'kepribadian',
                'description' => 'Mengerjakan: ' . $activity,
                'point_change' => 1,
                'tanggal' => $today,
            ]);
            $this->activeStudent->refresh();
        }
    }

    public function render()
    {
        $today = Carbon::today();
        
        // 1. Calculate Home vs School Scores for the Visualizer
        // School Score: Source is NOT orang_tua
        $schoolScoreTotal = CharacterScoreLog::where('student_id', $this->selectedStudentId)
            ->where('source', '!=', 'orang_tua')
            ->sum('point_change') ?? 0;
            
        // Home Score: Source IS orang_tua
        $homeScoreTotal = CharacterScoreLog::where('student_id', $this->selectedStudentId)
            ->where('source', 'orang_tua')
            ->sum('point_change') ?? 0;

        // 2. Kalkulasi Persentase Akademik
        // a. Kehadiran Harian
        $totalDaily = \App\Models\DailyAttendance::where('student_id', $this->selectedStudentId)->count();
        $hadirDaily = \App\Models\DailyAttendance::where('student_id', $this->selectedStudentId)->where('status', 'hadir')->count();
        $attendancePct = $totalDaily > 0 ? ($hadirDaily / $totalDaily) * 100 : 100; // Default 100% jika belum ada hari efektif
        
        // b. Nilai Akademik
        $avgGrade = \App\Models\Grade::where('student_id', $this->selectedStudentId)->avg('score');
        
        $academicPct = $avgGrade !== null ? ($attendancePct + $avgGrade) / 2 : $attendancePct;

        // 3. Kalkulasi Persentase Non-Akademik
        $totalExtra = \App\Models\ExtracurricularAttendance::where('student_id', $this->selectedStudentId)->count();
        $hadirExtra = \App\Models\ExtracurricularAttendance::where('student_id', $this->selectedStudentId)->where('status', 'hadir')->count();
        $nonAcademicPct = $totalExtra > 0 ? ($hadirExtra / $totalExtra) * 100 : 0; // Default 0 jika tidak ikut ekskul

        // 4. Kalkulasi Persentase Karakter
        // Asumsi target poin karakter adalah 100.
        $targetCharacterPoints = 100;
        $totalCharacterPoints = $schoolScoreTotal + $homeScoreTotal;
        $characterPct = $targetCharacterPoints > 0 ? ($totalCharacterPoints / $targetCharacterPoints) * 100 : 0;
        $characterPct = min($characterPct, 100); // Cap at 100%

        return view('livewire.portal.parent-dashboard', [
            'schoolScoreTotal' => $schoolScoreTotal,
            'homeScoreTotal' => $homeScoreTotal,
            'academicPct' => round($academicPct, 1),
            'nonAcademicPct' => round($nonAcademicPct, 1),
            'characterPct' => round($characterPct, 1),
        ])->layout('components.layouts.app');
    }
}
