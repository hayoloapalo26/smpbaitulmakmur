<?php

namespace Tests\Feature;

use App\Models\AttributeViolation;
use App\Models\BkReport;
use App\Models\CharacterScoreLog;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentDisciplineAndCaseInterconnectionTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $classroom;
    protected Student $student;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'email' => 'walikelas@baitulmakmur.sch.id',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->teacher->assignRole(['wali_kelas', 'superadmin']);
        $this->actingAs($this->teacher);

        $this->classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'VII-A',
            'tingkat' => '7',
            'wali_kelas_id' => $this->teacher->id,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '12345',
            'nisn' => '0012345678',
            'name' => 'Ahmad Fulan',
            'tanggal_lahir' => '2012-05-10',
            'jenis_kelamin' => 'L',
            'nama_ayah' => 'Bapak Fulan',
            'nama_ibu' => 'Ibu Fulan',
            'no_telepon_orang_tua' => '081234567890',
            'character_score' => 100,
        ]);
    }

    public function test_student_relationships_exist_and_functional(): void
    {
        $this->assertTrue(method_exists($this->student, 'parentSummons'));
        $this->assertTrue(method_exists($this->student, 'attributeViolations'));
        $this->assertTrue(method_exists($this->student, 'studentCases'));
        $this->assertTrue(method_exists($this->student, 'characterScoreLogs'));

        $this->assertCount(0, $this->student->parentSummons);
        $this->assertCount(0, $this->student->attributeViolations);
        $this->assertCount(0, $this->student->studentCases);
    }

    public function test_creating_student_case_automatically_logs_character_points_and_deducts_score(): void
    {
        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-001',
            'tanggal' => now()->toDateString(),
            'kategori' => 'sedang',
            'deskripsi' => 'Terlambat masuk kelas dan tidak mengerjakan tugas berulang kali',
            'tindakan_wali_kelas' => 'Diberikan peringatan lisan',
            'status' => 'proses',
        ]);

        $this->student->refresh();
        $this->assertEquals(95, $this->student->character_score);

        $this->assertDatabaseHas('character_score_logs', [
            'student_id' => $this->student->id,
            'category' => 'kedisiplinan',
            'point_change' => -5,
        ]);
    }

    public function test_escalating_student_case_to_bk_creates_detailed_bk_report(): void
    {
        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-002',
            'tanggal' => now()->toDateString(),
            'kategori' => 'berat',
            'deskripsi' => 'Perkelahian di luar jam istirahat',
            'tindakan_wali_kelas' => 'Dipanggil ke ruang wali kelas',
            'status' => 'proses',
        ]);

        $case->update([
            'recommended_to_bk' => true,
            'ditangani_oleh' => 'bk',
            'catatan_wali_kelas' => 'Perlu bimbingan khusus dari konselor BK',
        ]);

        $bkReport = BkReport::where('student_id', $this->student->id)->latest()->first();
        $this->assertNotNull($bkReport);
        $this->assertStringContainsString('KASUS-2026-002', $bkReport->isi_laporan);
        $this->assertStringContainsString('Perkelahian di luar jam istirahat', $bkReport->isi_laporan);
    }

    public function test_resolving_student_case_automatically_awards_restoration_points(): void
    {
        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-003',
            'tanggal' => now()->toDateString(),
            'kategori' => 'ringan',
            'deskripsi' => 'Meninggalkan perlengkapan sekolah',
            'status' => 'proses',
        ]);

        $this->student->refresh();
        $this->assertEquals(95, $this->student->character_score);

        $case->update([
            'status' => 'selesai',
            'hasil_penanganan' => 'Siswa telah meminta maaf dan berkomitmen memperbaiki diri',
        ]);

        $this->student->refresh();
        $this->assertEquals(97, $this->student->character_score);

        $this->assertDatabaseHas('character_score_logs', [
            'student_id' => $this->student->id,
            'category' => 'kedisiplinan',
            'point_change' => 2,
        ]);
    }

    public function test_parent_summon_creation_syncs_with_linked_student_case(): void
    {
        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-004',
            'tanggal' => now()->toDateString(),
            'kategori' => 'berat',
            'deskripsi' => 'Merusak fasilitas lab',
            'status' => 'proses',
        ]);

        $summon = ParentSummon::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'student_case_id' => $case->id,
            'teacher_id' => $this->teacher->id,
            'nomor_surat' => '045/SMPI/SP/X/2026',
            'tanggal_pemanggilan' => now()->toDateString(),
            'tanggal_pertemuan' => now()->addDays(2)->toDateString(),
            'tempat_pertemuan' => 'Ruang BK / Wali Kelas',
            'kasus' => 'Klarifikasi dan pembinaan terkait insiden fasilitas lab',
            'status' => 'menunggu',
        ]);

        $case->refresh();
        $this->assertStringContainsString('045/SMPI/SP/X/2026', (string) $case->catatan_wali_kelas);
        $this->assertStringContainsString('SURAT PANGGILAN ORANG TUA DITERBITKAN', (string) $case->catatan_wali_kelas);
    }

    public function test_parent_summon_attendance_outcome_syncs_into_student_case(): void
    {
        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-005',
            'tanggal' => now()->toDateString(),
            'kategori' => 'sedang',
            'deskripsi' => 'Sering membolos pada jam pelajaran siang',
            'status' => 'proses',
        ]);

        $summon = ParentSummon::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'student_case_id' => $case->id,
            'teacher_id' => $this->teacher->id,
            'nomor_surat' => '046/SMPI/SP/X/2026',
            'tanggal_pemanggilan' => now()->toDateString(),
            'tanggal_pertemuan' => now()->toDateString(),
            'tempat_pertemuan' => 'Ruang BK / Wali Kelas',
            'kasus' => 'Diskusi komitmen kehadiran',
            'status' => 'menunggu',
        ]);

        $summon->update([
            'status' => 'hadir',
            'hasil_pertemuan' => 'Orang tua bersedia mendampingi dan mengantar anak tepat waktu',
        ]);

        $case->refresh();
        $this->assertStringContainsString('[HASIL PERTEMUAN ORANG TUA', (string) $case->catatan_wali_kelas);
        $this->assertStringContainsString('Orang tua bersedia mendampingi dan mengantar anak tepat waktu', (string) $case->catatan_wali_kelas);
    }

    public function test_student_monitoring_modal_renders_with_all_interconnected_data(): void
    {
        StudentAchievement::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'nama_prestasi' => 'Juara 1 Lomba Kaligrafi Nasional',
            'kategori' => 'religius',
            'tingkat' => 'Nasional',
            'tanggal' => now()->toDateString(),
            'sertifikat' => 'https://drive.google.com/file/d/test-cert-drive-link/view',
            'keterangan' => 'Tingkat SMP se-Indonesia',
            'input_by' => $this->teacher->id,
        ]);

        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-006',
            'tanggal' => now()->toDateString(),
            'kategori' => 'ringan',
            'deskripsi' => 'Lupa membawa buku panduan',
            'status' => 'selesai',
        ]);

        ParentSummon::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'student_case_id' => $case->id,
            'teacher_id' => $this->teacher->id,
            'nomor_surat' => '047/SMPI/SP/X/2026',
            'tanggal_pemanggilan' => now()->toDateString(),
            'tanggal_pertemuan' => now()->toDateString(),
            'tempat_pertemuan' => 'Ruang BK',
            'kasus' => 'Koordinasi wali santri',
            'status' => 'hadir',
            'hasil_pertemuan' => 'Sudah disepakati solusi bersama',
            'surat_file' => 'https://drive.google.com/file/d/surat-panggilan/view',
        ]);

        AttributeViolation::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'tanggal' => now()->toDateString(),
            'attribute_type' => ['seragam'],
            'jenis_pelanggaran' => 'Kaus kaki tidak sesuai ketentuan',
        ]);

        $this->student->load([
            'studentAchievements.inputBy',
            'dailyAttendances',
            'studentCases.parentSummons',
            'parentSummons',
            'attributeViolations.teacher',
            'characterScoreLogs',
        ]);

        $view = view('filament.staff.modals.student-monitoring-modal', [
            'record' => $this->student,
        ])->render();

        $this->assertStringContainsString('Ahmad Fulan', $view);
        $this->assertStringContainsString('Juara 1 Lomba Kaligrafi Nasional', $view);
        $this->assertStringContainsString('Buka Link Drive', $view);
        $this->assertStringContainsString('KASUS-2026-006', $view);
        $this->assertStringContainsString('047/SMPI/SP/X/2026', $view);
        $this->assertStringContainsString('Kaus kaki tidak sesuai ketentuan', $view);
        $this->assertStringContainsString('Riwayat Poin Karakter', $view);
    }

    public function test_kepala_sekolah_can_only_view_parent_summons_and_cannot_create_or_edit(): void
    {
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        $kepsek = User::factory()->create([
            'email' => 'kepsek@baitulmakmur.sch.id',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $kepsek->assignRole('kepala_sekolah');

        $this->actingAs($kepsek);

        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->teacher->id,
            'nomor_kasus' => 'KASUS-2026-007',
            'tanggal' => now()->toDateString(),
            'kategori' => 'sedang',
            'deskripsi' => 'Ujian menyontek',
            'status' => 'proses',
        ]);

        $summon = ParentSummon::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'student_case_id' => $case->id,
            'teacher_id' => $this->teacher->id,
            'nomor_surat' => '048/SMPI/SP/X/2026',
            'tanggal_pemanggilan' => now()->toDateString(),
            'tanggal_pertemuan' => now()->addDay()->toDateString(),
            'tempat_pertemuan' => 'Ruang BK',
            'kasus' => 'Klarifikasi insiden menyontek',
            'status' => 'menunggu',
            'hasil_pertemuan' => 'Komitmen kejujuran akademik',
            'surat_file' => 'https://drive.google.com/file/d/sp-048/view',
        ]);

        $this->assertTrue(\App\Filament\Staff\Resources\ParentSummonResource::canAccess());
        $this->assertFalse(\App\Filament\Staff\Resources\ParentSummonResource::canCreate());
        $this->assertFalse(\App\Filament\Staff\Resources\ParentSummonResource::canEdit($summon));
        $this->assertFalse(\App\Filament\Staff\Resources\ParentSummonResource::canDelete($summon));

        $view = view('filament.staff.components.parent-summon-summary-table', [
            'record' => $summon,
        ])->render();

        $this->assertStringContainsString('Mode Tinjauan Kepala Sekolah', $view);
        $this->assertStringContainsString('Read-Only', $view);
        $this->assertStringContainsString('Surat Panggilan Orang Tua / Wali Murid', $view);
        $this->assertStringContainsString('048/SMPI/SP/X/2026', $view);
        $this->assertStringContainsString('Ahmad Fulan', $view);
        $this->assertStringContainsString('Bapak Fulan', $view);
        $this->assertStringContainsString('Klarifikasi insiden menyontek', $view);
        $this->assertStringContainsString('Komitmen kejujuran akademik', $view);
        $this->assertStringContainsString('https://drive.google.com/file/d/sp-048/view', $view);
    }
}
