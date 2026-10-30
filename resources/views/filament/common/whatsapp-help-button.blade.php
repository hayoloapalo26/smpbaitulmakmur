@php
    $user = auth()->user();
    if (!$user) {
        return;
    }

    $phone = '6282322672548';
    $pageUrl = url()->current();
    $nama = $user->name;
    $role = $user->roles->pluck('name')->implode(', ') ?: 'Staff';
    $lembaga = $user->institution?->name ?? 'SMP Islam Baitul Makmur';
    $msg = "Assalamu'alaikum Admin Sistem Informasi Baitul Makmur,\n\nSaya membutuhkan bantuan / ada kendala teknis pada sistem:\n• Nama: {$nama}\n• Lembaga/Unit: {$lembaga}\n• Posisi/Role: {$role}\n• Halaman: {$pageUrl}\n\nKendala / Pertanyaan:\n";

    $waUrl = "https://wa.me/{$phone}?text=" . rawurlencode($msg);
@endphp

{{-- ========================================================
     COMPACT & NON-INTRUSIVE FLOATING WHATSAPP HELP BUTTON
     - 100% Native CSS (Zero JavaScript overhead / Zero Infinite Loop)
     - Ukuran ringkas (FAB 42px/48px) agar tidak menutupi tombol lain
     - Smooth expand saat di-hover di desktop
     - Auto-hide instan saat modal / slide-over Filament terbuka
     ======================================================== --}}
<style>
    /* Sembunyikan otomatis jika ada modal / dialog / slide-over aktif */
    body:has(.fi-modal-window) #wa-help-floating-container,
    body:has([role="dialog"]) #wa-help-floating-container,
    body:has(.fi-modal-open) #wa-help-floating-container,
    body:has(.fi-modal) #wa-help-floating-container,
    body:has(.fi-slide-over) #wa-help-floating-container {
        display: none !important;
    }
</style>

<div id="wa-help-floating-container" 
     class="fixed z-30 bottom-20 right-3.5 md:bottom-6 md:right-6 select-none print:hidden pointer-events-auto transition-all duration-300">
    <a href="{{ $waUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Hubungi Admin WhatsApp (082322672548)"
       title="Ada pertanyaan atau kendala? Chat Admin WhatsApp (082322672548)"
       class="group flex items-center justify-center p-2.5 sm:p-2.5 md:p-3 rounded-full bg-[#25D366] hover:bg-[#20ba59] active:scale-95 text-white shadow-md hover:shadow-xl shadow-emerald-600/30 ring-2 ring-white/95 dark:ring-gray-900/95 transition-all duration-300 ease-out no-underline cursor-pointer touch-manipulation">

        <!-- Online pulsing indicator -->
        <span class="relative flex h-2 w-2 md:h-2.5 md:w-2.5 shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 md:h-2.5 md:w-2.5 bg-white"></span>
        </span>

        <!-- Official WhatsApp SVG Icon -->
        <svg class="w-5 h-5 md:w-5 md:h-5 fill-current shrink-0 ml-1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>

        <!-- Teks Muncul Hanya Saat Hover di Desktop -->
        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 md:group-hover:max-w-[130px] md:group-hover:opacity-100 md:group-hover:ml-2 md:group-hover:mr-1 transition-all duration-300 ease-out text-xs font-bold text-white tracking-tight">
            Tanya Admin WA
        </span>
    </a>
</div>
