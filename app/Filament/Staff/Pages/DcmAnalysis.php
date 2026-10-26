<?php

namespace App\Filament\Staff\Pages;

use App\Models\DcmResponse;
use App\Models\Student;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DcmAnalysis extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'superadmin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Analisis DCM';
    protected static ?string $title = 'Analisis Penskoran DCM';
    protected static string $view = 'filament.staff.pages.dcm-analysis';
    protected static ?int $navigationSort = 2;

    public string $selectedCategory = 'jawaban_kesehatan';
    public ?int $selectedPoint = null;
    public bool $showStudentModal = false;

    

    public function mount(): void
    {
        $this->selectedCategory = 'jawaban_kesehatan';
    }

    public function selectCategory(string $field): void
    {
        $this->selectedCategory = $field;
        $this->selectedPoint = null;
        $this->showStudentModal = false;
    }

    public function showStudentsForPoint(int $point): void
    {
        $this->selectedPoint = $point;
        $this->showStudentModal = true;
    }

    public function closeModal(): void
    {
        $this->showStudentModal = false;
        $this->selectedPoint = null;
    }

    /**
     * Hitung penskoran: berapa siswa yang memilih setiap nomor poin
     * di kategori yang dipilih, urut dari terbanyak ke tersedikit
     */
    public function getPointScores(): array
    {
        $categories = DcmResponse::getCategories();
        $category   = $categories[$this->selectedCategory] ?? null;
        if (!$category) return [];

        $field = $this->selectedCategory;

        // Ambil semua response
        $responses = DcmResponse::whereNotNull($field)->get();

        // Hitung berapa response yang memilih setiap nomor
        $scores = [];
        foreach ($category['questions'] as $no => $text) {
            $count = 0;
            foreach ($responses as $resp) {
                if (in_array($no, $resp->$field ?? [])) {
                    $count++;
                }
            }
            $scores[$no] = [
                'no'    => $no,
                'text'  => $text,
                'count' => $count,
            ];
        }

        // Urutkan dari terbanyak ke tersedikit
        usort($scores, fn($a, $b) => $b['count'] <=> $a['count']);

        return $scores;
    }

    /**
     * Dapatkan daftar siswa yang memilih poin tertentu
     */
    public function getStudentsForPoint(): array
    {
        if (!$this->selectedPoint || !$this->selectedCategory) return [];

        $field = $this->selectedCategory;
        $point = $this->selectedPoint;

        $responses = DcmResponse::whereNotNull($field)->with('student.classroom')->get();

        $students = [];
        foreach ($responses as $resp) {
            if (in_array($point, $resp->$field ?? [])) {
                $students[] = [
                    'name'      => $resp->student->name ?? '-',
                    'classroom' => $resp->student->classroom->name ?? '-',
                    'nis'       => $resp->student->nis ?? '-',
                ];
            }
        }

        // Urutkan berdasarkan kelas lalu nama
        usort($students, fn($a, $b) => $a['classroom'] === $b['classroom']
            ? strcmp($a['name'], $b['name'])
            : strcmp($a['classroom'], $b['classroom']));

        return $students;
    }

    /**
     * Statistik ringkasan per kategori (untuk sidebar)
     */
    public function getCategorySummary(): array
    {
        $categories = DcmResponse::getCategories();
        $allResponses = DcmResponse::all();
        $totalStudents = $allResponses->count();

        $summary = [];
        foreach ($categories as $field => $cat) {
            $totalPoints = 0;
            foreach ($allResponses as $resp) {
                $totalPoints += count($resp->$field ?? []);
            }
            $summary[$field] = [
                'label'         => $cat['label'],
                'short'         => $cat['short'],
                'total_points'  => $totalPoints,
                'total_questions' => count($cat['questions']),
            ];
        }

        // Urutkan berdasarkan total_points descending
        uasort($summary, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

        return $summary;
    }
}
