<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\Dashboard;
use App\Models\Institution;
use App\Models\TeacherCompetencyTest;
use App\Models\TeacherSupervision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupervisionAndUkgNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepalaSekolah;
    protected User $guruA;
    protected User $guruB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tim_pengembang_penjamin_mutu_yayasan', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->guruA = User::create([
            'name' => 'Guru Ahmad',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruA->assignRole('guru_mapel');

        $this->guruB = User::create([
            'name' => 'Guru Budi',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruB->assignRole('guru_mapel');

        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {
        }
    }

    public function test_supervision_schedule_only_visible_to_assigned_teacher(): void
    {
        $supervision = TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruA->id,
            'status' => 'dijadwalkan',
            'jadwal' => now()->addDays(2),
            'jalur_supervisi' => 'penjamin_mutu',
            'catatan' => 'Supervisi RPP & Modul Ajar',
        ]);

        // Guru A melihat jadwalnya
        $this->actingAs($this->guruA);
        $componentA = new Dashboard();
        $this->assertCount(1, $componentA->upcomingSchedules);
        $this->assertEquals($supervision->id, $componentA->upcomingSchedules->first()['id']);
        $this->assertEquals('supervisi', $componentA->upcomingSchedules->first()['type']);

        // Guru B tidak melihat jadwal Guru A
        $this->actingAs($this->guruB);
        $componentB = new Dashboard();
        $this->assertCount(0, $componentB->upcomingSchedules);

        // Kepala Sekolah tidak melihat jadwal supervisi Guru A di banner jadwal pribadinya
        $this->actingAs($this->kepalaSekolah);
        $componentKS = new Dashboard();
        $this->assertCount(0, $componentKS->upcomingSchedules);
    }

    public function test_ukg_schedule_only_visible_to_assigned_teacher(): void
    {
        $ukg = TeacherCompetencyTest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruB->id,
            'status' => 'dijadwalkan',
            'tanggal' => now()->addDays(3)->toDateString(),
            'jadwal' => now()->addDays(3),
            'catatan' => 'Uji Kompetensi Guru Tahunan',
        ]);

        // Guru B melihat jadwal UKG-nya
        $this->actingAs($this->guruB);
        $componentB = new Dashboard();
        $this->assertCount(1, $componentB->upcomingSchedules);
        $this->assertEquals($ukg->id, $componentB->upcomingSchedules->first()['id']);
        $this->assertEquals('ukg', $componentB->upcomingSchedules->first()['type']);

        // Guru A tidak melihat jadwal UKG Guru B
        $this->actingAs($this->guruA);
        $componentA = new Dashboard();
        $this->assertCount(0, $componentA->upcomingSchedules);

        // Kepala Sekolah tidak melihat jadwal UKG Guru B di banner jadwal pribadinya
        $this->actingAs($this->kepalaSekolah);
        $componentKS = new Dashboard();
        $this->assertCount(0, $componentKS->upcomingSchedules);
    }

    public function test_schedule_can_be_dismissed_when_completed(): void
    {
        $supervision = TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruA->id,
            'status' => 'dijadwalkan',
            'jadwal' => now()->addDays(1),
            'jalur_supervisi' => 'penjamin_mutu',
        ]);

        $this->actingAs($this->guruA);

        Livewire::test(Dashboard::class)
            ->call('dismissSchedule', 'supervisi', $supervision->id)
            ->assertHasNoErrors();

        $supervision->refresh();
        $this->assertNotNull($supervision->notif_dismissed_at);
        $this->assertEquals('selesai', $supervision->status);

        // Setelah dihilangkan, jadwal tidak muncul lagi di upcomingSchedules
        $component = new Dashboard();
        $this->assertCount(0, $component->upcomingSchedules);
    }

    public function test_graded_supervision_and_ukg_do_not_appear_in_upcoming_schedules(): void
    {
        // Supervisi sudah dinilai
        TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruA->id,
            'status' => 'dinilai',
            'score' => 88.5,
            'jadwal' => now()->subDay(),
            'jalur_supervisi' => 'penjamin_mutu',
        ]);

        // UKG sudah dinilai
        TeacherCompetencyTest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruA->id,
            'status' => 'dinilai',
            'score' => 85,
            'nilai_akhir' => 85.0,
            'total_skor' => 350,
            'tanggal' => now()->subDay()->toDateString(),
            'jadwal' => now()->subDay(),
        ]);

        $this->actingAs($this->guruA);
        $component = new Dashboard();
        $this->assertCount(0, $component->upcomingSchedules);
    }

    public function test_notification_database_only_sent_to_assigned_teacher(): void
    {
        // Buat supervisi dari penjamin mutu untuk Guru A
        TeacherSupervision::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guruA->id,
            'status' => 'dijadwalkan',
            'jadwal' => now()->addDays(2),
            'jalur_supervisi' => 'penjamin_mutu',
            'catatan' => 'Jadwal supervisi kelas 7',
        ]);

        // Guru A menerima notifikasi di database
        $this->assertGreaterThan(0, $this->guruA->notifications()->count());

        // Kepala Sekolah TIDAK menerima notifikasi jadwal tersebut
        $this->assertEquals(0, $this->kepalaSekolah->notifications()->count());
    }
}
