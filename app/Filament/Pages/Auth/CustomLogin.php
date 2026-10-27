<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Filament\Actions\Action;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CustomLogin extends BaseLogin
{
    /**
     * Override form schema to use single input for Email / Username.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Define the custom login input.
     */
    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Email / Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
                'autocapitalize' => 'none',
                'autocorrect' => 'off',
                'spellcheck' => 'false',
            ]);
    }

    /**
     * Override credentials logic to support email OR username.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $loginInput = trim($data['login']);
        $loginType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => $loginInput,
            'password' => $data['password'],
            'is_active' => 1,
        ];
    }

    /**
     * Remove standard password reset link if it exists.
     */
    public function hasPasswordReset(): bool
    {
        return false;
    }

    /**
     * Override failure exception to target 'login' field instead of 'email'
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    /**
     * Custom Action for Manual Forgot Password Request.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
            Action::make('forgotPassword')
                ->label('Lupa Password?')
                ->color('gray')
                ->link()
                ->form([
                    TextInput::make('user_input')
                        ->label('Username atau Email')
                        ->required()
                        ->placeholder('Masukkan username / email Anda')
                ])
                ->action(function (array $data) {
                    PasswordResetRequest::create([
                        'user_input' => $data['user_input'],
                        'status' => 'pending',
                    ]);

                    Notification::make()
                        ->title('Permintaan Terkirim')
                        ->body('Permintaan reset password telah dikirim ke Admin. Silakan tunggu prosesnya.')
                        ->success()
                        ->send();
                })
                ->modalHeading('Permintaan Reset Password')
                ->modalDescription('Masukkan username atau email Anda. Admin akan me-reset password Anda secara manual.')
                ->modalSubmitActionLabel('Kirim Permintaan'),
        ];
    }
}
