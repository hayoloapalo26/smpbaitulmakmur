<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\CustomLogin;
use App\Models\Institution;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppContactAdminTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->user = User::create([
            'name' => 'Ustadz Abdullah',
            'username' => 'abdullah_test',
            'email' => 'abdullah@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('guru_mapel');
    }

    public function test_floating_whatsapp_help_button_view_renders_correct_admin_number_and_user_info(): void
    {
        $this->actingAs($this->user);

        $rendered = view('filament.common.whatsapp-help-button')->render();

        $this->assertStringContainsString('https://wa.me/6282322672548', $rendered);
        $this->assertStringContainsString('082322672548', $rendered);
        $this->assertStringContainsString('Ustadz%20Abdullah', $rendered);
        $this->assertStringContainsString('SMP%20Islam%20Baitul%20Makmur', $rendered);
        $this->assertStringContainsString('Bantuan WA Admin', $rendered);
    }

    public function test_topbar_whatsapp_button_view_renders_correct_admin_number(): void
    {
        $this->actingAs($this->user);

        $rendered = view('filament.common.whatsapp-topbar-button')->render();

        $this->assertStringContainsString('https://wa.me/6282322672548', $rendered);
        $this->assertStringContainsString('082322672548', $rendered);
        $this->assertStringContainsString('Bantuan WA', $rendered);
    }

    public function test_login_page_renders_whatsapp_admin_contact_action(): void
    {
        Livewire::test(CustomLogin::class)
            ->assertSee('Kendala / Tanya Admin? Chat WhatsApp (082322672548)')
            ->assertSee('https://wa.me/6282322672548');
    }
}
