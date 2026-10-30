@php
    $user = auth()->user();
    $canPiket = $user && ($user->isPiketToday() || $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'guru_piket']));
    $currentRoute = request()->route()?->getName() ?? '';
    $isPiketArea = str_contains($currentRoute, 'piket') || 
                   str_contains($currentRoute, 'daily-attendances') || 
                   str_contains($currentRoute, 'absensi-manual') || 
                   str_contains($currentRoute, 'attribute-violations') ||
                   str_contains($currentRoute, 'duty-schedule') ||
                   str_contains($currentRoute, 'duty-swap');
@endphp

@if($canPiket && $isPiketArea)
{{-- Mobile Bottom Navigation Bar khusus Guru Piket & Tim Kesiswaan (Android & iOS) --}}
<nav class="fixed bottom-0 inset-x-0 z-40 md:hidden bg-white/95 dark:bg-gray-900/95 backdrop-blur-lg border-t border-gray-200/90 dark:border-gray-800 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] pb-[env(safe-area-inset-bottom,0.5rem)] pt-1 select-none touch-manipulation">
    <div class="flex items-center justify-around h-15 px-2 max-w-md mx-auto">
        
        {{-- 1. Dasbor Piket --}}
        <a href="{{ route('filament.staff.pages.piket-dashboard') }}" 
           class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'piket-dashboard') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
            <div class="relative p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'piket-dashboard') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 {{ str_contains($currentRoute, 'piket-dashboard') ? 'text-primary-600 dark:text-primary-400' : '' }}" />
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 truncate">Dasbor</span>
        </a>

        {{-- 2. Presensi Harian (Fingerprint & Rekap) --}}
        <a href="{{ route('filament.staff.resources.daily-attendances.index') }}" 
           class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'daily-attendances') ? 'text-primary-600 dark:text-primary-400 font-bold' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
            <div class="relative p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'daily-attendances') ? 'bg-primary-50 dark:bg-primary-950/60' : '' }}">
                <x-filament::icon icon="heroicon-o-finger-print" class="w-5 h-5 {{ str_contains($currentRoute, 'daily-attendances') ? 'text-primary-600 dark:text-primary-400' : '' }}" />
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 truncate">Presensi</span>
        </a>

        {{-- 3. Center Elevated Button: Presensi Manual Per Kelas --}}
        <a href="{{ route('filament.staff.pages.absensi-manual') }}" 
           class="flex flex-col items-center justify-center -mt-5 relative z-10 group active:scale-90 transition-transform">
            <div class="w-13 h-13 rounded-full flex items-center justify-center shadow-lg transition-transform duration-150 {{ str_contains($currentRoute, 'absensi-manual') ? 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white ring-4 ring-emerald-100 dark:ring-emerald-950/80 shadow-emerald-500/30' : 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white ring-4 ring-white dark:ring-gray-900 shadow-emerald-500/30' }}">
                <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-7 h-7" />
            </div>
            <span class="text-[10px] font-bold tracking-tight mt-1 text-gray-800 dark:text-gray-200 truncate">Manual</span>
        </a>

        {{-- 4. Pelanggaran Atribut --}}
        <a href="{{ route('filament.staff.resources.attribute-violations.index') }}" 
           class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'attribute-violations') ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
            <div class="relative p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'attribute-violations') ? 'bg-rose-50 dark:bg-rose-950/60' : '' }}">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 {{ str_contains($currentRoute, 'attribute-violations') ? 'text-rose-600 dark:text-rose-400' : '' }}" />
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 truncate">Atribut</span>
        </a>

        {{-- 5. Jadwal Piket 1 Semester --}}
        <a href="{{ route('filament.staff.resources.duty-schedules.index') }}" 
           class="flex flex-col items-center justify-center flex-1 py-1 px-1 transition-all group active:scale-90 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
            <div class="relative p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'duty-schedules') ? 'bg-indigo-50 dark:bg-indigo-950/60' : '' }}">
                <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 {{ str_contains($currentRoute, 'duty-schedules') ? 'text-indigo-600 dark:text-indigo-400' : '' }}" />
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 truncate">Jadwal</span>
        </a>
    </div>
</nav>
@endif
