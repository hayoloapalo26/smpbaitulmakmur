<?php

namespace App\Livewire\Portal;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\CharacterScoreLog;
use App\Models\BkReport;
use App\Models\StudentTodoList;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\DcmResponse;

class StudentDashboard extends Component
{
    public $student;
    public $todoTask;

    // DCM properties
    public bool $showDcmForm = false;
    public array $dcmState = []; // Track explicit 'ya' or 'tidak' per question
    public string $dcmUraian1 = '';
    public string $dcmUraian2 = '';
    public string $dcmUraian3 = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user->student_id) {
            abort(403, 'Akses ditolak: Akun tidak terhubung dengan siswa.');
        }
        $this->student = Student::find($user->student_id);

        $this->initDcmState();
    }

    private function initDcmState()
    {
        $existing = DcmResponse::where('student_id', $this->student->id)->latest()->first();
        $categories = DcmResponse::getCategories();
        
        foreach ($categories as $field => $cat) {
            $this->dcmState[$field] = [];
            $dbAnswers = $existing ? ($existing->$field ?? []) : [];
            
            foreach ($cat['questions'] as $no => $q) {
                if ($existing) {
                    // Jika sudah pernah submit, yg ada di DB = 'ya', sisanya 'tidak'
                    $this->dcmState[$field][$no] = in_array($no, $dbAnswers) ? 'ya' : 'tidak';
                } else {
                    // Form baru, belum dijawab
                    $this->dcmState[$field][$no] = null;
                }
            }
        }

        if ($existing) {
            $this->dcmUraian1 = $existing->uraian_1 ?? '';
            $this->dcmUraian2 = $existing->uraian_2 ?? '';
            $this->dcmUraian3 = $existing->uraian_3 ?? '';
        }
    }

    public function setDcmAnswer(string $field, int $questionNo, string $answer): void
    {
        $this->dcmState[$field][$questionNo] = $answer;
    }

    public function submitDcm(): void
    {
        $studentId = $this->student->id;

        $data = [
            'student_id'                => $studentId,
            'submitted_at'              => now(),
            'uraian_1'                  => $this->dcmUraian1,
            'uraian_2'                  => $this->dcmUraian2,
            'uraian_3'                  => $this->dcmUraian3,
        ];

        // Format kembali state 'ya'/'tidak' ke array of numbers (hanya yang 'ya')
        foreach ($this->dcmState as $field => $answers) {
            $yaAnswers = [];
            foreach ($answers as $no => $ans) {
                if ($ans === 'ya') {
                    $yaAnswers[] = $no;
                }
            }
            sort($yaAnswers);
            $data[$field] = $yaAnswers;
        }

        DcmResponse::updateOrCreate(
            ['student_id' => $studentId],
            $data
        );

        $this->showDcmForm = false;
        session()->flash('dcm_success', 'Formulir DCM berhasil disimpan. Terima kasih!');
    }

    public function addTodo()
    {
        $this->validate(['todoTask' => 'required|string|max:255']);
        
        StudentTodoList::create([
            'student_id' => $this->student->id,
            'tanggal'    => now()->toDateString(),
            'task'       => $this->todoTask,
            'is_done'    => false,
        ]);
        
        $this->todoTask = '';
    }

    public function toggleTodo($id)
    {
        $todo = StudentTodoList::where('student_id', $this->student->id)->find($id);
        if ($todo) {
            $todo->is_done = !$todo->is_done;
            $todo->save();
        }
    }

    public function deleteTodo($id)
    {
        $todo = StudentTodoList::where('student_id', $this->student->id)->find($id);
        if ($todo) {
            $todo->delete();
        }
    }

    public function render()
    {
        $scoreLogs    = CharacterScoreLog::where('student_id', $this->student->id)->orderBy('tanggal', 'desc')->get();
        $bkReports    = BkReport::where('student_id', $this->student->id)->whereNotNull('isi_laporan')->where('isi_laporan', '!=', '')->orderBy('tanggal', 'desc')->get();
        $todos        = StudentTodoList::where('student_id', $this->student->id)->orderBy('tanggal', 'desc')->get();
        $quranZiyadah = QuranAchievement::where('student_id', $this->student->id)->where('type', 'ziyadah')->count();
        $quranMurajaah = QuranAchievement::where('student_id', $this->student->id)->where('type', 'murajaah')->count();
        $tahfidzScore = ReadingQualityScore::where('student_id', $this->student->id)->where('program', 'tahfidz')->avg('scale_score');
        $dcmResponse  = DcmResponse::where('student_id', $this->student->id)->latest()->first();
        $dcmCategories = DcmResponse::getCategories();

        return view('livewire.portal.student-dashboard', [
            'scoreLogs'     => $scoreLogs,
            'bkReports'     => $bkReports,
            'todos'         => $todos,
            'quranZiyadah'  => $quranZiyadah,
            'quranMurajaah' => $quranMurajaah,
            'tahfidzScore'  => $tahfidzScore,
            'dcmResponse'   => $dcmResponse,
            'dcmCategories' => $dcmCategories,
        ])->layout('components.layouts.app');
    }
}
