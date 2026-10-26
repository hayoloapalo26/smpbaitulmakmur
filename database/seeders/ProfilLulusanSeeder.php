<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\TeacherSubjectClass;
use App\Models\Grade;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\StudentAchievement;
use Carbon\Carbon;

class ProfilLulusanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tahun Ajaran
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'Ganjil',
                'is_active' => true,
            ]
        );
        $academicYear->update(['is_active' => true]);

        // 2. Daftar Mata Pelajaran SMP
        $subjectList = [
            ['name' => 'Bahasa Inggris', 'kode' => 'ING'],
            ['name' => 'Bahasa Indonesia', 'kode' => 'IND'],
            ['name' => 'Informatika', 'kode' => 'INF'],
            ['name' => 'Matematika', 'kode' => 'MTK'],
            ['name' => 'Ilmu Pengetahuan Alam (IPA)', 'kode' => 'IPA'],
            ['name' => 'Ilmu Pengetahuan Sosial (IPS)', 'kode' => 'IPS'],
            ['name' => 'Pendidikan Agama Islam (PAI)', 'kode' => 'PAI'],
            ['name' => 'Pendidikan Pancasila & Kewarganegaraan (PPKn)', 'kode' => 'PKN'],
            ['name' => 'Pendidikan Jasmani & Kesehatan (PJOK)', 'kode' => 'PJK'],
            ['name' => 'Seni Budaya & Prakarya', 'kode' => 'SBD'],
            ['name' => 'Bahasa Arab', 'kode' => 'ARB'],
        ];

        $createdSubjects = [];
        foreach ($subjectList as $sub) {
            $createdSubjects[$sub['kode']] = Subject::firstOrCreate(
                ['kode' => $sub['kode']],
                [
                    'name' => $sub['name'],
                    'institution_id' => 2,
                ]
            );
        }

        // 3. Guru Pengampu
        $guruAnton = User::where('email', 'guruanton@smp.com')->first() ?? User::where('institution_id', 2)->first();
        $guruBudi  = User::where('email', 'gurubudi@smp.com')->first() ?? $guruAnton;
        $wakaKesiswaan = User::where('email', 'lya@gmail.com')->first() ?? User::role('waka_kesiswaan')->first() ?? $guruAnton;
        $guruDila  = User::where('email', 'dila@gmail.com')->first() ?? $guruAnton;
        $guruFajar = User::where('email', 'fajar@gmail.com')->first() ?? User::where('name', 'like', '%Fajar Setiawan%')->first() ?? $guruAnton;

        $subjectTeacherMap = [
            'ING' => $guruAnton->id,
            'IND' => $wakaKesiswaan->id,
            'INF' => $guruFajar ? $guruFajar->id : $guruAnton->id,
            'MTK' => $guruAnton->id,
            'IPA' => $guruDila->id,
            'IPS' => $guruBudi->id,
            'PAI' => $guruBudi->id,
            'PKN' => $wakaKesiswaan->id,
            'PJK' => $guruAnton->id,
            'SBD' => $guruDila->id,
            'ARB' => $guruBudi->id,
        ];

        // Petakan Guru ke Mata Pelajaran dan Kelas SMP
        $smpClasses = Classroom::where('institution_id', 2)->get();
        foreach ($smpClasses as $cls) {
            foreach ($createdSubjects as $kode => $subj) {
                $teacherId = $subjectTeacherMap[$kode] ?? $guruAnton->id;
                TeacherSubjectClass::firstOrCreate(
                    [
                        'subject_id'   => $subj->id,
                        'classroom_id' => $cls->id,
                    ],
                    [
                        'teacher_id'     => $teacherId,
                        'institution_id' => 2,
                    ]
                );
            }
        }

        // 4. Data Nilai Akademik Siswa dengan Update Harian Akumulasi
        $smpStudents = Student::where('institution_id', 2)->get();

        foreach ($smpStudents as $studentIndex => $student) {
            // Berikan nilai untuk setiap mapel
            foreach ($createdSubjects as $kode => $subj) {
                // Hapus nilai lama agar bersih dan terstruktur
                Grade::where('student_id', $student->id)->where('subject_id', $subj->id)->delete();

                // Basis nilai siswa
                $base = 78 + (($studentIndex * 3 + strlen($subj->name)) % 18);
                if ($base > 96) $base = 92;

                // 3 sampai 4 Tugas Harian di tanggal-tanggal berbeda untuk merefleksikan update berkala
                $dates = [
                    now()->subDays(32),
                    now()->subDays(24),
                    now()->subDays(15),
                    now()->subDays(7),
                ];

                foreach ($dates as $idx => $date) {
                    $scoreVal = min(100, max(65, $base + rand(-4, 6)));
                    Grade::create([
                        'student_id'       => $student->id,
                        'subject_id'       => $subj->id,
                        'academic_year_id' => $academicYear->id,
                        'semester'         => 'Ganjil',
                        'type'             => 'pengetahuan',
                        'score'            => $scoreVal,
                        'institution_id'   => 2,
                        'created_at'       => $date,
                        'updated_at'       => $date,
                    ]);
                }

                // 1 Nilai Keterampilan / Praktik
                $keterampilanDate = now()->subDays(10);
                Grade::create([
                    'student_id'       => $student->id,
                    'subject_id'       => $subj->id,
                    'academic_year_id' => $academicYear->id,
                    'semester'         => 'Ganjil',
                    'type'             => 'keterampilan',
                    'score'            => min(100, max(70, $base + rand(-2, 5))),
                    'institution_id'   => 2,
                    'created_at'       => $keterampilanDate,
                    'updated_at'       => $keterampilanDate,
                ]);

                // 1 Nilai Ujian (UTS)
                $examDate = now()->subDays(3);
                Grade::create([
                    'student_id'       => $student->id,
                    'subject_id'       => $subj->id,
                    'academic_year_id' => $academicYear->id,
                    'semester'         => 'Ganjil',
                    'type'             => 'ujian',
                    'score'            => min(100, max(68, $base + rand(-5, 4))),
                    'institution_id'   => 2,
                    'created_at'       => $examDate,
                    'updated_at'       => $examDate,
                ]);
            }

            // 5. Data Tahfidz & Tahsin (QuranAchievement & ReadingQualityScore)
            QuranAchievement::where('student_id', $student->id)->delete();
            ReadingQualityScore::where('student_id', $student->id)->delete();

            // Setoran Tahfidz (Ziyadah & Muraja'ah)
            $surahs = [
                ['surah' => '78', 'name' => 'An-Naba', 'juz' => 30, 'ayah' => '1-20'],
                ['surah' => '78', 'name' => 'An-Naba', 'juz' => 30, 'ayah' => '21-40'],
                ['surah' => '79', 'name' => 'An-Naziat', 'juz' => 30, 'ayah' => '1-25'],
                ['surah' => '79', 'name' => 'An-Naziat', 'juz' => 30, 'ayah' => '26-46'],
                ['surah' => '67', 'name' => 'Al-Mulk', 'juz' => 29, 'ayah' => '1-15'],
                ['surah' => '67', 'name' => 'Al-Mulk', 'juz' => 29, 'ayah' => '16-30'],
                ['surah' => '56', 'name' => 'Al-Waqiah', 'juz' => 27, 'ayah' => '1-30'],
            ];

            // Jumlah setoran tergantung index siswa agar bervariasi
            $setoranCount = 3 + ($studentIndex % 4);
            for ($k = 0; $k < $setoranCount; $k++) {
                $item = $surahs[$k % count($surahs)];
                $tgl = now()->subDays(30 - ($k * 5));
                $isZiyadah = ($k % 2 === 0);

                QuranAchievement::create([
                    'student_id'     => $student->id,
                    'teacher_id'     => $guruBudi->id,
                    'type'           => $isZiyadah ? ['ziyadah'] : ['murajaah'],
                    'juz'            => $item['juz'],
                    'surah'          => $item['surah'],
                    'ayah'           => $item['ayah'],
                    'tanggal'        => $tgl->format('Y-m-d'),
                    'catatan'        => $isZiyadah ? 'Hafalan lancar, makhraj huruf sangat baik' : 'Murajaah tertib dan mutqin',
                    'institution_id' => 2,
                    'created_at'     => $tgl,
                    'updated_at'     => $tgl,
                ]);
            }

            // Nilai Skala Mutu Bacaan (Tahsin & Tahfidz)
            $scale = min(98, 80 + (($studentIndex * 4) % 18));
            ReadingQualityScore::create([
                'student_id'     => $student->id,
                'teacher_id'     => $guruBudi->id,
                'program'        => 'tahfidz',
                'scale_score'    => $scale,
                'tanggal'        => now()->subDays(5)->format('Y-m-d'),
                'institution_id' => 2,
            ]);

            ReadingQualityScore::create([
                'student_id'     => $student->id,
                'teacher_id'     => $guruBudi->id,
                'program'        => 'tahsin',
                'scale_score'    => min(97, $scale + 2),
                'tanggal'        => now()->subDays(4)->format('Y-m-d'),
                'institution_id' => 2,
            ]);

            // 6. Data Prestasi dari Waka Kesiswaan (StudentAchievement)
            StudentAchievement::where('student_id', $student->id)->delete();

            $wakaId = $wakaKesiswaan ? $wakaKesiswaan->id : 24;

            // Variasi prestasi siswa
            if ($studentIndex % 2 === 0) {
                // Prestasi Akademik
                StudentAchievement::create([
                    'student_id'     => $student->id,
                    'nama_prestasi'  => 'Juara 1 Lomba Story Telling Bahasa Inggris',
                    'kategori'       => 'akademik',
                    'tingkat'        => 'Kota / Kabupaten',
                    'tanggal'        => now()->subMonths(2)->format('Y-m-d'),
                    'keterangan'     => 'Diselenggarakan oleh Dinas Pendidikan Kota, memperlihatkan kefasihan berbahasa Inggris luar biasa.',
                    'input_by'       => $wakaId,
                    'institution_id' => 2,
                ]);
            }

            if ($studentIndex % 3 === 0) {
                // Prestasi Non-Akademik
                StudentAchievement::create([
                    'student_id'     => $student->id,
                    'nama_prestasi'  => 'Juara 2 Musabaqah Hifdzil Quran (MHQ) 3 Juz',
                    'kategori'       => 'non_akademik',
                    'tingkat'        => 'Provinsi',
                    'tanggal'        => now()->subMonths(1)->format('Y-m-d'),
                    'keterangan'     => 'Festival Al-Qur\'an Tingkat Jawa Timur, membawakan juz 30 dan 29 dengan predikat Mumtaz.',
                    'input_by'       => $wakaId,
                    'institution_id' => 2,
                ]);
            }

            if ($studentIndex % 5 === 0) {
                // Prestasi Akademik Informatika / Sains
                StudentAchievement::create([
                    'student_id'     => $student->id,
                    'nama_prestasi'  => 'Medali Perak Olimpiade Informatika & Coding Pelajar',
                    'kategori'       => 'akademik',
                    'tingkat'        => 'Nasional',
                    'tanggal'        => now()->subWeeks(3)->format('Y-m-d'),
                    'keterangan'     => 'Kompetisi Pemrograman & Computational Thinking Nasional jenjang SMP.',
                    'input_by'       => $wakaId,
                    'institution_id' => 2,
                ]);
            }
        }
    }
}
