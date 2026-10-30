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
        ];
    }

    /**
     * Authenticate user with flexible username, email, NUPTK, or No Induk Yayasan,
     * with case-insensitivity and friendly active account handling.
     */
    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $loginInput = trim($data['login'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $remember = (bool) ($data['remember'] ?? false);

        if (empty($loginInput) || empty($password)) {
            $this->throwFailureValidationException();
        }

        $lowerInput = strtolower($loginInput);

        // Cari user fleksibel: email ATAU username ATAU nuptk ATAU no_induk_yayasan
        $user = \App\Models\User::withoutGlobalScopes()
            ->where(function ($q) use ($lowerInput, $loginInput) {
                $q->whereRaw('LOWER(email) = ?', [$lowerInput])
                  ->orWhereRaw('LOWER(username) = ?', [$lowerInput])
                  ->orWhere('nuptk', $loginInput)
                  ->orWhere('no_induk_yayasan', $loginInput);
            })
            ->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            $this->throwFailureValidationException();
        }

        // Cek jika akun nonaktif secara eksplisit
        if ($user->is_active === false || $user->is_active === 0) {
            throw ValidationException::withMessages([
                'data.login' => 'Akun Anda dinonaktifkan oleh administrator. Silakan hubungi admin sekolah.',
            ]);
        }

        \Filament\Facades\Filament::auth()->login($user, $remember);
        session()->regenerate();

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
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

            Action::make('contactAdminWhatsApp')
                ->label('Kendala / Tanya Admin? Chat WhatsApp (082322672548)')
                ->icon('heroicon-m-chat-bubble-left-right')
                ->color('success')
                ->link()
                ->url('https://wa.me/6282322672548?text=' . rawurlencode("Assalamu'alaikum Admin Sistem Informasi Baitul Makmur, saya mengalami kendala saat login ke sistem: "))
                ->openUrlInNewTab(),
        ];
    }
}
