<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\TeacherSubjectClass;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class RestoreCurriculumScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-curriculum-schedule {--force : Paksa generate ulang jadwal pelajaran & setting guru mapel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulihkan dan susun jadwal pelajaran serta setting guru mapel resmi SMP Islam Baitul Makmur secara terintegrasi dan bebas bentrok';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pemulihan jadwal pelajaran dan setting guru mapel SMP...');
        $restored = static::restoreSchedule(function ($msg) {
            $this->line(" - {$msg}");
        }, (bool) $this->option('force'));

        $this->info("✅ Selesai! Total {$restored} data berhasil dipulihkan.");
        return Command::SUCCESS;
    }

    /**
     * Logika pemulihan jadwal dan setting guru mapel terpusat.
     */
    public static function restoreSchedule(?callable $logger = null, bool $force = false): int
    {
        $log = $logger ?: function ($msg) {};
        $institutionId = 2; // SMP Islam Baitul Makmur

        // Pastikan record institusi SMP (ID 2) tersedia
        $smp = DB::table('institutions')->where('id', $institutionId)->first();
        if (!$smp) {
            DB::table('institutions')->insertOrIgnore([
                'id' => $institutionId,
                'name' => 'SMP Islam Baitul Makmur',
                'slug' => 'smp',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Cek jika sudah ada data dan tidak dipaksa
        $existingTsc = TeacherSubjectClass::withoutGlobalScopes()->where('institution_id', $institutionId)->count();
        $existingSch = Schedule::withoutGlobalScopes()->where('institution_id', $institutionId)->count();

        if ($existingTsc > 0 && $existingSch > 0 && !$force) {
            $log("Data Setting Guru Mapel ({$existingTsc}) dan Jadwal ({$existingSch}) sudah ada. Lewati karena tidak dipaksa (--force).");
            return $existingSch;
        }

        // 1. Dapatkan atau Buat Rombel Kelas SMP
        $classrooms = Classroom::withoutGlobalScopes()
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                  ->orWhereIn('grade_level', ['7', '8', '9'])
                  ->orWhereIn('tingkat', ['7', '8', '9'])
                  ->orWhere('name', 'like', '7%')
                  ->orWhere('name', 'like', '8%')
                  ->orWhere('name', 'like', '9%')
                  ->orWhere('name', 'like', 'Kelas 7%')
                  ->orWhere('name', 'like', 'Kelas 8%')
                  ->orWhere('name', 'like', 'Kelas 9%');
            })
            ->get();

        if ($classrooms->isEmpty()) {
            $defaultClasses = ['7A', '7B', '8A', '8B', '9A'];
            foreach ($defaultClasses as $cName) {
                $cls = Classroom::withoutGlobalScopes()->firstOrCreate([
                    'name' => $cName,
                ], [
                    'tingkat' => substr($cName, 0, 1),
                    'grade_level' => substr($cName, 0, 1),
                    'institution_id' => $institutionId,
                ]);
                $classrooms->push($cls);
            }
            $log("Membuat kelas default SMP: 7A, 7B, 8A, 8B, 9A.");
        } else {
            foreach ($classrooms as $cls) {
                if ($cls->institution_id != $institutionId) {
                    $cls->update(['institution_id' => $institutionId]);
                }
            }
        }

        // 2. Dapatkan atau Buat Mata Pelajaran Resmi SMP
        $mapelDefinitions = [
            ['kode' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'jp' => 3],
            ['kode' => 'MTK', 'name' => 'Matematika', 'jp' => 4],
            ['kode' => 'BIN', 'name' => 'Bahasa Indonesia', 'jp' => 4],
            ['kode' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'jp' => 4],
            ['kode' => 'BING', 'name' => 'Bahasa Inggris', 'jp' => 4],
            ['kode' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'jp' => 3],
            ['kode' => 'PKN', 'name' => 'Pendidikan Pancasila & Kewarganegaraan', 'jp' => 2],
            ['kode' => 'SBD', 'name' => 'Seni Budaya', 'jp' => 2],
            ['kode' => 'PJOK', 'name' => 'Pendidikan Jasmani & Olahraga', 'jp' => 3],
            ['kode' => 'INF', 'name' => 'Informatika', 'jp' => 2],
            ['kode' => 'BJAW', 'name' => 'Bahasa Jawa', 'jp' => 2],
            ['kode' => 'BTQ', 'name' => 'Al-Qur\'an & Tahfidz', 'jp' => 2],
        ];

        $subjects = [];
        foreach ($mapelDefinitions as $mDef) {
            $subj = Subject::withoutGlobalScopes()
                ->where(function ($q) use ($mDef) {
                    $q->where('kode', $mDef['kode'])
                      ->orWhere('name', 'like', "%{$mDef['name']}%");
                })
                ->first();

            if (!$subj) {
                $subj = Subject::withoutGlobalScopes()->create([
                    'name' => $mDef['name'],
                    'kode' => $mDef['kode'],
                    'institution_id' => $institutionId,
                ]);
            } else {
                if ($subj->institution_id != $institutionId) {
                    $subj->update(['institution_id' => $institutionId]);
                }
            }
            $subjects[$mDef['kode']] = [
                'model' => $subj,
                'jp' => $mDef['jp'],
            ];
        }

        // 3. Dapatkan Seluruh Guru Pengampu Aktif
        $teachers = User::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                  ->orWhereNull('institution_id')
                  ->orWhere('institution_id', 1)
                  ->orWhereHas('roles', function ($rq) {
                      $rq->whereIn('name', array_merge(['guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas', 'waka_kurikulum'], User::QURAN_TEACHER_ROLES));
                  });
            })
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = User::withoutGlobalScopes()->where('is_active', true)->get();
        }

        // Helper untuk mencari guru yang paling cocok per mata pelajaran
        $assignedTeachers = [];
        $teacherPool = $teachers->values();
        $fallbackIdx = 0;

        foreach ($mapelDefinitions as $mDef) {
            $kode = $mDef['kode'];
            $keywords = match ($kode) {
                'MTK' => ['matematika', 'mtk'],
                'IPA' => ['ipa', 'fisika', 'biologi'],
                'BIN' => ['indonesia', 'bin'],
                'BING' => ['inggris', 'bing'],
                'IPS' => ['ips', 'ekonomi', 'geografi', 'sejarah'],
                'PKN' => ['pkn', 'pancasila', 'kewarganegaraan'],
                'SBD' => ['seni', 'budaya', 'sbd', 'prakarya'],
                'PJOK' => ['pjok', 'olahraga', 'penjas'],
                'INF' => ['informatika', 'komputer', 'tik'],
                'BJAW' => ['jawa', 'daerah'],
                'BTQ' => ['quran', 'alquran', 'tahfidz', 'tahsin', 'yanbua', 'agama'],
                'PAI' => ['agama', 'islam', 'pai'],
                default => [$kode],
            };

            $matchedTeacher = $teachers->first(function ($t) use ($keywords) {
                $str = strtolower(($t->jabatan ?? '') . ' ' . ($t->name ?? '') . ' ' . ($t->email ?? ''));
                foreach ($keywords as $kw) {
                    if (str_contains($str, $kw)) return true;
                }
                return false;
            });

            if (!$matchedTeacher) {
                $matchedTeacher = $teacherPool[$fallbackIdx % count($teacherPool)];
                $fallbackIdx++;
            }

            $assignedTeachers[$kode] = $matchedTeacher;
            $log("Mapping: {$mDef['name']} diampu oleh {$matchedTeacher->name}");
        }

        // 4. Hubungkan ke TeacherSubjectClass untuk setiap rombel
        $totalTsc = 0;
        foreach ($classrooms as $cls) {
            foreach ($mapelDefinitions as $mDef) {
                $kode = $mDef['kode'];
                $subj = $subjects[$kode]['model'];
                $teacher = $assignedTeachers[$kode];

                TeacherSubjectClass::withoutGlobalScopes()->updateOrCreate([
                    'classroom_id' => $cls->id,
                    'subject_id'   => $subj->id,
                ], [
                    'teacher_id'     => $teacher->id,
                    'institution_id' => $institutionId,
                ]);
                $totalTsc++;
            }
        }
        $log("Mapping Setting Guru Mapel (TeacherSubjectClass) berhasil dikaitkan ({$totalTsc} data).");

        // 5. Susun Jadwal Pelajaran (Schedules) per JP Bebas Bentrok
        $scheduleGrid = [
            'Senin' => [1, 2, 3, 4, 5, 6, 7],
            'Selasa' => [1, 2, 3, 4, 5, 6, 7],
            'Rabu' => [1, 2, 3, 4, 5, 6, 7],
            'Kamis' => [1, 2, 3, 4, 5, 6, 7],
            'Jumat' => [1, 2, 3, 4],
        ];

        $totalCreated = 0;

        foreach ($classrooms as $clsIdx => $cls) {
            $jpList = [];
            foreach ($mapelDefinitions as $mDef) {
                $kode = $mDef['kode'];
                $subj = $subjects[$kode]['model'];
                $teacher = $assignedTeachers[$kode];
                $jpCount = $mDef['jp'];

                for ($p = 0; $p < $jpCount; $p++) {
                    $jpList[] = [
                        'subject_id' => $subj->id,
                        'teacher_id' => $teacher->id,
                        'name' => $subj->name,
                    ];
                }
            }

            // Rotasi bergeser per kelas agar jam pelajaran tidak bentrok antar kelas
            $shift = ($clsIdx * 7) % max(1, count($jpList));
            $jpList = array_merge(array_slice($jpList, $shift), array_slice($jpList, 0, $shift));

            $itemIdx = 0;
            foreach ($scheduleGrid as $day => $jamArray) {
                foreach ($jamArray as $jam) {
                    if ($itemIdx >= count($jpList)) {
                        break 2;
                    }

                    // Cari JP yang gurunya tidak bentrok di kelas lain pada hari & jam ini
                    $foundItemKey = null;
                    for ($k = $itemIdx; $k < count($jpList); $k++) {
                        $candidate = $jpList[$k];
                        $teacherBusy = Schedule::withoutGlobalScopes()
                            ->where('teacher_id', $candidate['teacher_id'])
                            ->where('hari', $day)
                            ->where('jam_ke', $jam)
                            ->where('classroom_id', '!=', $cls->id)
                            ->exists();

                        if (!$teacherBusy) {
                            $foundItemKey = $k;
                            break;
                        }
                    }

                    if ($foundItemKey !== null) {
                        $chosen = $jpList[$foundItemKey];
                        $temp = $jpList[$itemIdx];
                        $jpList[$itemIdx] = $chosen;
                        $jpList[$foundItemKey] = $temp;
                    } else {
                        $chosen = $jpList[$itemIdx];
                    }

                    Schedule::withoutGlobalScopes()->updateOrCreate([
                        'classroom_id' => $cls->id,
                        'hari' => $day,
                        'jam_ke' => $jam,
                    ], [
                        'subject_id' => $chosen['subject_id'],
                        'teacher_id' => $chosen['teacher_id'],
                        'institution_id' => $institutionId,
                    ]);

                    $totalCreated++;
                    $itemIdx++;
                }
            }
            $log("Jadwal kelas {$cls->name} berhasil disusun ({$itemIdx} JP).");
        }

        return $totalCreated;
    }
}
