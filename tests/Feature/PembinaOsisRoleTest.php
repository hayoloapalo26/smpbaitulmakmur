<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\OsisProgramResource;
use App\Models\Institution;
use App\Models\OsisProgram;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PembinaOsisRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $pembinaOsis;
    protected User $wakaKesiswaan;
    protected User $kepsek;
    protected User $superadmin;
    protected User $guruMapel;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pembina_osis', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(['id' => 1], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-bm']);

        $this->pembinaOsis = User::create([
            'name' => 'Ustadz Pembina OSIS',
            'username' => 'pembina_osis',
            'email' => 'pembina.osis@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->pembinaOsis->assignRole('pembina_osis');

        $this->wakaKesiswaan = User::create([
            'name' => 'Ustadz Waka Kesiswaan',
            'username' => 'waka_kesiswaan',
            'email' => 'kesiswaan@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek',
            'email' => 'kepsek@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->guruMapel = User::create([
            'name' => 'Guru Biasa',
            'username' => 'guru_biasa',
            'email' => 'guru@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruMapel->assignRole('guru_mapel');
    }

    /**
     * Test 1: Role helper and display name tests
     */
    public function test_pembina_osis_role_methods_and_display(): void
    {
        $this->assertTrue($this->pembinaOsis->isPembinaOsis());
        $this->assertFalse($this->wakaKesiswaan->isPembinaOsis());
        $this->assertFalse($this->guruMapel->isPembinaOsis());

        $this->assertEquals('⭐ Pembina OSIS', User::getRoleDisplayName('pembina_osis'));
    }

    /**
     * Test 2: OsisProgramResource navigation group is strictly outside Kesiswaan
     */
    public function test_osis_resource_navigation_group_is_tugas_pembina_osis(): void
    {
        $this->assertEquals('Tugas Pembina OSIS', OsisProgramResource::getNavigationGroup());
        $this->assertNotEquals('Kesiswaan & Karakter', OsisProgramResource::getNavigationGroup());
    }

    /**
     * Test 3: Waka Kesiswaan cannot access OsisProgramResource, but Pembina OSIS can
     */
    public function test_pembina_osis_can_access_and_waka_kesiswaan_cannot(): void
    {
        // Pembina OSIS can access
        $this->actingAs($this->pembinaOsis);
        $this->assertTrue(OsisProgramResource::canAccess());
        $this->assertTrue(OsisProgramResource::shouldRegisterNavigation());
        $this->assertTrue(OsisProgramResource::canCreate());

        // Waka Kesiswaan CANNOT access or see navigation
        $this->actingAs($this->wakaKesiswaan);
        $this->assertFalse(OsisProgramResource::canAccess());
        $this->assertFalse(OsisProgramResource::shouldRegisterNavigation());
        $this->assertFalse(OsisProgramResource::canCreate());

        // Ordinary teacher CANNOT access
        $this->actingAs($this->guruMapel);
        $this->assertFalse(OsisProgramResource::canAccess());

        // Kepala Sekolah can access and see navigation
        $this->actingAs($this->kepsek);
        $this->assertTrue(OsisProgramResource::canAccess());
        $this->assertTrue(OsisProgramResource::shouldRegisterNavigation());

        // Superadmin can access
        $this->actingAs($this->superadmin);
        $this->assertTrue(OsisProgramResource::canAccess());
    }

    /**
     * Test 4: OsisProgramPolicy restricts management to Pembina OSIS
     */
    public function test_osis_program_policy_enforcement(): void
    {
        $program = OsisProgram::create([
            'nama_program' => 'Latihan Dasar Kepemimpinan Siswa (LDKS)',
            'tanggal' => now()->toDateString(),
            'status' => 'direncanakan',
            'institution_id' => $this->institution->id,
        ]);

        // Pembina OSIS: can view, create, update, delete
        $this->actingAs($this->pembinaOsis);
        $this->assertTrue(Gate::allows('viewAny', OsisProgram::class));
        $this->assertTrue(Gate::allows('view', $program));
        $this->assertTrue(Gate::allows('create', OsisProgram::class));
        $this->assertTrue(Gate::allows('update', $program));
        $this->assertTrue(Gate::allows('delete', $program));

        // Waka Kesiswaan: CANNOT do any of these
        $this->actingAs($this->wakaKesiswaan);
        $this->assertFalse(Gate::allows('viewAny', OsisProgram::class));
        $this->assertFalse(Gate::allows('view', $program));
        $this->assertFalse(Gate::allows('create', OsisProgram::class));
        $this->assertFalse(Gate::allows('update', $program));
        $this->assertFalse(Gate::allows('delete', $program));

        // Kepala Sekolah: can view, update (to approve), but cannot create or delete
        $this->actingAs($this->kepsek);
        $this->assertTrue(Gate::allows('viewAny', OsisProgram::class));
        $this->assertTrue(Gate::allows('view', $program));
        $this->assertTrue(Gate::allows('update', $program));
        $this->assertFalse(Gate::allows('create', OsisProgram::class));
        $this->assertFalse(Gate::allows('delete', $program));
    }

    /**
     * Test 5: Report PDF route access check
     */
    public function test_report_osis_pdf_access(): void
    {
        // Pembina OSIS can download
        $response = $this->actingAs($this->pembinaOsis)->get(route('report.osis'));
        $response->assertStatus(200);

        // Kepala Sekolah can download
        $response = $this->actingAs($this->kepsek)->get(route('report.osis'));
        $response->assertStatus(200);

        // Waka Kesiswaan is forbidden
        $response = $this->actingAs($this->wakaKesiswaan)->get(route('report.osis'));
        $response->assertStatus(403);

        // Ordinary teacher is forbidden
        $response = $this->actingAs($this->guruMapel)->get(route('report.osis'));
        $response->assertStatus(403);
    }
}
