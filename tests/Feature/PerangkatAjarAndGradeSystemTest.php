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

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

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

    public function test_perangkat_ajar_separate_resources_are_hidden_from_sidebar_navigation(): void
    {
        $this->assertFalse(\App\Filament\Staff\Resources\CapaianPembelajaranResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Staff\Resources\TujuanPembelajaranResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Staff\Resources\AlurTujuanPembelajaranResource::shouldRegisterNavigation());
    }

    public function test_rppm_creation_directly_inputs_and_persists_cp_and_tp(): void
    {
        $page = new \App\Filament\Staff\Resources\RppmResource\Pages\CreateRppm();

        $reflection = new \ReflectionClass($page);
        $method = $reflection->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $formData = [
            'kelas_fase' => 'Kelas 7 / Fase D',
            'mata_pelajaran' => 'Matematika',
            'topik' => 'Pecahan dan Aljabar',
            'alokasi_waktu' => '2 x 40 Menit',
            'input_perangkat_mode' => 'input_baru',
            'new_cp_elemen' => 'Bilangan & Aljabar',
            'new_cp_masa_berlaku' => '1 Tahun',
            'new_cp_deskripsi' => 'Peserta didik mampu memahami operasi aljabar bilangan pecahan.',
            'new_tps' => [
                [
                    'kode_tp' => 'TP.7.1.10',
                    'materi' => 'Aljabar Pecahan',
                    'deskripsi_tp' => 'Menyederhanakan pecahan bentuk aljabar.',
                ],
                [
                    'kode_tp' => 'TP.7.1.11',
                    'materi' => 'Aljabar Pecahan',
                    'deskripsi_tp' => 'Menyelesaikan perkalian dan pembagian pecahan aljabar.',
                ],
            ],
            'content' => [],
        ];

        $mutated = $method->invoke($page, $formData);

        // CP should be created and linked
        $this->assertNotNull($mutated['capaian_pembelajaran_id']);
        $this->assertDatabaseHas('capaian_pembelajarans', [
            'id' => $mutated['capaian_pembelajaran_id'],
            'elemen' => 'Bilangan & Aljabar',
            'deskripsi_cp' => 'Peserta didik mampu memahami operasi aljabar bilangan pecahan.',
        ]);

        // 2 TPs should be created and linked
        $this->assertCount(2, $mutated['selected_tp_ids']);
        $this->assertDatabaseHas('tujuan_pembelajarans', [
            'kode_tp' => 'TP.7.1.10',
            'deskripsi_tp' => 'Menyederhanakan pecahan bentuk aljabar.',
        ]);
        $this->assertDatabaseHas('tujuan_pembelajarans', [
            'kode_tp' => 'TP.7.1.11',
            'deskripsi_tp' => 'Menyelesaikan perkalian dan pembagian pecahan aljabar.',
        ]);

        // Content in Desain Pembelajaran should be populated
        $this->assertEquals('Peserta didik mampu memahami operasi aljabar bilangan pecahan.', $mutated['content']['capaian_pembelajaran']);
        $this->assertCount(2, $mutated['content']['tujuan_pembelajaran']);

        // Temporary keys cleaned up
        $this->assertArrayNotHasKey('new_cp_deskripsi', $mutated);
        $this->assertArrayNotHasKey('new_tps', $mutated);
    }

    public function test_tujuan_pembelajaran_auto_generates_kode_without_manual_input(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'fase' => 'Fase D (SMP)',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'elemen' => 'Bilangan',
            'deskripsi_cp' => 'Capaian Bilangan',
            'masa_berlaku' => '1 Tahun',
        ]);

        // Create TP without specifying kode_tp
        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'deskripsi_tp' => 'Memahami bilangan desimal dan persen.',
            'materi' => 'Bilangan Desimal',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $this->assertNotEmpty($tp->kode_tp);
        $this->assertStringStartsWith('TP.7.', $tp->kode_tp);
        $this->assertDatabaseHas('tujuan_pembelajarans', [
            'id' => $tp->id,
            'deskripsi_tp' => 'Memahami bilangan desimal dan persen.',
        ]);
    }

    public function test_atp_is_automatically_synced_from_all_inputted_tps_of_teacher(): void
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
            'deskripsi_cp' => 'Capaian Aljabar',
            'masa_berlaku' => '1 Tahun',
        ]);

        $tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'deskripsi_tp' => 'TP 1 Aljabar Dasar',
            'materi' => 'Aljabar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        $tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'deskripsi_tp' => 'TP 2 Aljabar Lanjutan',
            'materi' => 'Aljabar',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        // ATP should have been automatically created and synced
        $atp = AlurTujuanPembelajaran::where('guru_id', $this->teacher->id)
            ->where('subject_id', $this->subject->id)
            ->where('tingkat', '7')
            ->first();

        $this->assertNotNull($atp);
        $this->assertContains($tp1->id, $atp->tp_ids);
        $this->assertContains($tp2->id, $atp->tp_ids);
        $this->assertCount(2, $atp->tp_ids);
    }

    public function test_rppm_can_be_saved_as_draft_halfway_and_then_published(): void
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
            'masa_berlaku' => '1 Tahun',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->teacher->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->subject->id,
            'deskripsi_tp' => 'TP 1 Geometri Bangun Ruang',
            'materi' => 'Geometri',
            'kelas_tingkat' => '7',
            'semester' => 'Ganjil',
        ]);

        // 1. Simpan Sementara (Draft) saat baru mengisi sebagian (misal: hanya CP dan TP pada Desain Pembelajaran)
        Livewire::test(\App\Filament\Staff\Resources\RppmResource\Pages\CreateRppm::class)
            ->fillForm([
                'kelas_fase' => 'Kelas 7 / Fase D',
                'mata_pelajaran' => $this->subject->name,
                'topik' => 'Bangun Ruang Sisi Datar (Draft)',
                'capaian_pembelajaran_id' => $cp->id,
                'selected_tp_ids' => [$tp->id],
                'content' => [
                    'capaian_pembelajaran' => $cp->deskripsi_cp,
                    'lintas_disiplin' => 'Fisika & Seni Budaya',
                ],
            ])
            ->call('saveDraft')
            ->assertHasNoFormErrors();

        $rppm = Rppm::where('topik', 'Bangun Ruang Sisi Datar (Draft)')->first();
        $this->assertNotNull($rppm);
        $this->assertEquals('draft', $rppm->status);
        $this->assertEquals($cp->id, $rppm->capaian_pembelajaran_id);
        $this->assertContains($tp->id, $rppm->selected_tp_ids);

        // 2. Lanjutkan Pengisian dan Publikasikan (Selesai)
        Livewire::test(\App\Filament\Staff\Resources\RppmResource\Pages\EditRppm::class, [
            'record' => $rppm->id,
        ])
            ->fillForm([
                'topik' => 'Bangun Ruang Sisi Datar - Selesai',
                'alokasi_waktu' => '2 x 40 Menit',
            ])
            ->call('save') // submit save untuk publikasi
            ->assertHasNoFormErrors();

        $rppm->refresh();
        $this->assertEquals('published', $rppm->status);
        $this->assertEquals('Bangun Ruang Sisi Datar - Selesai', $rppm->topik);
    }
}
