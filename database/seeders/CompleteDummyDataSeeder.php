<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use Carbon\Carbon;

class CompleteDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $students = Student::take(10)->get();
        $allStudents = Student::pluck('id')->toArray();
        $classrooms = Classroom::all();
        $subjects = Subject::all();
        $academicYear = AcademicYear::first();
        
        $guru = User::role('guru_mapel')->first() ?? User::first();
        $wakaKurikulum = User::role('waka_kurikulum')->first() ?? User::first();
        $kepsek = User::role('kepala_sekolah')->first() ?? User::first();

        if ($students->isEmpty() || $classrooms->isEmpty() || $subjects->isEmpty() || !$academicYear) {
            echo "Error: Pastikan data awal (Siswa, Kelas, Mapel, Tahun Ajaran) sudah ada!\n";
            return;
        }

        // 1. Data Nilai Siswa (Grades)
        foreach ($students as $student) {
            foreach ($subjects as $subject) {
                \App\Models\Grade::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'type' => 'pengetahuan',
                    'score' => $faker->numberBetween(75, 100),
                    'semester' => 'Ganjil',
                    'academic_year_id' => $academicYear->id
                ]);
                \App\Models\Grade::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'type' => 'keterampilan',
                    'score' => $faker->numberBetween(75, 100),
                    'semester' => 'Ganjil',
                    'academic_year_id' => $academicYear->id
                ]);
            }
        }

        // 2. Data Kehadiran Shalat (Prayer Attendances)
        foreach ($students as $student) {
            \App\Models\PrayerAttendance::create([
                'student_id' => $student->id,
                'teacher_id' => $guru->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'status' => 'hadir',
                'is_khusyu' => true
            ]);
        }

        // 3. Data Hafalan Quran (Quran Achievements)
        foreach ($students as $student) {
            \App\Models\QuranAchievement::create([
                'student_id' => $student->id,
                'teacher_id' => $guru->id,
                'type' => 'ziyadah',
                'juz' => 30,
                'surah' => 'Al-Mulk',
                'ayah' => '1-5',
                'tanggal' => Carbon::today()->format('Y-m-d')
            ]);
        }

        // 4. Data Nilai Tahsin (Reading Quality Scores)
        foreach ($students as $student) {
            \App\Models\ReadingQualityScore::create([
                'student_id' => $student->id,
                'teacher_id' => $guru->id,
                'program' => 'tahsin',
                'scale_score' => $faker->numberBetween(75, 95),
                'tanggal' => Carbon::today()->format('Y-m-d')
            ]);
        }

        // 5. Data Buku Penghubung / Karakter (Character Score Logs)
        foreach ($students as $student) {
            \App\Models\CharacterScoreLog::create([
                'student_id' => $student->id,
                'source' => 'guru_kelas',
                'category' => 'Adab dan Akhlak',
                'description' => 'Membantu membersihkan kelas setelah KBM selesai.',
                'point_change' => 5,
                'tanggal' => Carbon::today()->format('Y-m-d')
            ]);
        }

        // 6. Data Pelanggaran Atribut (Attribute Violations)
        if (isset($students[0])) {
            \App\Models\AttributeViolation::create([
                'student_id' => $students[0]->id,
                'teacher_id' => $guru->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'attribute_type' => 'Rambut',
                'jenis_pelanggaran' => 'Rambut panjang melebihi batas'
            ]);
        }

        // 7. Data Kasus Siswa (BK)
        if (isset($students[1])) {
            $kasus = \App\Models\StudentCase::create([
                'student_id' => $students[1]->id,
                'wali_kelas_id' => $guru->id,
                'deskripsi' => 'Sering datang terlambat dan tertidur di kelas',
                'recommended_to_bk' => true,
                'status' => 'proses'
            ]);

            // 8. Laporan BK (BK Reports)
            \App\Models\BkReport::create([
                'student_id' => $students[1]->id,
                'student_case_id' => $kasus->id,
                'bk_teacher_id' => $guru->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'isi_laporan' => 'Telah dilakukan pemanggilan dan konseling. Siswa berjanji tidak akan begadang lagi.'
            ]);
        }

        // 9. Prestasi Siswa (Student Achievements)
        if (isset($students[2])) {
            \App\Models\StudentAchievement::create([
                'student_id' => $students[2]->id,
                'nama_prestasi' => 'Juara 1 Lomba MTQ Tingkat Kota',
                'tingkat' => 'Kota/Kabupaten',
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'input_by' => $guru->id
            ]);
        }

        // 10. Data Ekskul
        $ekskul = \App\Models\Extracurricular::create([
            'name' => 'Pramuka',
            'pembina_id' => $guru->id
        ]);
        \App\Models\Extracurricular::create([
            'name' => 'Futsal',
            'pembina_id' => $guru->id
        ]);

        foreach ($students as $student) {
            \App\Models\ExtracurricularAttendance::create([
                'student_id' => $student->id,
                'extracurricular_id' => $ekskul->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'status' => 'hadir'
            ]);
        }

        // 11. Program OSIS
        \App\Models\OsisProgram::create([
            'nama_program' => 'Porseni (Pekan Olahraga dan Seni)',
            'tanggal' => Carbon::today()->addDays(14)->format('Y-m-d'),
            'status' => 'direncanakan'
        ]);

        // 12. Tracer Alumni
        \App\Models\AlumniTracer::create([
            'nama' => 'Budi Santoso',
            'tahun_lulus' => '2023',
            'data_pelacakan' => 'Melanjutkan studi di SMA Negeri 1.'
        ]);

        // 13. Surat Menyurat (Letters)
        \App\Models\Letter::create([
            'type' => 'masuk',
            'nomor' => '001/DINAS/2026',
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'perihal' => 'Undangan Rapat Koordinasi Kurikulum Kota'
        ]);

        // 14. Inventaris Sarpras
        \App\Models\Inventory::create([
            'nama_barang' => 'Proyektor Epson EB-X51',
            'jumlah' => 15,
            'kondisi' => 'Baik'
        ]);
        
        \App\Models\Inventory::create([
            'nama_barang' => 'Laptop Guru (Lenovo ThinkPad)',
            'jumlah' => 20,
            'kondisi' => 'Baik'
        ]);

        // 15. Dokumen Arsip
        \App\Models\DocumentArchive::create([
            'kategori' => 'SK Kepala Sekolah',
            'judul' => 'SK Pembagian Tugas Mengajar Ganjil 26/27',
            'file' => 'arsip/dummy.pdf',
            'tanggal' => Carbon::today()->format('Y-m-d')
        ]);

        // 16. Jadwal Pelajaran (Schedules)
        $schedule = \App\Models\Schedule::create([
            'classroom_id' => $classrooms[0]->id,
            'subject_id' => $subjects[0]->id,
            'teacher_id' => $guru->id,
            'hari' => 'Senin',
            'jam_ke' => 1
        ]);

        // 17. Jurnal Mengajar (Teaching Journals)
        $jurnal = \App\Models\TeachingJournal::create([
            'teacher_id' => $guru->id,
            'classroom_id' => $classrooms[0]->id,
            'subject_id' => $subjects[0]->id,
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'materi' => 'Pengenalan Bab 1',
            'jam_ke' => 1
        ]);

        // 18. Absensi Siswa per Mapel (Subject Attendances)
        foreach ($students as $student) {
            \App\Models\SubjectAttendance::create([
                'student_id' => $student->id,
                'teaching_journal_id' => $jurnal->id,
                'status' => 'hadir'
            ]);
        }

        // 19. Jadwal Piket & Tukar Jadwal (Duty Schedules)
        \App\Models\DutySchedule::create([
            'teacher_id' => $guru->id,
            'tanggal' => Carbon::today()->format('Y-m-d')
        ]);
        \App\Models\DutySwapRequest::create([
            'requester_id' => $guru->id,
            'original_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'substitute_teacher_id' => $wakaKurikulum->id,
            'new_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'status' => 'diajukan'
        ]);

        // 20. Jurnal Kelas Kosong & Laporan Izin (Empty Class Logs)
        $absen = \App\Models\TeacherAbsenceReport::create([
            'absent_teacher_id' => $wakaKurikulum->id,
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'jam_ke' => 3,
            'subject_id' => $subjects[1]->id,
            'classroom_id' => $classrooms[1]->id,
            'keterangan' => 'Rapat dinas mendadak'
        ]);
        \App\Models\EmptyClassLog::create([
            'duty_teacher_id' => $guru->id,
            'teacher_absence_report_id' => $absen->id,
            'aktivitas' => 'Siswa diberikan tugas membaca dari guru bersangkutan, kondisi kelas tertib.'
        ]);

        // 21. Ceklis Harian Orang Tua (Parent Daily Checklists)
        foreach ($students as $student) {
            \App\Models\ParentDailyChecklist::create([
                'student_id' => $student->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'time_slot' => 'pagi',
                'activity' => 'Merapikan Tempat Tidur',
                'is_done' => true
            ]);
        }

        // 22. To Do List Siswa
        foreach ($students as $student) {
            \App\Models\StudentTodoList::create([
                'student_id' => $student->id,
                'tanggal' => Carbon::today()->format('Y-m-d'),
                'task' => 'Mengerjakan PR Matematika',
                'is_done' => false
            ]);
        }

        echo "Berhasil mengisi Data Dummy secara komprehensif ke 30+ Tabel/Fitur!\n";
    }
}
