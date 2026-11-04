<?php

namespace App\Services;

use App\Models\BkWorkProgram;
use App\Models\HumasProgram;
use App\Models\Institution;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use App\Models\Student;
use App\Models\TeacherSupervision;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SchoolPillarService
{
    /**
     * Daftar 7 Pilar Konstituen yang menentukan ketercapaian Pilar 1 (Bidang Manajemen).
     * Sesuai ketentuan: Mutu Lulusan (2), Kurikulum (3), PTK (4), Kesiswaan (5),
     * Sarpras (6), Humas (8), dan BK (9).
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
            'route' => 'supervisi-guru',
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

            $tasks = self::resolveTasksForPillar($pillar, $institutionId, $user);
            $total = count($tasks);
            $selesai = collect($tasks)->where('status', 'selesai')->count();
            $percentage = $total > 0 ? (int) round(($selesai / $total) * 100) : 0;

            $calculatedPillars[$order] = [
                'id' => $pillar->id ?? $order,
                'order' => $order,
                'name' => $pillar->name,
                'total' => $total,
                'selesai' => $selesai,
                'percentage' => $percentage,
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
            $pct = $subPillar ? (int) $subPillar['percentage'] : 0;
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
            'tasks' => collect($tasksPilar1),
            'is_composite' => true,
        ];
    }

    /**
     * Resolusi tugas dan indikator riil untuk pilar 2 hingga 9.
     */
    protected static function resolveTasksForPillar(SchoolPillar $pillar, int $institutionId, ?User $user): array
    {
        $order = (int) $pillar->order;
        $tasks = [];

        switch ($order) {
            case 2: // Mutu Lulusan
                // Hubungkan dengan tugas mutu lulusan dan penilaian profil
                $staticTasks = $pillar->tasks ?? collect();
                if ($staticTasks->isNotEmpty()) {
                    $tasks = $staticTasks->map(function ($t) {
                        return (object) [
                            'id' => $t->id,
                            'name' => $t->name,
                            'status' => $t->status,
                            'code' => $t->code ?: '2.0',
                            'assigned_role' => $t->assigned_role ?: 'WKr',
                            'is_sop_merah' => (bool) $t->is_sop_merah,
                            'desc' => 'Mutu Lulusan | Evaluasi Profil & Standar Kompetensi Siswa',
                            'url' => self::resolveUrlForRoute('ketercapaian-profil-lulusan'),
                        ];
                    })->all();
                } else {
                    $tasks = [
                        (object) [
                            'id' => 'p2_task_1',
                            'name' => 'Profil atau mutu lulusan siswa',
                            'status' => 'belum',
                            'code' => '2.1',
                            'assigned_role' => 'WKr',
                            'is_sop_merah' => false,
                            'desc' => 'Standar capaian lulusan akademik, tahfidz, dan adab karakter',
                            'url' => self::resolveUrlForRoute('ketercapaian-profil-lulusan'),
                        ],
                        (object) [
                            'id' => 'p2_task_2',
                            'name' => 'Penilaian ketercapaian profil lulusan',
                            'status' => 'belum',
                            'code' => '2.2',
                            'assigned_role' => 'WKr',
                            'is_sop_merah' => false,
                            'desc' => 'Rekapitulasi penilaian komprehensif profil lulusan seluruh kelas',
                            'url' => self::resolveUrlForRoute('ketercapaian-profil-lulusan'),
                        ],
                        (object) [
                            'id' => 'p2_task_3',
                            'name' => 'SOP penilaian profil atau mutu lulusan',
                            'status' => 'belum',
                            'code' => '2.3',
                            'assigned_role' => 'KS',
                            'is_sop_merah' => true,
                            'desc' => 'Standar operasional prosedur penjaminan mutu kelulusan',
                            'url' => self::resolveUrlForRoute('ketercapaian-profil-lulusan'),
                        ],
                    ];
                }
                break;

            case 3: // Kurikulum & Pembelajaran
                $programs = self::filterByInstitution(ProgramKurikulum::query(), $institutionId, 'program_kurikulums')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        return (object) [
                            'id' => 'dynamic_kurikulum_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => strtolower($m->status_pelaksanaan) === 'selesai' ? 'selesai' : 'belum',
                            'code' => 'WKr',
                            'assigned_role' => 'WKr',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kurikulum | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                            'url' => self::resolveUrlForRoute('program-kurikulums'),
                        ];
                    })->all();
                } else {
                    $tasks = self::mapStaticTasks($pillar->tasks, 'WKr', 'program-kurikulums');
                }
                break;

            case 4: // PTK (Pendidik dan Tenaga Kependidikan)
                $staticTasks = $pillar->tasks ?? collect();
                if ($staticTasks->isNotEmpty()) {
                    $tasks = self::mapStaticTasks($staticTasks, 'KS', 'supervisi-guru');
                } else {
                    // Fallback tugas standar PTK
                    $tasks = [
                        (object) ['id' => 'p4_1', 'name' => 'Rekrutmen & Pemetaan PTK', 'status' => 'belum', 'code' => '4.1', 'assigned_role' => 'KS', 'is_sop_merah' => false, 'desc' => 'Perencanaan kebutuhan tenaga pendidik dan kependidikan', 'url' => self::resolveUrlForRoute('supervisi-guru')],
                        (object) ['id' => 'p4_2', 'name' => 'Penempatan & Beban Jam PTK', 'status' => 'belum', 'code' => '4.2', 'assigned_role' => 'KS', 'is_sop_merah' => false, 'desc' => 'Distribusi jadwal dan tugas pokok guru/karyawan', 'url' => self::resolveUrlForRoute('supervisi-guru')],
                        (object) ['id' => 'p4_3', 'name' => 'Pembinaan dan Peningkatan Kompetensi PTK', 'status' => 'belum', 'code' => '4.3', 'assigned_role' => 'KS', 'is_sop_merah' => false, 'desc' => 'Pelatihan berkala, workshop pedagogik, dan keislaman', 'url' => self::resolveUrlForRoute('supervisi-guru')],
                        (object) ['id' => 'p4_4', 'name' => 'Supervisi Akademik & Penilaian Kinerja Guru', 'status' => 'belum', 'code' => '4.4', 'assigned_role' => 'KS', 'is_sop_merah' => false, 'desc' => 'Observasi pembelajaran kelas dan evaluasi pedagogik', 'url' => self::resolveUrlForRoute('supervisi-guru')],
                    ];
                }
                break;

            case 5: // Kesiswaan
                $programs = self::filterByInstitution(ProgramKesiswaan::query(), $institutionId, 'program_kesiswaans')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        return (object) [
                            'id' => 'dynamic_kesiswaan_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => strtolower($m->status_pelaksanaan) === 'selesai' ? 'selesai' : 'belum',
                            'code' => 'WKs',
                            'assigned_role' => 'WKs',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kesiswaan | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                            'url' => self::resolveUrlForRoute('program-kesiswaans'),
                        ];
                    })->all();
                } else {
                    $tasks = self::mapStaticTasks($pillar->tasks, 'WKs', 'program-kesiswaans');
                }
                break;

            case 6: // Sarpras
                $programs = self::filterByInstitution(ProgramSarpras::query(), $institutionId, 'program_sarpras')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        return (object) [
                            'id' => 'dynamic_sarpras_' . $m->id,
                            'name' => $m->nama_program,
                            'status' => strtolower($m->status_pelaksanaan) === 'selesai' ? 'selesai' : 'belum',
                            'code' => 'SP',
                            'assigned_role' => 'SP',
                            'is_sop_merah' => false,
                            'desc' => 'Program Sarpras | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                            'url' => self::resolveUrlForRoute('program-sarpras'),
                        ];
                    })->all();
                } else {
                    $tasks = self::mapStaticTasks($pillar->tasks, 'SP', 'program-sarpras');
                }
                break;

            case 7: // Keuangan
                $staticTasks = $pillar->tasks ?? collect();
                if ($staticTasks->isNotEmpty()) {
                    $tasks = self::mapStaticTasks($staticTasks, 'TU', 'students');
                } else {
                    $tasks = [
                        (object) [
                            'id' => 'p7_task_1',
                            'name' => 'Input data status murid sudah membayar atau belum',
                            'status' => 'belum',
                            'code' => '7.1',
                            'assigned_role' => 'TU',
                            'is_sop_merah' => false,
                            'desc' => 'Rekapitulasi administrasi keuangan dan SPP siswa',
                            'url' => self::resolveUrlForRoute('students'),
                        ],
                    ];
                }
                break;

            case 8: // Humas
                $programs = self::filterByInstitution(ProgramHumas::query(), $institutionId, 'program_humas')->get();

                if ($programs->isEmpty()) {
                    // Coba periksa model HumasProgram alternatif
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
                            'name' => $m->nama_program ?? ($m->nama ?? 'Program Humas'),
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'Hm',
                            'assigned_role' => 'Hm',
                            'is_sop_merah' => false,
                            'desc' => 'Program Kehumasan | Sinergi Sekolah, Wali Murid & Masyarakat',
                            'url' => self::resolveUrlForRoute('program-humas'),
                        ];
                    })->all();
                } else {
                    $tasks = self::mapStaticTasks($pillar->tasks, 'Hm', 'program-humas');
                }
                break;

            case 9: // Layanan Khusus / BK
                $programs = self::filterByInstitution(BkWorkProgram::query(), $institutionId, 'bk_work_programs')->get();

                if ($programs->isNotEmpty()) {
                    $tasks = $programs->map(function ($m) {
                        $statusStr = strtolower($m->status ?? '');
                        $isDone = in_array($statusStr, ['terlaksana', 'selesai']);
                        return (object) [
                            'id' => 'dynamic_bk_' . $m->id,
                            'name' => $m->judul_program ?? 'Layanan Konseling / BK',
                            'status' => $isDone ? 'selesai' : 'belum',
                            'code' => 'BK',
                            'assigned_role' => 'BK',
                            'is_sop_merah' => false,
                            'desc' => 'Layanan Khusus / BK | Bidang: ' . ($m->bidang_layanan ?? 'Umum') . ' | Sasaran: ' . ($m->sasaran ?? 'Siswa'),
                            'url' => self::resolveUrlForRoute('bk-work-programs'),
                        ];
                    })->all();
                } else {
                    $tasks = self::mapStaticTasks($pillar->tasks, 'BK', 'bk-work-programs');
                }
                break;

            default:
                $tasks = self::mapStaticTasks($pillar->tasks ?? collect(), 'KS', '#');
                break;
        }

        return $tasks;
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
    protected static function resolveUrlForRoute(string $routeKey): string
    {
        return rescue(function () use ($routeKey) {
            return match ($routeKey) {
                'ketercapaian-profil-lulusan' => \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl(),
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
     * Dapatkan persentase ketercapaian global (rata-rata keseluruhan pilar).
     */
    public static function getOverallProgress(?int $institutionId = null, ?User $user = null): int
    {
        $pillars = self::getPillarsData($institutionId, $user);
        if (empty($pillars)) {
            return 0;
        }

        $totalPercentages = array_sum(array_column($pillars, 'percentage'));
        return (int) round($totalPercentages / count($pillars));
    }

    /**
     * Filter query berdasarkan lembaga secara aman (kolom institution_id atau relasi user).
     */
    protected static function filterByInstitution($query, int $institutionId, string $table)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'institution_id')) {
            return $query->where($table . '.institution_id', $institutionId);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'user_id')) {
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
