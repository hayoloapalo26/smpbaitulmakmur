<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\GradeResource\Pages\ManageGrades;
use App\Models\AcademicYear;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BatchGradeInputTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected Subject $subject;
    protected Classroom $classroom;
    protected AcademicYear $academicYear;
    protected CapaianPembelajaran $cp;
    protected TujuanPembelajaran $tp1;
    protected TujuanPembelajaran $tp2;
    protected Rppm $rppm;
    protected Student $student1;
    protected Student $student2;
    protected Student $student3;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Ustadz Ahmad, M.Pd.',
            'username' => 'ahmad_mpd',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->classroom = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->subject = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        TeacherSubjectClass::create([
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
        ]);

        // Siswa di kelas 7A
        $this->student1 = Student::create([
            'name' => 'Ahmad Dani',
            'nis' => '1001',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
        ]);

        $this->student2 = Student::create([
            'name' => 'Bilqis Humaira',
            'nis' => '1002',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-08-15',
        ]);

        $this->student3 = Student::create([
            'name' => 'Cecep Supriadi',
            'nis' => '1003',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-11-20',
        ]);

        // Capaian Pembelajaran (CP 1)
        $this->cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Aljabar',
            'deskripsi_cp' => 'Peserta didik dapat menyelesaikan persamaan dan pertidaksamaan linier satu variabel.',
        ]);

        // Tujuan Pembelajaran (TP 1.1 & TP 1.2)
        $this->tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->guru->id,
            'materi' => 'Aljabar Dasar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
            'deskripsi_tp' => 'Memahami variabel, koefisien, dan konstanta pada bentuk aljabar.',
        ]);

        $this->tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->guru->id,
            'materi' => 'Operasi Aljabar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
            'deskripsi_tp' => 'Menyelesaikan penjumlahan dan pengurangan suku-suku sejenis.',
        ]);

        // Dokumen RPPM
        $this->rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran' => 'Matematika',
            'kelas_fase' => 'Kelas 7',
            'topik' => 'Operasi Aljabar dan Persamaan Linier',
            'capaian_pembelajaran_id' => $this->cp->id,
            'selected_tp_ids' => [$this->tp1->id, $this->tp2->id],
        ]);
    }

    /**
     * Test 1: Action input_nilai_rombel tersedia di halaman ManageGrades
     */
    public function test_batch_grade_action_is_available_on_manage_grades_page(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(ManageGrades::class)
            ->assertActionExists('input_nilai_rombel')
            ->assertActionVisible('input_nilai_rombel');
    }

    /**
     * Test 2: Input nilai rombel satu kali untuk seluruh kelas (CP, TP, Materi disamakan, nilai & catatan per siswa)
     */
    public function test_batch_grade_input_saves_all_students_with_shared_parameters(): void
    {
        $this->actingAs($this->guru);

        $payload = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-30',
            'rppm_id' => $this->rppm->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'materi' => 'Operasi Aljabar dan Persamaan Linier',
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'type' => 'formatif',
            'bentuk_penilaian' => 'ulangan_harian',
            'student_scores' => [
                $this->student1->id => [
                    'score' => '95',
                    'catatan' => 'Sangat menguasai konsep aljabar dan teliti.',
                ],
                $this->student2->id => [
                    'score' => '82',
                    'catatan' => 'Tuntas dan aktif dalam menyelesaikan soal latihan.',
                ],
                $this->student3->id => [
                    'score' => '65',
                    'catatan' => 'Perlu bimbingan lebih lanjut pada penjumlahan suku berbeda jenis.',
                ],
            ],
        ];

        Livewire::test(ManageGrades::class)
            ->callAction('input_nilai_rombel', $payload)
            ->assertHasNoActionErrors();

        // Verifikasi database: 3 record nilai tersimpan dengan CP, TP, dan Materi yang sama
        $this->assertDatabaseCount('grades', 3);

        // Siswa 1
        $this->assertDatabaseHas('grades', [
            'student_id' => $this->student1->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'rppm_id' => $this->rppm->id,
            'materi' => 'Operasi Aljabar dan Persamaan Linier',
            'score' => 95,
            'catatan_guru' => 'Sangat menguasai konsep aljabar dan teliti.',
        ]);

        // Siswa 2
        $this->assertDatabaseHas('grades', [
            'student_id' => $this->student2->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'rppm_id' => $this->rppm->id,
            'materi' => 'Operasi Aljabar dan Persamaan Linier',
            'score' => 82,
            'catatan_guru' => 'Tuntas dan aktif dalam menyelesaikan soal latihan.',
        ]);

        // Siswa 3 (Nilai < 70, catatan pembinaan)
        $this->assertDatabaseHas('grades', [
            'student_id' => $this->student3->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->classroom->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'rppm_id' => $this->rppm->id,
            'materi' => 'Operasi Aljabar dan Persamaan Linier',
            'score' => 65,
            'catatan_guru' => 'Perlu bimbingan lebih lanjut pada penjumlahan suku berbeda jenis.',
        ]);

        // Verifikasi TP IDs tersimpan dalam bentuk JSON array
        $grade1 = Grade::where('student_id', $this->student1->id)->first();
        $this->assertEquals([$this->tp1->id, $this->tp2->id], $grade1->tp_ids);
    }

    /**
     * Test 3: Siswa dengan skor kosong tidak disimpan ke database
     */
    public function test_empty_student_scores_are_ignored(): void
    {
        $this->actingAs($this->guru);

        $payload = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-30',
            'capaian_pembelajaran_id' => $this->cp->id,
            'materi' => 'Latihan Soal Aljabar',
            'tp_ids' => [$this->tp1->id],
            'type' => 'formatif',
            'bentuk_penilaian' => 'kuis',
            'student_scores' => [
                $this->student1->id => [
                    'score' => '88',
                    'catatan' => 'Bagus.',
                ],
                $this->student2->id => [
                    'score' => '', // kosong
                    'catatan' => '',
                ],
                $this->student3->id => [
                    'score' => null, // null
                    'catatan' => '',
                ],
            ],
        ];

        Livewire::test(ManageGrades::class)
            ->callAction('input_nilai_rombel', $payload)
            ->assertHasNoActionErrors();

        // Hanya 1 record yang tersimpan (milik student1)
        $this->assertDatabaseCount('grades', 1);
        $this->assertDatabaseHas('grades', [
            'student_id' => $this->student1->id,
            'score' => 88,
        ]);
        $this->assertDatabaseMissing('grades', [
            'student_id' => $this->student2->id,
        ]);
    }

    /**
     * Test 4: Aksi modal input nilai rombel dapat di-mount dan form siap digunakan
     */
    public function test_batch_grade_action_can_be_mounted(): void
    {
        $this->actingAs($this->guru);

        Livewire::test(ManageGrades::class)
            ->mountAction('input_nilai_rombel')
            ->assertActionMounted('input_nilai_rombel');
    }
}
