<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Institution;
use App\Models\TeacherLeaveRequest;
use App\Models\TeacherAttendance;
use App\Models\Schedule;
use App\Models\Classroom;
use App\Models\Subject;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLeaveRequestGuruPiketTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(['slug' => 'smp-islam-baitul-makmur'], ['name' => 'SMP Islam Baitul Makmur']);
    }

    public function test_ensure_guru_piket_user_creates_account_with_role()
    {
        $guruPiket = User::ensureGuruPiketUser($this->institution->id);

        $this->assertNotNull($guruPiket);
        $this->assertEquals('Guru Piket', $guruPiket->name);
        $this->assertTrue($guruPiket->hasRole('guru_piket'));
    }

    public function test_teacher_can_submit_leave_with_guru_piket_as_substitute()
    {
        $teacher = User::create([
            'name' => 'Pak Ahmad',
            'email' => 'ahmad@smp.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
        ]);
        $teacher->assignRole('guru_mapel');

        $guruPiket = User::ensureGuruPiketUser($this->institution->id);

        $leave = TeacherLeaveRequest::create([
            'teacher_id' => $teacher->id,
            'institution_id' => $this->institution->id,
            'tipe_izin' => 'harian',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Sakit demam',
            'substitute_teacher_id' => $guruPiket->id,
            'status' => 'diajukan',
        ]);

        $this->assertEquals($guruPiket->id, $leave->substitute_teacher_id);
        $this->assertEquals('Guru Piket', $leave->substituteTeacher->name);
    }

    public function test_wakakur_can_approve_leave_and_assign_guru_piket()
    {
        $teacher = User::create([
            'name' => 'Pak Budi',
            'email' => 'budi@smp.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
        ]);
        $teacher->assignRole('guru_mapel');

        $wakakur = User::create([
            'name' => 'Bu Wakakur',
            'email' => 'wakakur@smp.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
        ]);
        $wakakur->assignRole('waka_kurikulum');

        $guruPiket = User::ensureGuruPiketUser($this->institution->id);

        // Ajukan izin tanpa guru pengganti awal
        $leave = TeacherLeaveRequest::create([
            'teacher_id' => $teacher->id,
            'institution_id' => $this->institution->id,
            'tipe_izin' => 'harian',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Dinas luar pelatihan',
            'substitute_teacher_id' => null,
            'status' => 'diajukan',
        ]);

        // Wakakur ACC dan memilih Guru Piket
        $leave->update([
            'status' => 'disetujui_wakakur',
            'substitute_teacher_id' => $guruPiket->id,
            'wakakur_approved_by' => $wakakur->id,
            'wakakur_approved_at' => now(),
        ]);

        $leave->refresh();
        $this->assertEquals('disetujui_wakakur', $leave->status);
        $this->assertEquals($guruPiket->id, $leave->substitute_teacher_id);
        $this->assertEquals('Guru Piket', $leave->substituteTeacher->name);

        // Verifikasi ke sinkronisasi presensi (TeacherAttendance)
        $attendance = TeacherAttendance::where('teacher_leave_request_id', $leave->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals($guruPiket->id, $attendance->substitute_teacher_id);
    }

    public function test_dropdown_query_includes_guru_piket_at_the_top()
    {
        $guruPiket = User::ensureGuruPiketUser($this->institution->id);

        $teacher1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi1@smp.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
        ]);
        $teacher1->assignRole('guru_mapel');

        $teacher2 = User::create([
            'name' => 'Andi Wijaya',
            'email' => 'andi@smp.com',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
        ]);
        $teacher2->assignRole('guru_mapel');

        $this->actingAs($teacher1);

        $query = User::query();
        $query->where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', [
                'guru_mapel',
                'guru_alquran',
                'wali_kelas',
                'guru_kelas',
                'waka_kurikulum',
                'guru_piket',
            ]))
            ->orWhere('name', 'Guru Piket')
            ->orWhere('username', 'gurupiket');
        });
        $query->where('id', '!=', $teacher1->id);
        $query->orderByRaw("CASE WHEN name = 'Guru Piket' THEN 0 WHEN name LIKE '%Piket%' THEN 1 ELSE 2 END, name ASC");

        $results = $query->get();

        $this->assertGreaterThanOrEqual(2, $results->count());
        $this->assertEquals('Guru Piket', $results->first()->name);
        $this->assertFalse($results->contains('id', $teacher1->id));
        $this->assertTrue($results->contains('id', $guruPiket->id));
        $this->assertTrue($results->contains('id', $teacher2->id));
    }
}
