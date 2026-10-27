<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ProgramSarprasResource;
use App\Models\Institution;
use App\Models\ProgramSarpras;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramSarprasInsidentilOptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'sarpras', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    }

    public function test_tipe_pelaksanaan_select_form_contains_insidentil_option(): void
    {
        $institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $sarprasUser = User::create([
            'name' => 'Staf Sarpras',
            'username' => 'staf_sarpras_test',
            'email' => 'sarpras_test@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);
        $sarprasUser->assignRole('sarpras');
        $this->actingAs($sarprasUser);

        $livewire = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;
            public function render() { return '<div></div>'; }
        };

        $form = ProgramSarprasResource::form(Form::make($livewire));
        $components = $form->getComponents();

        $tipePelaksanaanSelect = null;
        foreach ($components as $component) {
            if ($component instanceof \Filament\Forms\Components\Section) {
                foreach ($component->getChildComponents() as $child) {
                    if ($child instanceof \Filament\Forms\Components\Select && $child->getName() === 'tipe_pelaksanaan') {
                        $tipePelaksanaanSelect = $child;
                        break 2;
                    }
                }
            }
        }

        $this->assertNotNull($tipePelaksanaanSelect, 'Field tipe_pelaksanaan harus ada di form ProgramSarprasResource');
        $options = $tipePelaksanaanSelect->getOptions();
        $this->assertArrayHasKey('Insidentil', $options, 'Pilihan Insidentil harus ada di opsi tipe_pelaksanaan');
    }

    public function test_program_sarpras_can_be_saved_with_tipe_insidentil(): void
    {
        $institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $sarprasUser = User::create([
            'name' => 'Staf Sarpras',
            'username' => 'staf_sarpras_test2',
            'email' => 'sarpras_test2@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);
        $sarprasUser->assignRole('sarpras');
        $this->actingAs($sarprasUser);

        $program = ProgramSarpras::create([
            'user_id' => $sarprasUser->id,
            'institution_id' => $institution->id,
            'nama_program' => 'Perbaikan Darurat Atap Bocor',
            'kategori' => 'Pemeliharaan',
            'tipe_pelaksanaan' => 'Insidentil',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Rencana',
            'tahun_ajaran' => '2026/2027',
            'tujuan' => 'Mencegah kerusakan lebih lanjut akibat hujan lebat',
        ]);

        $this->assertDatabaseHas('program_sarpras', [
            'id' => $program->id,
            'tipe_pelaksanaan' => 'Insidentil',
        ]);
    }
}
