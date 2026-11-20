<div class="mb-4 space-y-3">
    {{-- Bar Navigasi Tanggal & Pilihan Mode (Harian vs Rentang Tanggal) --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3 w-full lg:w-auto">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl shadow-xs border border-emerald-200 dark:border-emerald-800 shrink-0">
                @if($mode === 'rentang') 📊 @else 📅 @endif
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @if($mode === 'rentang')
                            Rekapitulasi Presensi Rentang Tanggal
                        @else
                            Pemantauan Presensi & Sholat Harian Siswa
                        @endif
                    </span>
                    @if($mode === 'harian' && ($tanggal ?? '') === now()->toDateString())
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold uppercase tracking-wider border border-emerald-300 dark:border-emerald-700">Hari Ini (H)</span>
                    @endif
                </div>
                <div class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>{{ $stats['date_formatted'] ?? date('l, d F Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Kontrol Filter & Mode Switcher --}}
        <div class="flex flex-wrap items-center justify-end gap-2 w-full lg:w-auto">
            @if($isWakaOrAdmin)
                {{-- Switcher Mode: Harian vs Rentang Tanggal --}}
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-gray-50 dark:bg-gray-800 mr-1">
                    <button 
                        type="button" 
                        wire:click="setMode('harian')" 
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition-all {{ $mode === 'harian' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                        📅 Harian
                    </button>
                    <button 
                        type="button" 
                        wire:click="setMode('rentang')" 
                        class="px-2.5 py-1 text-xs font-bold rounded-md transition-all {{ $mode === 'rentang' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                        📊 Rentang Tanggal
                    </button>
                </div>
            @endif

            @if($mode === 'harian')
                {{-- Kontrol Navigasi Tanggal Harian --}}
                @if($isWakaOrAdmin)
                    <button type="button" wire:click="setYesterday" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors border border-gray-200 dark:border-gray-700 shadow-xs" title="Lihat Hari Kemarin">
                        <span>⬅️</span>
                        <span class="hidden sm:inline">Kemarin</span>
                    </button>

                    <div class="relative">
                        <input 
                            type="date" 
                            wire:model.live="tanggal" 
                            class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            title="Pilih Tanggal Tertentu"
                        />
                    </div>

                    <button type="button" wire:click="setToday" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/80 rounded-lg transition-colors border border-emerald-300 dark:border-emerald-700 shadow-xs" title="Kembali ke Hari Ini">
                        <span>🔄</span>
                        <span>Hari Ini</span>
                    </button>

                    <button type="button" wire:click="setTomorrow" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors border border-gray-200 dark:border-gray-700 shadow-xs" title="Lihat Hari Esok">
                        <span class="hidden sm:inline">Besok</span>
                        <span>➡️</span>
                    </button>
                @else
                    {{-- Guru Piket (Hanya Hari H) --}}
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-300 dark:border-emerald-700">
                        <span>📍 Anda Bertugas Piket Hari Ini ({{ now()->translatedFormat('d M Y') }})</span>
                    </div>
                @endif
            @else
                {{-- Kontrol Filter Rentang Tanggal (Khusus Waka Kesiswaan & Pimpinan) --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Dari:</span>
                    <input 
                        type="date" 
                        wire:model.live="tanggalMulai" 
                        class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    />

                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">s.d:</span>
                    <input 
                        type="date" 
                        wire:model.live="tanggalSelesai" 
                        class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    />

                    <div class="inline-flex items-center gap-1 ml-1">
                        <button type="button" wire:click="setThisWeek" class="px-2 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded border border-gray-200 dark:border-gray-700">
                            Minggu Ini
                        </button>
                        <button type="button" wire:click="setThisMonth" class="px-2 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded border border-gray-200 dark:border-gray-700">
                            Bulan Ini
                        </button>
                        <button type="button" wire:click="setLastMonth" class="px-2 py-1 text-[11px] font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded border border-gray-200 dark:border-gray-700">
                            Bulan Lalu
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- 5 Kartu Statistik Ringkasan Realtime --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        {{-- Total Siswa --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 shadow-sm hover:shadow transition-shadow">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-semibold mb-1">
                <span>Total Siswa Aktif</span>
                <span class="text-base">👥</span>
            </div>
            <div class="text-2xl font-black text-gray-900 dark:text-white">
                {{ $stats['total_siswa'] ?? 0 }}
            </div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>SMP Islam Baitul Makmur</span>
            </div>
        </div>

        {{-- Kedatangan Pagi --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 shadow-sm hover:shadow transition-shadow border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-semibold mb-1">
                <span>{{ $mode === 'rentang' ? 'Total Kedatangan Masuk' : 'Kedatangan Masuk' }}</span>
                <span class="text-base">🏫</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ $stats['masuk_hadir'] ?? 0 }}
                @if($mode === 'harian')
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">/ {{ $stats['total_siswa'] ?? 0 }}</span>
                @else
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Scan</span>
                @endif
            </div>
            <div class="text-[11px] mt-1 flex flex-wrap gap-x-2 gap-y-0.5">
                <span class="text-emerald-700 dark:text-emerald-400 font-semibold">✅ {{ $stats['masuk_tepat'] ?? 0 }} Tepat</span>
                @if(($stats['masuk_terlambat'] ?? 0) > 0)
                    <span class="text-amber-700 dark:text-amber-400 font-bold">⏰ {{ $stats['masuk_terlambat'] ?? 0 }} Telat</span>
                @endif
                @if($mode === 'harian' && is_numeric($stats['masuk_belum'] ?? null) && ($stats['masuk_belum'] ?? 0) > 0)
                    <span class="text-gray-500 dark:text-gray-400">⏳ {{ $stats['masuk_belum'] ?? 0 }} Belum</span>
                @endif
            </div>
        </div>

        {{-- Sholat Dhuhur / Keputrian --}}
        @php
            $isJumat = $mode === 'harian' && \Carbon\Carbon::parse($tanggal ?? now())->isFriday();
        @endphp
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 shadow-sm hover:shadow transition-shadow border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-semibold mb-1">
                <span>{{ $mode === 'rentang' ? 'Total Sholat Dhuhur' : ($isJumat ? 'Dhuhur & Keputrian' : 'Sholat Dhuhur') }}</span>
                <span class="text-base">{{ $isJumat ? '🕌 / 🌸' : '🕌' }}</span>
            </div>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400">
                {{ $stats['dhuhur_hadir'] ?? 0 }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $mode === 'rentang' ? 'Scan' : 'Santri' }}</span>
            </div>
            <div class="text-[11px] mt-1 flex flex-wrap gap-x-2 gap-y-0.5">
                <span class="text-blue-700 dark:text-blue-400 font-semibold">✅ {{ $stats['dhuhur_tepat'] ?? 0 }} Tepat</span>
                @if(($stats['dhuhur_halangan'] ?? 0) > 0)
                    <span class="text-purple-700 dark:text-purple-400 font-bold">🌸 {{ $stats['dhuhur_halangan'] ?? 0 }} Udzur</span>
                @endif
                @if(($stats['dhuhur_terlambat'] ?? 0) > 0)
                    <span class="text-amber-700 dark:text-amber-400 font-bold">⏰ {{ $stats['dhuhur_terlambat'] ?? 0 }} Telat</span>
                @endif
            </div>
        </div>

        {{-- Sholat Ashar --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 shadow-sm hover:shadow transition-shadow border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-semibold mb-1">
                <span>{{ $mode === 'rentang' ? 'Total Sholat Ashar' : 'Sholat Ashar & Pulang' }}</span>
                <span class="text-base">🕌</span>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ $stats['ashar_hadir'] ?? 0 }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $mode === 'rentang' ? 'Scan' : 'Santri' }}</span>
            </div>
            <div class="text-[11px] mt-1 flex flex-wrap gap-x-2 gap-y-0.5">
                <span class="text-amber-700 dark:text-amber-400 font-semibold">✅ {{ $stats['ashar_tepat'] ?? 0 }} Tepat</span>
                @if(($stats['ashar_halangan'] ?? 0) > 0)
                    <span class="text-purple-700 dark:text-purple-400 font-bold">🌸 {{ $stats['ashar_halangan'] ?? 0 }} Udzur</span>
                @endif
                @if(($stats['ashar_terlambat'] ?? 0) > 0)
                    <span class="text-red-700 dark:text-red-400 font-bold">⏰ {{ $stats['ashar_terlambat'] ?? 0 }} Telat</span>
                @endif
            </div>
        </div>

        {{-- Izin / Sakit / Alpha --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 shadow-sm hover:shadow transition-shadow border-l-4 border-l-rose-500 col-span-2 md:col-span-1">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-semibold mb-1">
                <span>Izin, Sakit & Alpha</span>
                <span class="text-base">📋</span>
            </div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400">
                {{ ($stats['sakit_count'] ?? 0) + ($stats['izin_count'] ?? 0) + ($stats['alpa_count'] ?? 0) }}
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Hari</span>
            </div>
            <div class="text-[11px] mt-1 flex flex-wrap gap-x-2 gap-y-0.5">
                <span class="text-amber-700 dark:text-amber-400 font-semibold">🤒 {{ $stats['sakit_count'] ?? 0 }} Sakit</span>
                <span class="text-blue-700 dark:text-blue-400 font-semibold">📋 {{ $stats['izin_count'] ?? 0 }} Izin</span>
                @if(($stats['alpa_count'] ?? 0) > 0)
                    <span class="text-red-700 dark:text-red-400 font-bold">❌ {{ $stats['alpa_count'] ?? 0 }} Alpha</span>
                @endif
            </div>
        </div>
    </div>
</div>
