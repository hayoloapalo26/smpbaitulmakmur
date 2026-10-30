<x-filament-panels::page>
    {{-- 1. HEADER BANNER PENUGASAN GURU PIKET (RESPONSIVE FOR ANDROID & IOS) --}}
    <div class="relative overflow-hidden rounded-xl sm:rounded-2xl bg-gradient-to-r {{ $isCurrentUserPiket ? 'from-emerald-700 via-teal-700 to-emerald-800' : 'from-slate-800 via-blue-900 to-indigo-900' }} p-4 sm:p-6 text-white shadow-md sm:shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold mb-2 {{ $isCurrentUserPiket ? 'bg-emerald-500/30 text-emerald-100 border border-emerald-400/30' : 'bg-white/10 text-white/90 border border-white/20' }}">
                    <span class="w-2 h-2 rounded-full {{ $isCurrentUserPiket ? 'bg-emerald-400 animate-ping' : 'bg-blue-400' }}"></span>
                    <span class="truncate">{{ $isCurrentUserPiket ? 'Anda Bertugas Sebagai Guru Piket Hari Ini' : 'Dasbor Monitoring Guru Piket' }}</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight sm:text-3xl">
                    Selamat Bertugas, {{ auth()->user()->name }}!
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-white/80 leading-relaxed">
                    Hari ini: <strong>{{ $hariIni }}, {{ now()->translatedFormat('d F Y') }}</strong> • Melayani ketertiban, presensi, kedisiplinan, dan koordinasi jam kosong sekolah.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                <div class="bg-black/30 backdrop-blur-md rounded-xl p-3 border border-white/15">
                    <div class="text-[10px] sm:text-[11px] uppercase tracking-wider text-white/70 font-semibold">Petugas Piket Hari Ini</div>
                    <div class="font-bold text-xs sm:text-sm text-white mt-0.5 leading-snug">
                        @if(!empty($petugasHariIni))
                            {{ implode(' • ', $petugasHariIni) }}
                        @else
                            <span class="text-amber-300 italic text-xs">Belum ada jadwal terdaftar</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. METRIK RINGKASAN PRESENSI HARI INI (RESPONSIVE GRID) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
        {{-- Total Siswa --}}
        <div class="rounded-xl border border-gray-200 bg-white p-3 sm:p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa</span>
                <span class="p-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-o-user-group" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-gray-900 dark:text-white">{{ $totalSiswa }}</div>
            <div class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5">Siswa terdaftar</div>
        </div>

        {{-- Hadir --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 sm:p-4 shadow-2xs dark:border-emerald-900/50 dark:bg-emerald-950/20 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold text-emerald-800 dark:text-emerald-300">Hadir</span>
                <span class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $siswaHadir }}</div>
            <div class="text-[10px] sm:text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $persenKehadiran }}% masuk</div>
        </div>

        {{-- Terlambat --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-3 sm:p-4 shadow-2xs dark:border-amber-900/50 dark:bg-amber-950/20 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold text-amber-800 dark:text-amber-300">Terlambat</span>
                <span class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300">
                    <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-amber-700 dark:text-amber-300">{{ $siswaTerlambat }}</div>
            <div class="text-[10px] sm:text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">Gerbang pagi</div>
        </div>

        {{-- Izin --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3 sm:p-4 shadow-2xs dark:border-blue-900/50 dark:bg-blue-950/20 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold text-blue-800 dark:text-blue-300">Izin</span>
                <span class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-blue-700 dark:text-blue-300">{{ $siswaIzin }}</div>
            <div class="text-[10px] sm:text-[11px] text-blue-600 dark:text-blue-400 mt-0.5">Dispensasi</div>
        </div>

        {{-- Sakit --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-3 sm:p-4 shadow-2xs dark:border-purple-900/50 dark:bg-purple-950/20 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold text-purple-800 dark:text-purple-300">Sakit</span>
                <span class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                    <x-filament::icon icon="heroicon-o-heart" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-purple-700 dark:text-purple-300">{{ $siswaSakit }}</div>
            <div class="text-[10px] sm:text-[11px] text-purple-600 dark:text-purple-400 mt-0.5">Keterangan</div>
        </div>

        {{-- Alpa --}}
        <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-3 sm:p-4 shadow-2xs dark:border-rose-900/50 dark:bg-rose-950/20 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] sm:text-xs font-semibold text-rose-800 dark:text-rose-300">Alpa / Belum</span>
                <span class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300">
                    <x-filament::icon icon="heroicon-o-x-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </span>
            </div>
            <div class="mt-1.5 sm:mt-2 text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-300">{{ $siswaAlpa }}</div>
            <div class="text-[10px] sm:text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">Tanpa kabar</div>
        </div>
    </div>

    {{-- 3. MENU PINTAS OPERASIONAL GURU PIKET (QUICK TOUCH BUTTONS) --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-bolt" class="w-5 h-5 text-amber-500" />
                <span class="text-base sm:text-lg">Aksi & Fitur Cepat Guru Piket</span>
            </div>
        </x-slot>
        <x-slot name="description">
            Akses langsung ke seluruh fitur harian guru piket (Responsif Mobile Android & iOS).
        </x-slot>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5 sm:gap-3 pt-2">
            {{-- 1. Input Absensi Manual (Cadangan Mati Lampu / Darurat) --}}
            <a href="{{ route('filament.staff.pages.absensi-manual') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Presensi Manual</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Cadangan Mati Lampu</span>
            </a>

            {{-- 2. Presensi Sholat (Dhuhur & Ashar) --}}
            <a href="{{ route('filament.staff.resources.prayer-attendances.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-cyan-500 hover:bg-cyan-50/50 dark:hover:bg-cyan-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-cyan-100 dark:bg-cyan-900/50 text-cyan-600 dark:text-cyan-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-cyan-600 dark:group-hover:text-cyan-400">Presensi Sholat</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Dhuhur & Ashar</span>
            </a>

            {{-- 3. Catatan Halangan Siswi (Udzur Syar'i) --}}
            <a href="{{ route('filament.staff.resources.student-halangans.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-pink-500 hover:bg-pink-50/50 dark:hover:bg-pink-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-pink-100 dark:bg-pink-900/50 text-pink-600 dark:text-pink-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-heart" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-pink-600 dark:group-hover:text-pink-400">Halangan Siswi</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Udzur Syar'i / Haid</span>
            </a>

            {{-- 4. Jadwal Piket Guru --}}
            <a href="{{ route('filament.staff.resources.duty-schedules.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-teal-500 hover:bg-teal-50/50 dark:hover:bg-teal-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-teal-600 dark:group-hover:text-teal-400">Jadwal Piket</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Jadwal 1 Semester</span>
            </a>

            {{-- 5. Izin Siswa --}}
            <a href="{{ route('filament.staff.resources.presensi-izin-siswas.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">Catat Izin</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Dispensasi</span>
            </a>

            {{-- 6. Pelanggaran Atribut --}}
            <a href="{{ route('filament.staff.resources.attribute-violations.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-rose-500 hover:bg-rose-50/50 dark:hover:bg-rose-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-rose-600 dark:group-hover:text-rose-400">Atribut & Disiplin</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Kerapian</span>
            </a>

            {{-- 7. Penilaian Karakter --}}
            <a href="{{ route('filament.staff.pages.character-score-report') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-amber-500 hover:bg-amber-50/50 dark:hover:bg-amber-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-star" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">Poin Karakter</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Akhlak Siswa</span>
            </a>

            {{-- 8. Tukar Piket --}}
            <a href="{{ route('filament.staff.resources.duty-swap-requests.index') }}" class="flex flex-col items-center justify-center p-3 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 active:scale-95 transition-all select-none group">
                <span class="p-2 sm:p-2.5 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-5 h-5 sm:w-6 sm:h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Tukar Jadwal</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Swap Shift</span>
            </a>
        </div>
    </x-filament::section>

    {{-- 3b. MONITORING SHOLAT BERJAMAAH & UDZUR SYAR'I (FINGERPRINT & PIKET) --}}
    <div class="rounded-xl border border-teal-200 dark:border-teal-900/60 bg-gradient-to-r from-teal-50/70 via-emerald-50/50 to-cyan-50/70 dark:from-teal-950/30 dark:via-emerald-950/20 dark:to-cyan-950/30 p-4 sm:p-5 shadow-2xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-teal-200/60 dark:border-teal-800/40">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-teal-500 text-white shadow-xs">
                    <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5" />
                </span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Monitoring Sholat Berjamaah & Udzur Syar'i
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-300">
                            Fingerprint + Piket
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Otomatis merekap dari Mesin Fingerprint (Dhuhur 12:00-13:59, Ashar 14:00-15:59) serta input Udzur/Sakit/Izin/Alpa dari Guru Piket.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('filament.staff.resources.prayer-attendances.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors shadow-2xs">
                    <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                    <span>Rekap Sholat</span>
                </a>
                <a href="{{ route('filament.staff.resources.student-halangans.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-pink-50 dark:bg-pink-950/40 border border-pink-200 dark:border-pink-900/60 text-pink-700 dark:text-pink-300 hover:bg-pink-100/70 transition-colors shadow-2xs">
                    <x-filament::icon icon="heroicon-o-plus-circle" class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                    <span>+ Catat Halangan</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3">
            {{-- Sholat Dhuhur --}}
            <div class="flex items-center justify-between p-3 rounded-lg bg-white/80 dark:bg-gray-900/80 border border-teal-100 dark:border-teal-900/40">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <div>
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200">Sholat Dhuhur</div>
                        <div class="text-[10px] text-gray-400">Fingerprint: 12:00 - 13:59</div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $sholatDhuhurHadir }}</span>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 ml-0.5">Hadir</span>
                </div>
            </div>

            {{-- Sholat Ashar --}}
            <div class="flex items-center justify-between p-3 rounded-lg bg-white/80 dark:bg-gray-900/80 border border-teal-100 dark:border-teal-900/40">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                    <div>
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200">Sholat Ashar</div>
                        <div class="text-[10px] text-gray-400">Fingerprint: 14:00 - 15:59</div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-cyan-600 dark:text-cyan-400">{{ $sholatAsharHadir }}</span>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 ml-0.5">Hadir</span>
                </div>
            </div>

            {{-- Siswi Halangan / Haid --}}
            <div class="flex items-center justify-between p-3 rounded-lg bg-white/80 dark:bg-gray-900/80 border border-pink-100 dark:border-pink-900/40">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                    <div>
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200">Udzur Syar'i (Haid)</div>
                        <div class="text-[10px] text-gray-400">Otomatis bebas sholat 1 hari</div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-pink-600 dark:text-pink-400">{{ $siswiHalangan }}</span>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 ml-0.5">Siswi</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. DUA KOLOM: MONITORING INVAL & SISWA BERMASALAH HARI INI --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        {{-- KOLOM KIRI: MONITORING KELAS KOSONG & GURU IZIN (INVAL PIKET) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5 text-indigo-500" />
                        <span class="text-sm sm:text-base font-bold">Jam Kosong & Inval Guru</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ count($guruIzinHariIni) > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
                        {{ count($guruIzinHariIni) }} Guru
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">
                Monitoring kelas kosong dan penugasan guru pengganti (inval).
            </x-slot>

            @if(count($guruIzinHariIni) > 0)
                {{-- MOBILE VIEW: TOUCH CARDS (< md) --}}
                <div class="block md:hidden space-y-2.5">
                    @foreach($guruIzinHariIni as $att)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-gray-900 dark:text-white">
                                    {{ $att->teacher?->name ?? 'Guru' }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'sakit' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-700' } }}">
                                    {{ $att->status }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-o-building-office" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <span>{{ $att->classroom?->name ?? ($att->schedule?->classroom?->name ?? '-') }} • {{ $att->subject?->name ?? ($att->schedule?->subject?->name ?? '-') }}</span>
                            </div>
                            @if($att->keterangan)
                                <div class="text-[11px] text-gray-500 italic bg-white dark:bg-gray-900/50 p-2 rounded-lg border border-gray-100 dark:border-gray-800">
                                    "{{ $att->keterangan }}"
                                </div>
                            @endif
                            <div class="pt-1 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-xs">
                                <span class="text-[11px] text-gray-400 font-medium">Petugas Inval:</span>
                                @if($att->substituteTeacher)
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                        <x-filament::icon icon="heroicon-m-check-badge" class="w-3.5 h-3.5" />
                                        {{ $att->substituteTeacher->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 italic text-[11px] font-semibold">
                                        <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-3.5 h-3.5" />
                                        Ditangani Guru Piket
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- DESKTOP VIEW: TABLE (>= md) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold">
                            <tr>
                                <th class="pb-2">Guru Izin</th>
                                <th class="pb-2">Kelas & Mapel</th>
                                <th class="pb-2">Keterangan</th>
                                <th class="pb-2">Guru Inval / Pengganti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($guruIzinHariIni as $att)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2.5 font-bold text-gray-900 dark:text-white">
                                        {{ $att->teacher?->name ?? 'Guru' }}
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $att->classroom?->name ?? ($att->schedule?->classroom?->name ?? '-') }}
                                        </span>
                                        <div class="text-[11px] text-gray-400">
                                            {{ $att->subject?->name ?? ($att->schedule?->subject?->name ?? '-') }}
                                        </div>
                                    </td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'sakit' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-700' } }}">
                                            {{ $att->status }}
                                        </span>
                                        @if($att->keterangan)
                                            <div class="text-[11px] text-gray-500 mt-0.5 truncate max-w-[120px]" title="{{ $att->keterangan }}">{{ $att->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        @if($att->substituteTeacher)
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                                <x-filament::icon icon="heroicon-m-check-badge" class="w-3.5 h-3.5" />
                                                {{ $att->substituteTeacher->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 italic text-[11px]">
                                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-3.5 h-3.5" />
                                                Ditangani Guru Piket
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-10 h-10 mx-auto mb-2 text-emerald-500 opacity-80" />
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">Seluruh Guru Hadir Mengajar</p>
                    <p class="text-xs text-gray-400 mt-0.5">Tidak ada guru yang izin atau jam kosong hari ini.</p>
                </div>
            @endif
        </x-filament::section>

        {{-- KOLOM KANAN: SISWA TERLAMBAT / ALPA & HUBUNGI ORANG TUA --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-user-minus" class="w-5 h-5 text-rose-500" />
                        <span class="text-sm sm:text-base font-bold">Terlambat & Alpa Hari Ini</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ count($siswaBermasalahHariIni) }} Siswa
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">
                Piket dapat langsung menghubungi wali murid melalui WhatsApp (1-Tap di Android & iOS).
            </x-slot>

            @if(count($siswaBermasalahHariIni) > 0)
                {{-- MOBILE VIEW: TOUCH CARDS (< md) --}}
                <div class="block md:hidden space-y-2.5 max-h-[420px] overflow-y-auto pr-1">
                    @foreach($siswaBermasalahHariIni as $att)
                        @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $att->student?->no_telepon_orang_tua ?? '');
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            $waText = "Assalamu'alaikum Warahmatullahi Wabarakatuh, Bapak/Ibu wali dari " . ($att->student?->name ?? 'ananda') . ".\nKami dari Guru Piket SMP Islam Baitul Makmur menginformasikan bahwa ananda hari ini tercatat " . ($att->is_late ? "TERLAMBAT masuk sekolah (Pukul {$att->waktu_kedatangan})" : strtoupper($att->status) . " di sekolah") . ".\nMohon perhatian dan konfirmasinya. Terima kasih.";
                            $waUrl = !empty($cleanPhone) ? 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($waText) : null;
                        @endphp
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xs space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-bold text-sm text-gray-900 dark:text-white">
                                        {{ $att->student?->name ?? 'Siswa' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Kelas {{ $att->student?->classroom?->name ?? '-' }}
                                    </div>
                                </div>
                                <div>
                                    @if($att->is_late && $att->status === 'hadir')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            Terlambat {{ $att->waktu_kedatangan ? \Carbon\Carbon::parse($att->waktu_kedatangan)->format('H:i') : '' }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'alpa' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', 'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-800' } }}">
                                            {{ $att->status }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Direct WA Action Button for Mobile --}}
                            @if($waUrl)
                                <a href="{{ $waUrl }}" target="_blank" class="flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs active:scale-95 transition-all">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="w-4 h-4" />
                                    <span>Hubungi Orang Tua (WhatsApp)</span>
                                </a>
                            @else
                                <div class="text-[11px] text-gray-400 italic text-center py-1">
                                    No HP Orang Tua belum tercatat
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- DESKTOP VIEW: TABLE (>= md) --}}
                <div class="hidden md:block overflow-x-auto max-h-[350px]">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold sticky top-0 bg-white dark:bg-gray-900">
                            <tr>
                                <th class="pb-2">Siswa</th>
                                <th class="pb-2">Kelas</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($siswaBermasalahHariIni as $att)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2.5 font-bold text-gray-900 dark:text-white">
                                        {{ $att->student?->name ?? 'Siswa' }}
                                        @if($att->is_late)
                                            <span class="block text-[10px] font-normal text-amber-600">Masuk: {{ $att->waktu_kedatangan ? \Carbon\Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $att->student?->classroom?->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        @if($att->is_late && $att->status === 'hadir')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'alpa' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', 'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-800' } }}">
                                                {{ $att->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right">
                                        @if(!empty($att->student?->no_telepon_orang_tua))
                                            @php
                                                $cleanPhone = preg_replace('/[^0-9]/', '', $att->student->no_telepon_orang_tua);
                                                if (str_starts_with($cleanPhone, '0')) {
                                                    $cleanPhone = '62' . substr($cleanPhone, 1);
                                                }
                                                $waText = "Assalamu'alaikum Warahmatullahi Wabarakatuh, Bapak/Ibu wali dari " . $att->student->name . ".\nKami dari Guru Piket sekolah menginformasikan bahwa ananda hari ini tercatat " . ($att->is_late ? "TERLAMBAT masuk sekolah (Pukul {$att->waktu_kedatangan})" : strtoupper($att->status) . " di sekolah") . ".\nMohon perhatian dan konfirmasinya. Terima kasih.";
                                                $waUrl = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($waText);
                                            @endphp
                                            <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold transition active:scale-95 shadow-2xs">
                                                <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="w-3.5 h-3.5" />
                                                <span>Kirim WA</span>
                                            </a>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">No HP (-)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-face-smile" class="w-10 h-10 mx-auto mb-2 text-emerald-500 opacity-80" />
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">Presensi Siswa Tertib</p>
                    <p class="text-xs text-gray-400 mt-0.5">Belum ada catatan siswa terlambat atau alpa hari ini.</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- 5. PELANGGARAN ATRIBUT HARI INI --}}
    @if(count($pelanggaranAtributHariIni) > 0)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="w-5 h-5 text-amber-500" />
                    <span class="text-sm sm:text-base font-bold">Catatan Pelanggaran Atribut & Kerapian Hari Ini</span>
                </div>
            </x-slot>

            {{-- MOBILE VIEW: CARDS (< md) --}}
            <div class="block md:hidden space-y-2.5">
                @foreach($pelanggaranAtributHariIni as $v)
                    <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xs space-y-1.5">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $v->student?->name ?? 'Siswa' }}</span>
                                <span class="text-xs text-gray-500 ml-1.5 font-medium">Kelas {{ $v->student?->classroom?->name ?? '-' }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">Oleh: {{ $v->teacher?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                {{ is_array($v->attribute_type) ? implode(', ', $v->attribute_type) : $v->attribute_type }}
                            </span>
                        </div>
                        @if($v->jenis_pelanggaran)
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                {{ $v->jenis_pelanggaran }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- DESKTOP VIEW: TABLE (>= md) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold">
                        <tr>
                            <th class="pb-2">Nama Siswa</th>
                            <th class="pb-2">Kelas</th>
                            <th class="pb-2">Atribut yang Dilanggar</th>
                            <th class="pb-2">Catatan Tambahan</th>
                            <th class="pb-2">Guru Piket Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($pelanggaranAtributHariIni as $v)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-2 font-bold text-gray-900 dark:text-white">{{ $v->student?->name ?? 'Siswa' }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $v->student?->classroom?->name ?? '-' }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        {{ is_array($v->attribute_type) ? implode(', ', $v->attribute_type) : $v->attribute_type }}
                                    </span>
                                </td>
                                <td class="py-2 text-gray-500">{{ $v->jenis_pelanggaran ?? '-' }}</td>
                                <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ $v->teacher?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
