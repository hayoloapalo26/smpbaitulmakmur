<?php

namespace App\Services;

use App\Models\BkWorkProgram;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\HumasProgram;
use App\Models\Institution;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TeacherCertificate;
use App\Models\TeacherSupervision;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SchoolPillarService
{
    /**
     * Daftar 7 Pilar Konstituen yang menentukan ketercapaian Pilar 1 (Bidang Manajemen).
     * Sesuai ketentuan: Mutu Lulusan (2), Kurikulum (3), PTK (4), Kesiswaan (5),
     * Sarpras (6), Humas (8), dan BK (9).
     * Pilar Keuangan (7) tidak memiliki persentase dan tidak dimasukkan ke dalam agregasi.
     */
    public const PILLAR_1_CONSTITUENTS = [
        2 => [
            'order' => 2,
            'code' => '1.1',
            'name' => 'Pilar Mutu Lulusan',
            'role' => 'WKr / Tim Mutu',
            'route' => 'ketercapaian-profil-lulusan',
        ],
        3 => [
            'order' => 3,
            'code' => '1.2',
            'name' => 'Pilar Kurikulum & Pembelajaran',
            'role' => 'WKr',
            'route' => 'program-kurikulums',
        ],
        4 => [
            'order' => 4,
            'code' => '1.3',
            'name' => 'Pilar PTK (Pendidik dan Tenaga Kependidikan)',
            'role' => 'KS / Kepegawaian',
            'route' => 'teacher-profiles',
        ],
        5 => [
            'order' => 5,
            'code' => '1.4',
            'name' => 'Pilar Kesiswaan',
            'role' => 'WKs',
            'route' => 'program-kesiswaans',
        ],
        6 => [
            'order' => 6,
            'code' => '1.5',
            'name' => 'Pilar Sarpras (Sarana Prasarana)',
            'role' => 'SP',
            'route' => 'program-sarpras',
        ],
        8 => [
            'order' => 8,
            'code' => '1.6',
            'name' => 'Pilar Kehumasan',
            'role' => 'Hm',
            'route' => 'program-humas',
        ],
        9 => [
            'order' => 9,
            'code' => '1.7',
            'name' => 'Pilar Layanan Khusus / BK',
            'role' => 'BK',
            'route' => 'bk-work-programs',
        ],
    ];

    /**
     * Hitung data ketercapaian 9 Pilar secara menyeluruh dan terintegrasi.
     * Hanya 8 pilar yang memiliki persentase; Pilar Keuangan khusus monitoring pembayaran SPP tanpa persentase.
     *
     * @param int|null $institutionId
     * @param User|null $user
     * @return array
     */
    public static function getPillarsData(?int $institutionId = null, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        if (!$institutionId) {
            $institutionId = $user?->institution_id ?? 2; // Default SMP
        }

        // 1. Tentukan kode PIC role pengguna saat ini
        $picCodes = self::getUserPicCodes($user);
        $isKS = in_array('KS', $picCodes) || ($user && $user->hasAnyRole(['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan']));

        // 2. Ambil data master pilar sekolah
        $rawPillars = SchoolPillar::query()
            ->when($institutionId, function ($q) use ($institutionId) {
                $q->where(function ($sub) use ($institutionId) {
                    $sub->where('institution_id', $institutionId)
                        ->orWhereNull('institution_id');
                });
            })
            ->orderBy('order')
            ->with(['tasks' => fn($q) => $q->orderBy('order')])
            ->get();

        // Jika master pilar di database kosong, gunakan struktur default 9 pilar
        if ($rawPillars->isEmpty()) {
            $rawPillars = self::getDefaultPillarsCollection();
        }

        // 3. Hitung progress pilar 2 sampai 9 terlebih dahulu
        $calculatedPillars = [];
        foreach ($rawPillars as $pillar) {
            $order = (int) $pillar->order;
            if ($order === 1) {
                // Pilar 1 akan dihitung belakangan berdasarkan 7 pilar konstituen
                continue;
            }

            $resolved = self::resolveTasksForPillar($pillar, $institutionId, $user);
            $tasks = $resolved['tasks'];
            $total = count($tasks);
            $selesai = collect($tasks)->where('status', 'selesai')->count();

            // Aturan khusus: Pilar Keuangan (Order 7) persentasenya dihilangkan
            if ($order === 7) {
                $percentage = null;
                $hasPercentage = false;
                $pillarName = 'Keuangan (Data SPP Siswa)';
            } else {
                $percentage = isset($resolved['custom_percentage'])
                    ? (int) $resolved['custom_percentage']
                    : ($total > 0 ? (int) round(($selesai / $total) * 100) : 0);
                $hasPercentage = true;
                $pillarName = $pillar->name;
            }

            $calculatedPillars[$order] = [
                'id' => $pillar->id ?? $order,
                'order' => $order,
                'name' => $pillarName,
                'total' => $total,
                'selesai' => $selesai,
                'percentage' => $percentage,
                'has_percentage' => $hasPercentage,
                'tasks' => collect($tasks),
            ];
        }

        // 4. Hitung Pilar 1 (Bidang Manajemen) berdasarkan 7 pilar konstituen
        $pillar1Model = $rawPillars->firstWhere('order', 1);
        $pilar1Data = self::calculatePilarManajemenComposite(
            $pillar1Model,
            $calculatedPillars,
            $institutionId,
            $user
        );

        // 5. Gabungkan seluruh 9 pilar berurutan dari Order 1 sampai 9
        $finalPillars = [];
        $finalPillars[1] = $pilar1Data;
        foreach ($calculatedPillars as $ord => $data) {
            $finalPillars[$ord] = $data;
        }
        ksort($finalPillars);

        // 6. Filter akses jika user non-KS hanya melihat tugas/pilar miliknya
        if (!$isKS && count($picCodes) > 0) {
            $finalPillars = array_filter($finalPillars, function ($pData) use ($picCodes) {
                $hasMatchingTask = collect($pData['tasks'])->contains(function ($task) use ($picCodes) {
                    $assigned = $task->assigned_role ?? '';
                    foreach ($picCodes as $code) {
                        if (str_contains($assigned, $code)) {
                            return true;
                        }
                    }
                    return false;
                });
                return $hasMatchingTask;
            });
        }

        return array_values($finalPillars);
    }

    /**
     * Hitung ketercapaian Pilar 1 (Bidang Manajemen) secara murni dari 7 pilar pendukung.
     */
    protected static function calculatePilarManajemenComposite(
        ?SchoolPillar $pillar1Model,
        array $calculatedPillars,
        int $institutionId,
        ?User $user
    ): array {
        $constituents = self::PILLAR_1_CONSTITUENTS;
        $percentages = [];
        $tasksPilar1 = [];
        $selesaiCount = 0;

        foreach ($constituents as $order => $meta) {
            $subPillar = $calculatedPillars[$order] ?? null;
            $pct = $subPillar ? (int) ($subPillar['percentage'] ?? 0) : 0;
            $percentages[] = $pct;

            $isDone = ($pct >= 100);
            if ($isDone) {
                $selesaiCount++;
            }

            $url = self::resolveUrlForRoute($meta['route']);

            $tasksPilar1[] = (object) [
                'id' => 'composite_pilar_' . $order,
                'code' => $meta['code'],
                'name' => 'Ketercapaian ' . $meta['name'],
                'status' => $isDone ? 'selesai' : 'belum',
                'assigned_role' => $meta['role'],
                'is_sop_merah' => false,
                'desc' => "Ketercapaian: {$pct}% dari target 100% (Diambil langsung dari {$meta['name']})",
                'url' => $url,
                'percentage' => $pct,
            ];
        }

        // Rata-rata ketercapaian dari 7 pilar pendukung.
        // Jika ke-7 pilar mencapai indikator 100, maka Pilar 1 otomatis menjadi 100%.
        $totalConstituents = count($percentages);
        $compositePercentage = $totalConstituents > 0
            ? (int) round(array_sum($percentages) / $totalConstituents)
            : 0;

        // Batasi nilai persentase antara 0 sampai 100
        $compositePercentage = max(0, min(100, $compositePercentage));

        return [
            'id' => $pillar1Model?->id ?? 1,
            'order' => 1,
            'name' => $pillar1Model?->name ?? 'Bidang Manajemen',
            'total' => $totalConstituents, // 7 Indikator Pilar
            'selesai' => $selesaiCount,    // Jumlah pilar yang sudah tuntas 100%
            'percentage' => $compositePercentage,
            'has_percentage' => true,
            'tasks' => collect($tasksPilar1),
            'is_composite' => true,
        ];
    }

    /**
     * Resolusi tugas dan indikator riil untuk pilar 2 hingga 9 sesuai spesifikasi user.
     */
    protected static function resolveTasksForPillar(SchoolPillar $pillar, int $institutionId, ?User $user): array
    {
        $order = (int) $pillar->order;
        $tasks = [];
        $customPercentage = null;

        switch ($order) {
            case 2: // Mutu Lulusan
                // Ketercapaian pilar ini diambil dari 2 fitur:
                // 1. Fitur Nilai Akademik Siswa
                // 2. Fitur Input Prestasi Siswa
                $totalSiswa = Student::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->count();

                // 1. Nilai Akademik Siswa
                $siswaDinilai = Student::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->where(function($q) {
                        $q->whereHas('grades')->orWhereHas('raporGrades');
                    })
                    ->count();

                $hasGrades = Grade::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->exists() || \App\Models\RaporGrade::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->exists();

                $pctAkademik = $totalSiswa > 0 
                    ? min(100, (int) round(($siswaDinilai / $totalSiswa) * 100))
                    : ($hasGrades ? 100 : 0);
                $isAkademikDone = ($pctAkademik >= 100);

                // 2. Input Prestasi Siswa
                $targetPrestasi = max(1, Classroom::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->count());

                $prestasiCount = StudentAchievement::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->count();

                $pctPrestasi = min(100, (int) round(($prestasiCount / $targetPrestasi) * 100));
                $isPrestasiDone = ($pctPrestasi >= 100);

                $tasks = [
                    (object) [
                        'id' => 'mutu_lulusan_akademik',
                        'name' => 'Input Nilai Akademik Siswa',
                        'status' => $isAkademikDone ? 'selesai' : 'belum',
                        'code' => '2.1',
                        'assigned_role' => 'WKr / Guru Mapel',
                        'is_sop_merah' => false,
                        'desc' => "Ketercapaian Nilai Akademik: {$siswaDinilai} dari {$totalSiswa} siswa tuntas ({$pctAkademik}%)",
                        'url' => self::resolveUrlForRoute('grades'),
                        'percentage' => $pctAkademik,
                    ],
                    (object) [
                        'id' => 'mutu_lulusan_prestasi',
                        'name' => 'Input Prestasi Siswa (Akademik & Non-Akademik)',
                        'status' => $isPrestasiDone ? 'selesai' : 'belum',
                        'code' => '2.2',
                        'assigned_role' => 'WKs / WKr',
                        'is_sop_merah' => false,
                        'desc' => "Ketercapaian Prestasi: {$prestasiCount} prestasi tercatat dari target {$targetPrestasi} ({$pctPrestasi}%)",
                        'url' => self::resolveUrlForRoute('student-achievements'),
                        'percentage' => $pctPrestasi,
                    ],
                ];

                $customPercentage = (int) round(($pctAkademik + $pctPrestasi) / 2);
                break;

            case 3: // Bidang Kurikulum
                // Ketercapaian HANYA diambil dari program kerja kurikulum
                $programs = self::filterByInstitution(ProgramKurikulum::query(), $institutionId, 'program_kurikulums')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $isDone = strtolower($m->status_pelaksanaan ?? '') === 'selesai';
                        return (object) [
                            'id' => 'dynamic_kurikulum_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'WKr',
                            'assigned_role' => 'WKr',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kerja Kurikulum | Status: ' . ($m->status_pelaksanaan ?: 'Rencana') . ' | Sifat: ' . ($m->sifat ?? 'Wajib'),
                            'url' => self::resolveUrlForRoute('program-kurikulums'),
                        ];
                    })->all();
                } else {
                    $tasks = [];
                    $customPercentage = 0;
                }
                break;

            case 4: // PTK (Pendidik dan Tenaga Kependidikan)
                // Ketercapaian pilar ini diambil dari 2 fitur:
                // 1. Fitur Input Sertifikat Guru
                // 2. Fitur Input Profil / Data Guru
                $teachers = User::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->whereHas('roles', fn($q) => $q->whereNotIn('name', ['siswa', 'orang_tua']))
                    ->get();
                $totalGuru = $teachers->count();

                // 1. Input Profil / Data Guru
                $guruProfilLengkap = $teachers->filter(function($u) {
                    return !empty($u->name) && !empty($u->email) && (!empty($u->nuptk) || !empty($u->no_hp) || !empty($u->phone) || !empty($u->nip) || !empty($u->nik));
                })->count();
                $pctProfil = $totalGuru > 0 
                    ? min(100, (int) round(($guruProfilLengkap / $totalGuru) * 100))
                    : 0;
                $isProfilDone = ($pctProfil >= 100);

                // 2. Input Sertifikat Guru
                $guruPunyaSertifikat = TeacherCertificate::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->distinct('teacher_id')
                    ->count('teacher_id');
                $totalSertifikat = TeacherCertificate::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->count();

                $pctSertifikat = $totalGuru > 0 
                    ? min(100, (int) round(($guruPunyaSertifikat / $totalGuru) * 100))
                    : ($totalSertifikat > 0 ? 100 : 0);
                $isSertifikatDone = ($pctSertifikat >= 100);

                $tasks = [
                    (object) [
                        'id' => 'ptk_profil_guru',
                        'name' => 'Input Profil & Data Induk Guru',
                        'status' => $isProfilDone ? 'selesai' : 'belum',
                        'code' => '4.1',
                        'assigned_role' => 'KS / TU',
                        'is_sop_merah' => false,
                        'desc' => "Kelengkapan Profil: {$guruProfilLengkap} dari {$totalGuru} guru lengkap ({$pctProfil}%)",
                        'url' => self::resolveUrlForRoute('teacher-profiles'),
                        'percentage' => $pctProfil,
                    ],
                    (object) [
                        'id' => 'ptk_sertifikat_guru',
                        'name' => 'Input Sertifikat Guru & Pelatihan',
                        'status' => $isSertifikatDone ? 'selesai' : 'belum',
                        'code' => '4.2',
                        'assigned_role' => 'KS / Guru',
                        'is_sop_merah' => false,
                        'desc' => "Input Sertifikat: {$totalSertifikat} sertifikat ({$guruPunyaSertifikat} dari {$totalGuru} guru, {$pctSertifikat}%)",
                        'url' => self::resolveUrlForRoute('teacher-certificates'),
                        'percentage' => $pctSertifikat,
                    ],
                ];

                $customPercentage = (int) round(($pctProfil + $pctSertifikat) / 2);
                break;

            case 5: // Kesiswaan
                // Ketercapaian HANYA diambil dari program kerja kesiswaan
                $programs = self::filterByInstitution(ProgramKesiswaan::query(), $institutionId, 'program_kesiswaans')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $isDone = strtolower($m->status_pelaksanaan ?? '') === 'selesai';
                        return (object) [
                            'id' => 'dynamic_kesiswaan_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'WKs',
                            'assigned_role' => 'WKs',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kerja Kesiswaan | Status: ' . ($m->status_pelaksanaan ?: 'Rencana') . ' | Sifat: ' . ($m->sifat ?? 'Wajib'),
                            'url' => self::resolveUrlForRoute('program-kesiswaans'),
                        ];
                    })->all();
                } else {
                    $tasks = [];
                    $customPercentage = 0;
                }
                break;

            case 6: // Sarpras
                // Ketercapaian HANYA diambil dari program kerja sarpras
                $programs = self::filterByInstitution(ProgramSarpras::query(), $institutionId, 'program_sarpras')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $isDone = strtolower($m->status_pelaksanaan ?? '') === 'selesai';
                        return (object) [
                            'id' => 'dynamic_sarpras_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'SP',
                            'assigned_role' => 'SP',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kerja Sarpras | Status: ' . ($m->status_pelaksanaan ?: 'Rencana') . ' | Sifat: ' . ($m->sifat ?? 'Wajib'),
                            'url' => self::resolveUrlForRoute('program-sarpras'),
                        ];
                    })->all();
                } else {
                    $tasks = [];
                    $customPercentage = 0;
                }
                break;

            case 7: // Keuangan & SPP Siswa
                // Sesuai permintaan user: persentase DIHILANGKAN.
                // Ketika diklik, menampilkan data siswa yang sudah membayar SPP atau belum per bulannya.
                $students = Student::query()
                    ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                    ->with(['classroom', 'sppPayments'])
                    ->orderBy('name')
                    ->get();

                $currentMonthName = Carbon::now()->translatedFormat('F');
                $totalSiswa = $students->count();
                $lunasCount = $students->filter(fn($st) => (bool) $st->is_administrasi_lunas)->count();
                $belumLunasCount = max(0, $totalSiswa - $lunasCount);

                $tasks = [];
                // Ringkasan SPP per bulan berjalan
                $tasks[] = (object) [
                    'id' => 'spp_summary_current_month',
                    'name' => "Rekapitulasi SPP Bulan {$currentMonthName}",
                    'status' => $belumLunasCount === 0 && $totalSiswa > 0 ? 'selesai' : 'belum',
                    'code' => 'SPP',
                    'assigned_role' => 'TU / Keuangan',
                    'is_sop_merah' => false,
                    'desc' => "Status: {$lunasCount} Lunas, {$belumLunasCount} Belum Lunas (Total: {$totalSiswa} Siswa)",
                    'url' => self::resolveUrlForRoute('students'),
                ];

                // Daftar status pembayaran SPP per siswa
                foreach ($students as $st) {
                    $isLunas = (bool) $st->is_administrasi_lunas;
                    $tasks[] = (object) [
                        'id' => 'spp_student_' . $st->id,
                        'name' => $st->name . ' (' . ($st->classroom?->name ?? 'Tanpa Kelas') . ')',
                        'status' => $isLunas ? 'selesai' : 'belum',
                        'code' => 'NIS: ' . ($st->nis ?: '-'),
                        'assigned_role' => 'TU',
                        'is_sop_merah' => !$isLunas,
                        'desc' => $isLunas 
                            ? "Pembayaran SPP Bulan {$currentMonthName}: Lunas" 
                            : "Pembayaran SPP Bulan {$currentMonthName}: Belum Membayar",
                        'url' => self::resolveUrlForRoute('students'),
                    ];
                }
                break;

            case 8: // Humas
                // Ketercapaian HANYA diambil dari program kerja humas
                $programs = self::filterByInstitution(ProgramHumas::query(), $institutionId, 'program_humas')->get();
                if ($programs->isEmpty()) {
                    $programs = self::filterByInstitution(HumasProgram::query(), $institutionId, 'humas_programs')->get();
                }

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $isDone = false;
                        if (isset($m->status_pelaksanaan)) {
                            $isDone = strtolower($m->status_pelaksanaan) === 'selesai';
                        } elseif (isset($m->status)) {
                            $isDone = in_array(strtolower($m->status), ['sudah', 'selesai']);
                        }
                        return (object) [
                            'id' => 'dynamic_humas_' . $m->id,
                            'name' => $m->nama_program ?? ($m->nama ?? 'Program Kerja Humas'),
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'Hm',
                            'assigned_role' => 'Hm',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kerja Humas | Sinergi Sekolah, Wali Murid & Masyarakat',
                            'url' => self::resolveUrlForRoute('program-humas'),
                        ];
                    })->all();
                } else {
                    $tasks = [];
                    $customPercentage = 0;
                }
                break;

            case 9: // Layanan Khusus / BK
                // Ketercapaian HANYA diambil dari program kerja BK
                $programs = self::filterByInstitution(BkWorkProgram::query(), $institutionId, 'bk_work_programs')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $statusStr = strtolower($m->status ?? '');
                        $isDone = in_array($statusStr, ['terlaksana', 'selesai']);
                        return (object) [
                            'id' => 'dynamic_bk_' . $m->id,
                            'name' => $m->judul_program ?? 'Program Kerja BK',
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'BK',
                            'assigned_role' => 'BK',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kerja BK | Sasaran: ' . ($m->sasaran ?? 'Siswa') . ' | Bidang: ' . ($m->bidang_layanan ?? 'Umum'),
                            'url' => self::resolveUrlForRoute('bk-work-programs'),
                        ];
                    })->all();
                } else {
                    $tasks = [];
                    $customPercentage = 0;
                }
                break;

            default:
                $tasks = self::mapStaticTasks($pillar->tasks ?? collect(), 'KS', '#');
                break;
        }

        return [
            'tasks' => $tasks,
            'custom_percentage' => $customPercentage,
        ];
    }

    /**
     * Map koleksi tugas statis dari database menjadi format konsisten.
     */
    protected static function mapStaticTasks(Collection $staticTasks, string $defaultRole, string $routeKey): array
    {
        if ($staticTasks->isEmpty()) {
            return [];
        }

        $url = self::resolveUrlForRoute($routeKey);

        return $staticTasks->map(function ($t) use ($defaultRole, $url) {
            return (object) [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'code' => $t->code ?: 'SOP',
                'assigned_role' => $t->assigned_role ?: $defaultRole,
                'is_sop_merah' => (bool) $t->is_sop_merah,
                'desc' => $t->catatan ?: 'Tugas Standar SOP Yayasan & Sekolah',
                'url' => $url,
            ];
        })->all();
    }

    /**
     * Ambil URL halaman/resource secara aman (aman dari exception).
     */
    public static function resolveUrlForRoute(string $routeKey): string
    {
        return rescue(function () use ($routeKey) {
            return match ($routeKey) {
                'ketercapaian-profil-lulusan' => \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl(),
                'grades' => \App\Filament\Staff\Resources\GradeResource::getUrl('index'),
                'student-achievements' => \App\Filament\Staff\Resources\StudentAchievementResource::getUrl('index'),
                'teacher-profiles' => \App\Filament\Staff\Resources\TeacherProfileResource::getUrl('index'),
                'teacher-certificates' => \App\Filament\Staff\Resources\TeacherCertificateResource::getUrl('index'),
                'program-kurikulums' => \App\Filament\Staff\Resources\ProgramKurikulumResource::getUrl('index'),
                'program-kesiswaans' => \App\Filament\Staff\Resources\ProgramKesiswaanResource::getUrl('index'),
                'program-sarpras' => \App\Filament\Staff\Resources\ProgramSarprasResource::getUrl('index'),
                'program-humas' => \App\Filament\Staff\Resources\ProgramHumasResource::getUrl('index'),
                'bk-work-programs' => \App\Filament\Staff\Resources\BkWorkProgramResource::getUrl('index'),
                'supervisi-guru' => \App\Filament\Staff\Resources\TeacherSupervisionResource::getUrl('index'),
                'students' => \App\Filament\Staff\Resources\StudentResource::getUrl('index'),
                default => '#',
            };
        }, '#');
    }

    /**
     * Dapatkan kode PIC per role user.
     */
    public static function getUserPicCodes(?User $user): array
    {
        if (!$user) {
            return ['KS'];
        }

        $codes = [];
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) $codes[] = 'KS';
        if ($user->hasRole('waka_kurikulum')) $codes[] = 'WKr';
        if ($user->hasRole('waka_kesiswaan')) $codes[] = 'WKs';
        if ($user->hasRole('sarpras')) $codes[] = 'SP';
        if ($user->hasRole('humas')) $codes[] = 'Hm';
        if ($user->hasRole('bk')) $codes[] = 'BK';
        if ($user->hasRole('tata_usaha')) $codes[] = 'TU';

        return $codes;
    }

    /**
     * Dapatkan persentase ketercapaian global.
     * Hanya menghitung 8 pilar yang memiliki persentase (Pilar Keuangan tidak dihitung).
     */
    public static function getOverallProgress(?int $institutionId = null, ?User $user = null): int
    {
        $pillars = self::getPillarsData($institutionId, $user);
        if (empty($pillars)) {
            return 0;
        }

        // Filter hanya pilar yang memiliki persentase resmi (8 pilar)
        $pillarsWithPct = array_filter($pillars, fn($p) => isset($p['has_percentage']) && $p['has_percentage'] === true && $p['percentage'] !== null);
        if (empty($pillarsWithPct)) {
            return 0;
        }

        $totalPercentages = array_sum(array_column($pillarsWithPct, 'percentage'));
        return (int) round($totalPercentages / count($pillarsWithPct));
    }

    /**
     * Filter query berdasarkan lembaga secara aman (kolom institution_id atau relasi user).
     */
    protected static function filterByInstitution($query, int $institutionId, string $table)
    {
        if (Schema::hasColumn($table, 'institution_id')) {
            return $query->where($table . '.institution_id', $institutionId);
        }

        if (Schema::hasColumn($table, 'user_id')) {
            return $query->whereHas('user', function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            });
        }

        return $query;
    }

    /**
     * Koleksi fallback jika database tabel master pilar belum terisi.
     */
    protected static function getDefaultPillarsCollection(): Collection
    {
        $definitions = [
            1 => 'Bidang Manajemen',
            2 => 'Mutu Lulusan',
            3 => 'Bidang Kurikulum dan Pembelajaran',
            4 => 'PTK (Pendidik dan Tenaga Kependidikan)',
            5 => 'Kesiswaan',
            6 => 'Sarpras (Sarana Prasarana)',
            7 => 'Keuangan',
            8 => 'Kehumasan',
            9 => 'Layanan Khusus Sekolah',
        ];

        return collect($definitions)->map(function ($name, $order) {
            $p = new SchoolPillar();
            $p->id = $order;
            $p->order = $order;
            $p->name = $name;
            $p->setRelation('tasks', collect());
            return $p;
        });
    }
}
