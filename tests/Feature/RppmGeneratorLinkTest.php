<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\RppmResource\Pages\CreateRppm;
use App\Filament\Staff\Resources\RppmResource\Pages\ListRppms;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RppmGeneratorLinkTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(['id' => 1], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-bm']);

        $this->guru = User::create([
            'name' => 'Guru IPA',
            'username' => 'guru_ipa',
            'email' => 'guru.ipa@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');
    }

    /**
     * Test 1: Blade component renders successfully with target link
     */
    public function test_generator_rppm_banner_blade_renders_with_link(): void
    {
        $view = $this->view('filament.staff.components.rppm-generator-banner');
        
        $view->assertSee('Generator Pembuatan RPPM', false);
        $view->assertSee('Buka Generator Pembuatan RPPM', false);
        $view->assertSee('https://gemini.google.com/share/d/1e2rmqWovfw8uUldDF8Ql-uBCv6_Z1_Nv?usp=sharing', false);
        $view->assertDontSee('AI');
    }

    /**
     * Test 2: Rppm form schema includes generator banner at the top
     */
    public function test_rppm_form_schema_contains_generator_banner(): void
    {
        $this->actingAs($this->guru);

        $livewire = Livewire::test(CreateRppm::class);
        $form = RppmResource::form(new Form($livewire->instance()));

        $components = $form->getComponents();
        $this->assertNotEmpty($components);

        // First component is the banner
        $firstComponent = $components[0];
        $this->assertEquals('generator_rppm_banner', $firstComponent->getName());
        $this->assertEquals('filament.staff.components.rppm-generator-banner', $firstComponent->getView());
    }

    /**
     * Test 3: ListRppms and CreateRppm have generator header action
     */
    public function test_header_actions_contain_generator_button(): void
    {
        $this->actingAs($this->guru);

        // ListRppms
        $listComponent = Livewire::test(ListRppms::class);
        $listComponent->assertActionExists('open_generator');

        // CreateRppm
        $createComponent = Livewire::test(CreateRppm::class);
        $createComponent->assertActionExists('open_generator');
    }
}
