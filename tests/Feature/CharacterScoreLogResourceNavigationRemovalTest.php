<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\CharacterScoreLogResource;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CharacterScoreLogResourceNavigationRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smpi']
        );

        $roles = [
            'guru_mapel',
            'wali_kelas',
            'guru_kelas',
            'kepala_sekolah',
            'waka_kesiswaan',
            'waka_kurikulum',
            'superadmin',
            'guru_piket',
            'tata_usaha',
            'guru_quran',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    /**
     * Pastikan menu "Penilaian Karakter Siswa" (CharacterScoreLogResource) tidak muncul di navigasi untuk SEMUA role user.
     */
    public function test_character_score_log_resource_should_not_register_navigation_for_any_role(): void
    {
        $this->assertFalse(
            CharacterScoreLogResource::shouldRegisterNavigation(),
            'CharacterScoreLogResource must return false for guest'
        );

        $roles = [
            'guru_mapel',
            'wali_kelas',
            'guru_kelas',
            'kepala_sekolah',
            'waka_kesiswaan',
            'waka_kurikulum',
            'superadmin',
            'guru_piket',
            'tata_usaha',
            'guru_quran',
        ];

        foreach ($roles as $roleName) {
            $user = User::factory()->create(['institution_id' => $this->institution->id]);
            $user->assignRole($roleName);

            $this->actingAs($user);

            $this->assertFalse(
                CharacterScoreLogResource::shouldRegisterNavigation(),
                "CharacterScoreLogResource must NOT register navigation for role: {$roleName}"
            );
        }
    }
}
