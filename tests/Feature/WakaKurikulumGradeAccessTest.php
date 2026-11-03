<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WakaKurikulumGradeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakakur;
    protected User $guruPJOK;
    protected Subject $mapelIPA; // Diampu oleh Wakakur
    protected Subject $mapelPJOK; // TIDAK diampu oleh Wakakur
    protected Classroom $kelas;
    protected Student $siswa;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->wakakur = User::create([
            'name' => 'Waka Kurikulum Nadila, M.Pd',
            'email' => 'wakakur_test@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakakur->assignRole('waka_kurikulum');

        $this->guruPJOK = User::create([
            'name' => 'Guru PJOK Budi, S.Pd',
            'email' => 'pjok_test@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruPJOK->assignRole('guru_mapel');

        $this->kelas = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 'VII',
            'institution_id' => $this->institution->id,
        ]);

        $this->siswa = Student::create([
            'name' => 'Ahmad Fulan',
            'nis' => '12345',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'classroom_id' => $this->kelas->id,
            'institution_id' => $this->institution->id,
            'status' => 'aktif',
        ]);

        $this->mapelIPA = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelPJOK = Subject::create([
            'name' => 'Pendidikan Jasmani dan Olahraga',
            'kode' => 'PJOK',
            'institution_id' => $this->institution->id,
        ]);

        // Wakakur HANYA ditugaskan mengampu mapel IPA
        TeacherSubjectClass::create([
            'teacher_id' => $this->wakakur->id,
            'subject_id' => $this->mapelIPA->id,
            'classroom_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'institution_id' => $this->institution->id,
        ]);

        // Guru PJOK mengampu PJOK
        TeacherSubjectClass::create([
            'teacher_id' => $this->guruPJOK->id,
            'subject_id' => $this->mapelPJOK->id,
            'classroom_id' => $this->kelas->id,
            'academic_year_id' => $this->academicYear->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: User helper teachesSubject & getTeachedSubjectIds mendeteksi mapel yang diampu
     */
    public function test_user_teaches_subject_helper(): void
    {
        $this->assertTrue($this->wakakur->teachesSubject($this->mapelIPA->id));
        $this->assertFalse($this->wakakur->teachesSubject($this->mapelPJOK->id));

        $teached = $this->wakakur->getTeachedSubjectIds();
        $this->assertContains($this->mapelIPA->id, $teached);
        $this->assertNotContains($this->mapelPJOK->id, $teached);
    }

    /**
     * Test 2: Wakakur melihat mapel PJOK (tidak diampu) berstatus View Only di InputNilaiMapel
     */
    public function test_wakakur_views_unassigned_subject_as_read_only(): void
    {
        $this->actingAs($this->wakakur);

        // Buat nilai awal untuk PJOK oleh Guru PJOK
        Grade::create([
            'student_id' => $this->siswa->id,
            'subject_id' => $this->mapelPJOK->id,
            'classroom_id' => $this->kelas->id,
            'teacher_id' => $this->guruPJOK->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 88,
        ]);

        $test = Livewire::test(InputNilaiMapel::class, [
            'subject_id' => $this->mapelPJOK->id,
            'classroom_id' => $this->kelas->id,
        ]);

        // canEditCurrentSubject harus bernilai false
        $this->assertFalse($test->instance()->canEditCurrentSubject());

        // View memuat indikator View Only & Mode Hanya Lihat
        $test->assertSee('Mode Hanya Lihat (View Only)')
            ->assertSee('Tabel Rekapitulasi Nilai')
            ->assertSee('88')
            ->assertDontSee('Simpan Semua Nilai (PJOK)');

        // Percobaan memanggil saveScores harus ditolak dan tidak boleh mengubah nilai
        $test->set("scores.{$this->siswa->id}.pengetahuan", '99');
        $test->call('saveScores');
        $test->assertNotified('Akses Ditolak');

        // Nilai di database tetap 88
        $this->assertEquals(88, Grade::where('student_id', $this->siswa->id)->where('subject_id', $this->mapelPJOK->id)->value('score'));
    }

    /**
     * Test 3: Wakakur pada mapel IPA (yang diampu) memiliki akses edit penuh
     */
    public function test_wakakur_can_edit_assigned_subject(): void
    {
        $this->actingAs($this->wakakur);

        $test = Livewire::test(InputNilaiMapel::class, [
            'subject_id' => $this->mapelIPA->id,
            'classroom_id' => $this->kelas->id,
        ]);

        // canEditCurrentSubject harus bernilai true
        $this->assertTrue($test->instance()->canEditCurrentSubject());

        $test->assertSee('Rumah Mapel Aktif (Dapat Diinput)')
            ->assertSee('Simpan Semua Nilai (IPA)');

        // Wakakur bisa menyimpan nilai pada mapel yang diampu
        $test->set("scores.{$this->siswa->id}.pengetahuan", '85');
        $test->call('saveScores');

        $this->assertDatabaseHas('grades', [
            'student_id' => $this->siswa->id,
            'subject_id' => $this->mapelIPA->id,
            'score' => 85,
        ]);
    }

    /**
     * Test 4: GradePolicy mencegah Wakakur mengupdate atau menghapus nilai mapel yang tidak diampu
     */
    public function test_grade_policy_restricts_wakakur_on_unassigned_subject(): void
    {
        $gradePJOK = Grade::create([
            'student_id' => $this->siswa->id,
            'subject_id' => $this->mapelPJOK->id,
            'classroom_id' => $this->kelas->id,
            'teacher_id' => $this->guruPJOK->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 80,
        ]);

        $gradeIPA = Grade::create([
            'student_id' => $this->siswa->id,
            'subject_id' => $this->mapelIPA->id,
            'classroom_id' => $this->kelas->id,
            'teacher_id' => $this->wakakur->id,
            'academic_year_id' => $this->academicYear->id,
            'semester' => 'Ganjil',
            'type' => 'pengetahuan',
            'score' => 90,
        ]);

        // Wakakur bisa VIEW semua nilai
        $this->assertTrue($this->wakakur->can('view', $gradePJOK));
        $this->assertTrue($this->wakakur->can('view', $gradeIPA));

        // Wakakur TIDAK BISA update atau delete grade PJOK (tidak diampu)
        $this->assertFalse($this->wakakur->can('update', $gradePJOK));
        $this->assertFalse($this->wakakur->can('delete', $gradePJOK));

        // Wakakur BISA update dan delete grade IPA (diampu)
        $this->assertTrue($this->wakakur->can('update', $gradeIPA));
        $this->assertTrue($this->wakakur->can('delete', $gradeIPA));
    }
}
