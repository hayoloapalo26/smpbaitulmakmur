@php
    $user = auth()->user();
    if (!$user) return;

    $currentRoute = request()->route()?->getName() ?? '';
    $path = request()->path();

    $isYayasan = str_starts_with($path, 'yayasan');
    $isPiketDuty = $user->isPiketToday() || $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'guru_piket']);
    $isPiketPage = str_contains($currentRoute, 'piket') || 
                   str_contains($currentRoute, 'scanner-presensi') || 
                   str_contains($currentRoute, 'absensi-manual') || 
                   str_contains($currentRoute, 'attribute-violations') ||
                   str_contains($currentRoute, 'duty-schedule') ||
                   str_contains($currentRoute, 'duty-swap');
@endphp

{{-- ========================================================
     UNIVERSAL MOBILE BOTTOM NAVIGATION (ANDROID & IOS)
     Only rendered on mobile screen width (< md:hidden)
     ======================================================== --}}
<nav class="fixed bottom-0 inset-x-0 z-40 md:hidden bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-t border-gray-200/90 dark:border-gray-800 shadow-[0_-8px_25px_rgba(0,0,0,0.08)] pb-[env(safe-area-inset-bottom,0.5rem)] pt-1 select-none touch-manipulation">
    <div class="flex items-center justify-around h-15 px-2 max-w-lg mx-auto">

        @if($isYayasan)
            {{-- ====== YAYASAN PANEL BOTTOM SHORTCUTS ====== --}}
            {{-- 1. Dashboard Yayasan --}}
            <a href="/yayasan" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ $path === 'yayasan' ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ $path === 'yayasan' ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-home" class="w-5 h-5 {{ $path === 'yayasan' ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Dasbor</span>
            </a>

            {{-- 2. Progres Lembaga --}}
            <a href="/yayasan/institution-progress" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($path, 'institution-progress') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($path, 'institution-progress') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="w-5 h-5 {{ str_contains($path, 'institution-progress') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Lembaga</span>
            </a>

            {{-- 3. Center: SPP Siswa --}}
            <a href="/yayasan/kelola-spp-siswas" 
               class="flex flex-col items-center justify-center -mt-5 relative z-10 group active:scale-90 transition-transform">
                <div class="w-13 h-13 rounded-full flex items-center justify-center shadow-lg bg-gradient-to-tr from-primary-600 to-amber-500 text-white ring-4 ring-white dark:ring-gray-900 shadow-primary-500/30">
                    <x-filament::icon icon="heroicon-o-credit-card" class="w-7 h-7" />
                </div>
                <span class="text-[10px] font-bold tracking-tight mt-1 text-gray-800 dark:text-gray-200 truncate">SPP Siswa</span>
            </a>

            {{-- 4. Instruksi Yayasan --}}
            <a href="/yayasan/instruksi-yayasan" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($path, 'instruksi-yayasan') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($path, 'instruksi-yayasan') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-megaphone" class="w-5 h-5 {{ str_contains($path, 'instruksi-yayasan') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Instruksi</span>
            </a>

        @elseif($isPiketPage || ($isPiketDuty && str_contains($currentRoute, 'piket')))
            {{-- ====== MODE PIKET AKTIF ====== --}}
            {{-- 1. Dasbor Piket --}}
            <a href="{{ route('filament.staff.pages.piket-dashboard') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'piket-dashboard') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'piket-dashboard') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 {{ str_contains($currentRoute, 'piket-dashboard') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Dasbor</span>
            </a>

            {{-- 2. Presensi Manual --}}
            <a href="{{ route('filament.staff.pages.absensi-manual') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'absensi-manual') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'absensi-manual') ? 'bg-emerald-50 dark:bg-emerald-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 {{ str_contains($currentRoute, 'absensi-manual') ? 'text-emerald-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Manual</span>
            </a>

            {{-- 3. Center Elevated: Scan QR Siswa --}}
            <a href="{{ route('filament.staff.pages.scanner-presensi') }}" 
               class="flex flex-col items-center justify-center -mt-5 relative z-10 group active:scale-90 transition-transform">
                <div class="w-13 h-13 rounded-full flex items-center justify-center shadow-lg transition-transform duration-150 {{ str_contains($currentRoute, 'scanner-presensi') ? 'bg-gradient-to-tr from-primary-600 to-amber-500 text-white ring-4 ring-primary-100 dark:ring-primary-950/80 shadow-primary-500/30' : 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white ring-4 ring-white dark:ring-gray-900 shadow-emerald-500/30' }}">
                    <x-filament::icon icon="heroicon-o-qr-code" class="w-7 h-7" />
                </div>
                <span class="text-[10px] font-bold tracking-tight mt-1 text-gray-800 dark:text-gray-200 truncate">Scan QR</span>
            </a>

            {{-- 4. Pelanggaran Atribut --}}
            <a href="{{ route('filament.staff.resources.attribute-violations.index') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'attribute-violations') ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'attribute-violations') ? 'bg-rose-50 dark:bg-rose-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 {{ str_contains($currentRoute, 'attribute-violations') ? 'text-rose-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Atribut</span>
            </a>

            {{-- 5. Jadwal Piket --}}
            <a href="{{ route('filament.staff.resources.duty-schedules.index') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'duty-schedules') ? 'bg-indigo-50 dark:bg-indigo-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-indigo-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Jadwal</span>
            </a>

        @else
            {{-- ====== MODE GURU & STAFF HARIAN ====== --}}
            {{-- 1. Dasbor Utama --}}
            <a href="{{ route('filament.staff.pages.dashboard') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ $currentRoute === 'filament.staff.pages.dashboard' ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ $currentRoute === 'filament.staff.pages.dashboard' ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-home" class="w-5 h-5 {{ $currentRoute === 'filament.staff.pages.dashboard' ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Dasbor</span>
            </a>

            {{-- 2. Jurnal Mengajar --}}
            @if(\Illuminate\Support\Facades\Route::has('filament.staff.resources.teaching-journals.index'))
            <a href="{{ route('filament.staff.resources.teaching-journals.index') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'teaching-journals') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'teaching-journals') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-book-open" class="w-5 h-5 {{ str_contains($currentRoute, 'teaching-journals') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Jurnal</span>
            </a>
            @endif

            {{-- 3. Center Elevated: Presensi Mapel Siswa --}}
            @if(\Illuminate\Support\Facades\Route::has('filament.staff.resources.subject-attendances.index'))
            <a href="{{ route('filament.staff.resources.subject-attendances.index') }}" 
               class="flex flex-col items-center justify-center -mt-5 relative z-10 group active:scale-90 transition-transform">
                <div class="w-13 h-13 rounded-full flex items-center justify-center shadow-lg bg-gradient-to-tr from-primary-600 to-amber-500 text-white ring-4 ring-white dark:ring-gray-900 shadow-primary-500/30">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-7 h-7" />
                </div>
                <span class="text-[10px] font-bold tracking-tight mt-1 text-gray-800 dark:text-gray-200 truncate">Presensi</span>
            </a>
            @endif

            {{-- 4. Nilai Siswa --}}
            @if(\Illuminate\Support\Facades\Route::has('filament.staff.resources.grades.index'))
            <a href="{{ route('filament.staff.resources.grades.index') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'grades') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'grades') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5 {{ str_contains($currentRoute, 'grades') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Nilai</span>
            </a>
            @endif

            {{-- 5. Jadwal Piket Master --}}
            @if(\Illuminate\Support\Facades\Route::has('filament.staff.resources.duty-schedules.index'))
            <a href="{{ route('filament.staff.resources.duty-schedules.index') }}" 
               class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'duty-schedules') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-primary-600' : '' }}" />
                </div>
                <span class="text-[10px] tracking-tight mt-0.5 truncate">Piket</span>
            </a>
            @endif
        @endif

    </div>
</nav>
