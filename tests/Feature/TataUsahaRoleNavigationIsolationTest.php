<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\AbsensiManual;
use App\Filament\Staff\Pages\PrayerAttendanceReport;
use App\Filament\Staff\Resources\AlumniTracerResource;
use App\Filament\Staff\Resources\ClassroomResource;
use App\Filament\Staff\Resources\DailyAttendanceResource;
use App\Filament\Staff\Resources\DocumentArchiveResource;
use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource;
use App\Filament\Staff\Resources\InventoryResource;
use App\Filament\Staff\Resources\LetterResource;
use App\Filament\Staff\Resources\MeetingMinuteResource;
use App\Filament\Staff\Resources\PenempatanSarprasResource;
use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource;
use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\StudentResource;
use App\Filament\Staff\Resources\TeacherAttendanceResource;
use App\Filament\Staff\Resources\TeacherCertificateResource;
use App\Filament\Staff\Resources\TeacherLeaveRequestResource;
use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TataUsahaRoleNavigationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $tataUsahaUser;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'id' => 2,
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-islam-baitul-makmur',
        ]);

        $this->tataUsahaUser = User::factory()->create([
            'name' => 'Staf Tata Usaha',
            'username' => 'tu_smp',
            'email' => 'tu@baitulmakmur.sch.id',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->tataUsahaUser->assignRole('tata_usaha');
    }

    public function test_tata_usaha_cannot_access_or_see_sarpras_resources(): void
    {
        $this->actingAs($this->tataUsahaUser);

        // Sarpras resources must not register navigation nor be accessible by tata_usaha
        $this->assertFalse(InventoryResource::shouldRegisterNavigation());
        $this->assertFalse(InventoryResource::canAccess());
        $this->assertFalse(InventoryResource::canCreate());

        $this->assertFalse(PenempatanSarprasResource::shouldRegisterNavigation());
        $this->assertFalse(PenempatanSarprasResource::canAccess());
        $this->assertFalse(PenempatanSarprasResource::canCreate());

        $this->assertFalse(PerawatanBerkalaSarprasResource::shouldRegisterNavigation());
        $this->assertFalse(PerawatanBerkalaSarprasResource::canAccess());
        $this->assertFalse(PerawatanBerkalaSarprasResource::canCreate());

        $this->assertFalse(PerbaikanPenggantianSarprasResource::shouldRegisterNavigation());
        $this->assertFalse(PerbaikanPenggantianSarprasResource::canAccess());
        $this->assertFalse(PerbaikanPenggantianSarprasResource::canCreate());

        $this->assertFalse(EvaluasiProgramSarprasResource::shouldRegisterNavigation());
        $this->assertFalse(EvaluasiProgramSarprasResource::canAccess());
        $this->assertFalse(EvaluasiProgramSarprasResource::canCreate());
    }

    public function test_tata_usaha_cannot_access_kesiswaan_kurikulum_and_piket_resources(): void
    {
        $this->actingAs($this->tataUsahaUser);

        // Kesiswaan Presensi & Izin Siswa
        $this->assertFalse(PresensiIzinSiswaResource::shouldRegisterNavigation());
        $this->assertFalse(PresensiIzinSiswaResource::canAccess());

        // Kurikulum Jadwal Pelajaran
        $this->assertFalse(ScheduleResource::canAccess());

        // Piket Presensi Manual & Prayer Report
        $this->assertFalse(AbsensiManual::shouldRegisterNavigation());
        $this->assertFalse(AbsensiManual::canAccess());
        $this->assertFalse(PrayerAttendanceReport::shouldRegisterNavigation());
        $this->assertFalse(PrayerAttendanceReport::canAccess());
        $this->assertFalse(DailyAttendanceResource::canAccess());
    }

    public function test_tata_usaha_has_access_to_administrasi_dan_data_induk_resources(): void
    {
        $this->actingAs($this->tataUsahaUser);

        // Core TU resources
        $this->assertTrue(TeacherProfileResource::canAccess());
        $this->assertTrue(TeacherCertificateResource::canAccess());
        $this->assertTrue(TeacherLeaveRequestResource::canAccess());
        $this->assertTrue(TeacherAttendanceResource::canAccess());
        $this->assertTrue(TeacherAttendanceResource::shouldRegisterNavigation());
        $this->assertSame('Administrasi & Data Induk', TeacherAttendanceResource::getNavigationGroup());

        $this->assertTrue(StudentResource::canAccess());
        $this->assertSame('Administrasi & Data Induk', StudentResource::getNavigationGroup());

        $this->assertTrue(ClassroomResource::canAccess());
        $this->assertSame('Administrasi & Data Induk', ClassroomResource::getNavigationGroup());

        $this->assertTrue(LetterResource::canAccess());
        $this->assertTrue(MeetingMinuteResource::canAccess());
        $this->assertTrue(DocumentArchiveResource::canAccess());
        $this->assertTrue(AlumniTracerResource::canAccess());
        $this->assertSame('Administrasi & Data Induk', AlumniTracerResource::getNavigationGroup());
    }
}
