<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\StudentResource as StaffStudentResource;
use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Filament\Yayasan\Resources\StudentResource as YayasanStudentResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleBasedDataGuruAndDataSiswaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected Institution $sd;

    protected User $superadmin;
    protected User $kepsekSmp;
    protected User $tuSmp;
    protected User $wakaKurikulumSmp;
    protected User $wakaKesiswaanSmp;
    protected User $guruMapelSmp;
    protected User $guruQuranSmp;
    protected User $waliKelasSmp;

    protected User $pembinaYayasan;
    protected User $ketuaYayasan;
    protected User $sekretarisYayasan;
    protected User $keuanganYayasan;
    protected User $timPengembangYayasan;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            'superadmin', 'kepala_sekolah', 'tata_usaha', 'waka_kurikulum', 'waka_kesiswaan',
            'guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'koordinator_madin',
            'pembina_yayasan', 'ketua_yayasan', 'sekretaris_yayasan', 'pegawai_keuangan_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan'
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->yayasan = Institution::firstOrCreate(['id' => 1], ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->smp = Institution::firstOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);
        $this->sd = Institution::firstOrCreate(['id' => 3], ['name' => 'SD Islam Baitul Makmur', 'slug' => 'sd']);

        // Lembaga SMP users
        $this->superadmin = User::create(['name' => 'Super Admin', 'username' => 'admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->superadmin->assignRole('superadmin');

        $this->kepsekSmp = User::create(['name' => 'Kepsek SMP', 'username' => 'kepsek_smp', 'email' => 'kepsek@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->kepsekSmp->assignRole('kepala_sekolah');

        $this->tuSmp = User::create(['name' => 'TU SMP', 'username' => 'tu_smp', 'email' => 'tu@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->tuSmp->assignRole('tata_usaha');

        $this->wakaKurikulumSmp = User::create(['name' => 'Waka Kurikulum SMP', 'username' => 'waka_kur_smp', 'email' => 'wakakur@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->wakaKurikulumSmp->assignRole('waka_kurikulum');

        $this->wakaKesiswaanSmp = User::create(['name' => 'Waka Kesiswaan SMP', 'username' => 'waka_kes_smp', 'email' => 'wakakes@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->wakaKesiswaanSmp->assignRole('waka_kesiswaan');

        $this->guruMapelSmp = User::create(['name' => 'Guru Mapel SMP', 'username' => 'guru_mapel_smp', 'email' => 'gurumapel@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->guruMapelSmp->assignRole('guru_mapel');

        $this->guruQuranSmp = User::create(['name' => 'Guru Quran SMP', 'username' => 'guru_quran_smp', 'email' => 'guruquran@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->guruQuranSmp->assignRole('guru_alquran');

        $this->waliKelasSmp = User::create(['name' => 'Wali Kelas SMP', 'username' => 'wali_kelas_smp', 'email' => 'walikelas@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $this->waliKelasSmp->assignRole('wali_kelas');

        // Yayasan users
        $this->pembinaYayasan = User::create(['name' => 'Pembina Yayasan', 'username' => 'pembina', 'email' => 'pembina@yayasan.test', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->pembinaYayasan->assignRole('pembina_yayasan');

        $this->ketuaYayasan = User::create(['name' => 'Ketua Yayasan', 'username' => 'ketua', 'email' => 'ketua@yayasan.test', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->ketuaYayasan->assignRole('ketua_yayasan');

        $this->sekretarisYayasan = User::create(['name' => 'Sekretaris Yayasan', 'username' => 'sekretaris', 'email' => 'sekretaris@yayasan.test', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->sekretarisYayasan->assignRole('sekretaris_yayasan');

        $this->keuanganYayasan = User::create(['name' => 'Keuangan Yayasan', 'username' => 'keuangan', 'email' => 'keuangan@yayasan.test', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->keuanganYayasan->assignRole('pegawai_keuangan_yayasan');

        $this->timPengembangYayasan = User::create(['name' => 'Tim Pengembang Yayasan', 'username' => 'penjamin_mutu', 'email' => 'mutu@yayasan.test', 'password' => bcrypt('password'), 'institution_id' => $this->yayasan->id]);
        $this->timPengembangYayasan->assignRole('tim_pengembang_penjamin_mutu_yayasan');
    }

    /**
     * Test 1: Fitur Data Guru HANYA muncul di role Kepala Sekolah, Tata Usaha, dan Waka Kurikulum (+ Superadmin)
     */
    public function test_data_guru_only_accessible_by_kepsek_tu_and_waka_kurikulum(): void
    {
        // 1. Roles yang DIIZINKAN:
        $this->actingAs($this->kepsekSmp);
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->tuSmp);
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->wakaKurikulumSmp);
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->superadmin);
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherProfileResource::shouldRegisterNavigation());

        // 2. Roles yang TIDAK DIIZINKAN (harus false):
        $this->actingAs($this->wakaKesiswaanSmp);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->guruMapelSmp);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->guruQuranSmp);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->waliKelasSmp);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->pembinaYayasan);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());

        $this->actingAs($this->ketuaYayasan);
        $this->assertFalse(TeacherProfileResource::canAccess());
        $this->assertFalse(TeacherProfileResource::shouldRegisterNavigation());
    }

    /**
     * Test 2: Data Guru diisolasi ketat per lembaga masing-masing (TU SMP hanya melihat guru SMP)
     */
    public function test_data_guru_isolated_per_institution(): void
    {
        // Guru di SMP
        $guruSmp = User::create(['name' => 'Guru IPA SMP', 'username' => 'guru_ipa', 'email' => 'ipa@smp.test', 'password' => bcrypt('password'), 'institution_id' => $this->smp->id]);
        $guruSmp->assignRole('guru_mapel');

        // Guru di SD
        $guruSd = User::create(['name' => 'Guru Kelas SD', 'username' => 'guru_sd', 'email' => 'sd@sd.test', 'password' => bcrypt('password'), 'institution_id' => $this->sd->id]);
        $guruSd->assignRole('guru_kelas');

        // Login sebagai TU SMP
        $this->actingAs($this->tuSmp);
        $teacherQuery = TeacherProfileResource::getEloquentQuery()->get();

        $this->assertTrue($teacherQuery->contains('id', $guruSmp->id));
        $this->assertFalse($teacherQuery->contains('id', $guruSd->id));

        // Login sebagai Superadmin: melihat guru SMP dan SD
        $this->actingAs($this->superadmin);
        $adminQuery = TeacherProfileResource::getEloquentQuery()->get();
        $this->assertTrue($adminQuery->contains('id', $guruSmp->id));
        $this->assertTrue($adminQuery->contains('id', $guruSd->id));
    }

    /**
     * Test 3: Data Siswa ditampilkan di SEMUA role lembaga (Kepsek, TU, Waka, Guru Mapel, Guru Quran, Wali Kelas, dll)
     */
    public function test_data_siswa_accessible_by_all_institution_roles(): void
    {
        $institutionRoles = [
            $this->kepsekSmp,
            $this->tuSmp,
            $this->wakaKurikulumSmp,
            $this->wakaKesiswaanSmp,
            $this->guruMapelSmp,
            $this->guruQuranSmp,
            $this->waliKelasSmp,
        ];

        foreach ($institutionRoles as $user) {
            $this->actingAs($user);
            $this->assertTrue(StaffStudentResource::canAccess(), "User {$user->name} should be able to access Data Siswa");
            $this->assertTrue(StaffStudentResource::shouldRegisterNavigation(), "User {$user->name} should see Data Siswa navigation");
        }
    }

    /**
     * Test 4: Data Siswa ditampilkan di role Yayasan (Pembina, Ketua, Sekretaris, Keuangan, Tim Pengembang & Penjamin Mutu)
     */
    public function test_data_siswa_accessible_by_all_yayasan_roles(): void
    {
        $yayasanRoles = [
            $this->pembinaYayasan,
            $this->ketuaYayasan,
            $this->sekretarisYayasan,
            $this->keuanganYayasan,
            $this->timPengembangYayasan,
            $this->superadmin,
        ];

        foreach ($yayasanRoles as $user) {
            $this->actingAs($user);

            // Akses di panel Staff
            $this->assertTrue(StaffStudentResource::canAccess(), "Yayasan User {$user->name} should be able to access Staff StudentResource");
            $this->assertTrue(StaffStudentResource::shouldRegisterNavigation(), "Yayasan User {$user->name} should see Staff StudentResource navigation");

            // Akses di panel Yayasan
            $this->assertTrue(YayasanStudentResource::canAccess(), "Yayasan User {$user->name} should be able to access Yayasan StudentResource");
        }
    }

    /**
     * Test 5: Query Data Siswa per lembaga vs Yayasan
     */
    public function test_data_siswa_query_scoping(): void
    {
        $siswaSmp = Student::create(['name' => 'Siswa SMP', 'nis' => '1001', 'institution_id' => $this->smp->id, 'jenis_kelamin' => 'L']);
        $siswaSd = Student::create(['name' => 'Siswa SD', 'nis' => '4001', 'institution_id' => $this->sd->id, 'jenis_kelamin' => 'P']);

        // Guru Mapel SMP melihat siswa SMP di lembaganya
        $this->actingAs($this->guruMapelSmp);
        $smpStudents = StaffStudentResource::getEloquentQuery()->get();
        $this->assertTrue($smpStudents->contains('id', $siswaSmp->id));
        $this->assertFalse($smpStudents->contains('id', $siswaSd->id));

        // Guru Al-Qur'an SMP melihat siswa SMP di lembaganya
        $this->actingAs($this->guruQuranSmp);
        $quranStudents = StaffStudentResource::getEloquentQuery()->get();
        $this->assertTrue($quranStudents->contains('id', $siswaSmp->id));
        $this->assertFalse($quranStudents->contains('id', $siswaSd->id));

        // Yayasan (Ketua Yayasan) melihat seluruh siswa lintas lembaga
        $this->actingAs($this->ketuaYayasan);
        $yayasanStudentsStaff = StaffStudentResource::getEloquentQuery()->get();
        $this->assertTrue($yayasanStudentsStaff->contains('id', $siswaSmp->id));
        $this->assertTrue($yayasanStudentsStaff->contains('id', $siswaSd->id));

        $yayasanStudentsPanel = YayasanStudentResource::getEloquentQuery()->get();
        $this->assertTrue($yayasanStudentsPanel->contains('id', $siswaSmp->id));
        $this->assertTrue($yayasanStudentsPanel->contains('id', $siswaSd->id));
    }
}
