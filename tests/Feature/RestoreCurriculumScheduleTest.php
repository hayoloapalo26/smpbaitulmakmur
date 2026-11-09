<?php

namespace Tests\Feature;

use App\Console\Commands\RestoreCurriculumScheduleCommand;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestoreCurriculumScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_curriculum_schedule_creates_schedules_and_assignments_without_clashes()
    {
        // Setup Institusi
        Institution::query()->delete();
        $inst1 = Institution::create([
            'id'   => 1,
            'name' => 'Yayasan LPI Baitul Makmur',
            'slug' => 'yayasan',
        ]);
        $inst2 = Institution::create([
            'id'   => 2,
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        // Buat beberapa guru
        $guru1 = User::create([
            'name' => 'Guru Matematika',
            'email' => 'mtk@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'jabatan' => 'Guru Matematika',
            'is_active' => true,
        ]);
        $guru1->assignRole('guru_mapel');

        $guru2 = User::create([
            'name' => 'Guru IPA',
            'email' => 'ipa@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'jabatan' => 'Guru IPA',
            'is_active' => true,
        ]);
        $guru2->assignRole('guru_mapel');

        // Jalankan perintah restore
        $restored = RestoreCurriculumScheduleCommand::restoreSchedule(null, true);

        $this->assertGreaterThan(0, $restored);
        $this->assertGreaterThan(0, Schedule::withoutGlobalScopes()->count());
        $this->assertGreaterThan(0, TeacherSubjectClass::withoutGlobalScopes()->count());

        // Verifikasi semua schedule memiliki institution_id = 2
        $this->assertEquals(0, Schedule::withoutGlobalScopes()->where('institution_id', '!=', 2)->count());

        // Verifikasi tidak ada jadwal bentrok untuk kelas
        $classClashes = Schedule::withoutGlobalScopes()
            ->select('classroom_id', 'hari', 'jam_ke')
            ->groupBy('classroom_id', 'hari', 'jam_ke')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $this->assertCount(0, $classClashes, 'Tidak boleh ada jadwal bentrok di rombel kelas yang sama.');
    }
}
