<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\StudentAchievement;
use App\Models\TeacherSubjectClass;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KetercapaianProfilLulusan extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Ketercapaian Profil Lulusan';
    protected static ?string $title = 'Ketercapaian Profil Lulusan';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.staff.pages.ketercapaian-profil-lulusan';

    public ?int $selectedClassroomId = null;
    public ?int $selectedStudentId = null;
    public ?int $selectedAcademicYearId = null;
    public string $selectedSemester = 'Ganjil';
    public string $searchStudent = '';
    public string $filterStatus = 'all'; // 'all', 'sangat_memenuhi', 'memenuhi', 'perlu_peningkatan'
    public string $activeAspectTab = 'overview'; // 'overview', 'akademik', 'tahfidz', 'prestasi'

    protected $queryString = [
        'selectedClassroomId' => ['except' => null, 'as' => 'class_id'],
        'selectedStudentId'   => ['except' => null, 'as' => 'student_id'],
    ];

    public function mount(): void
    {
        $user = Auth::user();

        // 1. Dapatkan Tahun Ajaran Aktif
        $currentYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first();
        $this->selectedAcademicYearId = $currentYear?->id;

        // 2. Dapatkan Kelas yang Tersedia
        $availableClasses = $this->getAvailableClasses();

        // Cek jika ada request query param class_id
        $requestedClassId = request()->query('class_id');
        if ($requestedClassId && $availableClasses->contains('id', (int) $requestedClassId)) {
            $this->selectedClassroomId = (int) $requestedClassId;
        } elseif ($availableClasses->isNotEmpty()) {
            // Default ke kelas tingkat akhir (misal Kelas 9) jika ada, atau kelas pertama
            $grade9 = $availableClasses->firstWhere('tingkat', '9') ?? $availableClasses->first();
            $this->selectedClassroomId = $grade9?->id;
        }

        // Cek jika ada request query param student_id
        $requestedStudentId = request()->query('student_id');
        if ($requestedStudentId) {
            $student = Student::find((int) $requestedStudentId);
            if ($student) {
                $this->selectedStudentId = $student->id;
                $this->selectedClassroomId = $student->classroom_id;
            }
        }
    }

    public function selectClass(int $classroomId): void
    {
        $this->selectedClassroomId = $classroomId;
        $this->selectedStudentId = null;
        $this->searchStudent = '';
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
        $this->activeAspectTab = 'overview';
    }

    public function backToClassList(): void
    {
        $this->selectedStudentId = null;
    }

    public function setAspectTab(string $tab): void
    {
        $this->activeAspectTab = $tab;
    }

    public function getAvailableClasses()
    {
        $user = Auth::user();
        if ($user->hasRole('superadmin') || $user->institution_id == 1) {
            return Classroom::orderBy('institution_id')->orderBy('tingkat')->orderBy('name')->get();
        }

        return Classroom::where('institution_id', $user->institution_id)
            ->orderBy('tingkat')
            ->orderBy('name')
            ->get();
    }

    public function getSurahName(?string $surahId): string
    {
        $surahs = [
            '1' => 'Al-Fatihah', '2' => 'Al-Baqarah', '3' => 'Ali Imran', '4' => 'An-Nisa', '5' => 'Al-Maidah',
            '6' => 'Al-An\'am', '7' => 'Al-A\'raf', '8' => 'Al-Anfal', '9' => 'At-Taubah', '10' => 'Yunus',
            '11' => 'Hud', '12' => 'Yusuf', '13' => 'Ar-Ra\'d', '14' => 'Ibrahim', '15' => 'Al-Hijr',
            '16' => 'An-Nahl', '17' => 'Al-Isra', '18' => 'Al-Kahf', '19' => 'Maryam', '20' => 'Taha',
            '21' => 'Al-Anbiya', '22' => 'Al-Hajj', '23' => 'Al-Mu\'minun', '24' => 'An-Nur', '25' => 'Al-Furqan',
            '26' => 'Ash-Shu\'ara', '27' => 'An-Naml', '28' => 'Al-Qasas', '29' => 'Al-Ankabut', '30' => 'Ar-Rum',
            '31' => 'Luqman', '32' => 'As-Sajdah', '33' => 'Al-Ahzab', '34' => 'Saba\'', '35' => 'Fatir',
            '36' => 'Ya-Sin', '37' => 'As-Saffat', '38' => 'Sad', '39' => 'Az-Zumar', '40' => 'Ghafir',
            '41' => 'Fussilat', '42' => 'Ash-Shura', '43' => 'Az-Zukhruf', '44' => 'Ad-Dukhan', '45' => 'Al-Jathiyah',
            '46' => 'Al-Ahqaf', '47' => 'Muhammad', '48' => 'Al-Fath', '49' => 'Al-Hujurat', '50' => 'Qaf',
            '51' => 'Adh-Dhariyat', '52' => 'At-Tur', '53' => 'An-Najm', '54' => 'Al-Qamar', '55' => 'Ar-Rahman',
            '56' => 'Al-Waqi\'ah', '57' => 'Al-Hadid', '58' => 'Al-Mujadila', '59' => 'Al-Hashr', '60' => 'Al-Mumtahanah',
            '61' => 'As-Saff', '62' => 'Al-Jumu\'ah', '63' => 'Al-Munafiqun', '64' => 'At-Taghabun', '65' => 'At-Talaq',
            '66' => 'At-Tahrim', '67' => 'Al-Mulk', '68' => 'Al-Qalam', '69' => 'Al-Haqqah', '70' => 'Al-Ma\'arij',
            '71' => 'Nuh', '72' => 'Al-Jinn', '73' => 'Al-Muzzammil', '74' => 'Al-Muddaththir', '75' => 'Al-Qiyamah',
            '76' => 'Al-Insan', '77' => 'Al-Mursalat', '78' => 'An-Naba\'', '79' => 'An-Nazi\'at', '80' => '\'Abasa',
            '81' => 'At-Takwir', '82' => 'Al-Infitar', '83' => 'Al-Mutaffifin', '84' => 'Al-Inshiqaq', '85' => 'Al-Buruj',
            '86' => 'At-Tariq', '87' => 'Al-A\'la', '88' => 'Al-Ghashiyah', '89' => 'Al-Fajr', '90' => 'Al-Balad',
            '91' => 'Ash-Shams', '92' => 'Al-Lail', '93' => 'Ad-Duha', '94' => 'Ash-Sharh', '95' => 'At-Tin',
            '96' => 'Al-\'Alaq', '97' => 'Al-Qadr', '98' => 'Al-Bayyinah', '99' => 'Az-Zalzalah', '100' => 'Al-\'Adiyat',
            '101' => 'Al-Qari\'ah', '102' => 'At-Takathur', '103' => 'Al-\'Asr', '104' => 'Al-Humazah', '105' => 'Al-Fil',
            '106' => 'Quraisy', '107' => 'Al-Ma\'un', '108' => 'Al-Kautsar', '109' => 'Al-Kafirun', '110' => 'An-Nasr',
            '111' => 'Al-Lahab', '112' => 'Al-Ikhlas', '113' => 'Al-Falaq', '114' => 'An-Nas'
        ];

        if (!$surahId) return '-';
        return $surahs[$surahId] ?? "Surah ke-{$surahId}";
    }

    /**
     * Hitung ringkasan 3 aspek untuk setiap siswa di kelas terpilih
     */
    public function getStudentsData()
    {
        if (!$this->selectedClassroomId) {
            return collect();
        }

        $query = Student::where('classroom_id', $this->selectedClassroomId)
            ->with(['classroom.waliKelas', 'studentAchievements', 'quranAchievements', 'readingQualityScores']);

        if (!empty($this->searchStudent)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchStudent . '%')
                  ->orWhere('nis', 'like', '%' . $this->searchStudent . '%');
            });
        }

        $students = $query->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        $compiled = $students->map(function ($student) use ($subjects) {
            // 1. Aspek Akademik (Rata-rata kumulatif seluruh mapel)
            $grades = Grade::where('student_id', $student->id)
                ->when($this->selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $this->selectedAcademicYearId))
                ->where('semester', $this->selectedSemester)
                ->get();

            $totalSubjectScore = 0;
            $gradedSubjectCount = 0;

            foreach ($subjects as $subject) {
                $subGrades = $grades->where('subject_id', $subject->id);
                if ($subGrades->isNotEmpty()) {
                    $harian = $subGrades->whereIn('type', ['pengetahuan', 'keterampilan'])->avg('score');
                    $ujian = $subGrades->where('type', 'ujian')->first()?->score;

                    $final = null;
                    if ($harian !== null && $ujian !== null) {
                        $final = ($harian * 0.6) + ($ujian * 0.4);
                    } elseif ($ujian !== null) {
                        $final = $ujian;
                    } elseif ($harian !== null) {
                        $final = $harian;
                    }

                    if ($final !== null) {
                        $totalSubjectScore += $final;
                        $gradedSubjectCount++;
                    }
                }
            }

            $academicAverage = $gradedSubjectCount > 0 ? round($totalSubjectScore / $gradedSubjectCount, 1) : 0;

            // 2. Aspek Tahfidz & Tahsin
            $achievements = $student->quranAchievements;
            $ziyadahCount = $achievements->filter(function ($a) {
                return is_array($a->type) ? in_array('ziyadah', $a->type) : $a->type === 'ziyadah';
            })->count();

            $murajaahCount = $achievements->filter(function ($a) {
                return is_array($a->type) ? in_array('murajaah', $a->type) : $a->type === 'murajaah';
            })->count();

            $latestTahfidz = $achievements->sortByDesc('tanggal')->first();
            $qualityAvg = $student->readingQualityScores->avg('scale_score') ?? 0;
            $tahfidzScore = round($qualityAvg > 0 ? $qualityAvg : min(95, 70 + ($ziyadahCount * 5)), 1);

            // 3. Aspek Prestasi (Inputan Waka Kesiswaan: Non-Akademik, Prestasi Kelas (Religius, Karakter, Adab), Akademik, Reward)
            $achieveList = $student->studentAchievements;
            $classAchieveList = $student->classroom_id
                ? StudentAchievement::where('classroom_id', $student->classroom_id)->whereNull('student_id')->get()
                : collect();
            $allAchieve = $achieveList->concat($classAchieveList);

            $totalAchievements = $allAchieve->count();
            $akademikAchievements = $allAchieve->filter(fn ($a) => $a->kategori === 'akademik')->count();
            $religiusAchievements = $allAchieve->filter(fn ($a) => $a->kategori === 'religius' || ($a->kategori === 'prestasi_kelas' && $a->sub_kategori === 'religius') || $a->jenis_reward === 'reward_religi')->count();
            $karakterAdabAchievements = $allAchieve->filter(fn ($a) => $a->kategori === 'prestasi_kelas' && in_array($a->sub_kategori, ['karakter', 'adab']))->count();
            $nonAkademikAchievements = $allAchieve->filter(fn ($a) => $a->kategori === 'non_akademik')->count();

            // Poin prestasi: Dasar 70 jika belum ada prestasi, ditambah bobot per prestasi
            $prestasiScore = min(100, 70 + ($akademikAchievements * 12) + ($religiusAchievements * 12) + ($karakterAdabAchievements * 10) + ($nonAkademikAchievements * 10));

            // Skor Komposit Ketercapaian Profil Lulusan
            // Bobot: Akademik (40%) + Tahfidz (35%) + Prestasi Waka (25%)
            $compositeScore = round(
                ($academicAverage * 0.40) +
                ($tahfidzScore * 0.35) +
                ($prestasiScore * 0.25),
                1
            );

            // Status Kelulusan Profil
            if ($compositeScore >= 85 && $ziyadahCount >= 3) {
                $statusKey = 'sangat_memenuhi';
                $statusLabel = 'Sangat Memenuhi Profil Lulusan';
                $statusColor = 'emerald';
                $predikat = 'A';
            } elseif ($compositeScore >= 75) {
                $statusKey = 'memenuhi';
                $statusLabel = 'Memenuhi Profil Lulusan';
                $statusColor = 'blue';
                $predikat = 'B';
            } else {
                $statusKey = 'perlu_peningkatan';
                $statusLabel = 'Perlu Peningkatan';
                $statusColor = 'amber';
                $predikat = 'C';
            }

            return (object) [
                'id'                      => $student->id,
                'name'                    => $student->name,
                'nis'                     => $student->nis,
                'nisn'                    => $student->nisn,
                'jenis_kelamin'           => $student->jenis_kelamin,
                'foto'                    => $student->foto,
                'academic_average'        => $academicAverage,
                'graded_subjects_count'   => $gradedSubjectCount,
                'ziyadah_count'           => $ziyadahCount,
                'murajaah_count'          => $murajaahCount,
                'last_juz'                => $latestTahfidz?->juz ?? '-',
                'last_surah_name'         => $this->getSurahName($latestTahfidz?->surah),
                'tahfidz_score'           => $tahfidzScore,
                'total_achievements'      => $totalAchievements,
                'akademik_achievements'   => $akademikAchievements,
                'non_akademik_achievements' => $nonAkademikAchievements,
                'prestasi_score'          => $prestasiScore,
                'composite_score'         => $compositeScore,
                'status_key'              => $statusKey,
                'status_label'            => $statusLabel,
                'status_color'            => $statusColor,
                'predikat'                => $predikat,
            ];
        });

        // Filter status jika dipilih
        if ($this->filterStatus !== 'all') {
            $compiled = $compiled->where('status_key', $this->filterStatus);
        }

        return $compiled->sortByDesc('composite_score')->values();
    }

    /**
     * Hitung data lengkap untuk detail siswa terpilih
     */
    public function getSelectedStudentDetail()
    {
        if (!$this->selectedStudentId) {
            return null;
        }

        $student = Student::with(['classroom.waliKelas', 'studentAchievements.inputBy', 'quranAchievements.teacher', 'readingQualityScores'])
            ->find($this->selectedStudentId);

        if (!$student) {
            return null;
        }

        $subjects = Subject::orderBy('name')->get();
        $academicYear = AcademicYear::find($this->selectedAcademicYearId) ?? AcademicYear::latest()->first();

        // ----------------------------------------------------
        // ASPEK 1: NILAI AKADEMIK PER SISWA
        // ----------------------------------------------------
        $grades = Grade::where('student_id', $student->id)
            ->when($this->selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $this->selectedAcademicYearId))
            ->where('semester', $this->selectedSemester)
            ->orderBy('created_at')
            ->get();

        // Dapatkan mapping guru mapel untuk kelas ini
        $tscList = TeacherSubjectClass::where('classroom_id', $student->classroom_id)
            ->with(['teacher', 'subject'])
            ->get()
            ->keyBy('subject_id');

        $subjectReports = [];
        $totalFinalScore = 0;
        $totalGraded = 0;
        $totalTuntas = 0;

        foreach ($subjects as $subject) {
            $subGrades = $grades->where('subject_id', $subject->id);
            $teacher = $tscList[$subject->id]?->teacher?->name ?? 'Guru Mata Pelajaran';

            // Pisahkan nilai harian/tugas vs ujian
            $harianGrades = $subGrades->whereIn('type', ['pengetahuan', 'keterampilan'])->values();
            $ujianGrades  = $subGrades->where('type', 'ujian')->values();

            $avgHarian = $harianGrades->isNotEmpty() ? round($harianGrades->avg('score'), 1) : null;
            $scoreUjian = $ujianGrades->isNotEmpty() ? round($ujianGrades->first()->score, 1) : null;

            // Hitung akumulasi akhir: 60% Harian + 40% Ujian
            $finalScore = null;
            if ($avgHarian !== null && $scoreUjian !== null) {
                $finalScore = round(($avgHarian * 0.6) + ($scoreUjian * 0.4), 1);
            } elseif ($scoreUjian !== null) {
                $finalScore = $scoreUjian;
            } elseif ($avgHarian !== null) {
                $finalScore = $avgHarian;
            }

            // Predikat & Ketuntasan (KKM 75)
            $predikat = '-';
            $isTuntas = false;
            if ($finalScore !== null) {
                $totalFinalScore += $finalScore;
                $totalGraded++;
                $isTuntas = $finalScore >= 75;
                if ($isTuntas) $totalTuntas++;

                if ($finalScore >= 90) $predikat = 'A';
                elseif ($finalScore >= 80) $predikat = 'B';
                elseif ($finalScore >= 70) $predikat = 'C';
                else $predikat = 'D';
            }

            // Riwayat Log Update Harian
            $updateHistory = $subGrades->map(function ($g) {
                $typeLabel = match($g->type) {
                    'ujian' => 'Ujian / Evaluasi (UTS/UAS)',
                    'keterampilan' => 'Tugas Praktik / Keterampilan',
                    default => 'Penilaian Harian / Tugas Harian'
                };

                return [
                    'id'         => $g->id,
                    'type'       => $g->type,
                    'type_label' => $typeLabel,
                    'score'      => $g->score,
                    'date'       => $g->created_at ? Carbon::parse($g->created_at)->format('d M Y') : '-',
                    'time'       => $g->created_at ? Carbon::parse($g->created_at)->format('H:i') : '',
                ];
            });

            $subjectReports[] = [
                'subject_id'     => $subject->id,
                'name'           => $subject->name,
                'kode'           => $subject->kode ?? strtoupper(substr($subject->name, 0, 3)),
                'teacher_name'   => $teacher,
                'daily_updates'  => $updateHistory,
                'update_count'   => $harianGrades->count(),
                'avg_harian'     => $avgHarian,
                'score_ujian'    => $scoreUjian,
                'final_score'    => $finalScore,
                'predikat'       => $predikat,
                'is_tuntas'      => $isTuntas,
            ];
        }

        $overallAcademicAverage = $totalGraded > 0 ? round($totalFinalScore / $totalGraded, 1) : 0;
        $academicCompletionRate = count($subjects) > 0 ? round(($totalTuntas / count($subjects)) * 100, 1) : 0;

        // ----------------------------------------------------
        // ASPEK 2: NILAI TAHFIDZ & TAHSIN
        // ----------------------------------------------------
        $quranAchievements = QuranAchievement::where('student_id', $student->id)
            ->with('teacher')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $ziyadahList = $quranAchievements->filter(function ($a) {
            return is_array($a->type) ? in_array('ziyadah', $a->type) : $a->type === 'ziyadah';
        });

        $murajaahList = $quranAchievements->filter(function ($a) {
            return is_array($a->type) ? in_array('murajaah', $a->type) : $a->type === 'murajaah';
        });

        $latestAchievement = $quranAchievements->first();

        // Reading Quality Scores (Mutu Bacaan)
        $qualityScores = ReadingQualityScore::where('student_id', $student->id)
            ->orderByDesc('tanggal')
            ->get();

        $tahfidzQuality = $qualityScores->where('program', 'tahfidz')->avg('scale_score');
        $tahsinQuality  = $qualityScores->where('program', 'tahsin')->avg('scale_score');
        $overallQuality = $qualityScores->avg('scale_score') ?? 80;

        // Predikat Mutu Bacaan
        $mutuPredikat = match(true) {
            $overallQuality >= 90 => 'Mumtaz (Istimewa)',
            $overallQuality >= 80 => 'Jayyid Jiddan (Sangat Baik)',
            $overallQuality >= 70 => 'Jayyid (Baik)',
            default => 'Maqbul (Cukup)',
        };

        // Target Profil Lulusan Tahfidz: Minimal 3 Juz (Juz 30, 29, 28)
        $uniqueJuzCompleted = $ziyadahList->pluck('juz')->unique()->filter()->count();
        $targetJuz = 3;
        $tahfidzProgressPct = min(100, round(($uniqueJuzCompleted / $targetJuz) * 100));

        // Format riwayat setoran
        $tahfidzLogs = $quranAchievements->map(function ($item) {
            $types = is_array($item->type) ? implode(', ', array_map('ucfirst', $item->type)) : ucfirst($item->type);
            return [
                'id'           => $item->id,
                'tanggal'      => $item->tanggal ? Carbon::parse($item->tanggal)->format('d M Y') : '-',
                'type'         => $types,
                'juz'          => $item->juz,
                'surah_name'   => $this->getSurahName($item->surah),
                'ayah'         => $item->ayah,
                'catatan'      => $item->catatan ?: 'Bacaan lancar sesuai kaidah tajwid.',
                'teacher_name' => $item->teacher?->name ?? 'Guru Pembimbing Al-Qur\'an',
            ];
        });

        // ----------------------------------------------------
        // ASPEK 3: INPUTAN PRESTASI DARI WAKA KESISWAAN
        // ----------------------------------------------------
        $studentAchievements = StudentAchievement::where('student_id', $student->id)
            ->orWhere(function ($q) use ($student) {
                if ($student->classroom_id) {
                    $q->where('classroom_id', $student->classroom_id)->whereNull('student_id');
                }
            })
            ->with(['inputBy', 'classroom'])
            ->orderByDesc('tanggal')
            ->get();

        $prestasiAkademik = $studentAchievements->filter(fn ($a) => $a->kategori === 'akademik')->values();
        $prestasiReligius = $studentAchievements->filter(fn ($a) => $a->kategori === 'religius' || ($a->kategori === 'prestasi_kelas' && $a->sub_kategori === 'religius') || $a->jenis_reward === 'reward_religi')->values();
        $prestasiKelasKarakterAdab = $studentAchievements->filter(fn ($a) => $a->kategori === 'prestasi_kelas' && in_array($a->sub_kategori, ['karakter', 'adab']))->values();
        $prestasiNonAkademik = $studentAchievements->filter(fn ($a) => $a->kategori === 'non_akademik')->values();

        $prestasiTingkatTertinggi = '-';
        if ($studentAchievements->isNotEmpty()) {
            $order = ['Internasional', 'Nasional', 'Provinsi', 'Kota / Kabupaten', 'Kecamatan', 'Sekolah / Gugus'];
            foreach ($order as $tier) {
                if ($studentAchievements->contains('tingkat', $tier)) {
                    $prestasiTingkatTertinggi = $tier;
                    break;
                }
            }
        }

        // Poin Aspek Prestasi (Bobot: Akademik & Religius bernilai tinggi, Karakter/Adab & Non-Akademik melengkapi)
        $prestasiPoint = min(100, 70 + ($prestasiAkademik->count() * 15) + ($prestasiReligius->count() * 15) + ($prestasiKelasKarakterAdab->count() * 12) + ($prestasiNonAkademik->count() * 10));

        // ----------------------------------------------------
        // SINTESIS AKHIR KETERCAPAIAN PROFIL LULUSAN
        // ----------------------------------------------------
        $tahfidzFinalScore = round($overallQuality > 0 ? $overallQuality : min(95, 75 + ($ziyadahList->count() * 4)), 1);

        $compositeScore = round(
            ($overallAcademicAverage * 0.40) +
            ($tahfidzFinalScore * 0.35) +
            ($prestasiPoint * 0.25),
            1
        );

        if ($compositeScore >= 85 && $ziyadahList->count() >= 3) {
            $statusLabel = 'SANGAT MEMENUHI PROFIL LULUSAN';
            $statusSubtext = 'Siswa mencapai standar unggul pada akademik harian, hafalan tahfidz, dan memiliki rekam jejak prestasi.';
            $statusBadgeClass = 'bg-emerald-500 text-white';
            $predikatAkhir = 'A (Unggul / Mumtaz)';
        } elseif ($compositeScore >= 75) {
            $statusLabel = 'MEMENUHI PROFIL LULUSAN';
            $statusSubtext = 'Siswa memenuhi seluruh kriteria kelulusan minimum dengan capaian akademik dan tahfidz yang baik.';
            $statusBadgeClass = 'bg-blue-600 text-white';
            $predikatAkhir = 'B (Baik / Jayyid)';
        } else {
            $statusLabel = 'PERLU PENINGKATAN KHUSUS';
            $statusSubtext = 'Diperlukan pendampingan tambahan pada aspek nilai mapel harian atau setoran tahfidz.';
            $statusBadgeClass = 'bg-amber-500 text-white';
            $predikatAkhir = 'C (Perlu Bimbingan)';
        }

        return (object) [
            'student'                  => $student,
            'academic_year'            => $academicYear,
            'semester'                 => $this->selectedSemester,
            'subjects'                 => $subjectReports,
            'overall_academic_avg'     => $overallAcademicAverage,
            'academic_completion_rate' => $academicCompletionRate,
            'total_tuntas'             => $totalTuntas,
            'total_subjects'           => count($subjects),
            'tahfidz'                  => (object) [
                'total_ziyadah'        => $ziyadahList->count(),
                'total_murajaah'       => $murajaahList->count(),
                'last_juz'             => $latestAchievement?->juz ?? '-',
                'last_surah'           => $this->getSurahName($latestAchievement?->surah),
                'last_ayah'            => $latestAchievement?->ayah ?? '-',
                'last_date'            => $latestAchievement?->tanggal ? Carbon::parse($latestAchievement->tanggal)->format('d M Y') : '-',
                'last_teacher'         => $latestAchievement?->teacher?->name ?? 'Guru Al-Qur\'an',
                'last_note'            => $latestAchievement?->catatan ?? '-',
                'tahfidz_quality'      => $tahfidzQuality ? round($tahfidzQuality, 1) : null,
                'tahsin_quality'       => $tahsinQuality ? round($tahsinQuality, 1) : null,
                'overall_quality'      => round($overallQuality, 1),
                'mutu_predikat'        => $mutuPredikat,
                'progress_pct'         => $tahfidzProgressPct,
                'completed_juz_count'  => $uniqueJuzCompleted,
                'target_juz'           => $targetJuz,
                'final_score'          => $tahfidzFinalScore,
                'logs'                 => $tahfidzLogs,
            ],
            'prestasi'                 => (object) [
                'total_count'          => $studentAchievements->count(),
                'akademik_count'       => $prestasiAkademik->count(),
                'religius_count'       => $prestasiReligius->count(),
                'non_akademik_count'   => $prestasiNonAkademik->count() + $prestasiKelasKarakterAdab->count(),
                'kelas_count'          => $studentAchievements->filter(fn ($a) => $a->lingkup === 'kelas')->count(),
                'reward_count'         => $studentAchievements->filter(fn ($a) => in_array($a->jenis_reward, ['reward_religi', 'reward_akademik']))->count(),
                'tingkat_tertinggi'    => $prestasiTingkatTertinggi,
                'score'                => $prestasiPoint,
                'list'                 => $studentAchievements,
                'akademik_list'        => $prestasiAkademik,
                'religius_list'        => $prestasiReligius,
                'non_akademik_list'    => $prestasiNonAkademik,
            ],
            'composite_score'          => $compositeScore,
            'status_label'             => $statusLabel,
            'status_subtext'           => $statusSubtext,
            'status_badge_class'       => $statusBadgeClass,
            'predikat_akhir'           => $predikatAkhir,
        ];
    }

    protected function getViewData(): array
    {
        $availableClasses = $this->getAvailableClasses();
        $selectedClass = Classroom::find($this->selectedClassroomId);
        $academicYears = AcademicYear::orderByDesc('id')->get();

        $studentsData = $this->getStudentsData();
        $selectedStudentDetail = $this->getSelectedStudentDetail();

        // Ringkasan statistik kelas saat ini
        $classStats = (object) [
            'total_students'      => $studentsData->count(),
            'avg_academic'        => $studentsData->avg('academic_average') ? round($studentsData->avg('academic_average'), 1) : 0,
            'total_ziyadah'       => $studentsData->sum('ziyadah_count'),
            'total_achievements'  => $studentsData->sum('total_achievements'),
            'pass_rate'           => $studentsData->count() > 0 
                ? round(($studentsData->whereIn('status_key', ['sangat_memenuhi', 'memenuhi'])->count() / $studentsData->count()) * 100, 1) 
                : 0,
        ];

        return [
            'availableClasses'      => $availableClasses,
            'selectedClass'         => $selectedClass,
            'selectedClassroomId'   => $this->selectedClassroomId,
            'selectedStudentId'     => $this->selectedStudentId,
            'selectedAcademicYearId'=> $this->selectedAcademicYearId,
            'selectedSemester'      => $this->selectedSemester,
            'searchStudent'         => $this->searchStudent,
            'filterStatus'          => $this->filterStatus,
            'activeAspectTab'       => $this->activeAspectTab,
            'academicYears'         => $academicYears,
            'studentsData'          => $studentsData,
            'selectedStudentDetail' => $selectedStudentDetail,
            'classStats'            => $classStats,
        ];
    }
}
