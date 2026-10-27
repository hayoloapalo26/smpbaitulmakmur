<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use App\Filament\Staff\Pages\TeacherPerformanceDashboard as StaffPerformanceDashboard;
use App\Filament\Staff\Widgets\TeacherRankingTableWidget;
use App\Filament\Yayasan\Pages\TeacherPerformanceDashboard as YayasanPerformanceDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherRankingPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKurikulum;
    protected User $kepalaSekolah;
    protected User $ketuaYayasan;
    protected User $pembinaYayasan;
    protected User $sekretarisYayasan;
    protected User $timPenjaminMutu;
    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            'superadmin', 'kepala_sekolah', 'waka_kurikulum',
            'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan',
            'guru_mapel'
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->wakaKurikulum = User::create([
            'name' => 'Waka Kurikulum',
            'email' => 'kurikulum@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKurikulum->assignRole('waka_kurikulum');

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->ketuaYayasan = User::create([
            'name' => 'Ketua Yayasan',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->ketuaYayasan->assignRole('ketua_yayasan');

        $this->pembinaYayasan = User::create([
            'name' => 'Pembina Yayasan',
            'email' => 'pembina@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->pembinaYayasan->assignRole('pembina_yayasan');

        $this->sekretarisYayasan = User::create([
            'name' => 'Sekretaris Yayasan',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->sekretarisYayasan->assignRole('sekretaris_yayasan');

        $this->timPenjaminMutu = User::create([
            'name' => 'Tim Penjamin Mutu',
            'email' => 'mutu@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->timPenjaminMutu->assignRole('tim_pengembang_penjamin_mutu_yayasan');

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 1,
        ]);
        $this->superadmin->assignRole('superadmin');
    }

    public function test_waka_kurikulum_cannot_access_ranking_guru(): void
    {
        $this->actingAs($this->wakaKurikulum);

        // Harus false: ranking guru di kurikulum dihilangkan
        $this->assertFalse(StaffPerformanceDashboard::canAccess());
        $this->assertFalse(StaffPerformanceDashboard::shouldRegisterNavigation());
        $this->assertFalse(TeacherRankingTableWidget::canView());
    }

    public function test_kepala_sekolah_can_access_ranking_guru(): void
    {
        $this->actingAs($this->kepalaSekolah);

        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(StaffPerformanceDashboard::shouldRegisterNavigation());
        $this->assertTrue(TeacherRankingTableWidget::canView());
    }

    public function test_pihak_yayasan_can_access_ranking_guru(): void
    {
        // 1. Pembina Yayasan
        $this->actingAs($this->pembinaYayasan);
        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(YayasanPerformanceDashboard::canAccess());
        $this->assertTrue(TeacherRankingTableWidget::canView());

        // 2. Ketua Yayasan
        $this->actingAs($this->ketuaYayasan);
        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(YayasanPerformanceDashboard::canAccess());
        $this->assertTrue(TeacherRankingTableWidget::canView());

        // 3. Tim Pengembang dan Penjamin Mutu
        $this->actingAs($this->timPenjaminMutu);
        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(YayasanPerformanceDashboard::canAccess());
        $this->assertTrue(TeacherRankingTableWidget::canView());

        // 4. Sekretaris Yayasan
        $this->actingAs($this->sekretarisYayasan);
        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(YayasanPerformanceDashboard::canAccess());
        $this->assertTrue(TeacherRankingTableWidget::canView());

        // 5. Superadmin
        $this->actingAs($this->superadmin);
        $this->assertTrue(StaffPerformanceDashboard::canAccess());
        $this->assertTrue(YayasanPerformanceDashboard::canAccess());
        $this->assertTrue(TeacherRankingTableWidget::canView());
    }
}
