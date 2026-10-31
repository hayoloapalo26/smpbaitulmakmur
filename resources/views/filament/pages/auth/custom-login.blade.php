<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{-- Pemisah Elegan Bantuan --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200 dark:border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-wider">
            <span class="bg-white dark:bg-gray-900 px-3 text-gray-400 dark:text-gray-400 font-semibold flex items-center gap-1.5 rounded-full">
                <svg class="w-3.5 h-3.5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Pusat Bantuan & Layanan
            </span>
        </div>
    </div>

    {{-- Kartu WhatsApp Bantuan Admin (Desain Eksklusif & Terstruktur Rapi) --}}
    <div class="rounded-2xl border border-emerald-500/25 bg-gradient-to-br from-emerald-500/5 via-emerald-500/10 to-teal-500/5 dark:from-emerald-950/40 dark:via-gray-900/80 dark:to-teal-950/30 p-4 shadow-sm transition-all duration-300 hover:border-emerald-500/50 hover:shadow-lg hover:shadow-emerald-500/10">
        <div class="flex items-start gap-3.5">
            <div class="relative flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-green-500 text-white shadow-md shadow-emerald-500/30">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white dark:border-gray-900"></span>
                </span>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-900 dark:text-white">
                        Kendala / Tanya Admin? Chat WhatsApp (082322672548)
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300">
                        Online
                    </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                    Mengalami kendala akun, reset kata sandi, atau mutasi tugas? Tim admin siap melayani.
                </p>
            </div>
        </div>

        <div class="mt-3.5 pt-3 border-t border-emerald-500/20 flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                WA Admin: <strong class="text-emerald-700 dark:text-emerald-400 tracking-wide">082322672548</strong>
            </span>

            <a href="https://wa.me/6282322672548?text={{ rawurlencode("Assalamu'alaikum Admin Sistem Informasi Baitul Makmur, saya mengalami kendala saat login ke sistem: ") }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 active:from-emerald-700 active:to-green-700 shadow-md shadow-emerald-600/25 transition-all duration-200 transform hover:-translate-y-0.5">
                <span>Hubungi via WhatsApp</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>

    {{-- Footer Info Keamanan --}}
    <div class="mt-6 text-center text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <span>Sistem Informasi Manajemen Sekolah • LPI Baitul Makmur</span>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    <style>
        .fi-simple-main {
            border-radius: 1.5rem !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 50px -10px rgba(255, 130, 0, 0.12) !important;
            background: rgba(17, 24, 39, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
        }
        .dark .fi-simple-main {
            background: rgba(15, 23, 42, 0.94) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .fi-simple-layout {
            background: radial-gradient(circle at 50% 0%, rgba(255, 130, 0, 0.14) 0%, rgba(15, 23, 42, 0) 55%),
                        radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.08) 0%, rgba(15, 23, 42, 0) 45%),
                        #090d16 !important;
            min-height: 100vh;
        }
    </style>
</x-filament-panels::page.simple>
