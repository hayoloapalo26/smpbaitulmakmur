<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Filament\Staff\Resources\TeacherProfileResource\Pages\CreateTeacherProfile;
use App\Filament\Staff\Resources\TeacherProfileResource\Pages\ListTeacherProfiles;
use App\Models\Institution;
use App\Models\TeacherProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherProfileCreateByTuTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $tataUsaha;
    protected User $superadmin;

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

        $this->tataUsaha = User::create([
            'name' => 'Staff TU SMP',
            'username' => 'tu_smp',
            'email' => 'tu_smp@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
            'has_account' => true,
        ]);
        $this->tataUsaha->assignRole('tata_usaha');

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'admin@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
            'has_account' => true,
        ]);
        $this->superadmin->assignRole('superadmin');
    }

    /**
     * Test 1: Role tata_usaha canCreate, canEdit on TeacherProfileResource, and ListTeacherProfiles has Input Data Guru action.
     */
    public function test_tu_can_access_create_action_and_can_create(): void
    {
        $this->actingAs($this->tataUsaha);

        $this->assertTrue(TeacherProfileResource::canCreate());
        $this->assertTrue(TeacherProfileResource::canAccess());

        Livewire::test(ListTeacherProfiles::class)
            ->assertActionExists('create');
    }

    /**
     * Test 2: Role tata_usaha can input full PTK data guru variables through CreateTeacherProfile page,
     * and newly inputted teacher has status 'Belum Memiliki Akun' (has_account = false, is_active = false).
     */
    public function test_tu_can_input_teacher_with_all_ptk_variables(): void
    {
        $this->actingAs($this->tataUsaha);

        $formData = [
            'name' => 'Ustadz Ahmad Fauzan, S.Pd.',
            'no_induk_yayasan' => 'NIY-2026-0089',
            'nuptk' => '1234567890123456',
            'gender' => 'L',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1990-05-15',
            'satminkal' => 'SMP Islam Baitul Makmur',
            'institution_id' => $this->institution->id,
            'jenjang_pendidikan' => 'S1',
            'jurusan_pendidikan' => 'Pendidikan Agama Islam',
            'universitas' => 'UIN Sunan Ampel',
            'tanggal_masuk' => '2020-07-01',
            'jabatan' => 'Guru PAI & Al-Qur\'an',
            'status_kepegawaian' => 'GTY',
            'no_sk_gty' => 'SK/YBM/2020/045',
            'no_ukg' => '202012345678',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Makmur No. 123, Surabaya',
        ];

        Livewire::test(CreateTeacherProfile::class)
            ->fillForm($formData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verifikasi record berhasil tersimpan di database dengan semua variabel PTK
        $createdTeacher = User::where('name', 'Ustadz Ahmad Fauzan, S.Pd.')->first();
        $this->assertNotNull($createdTeacher);

        $this->assertEquals('Ustadz Ahmad Fauzan, S.Pd.', $createdTeacher->name);
        $this->assertEquals('NIY-2026-0089', $createdTeacher->no_induk_yayasan);
        $this->assertEquals('1234567890123456', $createdTeacher->nuptk);
        $this->assertEquals('L', $createdTeacher->gender);
        $this->assertEquals('Surabaya', $createdTeacher->tempat_lahir);
        $this->assertStringStartsWith('1990-05-15', (string)$createdTeacher->tanggal_lahir);
        $this->assertEquals('SMP Islam Baitul Makmur', $createdTeacher->satminkal);
        $this->assertEquals('S1', $createdTeacher->jenjang_pendidikan);
        $this->assertEquals('Pendidikan Agama Islam', $createdTeacher->jurusan_pendidikan);
        $this->assertEquals('UIN Sunan Ampel', $createdTeacher->universitas);
        $this->assertStringStartsWith('2020-07-01', (string)$createdTeacher->tanggal_masuk);
        $this->assertEquals('Guru PAI & Al-Qur\'an', $createdTeacher->jabatan);
        $this->assertEquals('GTY', $createdTeacher->status_kepegawaian);
        $this->assertEquals('SK/YBM/2020/045', $createdTeacher->no_sk_gty);
        $this->assertEquals('202012345678', $createdTeacher->no_ukg);
        $this->assertEquals('081234567890', $createdTeacher->no_hp);
        $this->assertEquals('Jl. Makmur No. 123, Surabaya', $createdTeacher->alamat);

        // Status akun guru yang baru diinputkan adalah BELUM MEMILIKI AKUN
        $this->assertFalse($createdTeacher->has_account);
        $this->assertFalse($createdTeacher->is_active);
        $this->assertStringContainsString('@placeholder.local', $createdTeacher->email);

        // Verifikasi unit lembaga tersinkronisasi
        $this->assertEquals($this->institution->id, $createdTeacher->institution_id);
        if (Schema::hasTable('institution_user')) {
            $this->assertDatabaseHas('institution_user', [
                'user_id' => $createdTeacher->id,
                'institution_id' => $this->institution->id,
            ]);
        }
    }

    /**
     * Test 3: Guru yang baru diinputkan muncul dengan status 'Belum Memiliki Akun',
     * TU melihat action 'hubungi_admin' untuk WhatsApp ke Admin Yayasan, dan Superadmin melihat 'buat_akun'.
     */
    public function test_newly_created_teacher_shows_hubungi_admin_action_for_tu(): void
    {
        $guru = User::create([
            'name' => 'Ust. Zulkifli, M.Pd.',
            'username' => 'guru_zulkifli_temp',
            'email' => 'zulkifli_temp@placeholder.local',
            'password' => bcrypt('secret'),
            'institution_id' => $this->institution->id,
            'is_active' => false,
            'has_account' => false,
            'no_hp' => '08987654321',
            'jabatan' => 'Guru IPA',
        ]);
        $guru->assignRole('guru_mapel');

        $this->actingAs($this->tataUsaha);

        Livewire::test(ListTeacherProfiles::class)
            ->assertSee('Ust. Zulkifli, M.Pd.')
            ->assertSee('Belum Memiliki Akun')
            ->assertTableActionVisible('hubungi_admin', $guru)
            ->assertTableActionHidden('buat_akun', $guru);

        $this->actingAs($this->superadmin);

        Livewire::test(ListTeacherProfiles::class)
            ->assertTableActionVisible('buat_akun', $guru)
            ->assertTableActionHidden('hubungi_admin', $guru);
    }
}
