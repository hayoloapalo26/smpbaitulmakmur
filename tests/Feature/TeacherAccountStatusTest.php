<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Filament\Staff\Resources\TeacherProfileResource\Pages\ListTeacherProfiles;
use App\Models\Institution;
use App\Models\TeacherProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $admin;
    protected User $tataUsaha;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->admin = User::create([
            'name' => 'Superadmin Baitul Makmur',
            'username' => 'admin_bm',
            'email' => 'admin@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
            'has_account' => true,
        ]);
        $this->admin->assignRole('superadmin');

        $this->tataUsaha = User::create([
            'name' => 'TU Baitul Makmur',
            'username' => 'tu_bm',
            'email' => 'tu@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
            'has_account' => true,
        ]);
        $this->tataUsaha->assignRole('tata_usaha');
    }

    /**
     * Test 1: Guru yang dibuat akunnya dengan email & username riil otomatis berstatus 'Sudah Dibuat',
     * meskipun nilai kolom database has_account awalnya bernilai 0/false (solusi otomatis bug status).
     */
    public function test_teacher_with_real_account_automatically_resolves_status_sudah_dibuat(): void
    {
        // Simulasi kondisi user: Akun sudah dibuat dengan email dan username asli, tapi has_account masih false
        $guru = User::create([
            'name' => 'Ust. Ahmad Fauzi, S.Pd.',
            'username' => 'ahmad_fauzi',
            'email' => 'ahmad.fauzi@smpbm.sch.id',
            'password' => bcrypt('guru123'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
            'has_account' => false, // sebelum fix bernilai false
        ]);
        $guru->assignRole('guru_mapel');

        // Refresh model
        $guru = TeacherProfile::find($guru->id);

        // Accessor otomatis mengevaluasi menjadi true
        $this->assertTrue($guru->has_account);

        $this->actingAs($this->admin);
        Livewire::test(ListTeacherProfiles::class)
            ->assertSee('Ust. Ahmad Fauzi, S.Pd.')
            ->assertSee('Sudah Memiliki Akun')
            ->assertDontSeeHtml('heroicon-m-x-circle');
    }

    /**
     * Test 2: Guru yang belum dibuatkan akun (masih email placeholder) berstatus 'Belum Memiliki Akun'.
     */
    public function test_teacher_with_placeholder_shows_belum_dibuat(): void
    {
        $guru = User::create([
            'name' => 'Guru Baru Placeholder',
            'username' => 'guru1234',
            'email' => 'guru1234@placeholder.local',
            'password' => bcrypt('secret'),
            'institution_id' => $this->institution->id,
            'is_active' => false,
            'has_account' => false,
        ]);
        $guru->assignRole('guru_mapel');

        $guru = TeacherProfile::find($guru->id);
        $this->assertFalse($guru->has_account);

        $this->actingAs($this->admin);
        Livewire::test(ListTeacherProfiles::class)
            ->assertSee('Guru Baru Placeholder')
            ->assertSee('Belum Memiliki Akun');
    }

    /**
     * Test 3: Action buat_akun berhasil mengaktifkan akun dan mengubah status menjadi 'Sudah Memiliki Akun'.
     */
    public function test_buat_akun_action_activates_account(): void
    {
        $guru = User::create([
            'name' => 'Usth. Maryam',
            'username' => 'maryam_temp',
            'email' => 'maryam_temp@placeholder.local',
            'password' => bcrypt('secret'),
            'institution_id' => $this->institution->id,
            'is_active' => false,
            'has_account' => false,
        ]);
        $guru->assignRole('guru_mapel');

        $this->actingAs($this->admin);

        Livewire::test(ListTeacherProfiles::class)
            ->callTableAction('buat_akun', $guru, [
                'name' => 'Usth. Maryam, S.Pd.I',
                'username' => 'maryam_pai',
                'email' => 'maryam@smpbm.sch.id',
                'password' => 'guru123',
                'institution_id' => $this->institution->id,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('users', [
            'id' => $guru->id,
            'username' => 'maryam_pai',
            'email' => 'maryam@smpbm.sch.id',
            'has_account' => true,
            'is_active' => true,
        ]);

        $guruRefreshed = TeacherProfile::find($guru->id);
        $this->assertTrue($guruRefreshed->has_account);
    }

    /**
     * Test 4: Pihak lembaga (Tata Usaha) melihat aksi 'hubungi_admin' untuk memohon pembuatan akun,
     * sedangkan wewenang eksekusi 'buat_akun' berada pada Superadmin.
     */
    public function test_tata_usaha_can_manage_teacher_accounts(): void
    {
        $guru = User::create([
            'name' => 'Ust. Budi',
            'username' => 'budi_temp',
            'email' => 'budi_temp@placeholder.local',
            'password' => bcrypt('secret'),
            'institution_id' => $this->institution->id,
            'is_active' => false,
            'has_account' => false,
        ]);
        $guru->assignRole('guru_mapel');

        $this->actingAs($this->tataUsaha);

        Livewire::test(ListTeacherProfiles::class)
            ->assertTableActionVisible('hubungi_admin', $guru)
            ->assertTableActionHidden('buat_akun', $guru);

        $this->actingAs($this->admin);

        Livewire::test(ListTeacherProfiles::class)
            ->assertTableActionVisible('buat_akun', $guru)
            ->assertTableActionHidden('hubungi_admin', $guru);
    }
}
