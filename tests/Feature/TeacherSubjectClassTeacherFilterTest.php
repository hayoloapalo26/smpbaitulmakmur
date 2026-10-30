<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherSubjectClassTeacherFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $superAdmin;
    protected User $staffTu;
    protected User $guruMapel;
    protected User $guruQuran;
    protected User $inactiveGuru;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);

        Institution::create(['slug' => 'yayasan', 'name' => 'Yayasan Baitul Makmur']);
        $this->institution = Institution::create(['slug' => 'smp', 'name' => 'SMP Islam Baitul Makmur']);

        // 1. Super Admin (TIDAK BOLEH muncul di filter guru mapel)
        $this->superAdmin = User::create([
            'name' => 'Super Admin Lana',
            'username' => 'superadmin_lana',
            'email' => 'superadmin@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('superadmin');

        // 2. Staff TU (TIDAK BOLEH muncul di filter guru mapel)
        $this->staffTu = User::create([
            'name' => 'Staff Tata Usaha',
            'username' => 'staff_tu',
            'email' => 'tu@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->staffTu->assignRole('tata_usaha');

        // 3. Guru Mapel Resmi (HARUS muncul di filter guru mapel)
        $this->guruMapel = User::create([
            'name' => 'Rina Melati, S.Pd',
            'username' => 'rina_melati',
            'email' => 'rina@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        // 4. Guru Al-Qur'an (HARUS muncul karena mengajar mata pelajaran Al-Qur'an)
        $this->guruQuran = User::create([
            'name' => 'Ustadz Ahmad Al-Hafidz',
            'username' => 'ust_ahmad',
            'email' => 'ahmad@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruQuran->assignRole('guru_quran_tahsin');

        // 5. Guru Tidak Aktif (TIDAK BOLEH muncul)
        $this->inactiveGuru = User::create([
            'name' => 'Guru Nonaktif, S.Pd',
            'username' => 'guru_nonaktif',
            'email' => 'nonaktif@smpi.test',
            'password' => bcrypt('password123'),
            'institution_id' => $this->institution->id,
            'is_active' => false,
        ]);
        $this->inactiveGuru->assignRole('guru_mapel');
    }

    /**
     * Test: Filter Guru pada tabel Setting Guru & Mapel HANYA menampilkan guru mapel/quran,
     * dan secara tegas TIDAK menampilkan akun Super Admin Lana atau staf non-guru.
     */
    public function test_teacher_filter_in_subject_class_only_includes_teachers_and_excludes_superadmin(): void
    {
        $this->actingAs($this->superAdmin);

        // Uji query resmi getEligibleTeachersQuery yang digunakan pada filter dan form
        $filteredUserIds = TeacherSubjectClassResource::getEligibleTeachersQuery(null, $this->superAdmin)->pluck('id')->toArray();

        // 1. Guru Mapel dan Guru Al-Qur'an AKTIF HARUS ada di dalam filter
        $this->assertContains($this->guruMapel->id, $filteredUserIds, "Guru Mapel harus ada di opsi filter guru.");
        $this->assertContains($this->guruQuran->id, $filteredUserIds, "Guru Al-Qur'an harus ada di opsi filter guru.");

        // 2. Super Admin Lana dan Staf TU DILARANG muncul di opsi filter guru
        $this->assertNotContains($this->superAdmin->id, $filteredUserIds, "Super Admin Lana TIDAK BOLEH muncul di opsi filter guru!");
        $this->assertNotContains($this->staffTu->id, $filteredUserIds, "Staff TU TIDAK BOLEH muncul di opsi filter guru!");

        // 3. Guru nonaktif TIDAK BOLEH muncul
        $this->assertNotContains($this->inactiveGuru->id, $filteredUserIds, "Guru yang tidak aktif TIDAK BOLEH muncul di opsi filter guru!");
    }
}

