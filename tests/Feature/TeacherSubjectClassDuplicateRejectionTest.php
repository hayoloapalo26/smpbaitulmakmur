<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherSubjectClassDuplicateRejectionTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru1;
    protected User $guru2;
    protected Subject $mapelInformatika;
    protected Classroom $kelas7;
    protected Classroom $kelas8;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::create(['slug' => 'yayasan', 'name' => 'Yayasan Baitul Makmur']);
        $this->institution = Institution::create(['slug' => 'smp', 'name' => 'SMP Islam Baitul Makmur']);

        $this->guru1 = User::create([
            'name' => 'Siti Ismiati Habibatussalamah, S.Kom',
            'username' => 'siti_ismiati',
            'email' => 'siti@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru1->assignRole('guru_mapel');

        $this->guru2 = User::create([
            'name' => 'Budi Prasetyo, S.Kom',
            'username' => 'budi_prasetyo',
            'email' => 'budi@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru2->assignRole('guru_mapel');

        $this->mapelInformatika = Subject::create([
            'name' => 'Informatika',
            'kode' => 'INF-7',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelas7 = Classroom::create([
            'name' => 'Kelas 7',
            'tingkat' => 'SMP',
            'tipe' => Classroom::TIPE_REGULER,
            'institution_id' => $this->institution->id,
        ]);

        $this->kelas8 = Classroom::create([
            'name' => 'Kelas 8',
            'tingkat' => 'SMP',
            'tipe' => Classroom::TIPE_REGULER,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Penugasan pertama untuk Mapel Informatika di Kelas 7 berhasil
     */
    public function test_first_teacher_subject_class_assignment_succeeds(): void
    {
        $tsc = TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('teacher_subject_classes', [
            'id' => $tsc->id,
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
        ]);
    }

    /**
     * Test 2: Input dobel untuk Mapel yang sama di Kelas yang sama otomatis DITOLAK sistem
     */
    public function test_duplicate_subject_in_same_class_is_strictly_rejected(): void
    {
        // Penugasan pertama berhasil
        TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);

        // Percobaan input kedua dengan guru yang sama (kasus dobel seperti di screenshot) HARUS DITOLAK
        $this->expectException(ValidationException::class);

        TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 3: Guru lain yang mencoba mengampu mapel yang sudah terisi di kelas tersebut juga DITOLAK
     */
    public function test_another_teacher_assigning_already_taken_subject_class_is_rejected(): void
    {
        TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->expectException(ValidationException::class);

        // Guru 2 mencoba mengampu Informatika di Kelas 7 yang sudah ada guru 1
        TeacherSubjectClass::create([
            'teacher_id' => $this->guru2->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 4: Guru yang sama boleh mengampu mapel di kelas yang berbeda (Kelas 8)
     */
    public function test_same_teacher_can_teach_same_subject_in_different_class(): void
    {
        $tsc7 = TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas7->id,
            'institution_id' => $this->institution->id,
        ]);

        $tsc8 = TeacherSubjectClass::create([
            'teacher_id' => $this->guru1->id,
            'subject_id' => $this->mapelInformatika->id,
            'classroom_id' => $this->kelas8->id,
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $tsc7->id]);
        $this->assertDatabaseHas('teacher_subject_classes', ['id' => $tsc8->id]);
    }
}
