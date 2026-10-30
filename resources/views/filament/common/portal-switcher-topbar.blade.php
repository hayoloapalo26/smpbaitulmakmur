@php
    $user = auth()->user();
    if (!$user) return;

    $isYayasan = $user->hasAnyRole([
        'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',
        'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan',
        'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan'
    ]);

    $isTeacherOrStaff = $user->isQuranTeacher() || $user->hasAnyRole([
        'guru_mapel', 'guru_kelas', 'guru_piket', 'wali_kelas', 'koordinator_madin', 'guru_alquran'
    ]);

    // Hanya tampil untuk user yang memiliki hak akses ganda (misal: Ketua Yayasan sekaligus Guru Tahfidz Lanjutan)
    if (!$isYayasan || !$isTeacherOrStaff) return;

    $isCurrentYayasan = request()->is('yayasan*');
    $targetUrl = $isCurrentYayasan ? url('/staff') : url('/yayasan');
@endphp

@if ($isCurrentYayasan)
    {{-- Saat berada di Portal Yayasan: Tombol beralih ke Portal Guru Al-Qur'an / Staff --}}
    <a href="{{ $targetUrl }}"
       title="Beralih ke Portal Guru (Staff) untuk Mengajar & Input Nilai Tahfidz Santri"
       class="inline-flex items-center gap-1.5 p-1.5 sm:px-3 sm:py-1.5 text-xs font-bold rounded-lg bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white shadow-sm transition-all mr-1 sm:mr-2 group touch-manipulation">
        <svg class="w-4 h-4 shrink-0 text-white group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
        </svg>
        <span class="hidden md:inline">Beralih ke Portal Guru (Staff)</span>
        <span class="md:hidden">Mode Guru</span>
    </a>
@else
    {{-- Saat berada di Portal Staff: Tombol beralih ke Portal Yayasan --}}
    <a href="{{ $targetUrl }}"
       title="Beralih ke Portal Yayasan untuk Monitoring Seluruh Lembaga"
       class="inline-flex items-center gap-1.5 p-1.5 sm:px-3 sm:py-1.5 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white shadow-sm transition-all mr-1 sm:mr-2 group touch-manipulation">
        <svg class="w-4 h-4 shrink-0 text-white group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A48.357 48.357 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.583V21h15Z" />
        </svg>
        <span class="hidden md:inline">Beralih ke Portal Yayasan</span>
        <span class="md:hidden">Mode Yayasan</span>
    </a>
@endif
