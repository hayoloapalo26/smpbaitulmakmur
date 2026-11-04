<?php

namespace Tests\Feature;

use App\Console\Commands\ResetLaunchDataCommand;
use App\Models\AcademicYear;
use App\Models\BkWorkProgram;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\RaporGrade;
use App\Models\Schedule;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\Subject;
use App\Models\TeacherCertificate;
use App\Models\TeacherSubjectClass;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\SchoolPillarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResetLaunchDataTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $smp;
    protected User $kepalaSekolah;
    protected User $guruMapel;
    protected User $ketuaYayasan;
    protected Student $student;
    protected Classroom $classroom;
    protected Subject $subject;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'ketua_yayasan', 'guard_name' => 'web']);

        $yayasan = Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->smp = Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->ketuaYayasan = User::create([
            'name' => 'Ketua Yayasan',
            'email' => 'ketua@yayasan.org',
            'password' => bcrypt('password'),
            'institution_id' => $yayasan->id,
            'jabatan' => 'Ketua Dewan Pembina',
            'is_active' => true,
        ]);
        $this->ketuaYayasan->assignRole('ketua_yayasan');

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah SMP',
            'email' => 'kepsek@smp.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'jabatan' => 'Kepala Sekolah',
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->guruMapel = User::create([
            'name' => 'Ustadz Budi Guru',
            'email' => 'budi@smp.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->smp->id,
            'jabatan' => 'Guru Matematika',
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->classroom = Classroom::create([
            'name' => '7A',
            'tingkat' => '7',
            'wali_kelas_id' => $this->guruMapel->id,
            'institution_id' => $this->smp->id,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->smp->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Siswa Teladan',
            'nis' => '1001',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2011-05-10',
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->smp->id,
            'name' => 'Matematika',
            'kode' => 'MTK',
        ]);
    }

    /**
     * Test bahwa reset hanya mengosongkan jadwal pelajaran, setting guru mapel,
     * nilai/prestasi, dan status program kerja, sedangkan data siswa dan guru tetap aman.
     */
    public function test_reset_clears_schedules_and_routings_while_preserving_users_and_students(): void
    {
        // 1. Buat data setting guru mapel (routing) dan jadwal
        $tsc = TeacherSubjectClass::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
        ]);

        $schedule = Schedule::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'hari' => 'Senin',
            'jam_ke' => '1',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '07:45:00',
        ]);

        $journal = TeachingJournal::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'schedule_id' => $schedule->id,
            'tanggal' => now(),
            'jam_ke' => 1,
            'materi' => 'Aljabar Dasar',
        ]);

        // 2. Buat data nilai & prestasi & sertifikat
        Grade::create([
            'institution_id' => $this->smp->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->guruMapel->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'score' => 95,
            'type' => 'formatif',
        ]);

        StudentAchievement::create([
            'institution_id' => $this->smp->id,
            'student_id' => $this->student->id,
            'nama_prestasi' => 'Juara 1 Catur',
            'kategori' => 'non_akademik',
            'tingkat' => 'Kabupaten',
            'tanggal' => now(),
            'input_by' => $this->kepalaSekolah->id,
        ]);

        TeacherCertificate::create([
            'institution_id' => $this->smp->id,
            'teacher_id' => $this->guruMapel->id,
            'nama_kegiatan' => 'Workshop Kurikulum Merdeka',
            'penyelenggara' => 'Dinas Pendidikan',
            'tanggal_pelaksanaan' => now(),
            'drive_url' => 'https://drive.google.com/test-cert',
        ]);

        // 3. Buat program kerja dengan status Selesai
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Penyusunan Modul',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // Verifikasi data sebelum reset ada
        $initialUserCount = User::count();
        $initialSubjectCount = Subject::count();
        $this->assertEquals(1, TeacherSubjectClass::count());
        $this->assertEquals(1, Schedule::count());
        $this->assertEquals(1, TeachingJournal::count());
        $this->assertEquals(1, Grade::count());
        $this->assertEquals(1, StudentAchievement::count());
        $this->assertEquals(1, TeacherCertificate::count());
        $this->assertEquals('Selesai', ProgramKurikulum::first()->status_pelaksanaan);

        // JALANKAN RESET
        ResetLaunchDataCommand::executeReset();

        // Verifikasi Routing Guru Mapel & Jadwal Pelajaran BERSIH (0)
        $this->assertEquals(0, TeacherSubjectClass::count(), 'TeacherSubjectClass harus kosong.');
        $this->assertEquals(0, Schedule::count(), 'Schedule harus kosong.');
        $this->assertEquals(0, TeachingJournal::count(), 'TeachingJournal harus kosong.');

        // Verifikasi Nilai, Prestasi, dan Sertifikat BERSIH (0)
        $this->assertEquals(0, Grade::count(), 'Grade harus kosong.');
        $this->assertEquals(0, StudentAchievement::count(), 'StudentAchievement harus kosong.');
        $this->assertEquals(0, TeacherCertificate::count(), 'TeacherCertificate harus kosong.');

        // Verifikasi Data Dummy Program Kerja BERSIH (0)
        $this->assertEquals(0, ProgramKurikulum::count(), 'ProgramKurikulum dummy harus kosong.');

        // Verifikasi DATA SISWA TETAP ADA & UTUH
        $this->assertEquals(1, Student::count());
        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'name' => 'Siswa Teladan',
            'nis' => '1001',
        ]);

        // Verifikasi DATA GURU, KETUA YAYASAN, DAN JABATAN TETAP ADA & UTUH
        $this->assertEquals($initialUserCount, User::count());
        $this->assertDatabaseHas('users', [
            'id' => $this->ketuaYayasan->id,
            'name' => 'Ketua Yayasan',
            'jabatan' => 'Ketua Dewan Pembina',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->kepalaSekolah->id,
            'name' => 'Kepala Sekolah SMP',
            'jabatan' => 'Kepala Sekolah',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->guruMapel->id,
            'name' => 'Ustadz Budi Guru',
            'jabatan' => 'Guru Matematika',
        ]);

        // Periksa roles/jabatan
        $this->assertTrue($this->ketuaYayasan->fresh()->hasRole('ketua_yayasan'));
        $this->assertTrue($this->kepalaSekolah->fresh()->hasRole('kepala_sekolah'));
        $this->assertTrue($this->guruMapel->fresh()->hasRole('guru_mapel'));

        // Periksa Master Data (Kelas, Mapel, Tahun Ajaran) TETAP AMAN
        $this->assertEquals(1, Classroom::count());
        $this->assertEquals($initialSubjectCount, Subject::count());
        $this->assertEquals(1, AcademicYear::count());
    }

    /**
     * Test bahwa artisan command `app:reset-launch-data --force` dapat dipanggil sukses.
     */
    public function test_artisan_command_app_reset_launch_data_runs_successfully(): void
    {
        $this->artisan('app:reset-launch-data', ['--force' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('SUKSES: Jadwal pelajaran, setting guru mapel, dan progress pilar telah direset');
    }
}
