<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\SchoolWorkPlanResource;
use App\Models\Institution;
use App\Models\SchoolWorkPlan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolWorkPlanRkasTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepsek;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->kepsek = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@smpbm.sch.id',
            'password' => bcrypt('password'),
            'has_account' => true,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');
    }

    public function test_resource_labels_use_rkas(): void
    {
        $this->assertEquals('RKT & RKAS', SchoolWorkPlanResource::getNavigationLabel());
        $this->assertEquals('RKAS (Rencana Kegiatan & Anggaran Sekolah)', SchoolWorkPlanResource::getModelLabel());
        $this->assertEquals('RKT & RKAS (Rencana Kegiatan & Anggaran Sekolah)', SchoolWorkPlanResource::getPluralModelLabel());
    }

    public function test_can_create_and_query_rkas_document(): void
    {
        $this->actingAs($this->kepsek);

        $plan = SchoolWorkPlan::create([
            'institution_id' => $this->institution->id,
            'document_type' => 'RKAS',
            'drive_link' => 'https://drive.google.com/drive/folders/sample-rkas',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('school_work_plans', [
            'id' => $plan->id,
            'document_type' => 'RKAS',
        ]);

        Livewire::test(SchoolWorkPlanResource\Pages\ListSchoolWorkPlans::class)
            ->assertCanSeeTableRecords([$plan]);
    }
}
