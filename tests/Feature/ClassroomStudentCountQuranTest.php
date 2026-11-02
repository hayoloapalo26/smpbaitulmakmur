<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ClassroomResource\Pages\ListClassrooms;
use App\Filament\Staff\Resources\StudentResource\Pages\ManageStudents;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassroomStudentCountQuranTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected User $staffTu;
    protected User $guruZainal;
    protected User $guruTahsin;
    protected Classroom $kelas7A;
    protected Classroom $rombelYanbua;
    protected Classroom $rombelTahsin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_tahsin', 'guard_name' => 'web']);

        // 2. Institutions
        $this->yayasan = Institution::create([
            'id' => 1,
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
            'type' => 'yayasan',
        ]);

        $this->smp = Institution::create([
            'id' => 2,
            'name' => 'SMP IT Baitul Makmur',
            'slug' => 'smp',
            'type' => 'smp',
        ]);

        // 3. Teachers & Staff
        $this->guruZainal = User::create([
            'name' => 'Zaenal Musthofa, S.Pd',
            'email' => 'zainal@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruZainal->assignRole('guru_yanbua');

        $this->guruTahsin = User::create([
            'name' => 'Iqomatu Nouvi Khuluq, M.Pd',
            'email' => 'nouvi@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruTahsin->assignRole('guru_tahsin');

        $this->staffTu = User::create([
            'name' => 'Staff TU Baitul Makmur',
            'email' => 'tu@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->staffTu->assignRole('tata_usaha');

        // 4. Classrooms
        $this->kelas7A = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => '7',
            'tipe' => Classroom::TIPE_REGULER,
            'institution_id' => 2,
        ]);

        $this->rombelYanbua = Classroom::create([
            'name' => 'Yanbu\'a',
            'tingkat' => 'Yanbu\'a SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruZainal->id,
            'institution_id' => 2,
        ]);

        $this->rombelTahsin = Classroom::create([
            'name' => 'Tahsin',
            'tingkat' => 'Tahsin SMP',
            'tipe' => Classroom::TIPE_QURAN,
            'wali_kelas_id' => $this->guruTahsin->id,
            'institution_id' => 2,
        ]);
    }

    public function test_quran_classroom_counts_students_assigned_via_guru_quran_id(): void
    {
        // Student is in regular academic class 7A, but assigned to Pak Zainal for Quran
        $student1 = Student::create([
            'nis' => '1001',
            'name' => 'Santri Binaan Zainal 1',
            'classroom_id' => $this->kelas7A->id,
            'guru_quran_id' => $this->guruZainal->id,
            'kelas_quran' => 'yanbua',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'institution_id' => 2,
        ]);

        // Academic class 7A has 1 student
        $this->assertEquals(1, $this->kelas7A->getRealStudentsCount());

        // Yanbu'a (Pak Zainal) MUST count this student -> 1
        $this->assertEquals(1, $this->rombelYanbua->getRealStudentsCount());
        $this->assertEquals(1, $this->rombelYanbua->students_count);

        // Tahsin (Iqomatu) has 0 students
        $this->assertEquals(0, $this->rombelTahsin->getRealStudentsCount());

        // Check getRealStudents()
        $yanbuaStudents = $this->rombelYanbua->getRealStudents();
        $this->assertCount(1, $yanbuaStudents);
        $this->assertEquals('Santri Binaan Zainal 1', $yanbuaStudents->first()->name);
    }

    public function test_quran_classroom_counts_multiple_students_across_different_academic_classes(): void
    {
        $kelas7B = Classroom::create([
            'name' => 'Kelas 7B',
            'tingkat' => '7',
            'tipe' => Classroom::TIPE_REGULER,
            'institution_id' => 2,
        ]);

        // Santri 1 from 7A
        Student::create([
            'nis' => '1001',
            'name' => 'Santri A',
            'classroom_id' => $this->kelas7A->id,
            'guru_quran_id' => $this->guruZainal->id,
            'kelas_quran' => 'yanbua',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'institution_id' => 2,
        ]);

        // Santri 2 from 7B
        Student::create([
            'nis' => '1002',
            'name' => 'Santri B',
            'classroom_id' => $kelas7B->id,
            'guru_quran_id' => $this->guruZainal->id,
            'kelas_quran' => 'yanbua',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-02',
            'institution_id' => 2,
        ]);

        // Santri 3 for Tahsin
        Student::create([
            'nis' => '1003',
            'name' => 'Santri C',
            'classroom_id' => $this->kelas7A->id,
            'guru_quran_id' => $this->guruTahsin->id,
            'kelas_quran' => 'tahsin',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2010-01-03',
            'institution_id' => 2,
        ]);

        // Class 7A has 2 students (Santri A & C)
        $this->assertEquals(2, $this->kelas7A->getRealStudentsCount());

        // Class 7B has 1 student (Santri B)
        $this->assertEquals(1, $kelas7B->getRealStudentsCount());

        // Yanbu'a (Pak Zainal) has 2 students (Santri A & B)
        $this->assertEquals(2, $this->rombelYanbua->getRealStudentsCount());

        // Tahsin (Iqomatu) has 1 student (Santri C)
        $this->assertEquals(1, $this->rombelTahsin->getRealStudentsCount());
    }

    public function test_direct_quran_classroom_assignment_auto_syncs_teacher_and_group(): void
    {
        // When a student is assigned directly to Yanbu'a classroom
        $student = Student::create([
            'nis' => '1099',
            'name' => 'Santri Langsung Yanbua',
            'classroom_id' => $this->rombelYanbua->id,
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-05-05',
            'institution_id' => 2,
        ]);

        $student->refresh();

        // Auto-synced guru_quran_id and kelas_quran
        $this->assertEquals($this->guruZainal->id, $student->guru_quran_id);
        $this->assertEquals('yanbua', $student->kelas_quran);
        $this->assertNotNull($student->tanggal_penempatan_quran);

        // Count is 1
        $this->assertEquals(1, $this->rombelYanbua->getRealStudentsCount());
    }

    public function test_staff_tu_views_classroom_list_and_sees_correct_student_counts(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        // Assign 1 student to 7A and Pak Zainal's Yanbu'a
        Student::create([
            'nis' => '1001',
            'name' => 'Santri Binaan Zainal 1',
            'classroom_id' => $this->kelas7A->id,
            'guru_quran_id' => $this->guruZainal->id,
            'kelas_quran' => 'yanbua',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2010-01-01',
            'institution_id' => 2,
        ]);

        $this->actingAs($this->staffTu);

        Livewire::test(ListClassrooms::class)
            ->set('activeTab', 'quran')
            ->assertCanSeeTableRecords([$this->rombelYanbua, $this->rombelTahsin])
            ->assertTableColumnStateSet('students_count', 1, $this->rombelYanbua)
            ->assertTableColumnStateSet('students_count', 0, $this->rombelTahsin);
    }

    public function test_tu_can_create_student_with_quran_placement_in_student_resource(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->actingAs($this->staffTu);

        Livewire::test(ManageStudents::class)
            ->callAction('create', [
                'nis' => '2001',
                'name' => 'Santri Baru TU 2001',
                'classroom_id' => $this->kelas7A->id,
                'kelas_quran' => 'yanbua',
                'guru_quran_id' => $this->guruZainal->id,
                'status_siswa' => 'aktif',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2010-06-01',
            ])
            ->assertHasNoActionErrors();

        $student = Student::where('nis', '2001')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Santri Baru TU 2001', $student->name);
        $this->assertEquals($this->kelas7A->id, $student->classroom_id);
        $this->assertEquals($this->guruZainal->id, $student->guru_quran_id);
        $this->assertEquals('yanbua', $student->kelas_quran);

        // Verify both 7A and Yanbu'a count reflect this student immediately
        $this->assertEquals(1, $this->kelas7A->getRealStudentsCount());
        $this->assertEquals(1, $this->rombelYanbua->getRealStudentsCount());
    }
}
