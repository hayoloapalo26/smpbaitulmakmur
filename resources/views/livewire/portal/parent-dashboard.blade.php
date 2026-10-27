<div class="max-w-7xl mx-auto py-4 sm:py-8 px-2.5 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

    <!-- Header / Student Selector -->
    <div class="bg-white p-6 shadow-sm sm:rounded-2xl border border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Orang Tua</h2>
            <p class="text-sm text-gray-500">Pantau dan laporkan kegiatan harian anak di rumah.</p>
        </div>
        <div class="w-full sm:w-72">
            <label for="studentSelect" class="sr-only">Pilih Anak:</label>
            <div class="relative">
                <select wire:model.live="selectedStudentId" id="studentSelect" class="block w-full pl-4 pr-10 py-3 text-base font-medium text-gray-700 bg-gray-50 border-gray-200 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 rounded-xl appearance-none cursor-pointer">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} (NIS: {{ $s->nis }})</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>
    </div>

    @if($activeStudent)
    <!-- E-Rapor Section -->
    <div class="mt-6">
        @if($activeStudent->is_administrasi_lunas)
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-emerald-900">E-Rapor Akademik</h3>
                    <p class="text-sm text-emerald-700">Administrasi telah lunas. Anda dapat mengunduh E-Rapor ananda.</p>
                </div>
                <a href="/portal/rapor/{{ $activeStudent->id }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download E-Rapor
                </a>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-red-900">Pembayaran Administrasi Diperlukan</h3>
                    <p class="text-sm text-red-700 mt-1">Mohon maaf, Anda belum dapat mengunduh E-Rapor. Silakan selesaikan pembayaran administrasi untuk membuka akses unduh E-Rapor ananda.</p>
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- Active Student Hero -->
    <div class="bg-gradient-to-r from-teal-600 to-emerald-500 rounded-2xl shadow-xl overflow-hidden relative p-5 sm:p-10 text-white flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-10 -mb-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
        
        <div class="relative z-10 text-center md:text-left">
            <p class="text-teal-100 font-medium mb-1 tracking-wide uppercase text-xs sm:text-sm">Sedang Memantau</p>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $activeStudent->name }}</h2>
            <p class="mt-2 text-teal-50 max-w-xl text-xs sm:text-sm">Mari bekerja sama membentuk kebiasaan baik anak di rumah.</p>
        </div>
    </div>

    <!-- 3 Kotak Metrik Utama (Akademik, Non-Akademik, Karakter) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <!-- Akademik -->
        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-indigo-100/50">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-sm font-bold text-indigo-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                    Akademik
                </h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-indigo-900">{{ $academicPct }}</span>
                    <span class="text-lg font-bold text-indigo-600">%</span>
                </div>
                <div class="mt-4 w-full bg-indigo-200 rounded-full h-2">
                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $academicPct }}%"></div>
                </div>
                <p class="text-xs text-indigo-700 mt-2 font-medium">Gabungan Nilai & Kehadiran Harian</p>
            </div>
        </div>

        <!-- Non-Akademik -->
        <div class="bg-teal-50 border border-teal-100 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-teal-100/50">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-sm font-bold text-teal-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                    Non-Akademik
                </h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-teal-900">{{ $nonAcademicPct }}</span>
                    <span class="text-lg font-bold text-teal-600">%</span>
                </div>
                <div class="mt-4 w-full bg-teal-200 rounded-full h-2">
                    <div class="bg-teal-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $nonAcademicPct }}%"></div>
                </div>
                <p class="text-xs text-teal-700 mt-2 font-medium">Kehadiran Ekstrakurikuler & Organisasi</p>
            </div>
        </div>

        <!-- Karakter -->
        <div class="bg-fuchsia-50 border border-fuchsia-100 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute -right-6 -top-6 text-fuchsia-100/50">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-sm font-bold text-fuchsia-800 uppercase tracking-widest mb-2 flex items-center gap-2">
                    Karakter
                </h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-fuchsia-900">{{ $characterPct }}</span>
                    <span class="text-lg font-bold text-fuchsia-600">%</span>
                </div>
                <div class="mt-4 w-full bg-fuchsia-200 rounded-full h-2">
                    <div class="bg-fuchsia-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $characterPct }}%"></div>
                </div>
                <p class="text-xs text-fuchsia-700 mt-2 font-medium">Skor Pencapaian Karakter Global</p>
            </div>
        </div>

    </div>

    <!-- Karakter Komparasi: Sekolah vs Rumah -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Detail Skor Karakter Siswa
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            
            <!-- Visualisasi Bar Sejajar -->
            <div class="space-y-6">
                <!-- Sekolah -->
                <div>
                    <div class="flex justify-between text-sm font-bold mb-2">
                        <span class="text-blue-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Karakter di Sekolah
                        </span>
                        <span class="text-blue-900">{{ $schoolScoreTotal }} Poin</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-blue-500 h-3 rounded-full" style="width: {{ min(100, max(0, ($schoolScoreTotal / max(1, $schoolScoreTotal + $homeScoreTotal)) * 100)) }}%"></div>
                    </div>
                </div>

                <!-- Rumah -->
                <div>
                    <div class="flex justify-between text-sm font-bold mb-2">
                        <span class="text-amber-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Karakter di Rumah
                        </span>
                        <span class="text-amber-900">{{ $homeScoreTotal }} Poin</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3">
                        <div class="bg-amber-500 h-3 rounded-full" style="width: {{ min(100, max(0, ($homeScoreTotal / max(1, $schoolScoreTotal + $homeScoreTotal)) * 100)) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Deskripsi Singkat -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                <h4 class="text-gray-900 font-bold mb-2">Analisis Singkat</h4>
                <p class="text-gray-600 text-sm leading-relaxed">
                    @if($schoolScoreTotal == 0 && $homeScoreTotal == 0)
                        Belum ada pencatatan poin karakter untuk ananda. Mari mulai catat kebaikan amaliyah di rumah melalui checklist di bawah ini!
                    @elseif($homeScoreTotal > $schoolScoreTotal)
                        Alhamdulillah, amaliyah ibadah dan karakter di rumah sangat baik! Poin di rumah mendominasi skor karakter ananda. Terus pertahankan dukungan Anda.
                    @elseif($schoolScoreTotal > $homeScoreTotal)
                        Poin karakter di sekolah mendominasi. Ananda aktif dan berperilaku baik di lingkungan sekolah. Jangan lupa untuk terus memantau Checklist Amaliyah di bawah ini agar karakter di rumah juga meningkat.
                    @else
                        Karakter ananda seimbang antara di sekolah dan di rumah. Terus pertahankan sinergi yang baik ini!
                    @endif
                </p>
            </div>

        </div>
    </div>

    <!-- Checklists Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Kegiatan Pagi -->
        <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-full -z-10"></div>
            
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6">
                <span class="p-2 bg-amber-100 text-amber-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
                Kegiatan Pagi
            </h3>
            
            @if (session()->has('message_morning'))
                <div class="px-4 py-3 mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('message_morning') }}
                </div>
            @endif
            
            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-amber-100 bg-gray-50/50 cursor-pointer transition-colors {{ $morning_sholat_subuh ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Sholat Subuh</span>
                    <input type="checkbox" wire:model="morning_sholat_subuh" {{ $morning_sholat_subuh ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $morning_sholat_subuh ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-amber-100 bg-gray-50/50 cursor-pointer transition-colors {{ $morning_merapikan_tempat_tidur ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Merapikan Tempat Tidur</span>
                    <input type="checkbox" wire:model="morning_merapikan_tempat_tidur" {{ $morning_merapikan_tempat_tidur ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $morning_merapikan_tempat_tidur ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-amber-100 bg-gray-50/50 cursor-pointer transition-colors {{ $morning_meminta_doa ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Meminta Doa sebelum berangkat</span>
                    <input type="checkbox" wire:model="morning_meminta_doa" {{ $morning_meminta_doa ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $morning_meminta_doa ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <div class="pt-4">
                    <button wire:click="saveMorning" class="w-full bg-amber-500 text-white px-4 py-3 rounded-xl font-bold tracking-wide hover:bg-amber-600 focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors shadow-sm">
                        Kirim Laporan Pagi
                    </button>
                </div>
            </div>
        </div>

        <!-- Kegiatan Sore/Malam -->
        <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 rounded-bl-full -z-10"></div>
            
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6">
                <span class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </span>
                Kegiatan Sore & Malam
            </h3>
            
            @if (session()->has('message_evening'))
                <div class="px-4 py-3 mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('message_evening') }}
                </div>
            @endif
            
            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_sholat_maghrib ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Sholat Maghrib</span>
                    <input type="checkbox" wire:model="evening_sholat_maghrib" {{ $evening_sholat_maghrib ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_sholat_maghrib ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_sholat_isya ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Sholat Isya'</span>
                    <input type="checkbox" wire:model="evening_sholat_isya" {{ $evening_sholat_isya ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_sholat_isya ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_murajaah ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Muraja'ah Hafalan</span>
                    <input type="checkbox" wire:model="evening_murajaah" {{ $evening_murajaah ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_murajaah ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_mengaji ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Mengaji</span>
                    <input type="checkbox" wire:model="evening_mengaji" {{ $evening_mengaji ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_mengaji ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_membantu_ortu ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Membantu Orang Tua (Min. 2 Kegiatan)</span>
                    <input type="checkbox" wire:model="evening_membantu_ortu" {{ $evening_membantu_ortu ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_membantu_ortu ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 bg-gray-50/50 cursor-pointer transition-colors {{ $evening_persiapan_sekolah ? 'opacity-60 bg-gray-100 cursor-not-allowed' : '' }}">
                    <span class="font-medium text-gray-700">Persiapan Sekolah Esok Hari</span>
                    <input type="checkbox" wire:model="evening_persiapan_sekolah" {{ $evening_persiapan_sekolah ? 'disabled' : '' }} class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 {{ $evening_persiapan_sekolah ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                </label>
                
                <div class="pt-4">
                    <button wire:click="saveEvening" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold tracking-wide hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
                        Kirim Laporan Malam
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

