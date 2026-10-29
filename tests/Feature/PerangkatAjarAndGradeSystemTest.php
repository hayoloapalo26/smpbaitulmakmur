<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\GradeResource;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerangkatAjarAndGradeSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $teacher;
    protected Classroom $classroom;
    protected Student $student;
    protected Student $student2;
    protected Subject $subject;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'institution_id' => $this->institution->id,
                'semester' => 'Ganjil',
                'is_active' => true,
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
            ]
        );

        $this->teacher = User::create([
            'name' => 'Guru Matematika',
            'username' => 'guru_matematika',
            'email' => 'guru.mtk@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->teacher->assignRole('guru_mapel');
        $this->actingAs($this->teacher);

        $this->classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'tingkat' => '7',
            'is_active' => true,
        ]);

        $this->subject = Subject::firstOrCreate(
            ['name' => 'Matematika'],
            [
                'institution_id' => $this->institution->id,
                'kode' => 'MTK-7',
                'is_active' => true,
            ]
        );

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ahmad Raihan',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'tanggal_lahir' => '2012-05-10',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->classroom->id,
            'is_active' => true,
        ]);

        $this->student2 = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siti Nurhaliza',
            'nis' => '2026002',
            'nisn' => '0012345679',
            'tanggal_lahir' => '2012-08-15',
            'jenis_kelamin' => 'P',
            'classroom_id' => $this->classroom->id,
            'is_active' => true,
        ]);
    }

    public function test_can_create_perangkat_ajar_cp_tp_and_atp_hierarchy(): void
    {
        // 1. CP (Capaian Pembelajaran)
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'elemen' => 'Bilangan & Aljabar',
            'deskripsi_cp' => 'Peserta didik dapat memahami bilangan bulat dan operasi aljabar dasar.',
            'masa_berlaku' => '1 Tahun',
        ]);

        $this->assertDatabaseHas('capaian_pembelajarans', [
            'id' => $cp->id,
            'elemen' => 'Bilangan & Aljabar',
        ]);

        // 2. TP (Tujuan Pembelajaran) derived from CP
        $tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'kode_tp' => 'TP.7.1.1',
            'deskripsi_tp' => 'Memahami konsep bilangan bulat negatif dan positif.',
            'materi' => 'Bilangan Bulat',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'kode_tp' => 'TP.7.1.2',
            'deskripsi_tp' => 'Menyelesaikan operasi hitung campuran bilangan bulat.',
            'materi' => 'Operasi Bilangan Bulat',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $this->assertDatabaseHas('tujuan_pembelajarans', ['kode_tp' => 'TP.7.1.1']);
        $this->assertDatabaseHas('tujuan_pembelajarans', ['kode_tp' => 'TP.7.1.2']);

        // 3. ATP (Alur Tujuan Pembelajaran) accumulating TPs
        $atp = AlurTujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'judul_atp' => 'Alur Pembelajaran Matematika Semester Ganjil',
            'tp_ids' => [$tp1->id, $tp2->id],
            'total_alokasi_jp' => 10,
        ]);

        $this->assertDatabaseHas('alur_tujuan_pembelajarans', [
            'id' => $atp->id,
            'judul_atp' => 'Alur Pembelajaran Matematika Semester Ganjil',
        ]);
        $this->assertCount(2, $atp->fresh()->tp_ids);
    }

    public function test_can_connect_rppm_to_cp_atp_and_tp(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'elemen' => 'Geometri',
            'deskripsi_cp' => 'Peserta didik dapat memahami bangun ruang.',
            'masa_berlaku' => '1 Semester',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'kode_tp' => 'TP.7.2.1',
            'deskripsi_tp' => 'Menghitung volume kubus dan balok.',
            'materi' => 'Bangun Ruang',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $atp = AlurTujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'judul_atp' => 'Alur Geometri Kelas 7',
            'tp_ids' => [$tp->id],
        ]);

        $rppm = Rppm::create([
            'guru_id' => $this->teacher->id,
            'kelas_fase' => 'Fase D - Kelas 7',
            'mata_pelajaran' => 'Matematika',
            'capaian_pembelajaran_id' => $cp->id,
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'selected_tp_ids' => [$tp->id],
            'topik' => 'Kubus dan Balok',
            'alokasi_waktu' => '2 x 40 Menit',
            'content' => [
                'tujuan_pembelajaran' => [$tp->kode_tp . ' - ' . $tp->deskripsi_tp],
            ],
        ]);

        $this->assertEquals($cp->id, $rppm->capaianPembelajaran->id);
        $this->assertEquals($atp->id, $rppm->alurTujuanPembelajaran->id);
        $this->assertCount(1, $rppm->selected_tps);
        $this->assertEquals('TP.7.2.1', $rppm->selected_tps->first()->kode_tp);
    }

    public function test_can_create_grade_with_five_types_forms_and_tp(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'elemen' => 'Aljabar',
            'deskripsi_cp' => 'Capaian aljabar',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'kode_tp' => 'TP.7.1.1',
            'deskripsi_tp' => 'Konsep Aljabar 1 Variabel',
            'materi' => 'Aljabar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        // Formatif grade
        $grade1 = Grade::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'formatif',
            'bentuk_penilaian' => 'quiz',
            'materi' => 'Operasi Dasar Aljabar',
            'tanggal' => now()->toDateString(),
            'tp_ids' => [$tp->id],
            'score' => 78.5,
            'catatan_guru' => 'Ananda aktif menjawab kuis namun perlu berlatih teliti pada suku aljabar sejenis.',
        ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade1->id,
            'type' => 'formatif',
            'bentuk_penilaian' => 'quiz',
            'score' => 78.5,
        ]);

        // Custom type & custom form grade
        $gradeCustom = Grade::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student2->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'kustom',
            'custom_type_name' => 'Proyek Portofolio Mandiri',
            'bentuk_penilaian' => 'kustom',
            'custom_bentuk_penilaian' => 'Laporan Portofolio Siswa',
            'materi' => 'Pemodelan Matematika di Kehidupan Sehari-hari',
            'tanggal' => now()->toDateString(),
            'tp_ids' => [$tp->id],
            'score' => 96.0,
            'catatan_guru' => 'Sangat kreatif dalam mendokumentasikan penerapan aljabar pada anggaran belanja.',
        ]);

        $this->assertEquals('kustom', $gradeCustom->type);
        $this->assertEquals('Proyek Portofolio Mandiri', $gradeCustom->custom_type_name);
        $this->assertEquals('Laporan Portofolio Siswa', $gradeCustom->custom_bentuk_penilaian);
        $this->assertEquals(96.0, $gradeCustom->score);
    }

    public function test_remedial_updates_grade_correctly(): void
    {
        $grade = Grade::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'formatif',
            'bentuk_penilaian' => 'ulangan_harian',
            'materi' => 'Persamaan Linier Satu Variabel',
            'tanggal' => '2026-08-10',
            'score' => 62.0, // Below standard
            'catatan_guru' => 'Perlu pendampingan khusus pada materi perpindahan ruas.',
        ]);

        // Teacher executes remedial
        $grade->update([
            'is_remedial' => true,
            'tanggal_remedial' => '2026-08-17',
            'nilai_remedial' => 78.0,
            'catatan_remedial' => 'Alhamdulillah telah tuntas setelah bimbingan tambahan dan tes ulang.',
        ]);

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'is_remedial' => true,
            'nilai_remedial' => 78.0,
            'catatan_remedial' => 'Alhamdulillah telah tuntas setelah bimbingan tambahan dan tes ulang.',
        ]);
    }

    public function test_pengayaan_feature_specifically_for_score_95_and_above_with_upgraded_tp(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'elemen' => 'Geometri',
            'deskripsi_cp' => 'Capaian Geometri',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'kode_tp' => 'TP.7.3.1',
            'deskripsi_tp' => 'Menghitung keliling dan luas lingkaran standar.',
            'materi' => 'Lingkaran',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        // Student with score 97 qualifies for Pengayaan (>= 95)
        $highGrade = Grade::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'formatif',
            'bentuk_penilaian' => 'quiz',
            'materi' => 'Lingkaran',
            'tanggal' => '2026-08-20',
            'tp_ids' => [$tp->id],
            'score' => 97.0,
            'catatan_guru' => 'Luar biasa, penguasaan rumus dan logika lingkaran sangat matang.',
        ]);

        $this->assertGreaterThanOrEqual(95, $highGrade->score);

        // Teacher executes Pengayaan & TP Upgrade Level 2 for this student
        $highGrade->update([
            'is_pengayaan' => true,
            'tp_pengayaan_id' => $tp->id,
            'upgraded_tp_description' => 'TP Level 2: Menganalisis persoalan luas juring dan tembereng gabungan bangun datar tingkat lanjut (HOTS).',
            'bentuk_pengayaan' => 'soal_tingkat_lanjut',
            'link_drive_pengayaan' => 'https://drive.google.com/drive/folders/contoh-tugas-pengayaan-ahmad',
            'tanggal_pengayaan' => '2026-08-25',
            'nilai_pengayaan' => 99.0,
            'catatan_pengayaan' => 'Siswa menyelesaikan soal tantangan olimpiade geometri dengan sangat sempurna.',
        ]);

        $this->assertDatabaseHas('grades', [
            'id' => $highGrade->id,
            'is_pengayaan' => true,
            'tp_pengayaan_id' => $tp->id,
            'upgraded_tp_description' => 'TP Level 2: Menganalisis persoalan luas juring dan tembereng gabungan bangun datar tingkat lanjut (HOTS).',
            'bentuk_pengayaan' => 'soal_tingkat_lanjut',
            'nilai_pengayaan' => 99.0,
        ]);

        // Verify student with score < 95 does not meet criteria
        $normalGrade = Grade::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student2->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'ganjil',
            'type' => 'formatif',
            'bentuk_penilaian' => 'quiz',
            'materi' => 'Lingkaran',
            'tanggal' => '2026-08-20',
            'tp_ids' => [$tp->id],
            'score' => 88.0,
            'catatan_guru' => 'Sangat baik memahami konsep dasar lingkaran.',
        ]);

        $this->assertLessThan(95, $normalGrade->score);
    }
}
