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

class GradingVariablesRemedialPengayaanTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $teacher;
    protected Classroom $class7A;
    protected Classroom $class7B;
    protected Student $student1;
    protected Student $student2;
    protected Subject $subject;
    protected AcademicYear $academicYear;
    protected TujuanPembelajaran $tp1;
    protected TujuanPembelajaran $tp2;
    protected Rppm $rppm;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-islam-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'institution_id' => $this->institution->id,
            'semester' => 'Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);

        $this->teacher = User::create([
            'name' => 'Ustadz Ahmad Fauzi',
            'username' => 'ahmad_fauzi',
            'email' => 'ahmad.fauzi@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->teacher->assignRole('guru_mapel');
        $this->actingAs($this->teacher);

        $this->class7A = Classroom::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'tingkat' => '7',
            'is_active' => true,
        ]);

        $this->class7B = Classroom::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-B',
            'tingkat' => '7',
            'is_active' => true,
        ]);

        $this->student1 = Student::create([
            'classroom_id' => $this->class7A->id,
            'name' => 'Muhammad Zaki',
            'nis' => '20260701',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
        ]);

        $this->student2 = Student::create([
            'classroom_id' => $this->class7B->id,
            'name' => 'Fatimah Az-Zahra',
            'nis' => '20260702',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-08-15',
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->institution->id,
            'name' => 'Informatika & Rekayasa',
            'kode' => 'INF',
        ]);

        TeacherSubjectClass::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'classroom_id' => $this->class7A->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'masa_berlaku' => '1 Tahun',
            'deskripsi_cp' => 'Memahami logika komputasi dan rancang bangun dasar rekayasa teknologi.',
        ]);

        $this->tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->teacher->id,
            'kode_tp' => 'TP.7.1',
            'deskripsi_tp' => 'Membaca dan menafsirkan gambar kerja rangkaian digital.',
            'materi' => 'Gambar Kerja Teknik',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $this->tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'guru_id' => $this->teacher->id,
            'kode_tp' => 'TP.7.2',
            'deskripsi_tp' => 'Mengoperasikan instrumen dan alat ukur dasar dengan terampil dan aman.',
            'materi' => 'Alat Ukur Dasar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $this->rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'kelas_fase' => 'Kelas 7 / Fase D',
            'mata_pelajaran' => 'Informatika & Rekayasa',
            'capaian_pembelajaran_id' => $cp->id,
            'selected_tp_ids' => [$this->tp1->id, $this->tp2->id],
            'topik' => 'Rancang Bangun dan Pengukuran Dasar',
            'alokasi_waktu' => '2 x 40 Menit',
            'status' => 'published',
        ]);
    }

    public function test_can_input_grade_with_all_variables_and_multiple_tps_sharing_single_score()
    {
        $grade = Grade::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rppm_id' => $this->rppm->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-29',
            'type' => 'formatif',
            'bentuk_penilaian' => 'ulangan_harian',
            'materi' => 'Rancang Bangun dan Pengukuran Dasar',
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'score' => 68.0, // Belum tuntas (< 75)
            'catatan_per_tp' => [
                (string)$this->tp1->id => 'Masih keliru membaca gambar kerja',
                (string)$this->tp2->id => 'Sangat terampil menggunakan alat',
            ],
            'catatan_guru' => 'Ananda sangat terampil menggunakan alat, namun masih keliru membaca gambar kerja.',
        ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'score' => 68.0,
            'materi' => 'Rancang Bangun dan Pengukuran Dasar',
            'rppm_id' => $this->rppm->id,
        ]);

        $this->assertFalse($grade->isTuntas());
        $this->assertCount(2, $grade->tp_ids);
        $this->assertEquals('Masih keliru membaca gambar kerja', $grade->catatan_per_tp[$this->tp1->id]);
        $this->assertEquals('Sangat terampil menggunakan alat', $grade->catatan_per_tp[$this->tp2->id]);
    }

    public function test_can_record_remedial_for_uncompleted_tps()
    {
        $grade = Grade::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-25',
            'type' => 'formatif',
            'bentuk_penilaian' => 'ulangan_harian',
            'materi' => 'Rancang Bangun',
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'score' => 65.0,
            'catatan_guru' => 'Perlu perbaikan pada TP membaca gambar kerja.',
        ]);

        $this->assertFalse($grade->isTuntas());

        // Jalankan aksi Remedial
        $grade->update([
            'is_remedial' => true,
            'tanggal_remedial' => '2026-10-29',
            'nilai_remedial' => 80.0,
            'tp_remedial_ids' => [$this->tp1->id], // Hanya TP 1 yang diulang
            'catatan_remedial' => 'Siswa telah mempelajari kembali pembacaan simbol gambar kerja dan tuntas pada latihan ulang.',
        ]);

        $grade->refresh();
        $this->assertTrue($grade->is_remedial);
        $this->assertTrue($grade->isTuntas()); // Nilai remedial 80 >= 75
        $this->assertEquals(80.0, $grade->nilai_akhir);
        $this->assertEquals([$this->tp1->id], $grade->tp_remedial_ids);
    }

    public function test_can_record_pengayaan_only_for_completed_students_with_all_variables()
    {
        // Siswa dengan nilai tuntas (score >= 75)
        $tuntasGrade = Grade::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-28',
            'type' => 'keterampilan',
            'bentuk_penilaian' => 'praktik',
            'materi' => 'Rancang Bangun',
            'tp_ids' => [$this->tp1->id, $this->tp2->id],
            'score' => 92.0, // Tuntas
            'catatan_guru' => 'Sangat memahami materi dan terampil.',
        ]);

        $this->assertTrue($tuntasGrade->isTuntas());

        // Berikan Pengayaan
        $tuntasGrade->update([
            'is_pengayaan' => true,
            'tp_pengayaan_id' => $this->tp2->id, // TP yang dikembangkan
            'upgraded_tp_description' => 'Mampu merancang prosedur kerja sendiri untuk pengujian sirkuit sensorik mandiri.',
            'bentuk_pengayaan' => 'projek_mini',
            'link_drive_pengayaan' => 'https://drive.google.com/file/d/1234567890/view?usp=sharing',
            'tanggal_pengayaan' => '2026-10-29',
            'nilai_pengayaan' => 96.0,
            'catatan_pengayaan' => 'Mampu merancang prosedur kerja sendiri',
        ]);

        $tuntasGrade->refresh();
        $this->assertTrue($tuntasGrade->is_pengayaan);
        $this->assertEquals($this->tp2->id, $tuntasGrade->tp_pengayaan_id);
        $this->assertEquals('projek_mini', $tuntasGrade->bentuk_pengayaan);
        $this->assertEquals('https://drive.google.com/file/d/1234567890/view?usp=sharing', $tuntasGrade->link_drive_pengayaan);
        $this->assertEquals('Mampu merancang prosedur kerja sendiri', $tuntasGrade->catatan_pengayaan);
        $this->assertEquals(96.0, $tuntasGrade->nilai_pengayaan);
    }

    public function test_renders_manage_grades_tabs_properly()
    {
        // 1 Nilai Belum Tuntas (< 75)
        Grade::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-28',
            'type' => 'formatif',
            'bentuk_penilaian' => 'kuis',
            'materi' => 'Kuis Logika',
            'tp_ids' => [$this->tp1->id],
            'score' => 60.0,
            'is_remedial' => false,
            'catatan_guru' => 'Perlu remedial.',
        ]);

        // 1 Nilai Tuntas Siap Pengayaan
        Grade::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class7A->id,
            'student_id' => $this->student1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'tanggal' => '2026-10-28',
            'type' => 'formatif',
            'bentuk_penilaian' => 'ulangan_harian',
            'materi' => 'Logika Digital',
            'tp_ids' => [$this->tp2->id],
            'score' => 88.0,
            'is_pengayaan' => false,
            'catatan_guru' => 'Tuntas.',
        ]);

        Livewire::test(ManageGrades::class)
            ->assertSuccessful()
            ->assertSee('Semua Nilai')
            ->assertSee('Perlu Remedial')
            ->assertSee('Tuntas (Siap Pengayaan)');
    }
}
