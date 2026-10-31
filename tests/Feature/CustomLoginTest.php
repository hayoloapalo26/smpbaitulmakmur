<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Filament\Facades\Filament;
use Livewire\Livewire;
use App\Filament\Pages\Auth\CustomLogin;

class CustomLoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $institution = Institution::create([
            'id' => 2,
            'name' => 'SMP Baitul Makmur',
            'code' => 'SMPI',
            'slug' => 'smpi',
        ]);

        $this->user = User::create([
            'name' => 'Ahmad Guru',
            'username' => 'ahmadguru',
            'email' => 'ahmad@smp.com',
            'nuptk' => '1234567890',
            'no_induk_yayasan' => 'YBM-001',
            'password' => Hash::make('password123'),
            'institution_id' => $institution->id,
            'is_active' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('staff'));
    }

    public function test_can_login_with_username_lowercase_and_uppercase(): void
    {
        Livewire::test(CustomLogin::class)
            ->fillForm([
                'login' => 'AhmadGuru',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_can_login_with_email_case_insensitive(): void
    {
        Livewire::test(CustomLogin::class)
            ->fillForm([
                'login' => 'AHMAD@SMP.COM',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_can_login_with_nuptk(): void
    {
        Livewire::test(CustomLogin::class)
            ->fillForm([
                'login' => '1234567890',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_cannot_login_with_wrong_password(): void
    {
        Livewire::test(CustomLogin::class)
            ->fillForm([
                'login' => 'ahmadguru',
                'password' => 'wrongpassword',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.login']);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user->update(['is_active' => false]);

        Livewire::test(CustomLogin::class)
            ->fillForm([
                'login' => 'ahmadguru',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.login']);

        $this->assertGuest();
    }

    public function test_can_submit_forgot_password_request(): void
    {
        Livewire::test(CustomLogin::class)
            ->callAction('forgotPassword', [
                'user_input' => 'ahmadguru',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('password_reset_requests', [
            'user_input' => 'ahmadguru',
            'status' => 'pending',
        ]);
    }
}
