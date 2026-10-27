<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardGoodBadNewsViewTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepalaSekolah;
    protected User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek_dashboard@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->guru = User::create([
            'name' => 'Guru Mapel',
            'email' => 'guru_dashboard@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');
    }

    public function test_dashboard_renders_compact_good_and_bad_news_buttons(): void
    {
        $this->actingAs($this->kepalaSekolah);

        $response = $this->get('/staff');
        $response->assertSuccessful();

        $content = $response->getContent();

        // Verifikasi tombol kompak Good News & Bad News ada pada tampilan
        $this->assertStringContainsString('Good News', $content);
        $this->assertStringContainsString('Bad News', $content);
        $this->assertStringContainsString('activeNews', $content);

        // Verifikasi panel rangkuman terkonfigurasi dengan x-show dan x-collapse
        $this->assertStringContainsString("activeNews === 'good'", $content);
        $this->assertStringContainsString("activeNews === 'bad'", $content);
        $this->assertStringContainsString('Ringkasan & Penjelasan Good News', $content);
        $this->assertStringContainsString('Ringkasan & Penjelasan Bad News', $content);
    }

    public function test_dashboard_shows_top_ranking_for_kepala_sekolah(): void
    {
        $this->actingAs($this->kepalaSekolah);

        $response = $this->get('/staff');
        $response->assertSuccessful();

        $content = $response->getContent();
        $this->assertStringContainsString('Top 1 Kinerja Guru', $content);
    }
}
