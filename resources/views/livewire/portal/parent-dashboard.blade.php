<div class="max-w-7xl mx-auto py-4 sm:py-8 px-2.5 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

    <!-- Top Navigation / Header & Child Switcher -->
    <div class="bg-white p-5 sm:p-6 shadow-sm sm:rounded-2xl border border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Portal Monitoring Orang Tua
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-gray-900">
                Laporan Harian & Karakter Ananda
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                Monitoring terpadu kehadiran, ibadah sholat, presensi mapel, poin karakter, nilai, dan capaian Al-Qur'an.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Student Selector (Jika punya >1 anak) -->
            @if($students->isNotEmpty())
            <div class="relative min-w-[220px]">
                <label for="studentSelect" class="sr-only">Pilih Anak:</label>
                <select wire:model.live="selectedStudentId" id="studentSelect" class="block w-full pl-3 pr-10 py-2.5 text-xs sm:text-sm font-bold text-gray-800 bg-emerald-50/60 border border-emerald-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 rounded-xl appearance-none cursor-pointer shadow-sm">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">👦 {{ $s->name }} ({{ $s->classroom?->name ?? 'Kelas -' }})</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            @endif

            <!-- Date Selector -->
            <div class="flex items-center gap-2 bg-gray-50 p-1 rounded-xl border border-gray-200">
                <button type="button" wire:click="setDateToday" class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $selectedDate === \Carbon\Carbon::today()->toDateString() ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200' }}">
                    Hari Ini
                </button>
                <button type="button" wire:click="setDateYesterday" class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $selectedDate === \Carbon\Carbon::yesterday()->toDateString() ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200' }}">
                    Kemarin
                </button>
                <input type="date" wire:model.live="selectedDate" class="text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-lg px-2 py-1 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>
    </div>

    @if($students->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center space-y-4 shadow-sm">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-amber-900">Akun Orang Tua Telah Aktif</h3>
        <p class="text-sm text-amber-700 max-w-lg mx-auto">
            Akun Anda telah berhasil dibuat. Saat ini bagian Tata Usaha (TU) sedang memetakan data ananda ke akun Anda. Mohon konfirmasi ke pihak Tata Usaha jika data ananda belum tampil.
        </p>
    </div>
    @endif

    @if($activeStudent)
    <!-- Active Student Hero Header -->
    <div class="bg-gradient-to-r from-emerald-700 via-teal-600 to-cyan-600 rounded-2xl shadow-xl overflow-hidden relative p-6 sm:p-8 text-white">
        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-40 -mb-6 w-32 h-32 bg-emerald-400/20 rounded-full blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-2xl font-black shadow-inner">
                    {{ strtoupper(substr($activeStudent->name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $activeStudent->name }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 text-white border border-white/30">
                            {{ $activeStudent->classroom?->name ?? 'Belum ada kelas' }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-emerald-100 mt-1">
                        NISN: <span class="font-bold text-white">{{ $activeStudent->nisn ?: '-' }}</span>
                        • Wali Kelas: <span class="font-bold text-white">{{ $activeStudent->classroom?->waliKelas?->name ?? 'Belum ditentukan' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 bg-black/20 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/20">
                <div class="text-right">
                    <p class="text-[10px] text-emerald-200 uppercase tracking-widest font-bold">Periode Tanggal Pantau</p>
                    <p class="text-sm font-extrabold text-white">
                        {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/40 flex items-center justify-center text-white">
                    📅
                </div>
            </div>
        </div>
    </div>

    <!-- 7 KARTU RINGKASAN CEPAT (7 POIN UTAMA) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
        
        <!-- 1. Presensi Masuk -->
        @php
            $arrival = $this->arrivalAttendance;
            $hasArrival = !empty($arrival);
            $waktuMasuk = $arrival?->waktu_kedatangan ? \Carbon\Carbon::parse($arrival->waktu_kedatangan)->format('H:i') : null;
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $hasArrival ? ($arrival->is_late ? 'border-amber-300 bg-amber-50/40' : 'border-emerald-200 bg-emerald-50/40') : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">1. Kehadiran</span>
                    <span>🕒</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    {{ $waktuMasuk ? "{$waktuMasuk} WIB" : ($hasArrival ? ucfirst($arrival->status) : 'Belum Scan') }}
                </div>
            </div>
            <div class="mt-2">
                @if($hasArrival)
                    @if($arrival->is_late)
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-amber-100 text-amber-800">⚠️ Terlambat</span>
                    @elseif($arrival->status === 'hadir')
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800">✓ Tepat Waktu</span>
                    @else
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-800">{{ ucfirst($arrival->status) }}</span>
                    @endif
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Menunggu</span>
                @endif
            </div>
        </div>

        <!-- 2. Sholat Dhuhur -->
        @php
            $dhuhur = $this->dhuhurPrayer;
            $hasDhuhur = !empty($dhuhur);
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $hasDhuhur ? 'border-teal-200 bg-teal-50/40' : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">2. Sholat Dhuhur</span>
                    <span>🕌</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    @if($hasDhuhur)
                        {{ $dhuhur->status === 'halangan' ? 'Halangan' : (in_array($dhuhur->status, ['hadir', 'masuk']) ? ($dhuhur->is_khusyu ? 'Tepat Waktu' : 'Masbuk') : ucfirst($dhuhur->status)) }}
                    @else
                        Belum Sholat
                    @endif
                </div>
            </div>
            <div class="mt-2">
                @if($hasDhuhur)
                    @if($dhuhur->status === 'halangan')
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-purple-100 text-purple-800">🌸 Halangan</span>
                    @elseif(in_array($dhuhur->status, ['hadir', 'masuk']))
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded {{ $dhuhur->is_khusyu ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $dhuhur->is_khusyu ? '✓ Khusyu' : '⏳ Masbuk' }}
                        </span>
                    @else
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-800">{{ ucfirst($dhuhur->status) }}</span>
                    @endif
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Jadwal Dhuhur</span>
                @endif
            </div>
        </div>

        <!-- 3. Sholat Ashar -->
        @php
            $ashar = $this->asharPrayer;
            $hasAshar = !empty($ashar);
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $hasAshar ? 'border-teal-200 bg-teal-50/40' : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">3. Sholat Ashar</span>
                    <span>🕌</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    @if($hasAshar)
                        {{ $ashar->status === 'halangan' ? 'Halangan' : (in_array($ashar->status, ['hadir', 'masuk']) ? ($ashar->is_khusyu ? 'Tepat Waktu' : 'Masbuk') : ucfirst($ashar->status)) }}
                    @else
                        Belum Sholat
                    @endif
                </div>
            </div>
            <div class="mt-2">
                @if($hasAshar)
                    @if($ashar->status === 'halangan')
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-purple-100 text-purple-800">🌸 Halangan</span>
                    @elseif(in_array($ashar->status, ['hadir', 'masuk']))
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded {{ $ashar->is_khusyu ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $ashar->is_khusyu ? '✓ Khusyu' : '⏳ Masbuk' }}
                        </span>
                    @else
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-800">{{ ucfirst($ashar->status) }}</span>
                    @endif
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Jadwal Ashar</span>
                @endif
            </div>
        </div>

        <!-- 4. Kehadiran Mapel -->
        @php
            $subjAtts = $this->subjectAttendances;
            $countSubj = $subjAtts->count();
            $countHadirSubj = $subjAtts->where('status', 'hadir')->count();
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $countSubj > 0 ? 'border-sky-200 bg-sky-50/40' : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">4. Presensi Mapel</span>
                    <span>📚</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    {{ $countSubj > 0 ? "{$countHadirSubj} / {$countSubj} Mapel" : 'Belum Ada Jurnal' }}
                </div>
            </div>
            <div class="mt-2">
                @if($countSubj > 0)
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-sky-100 text-sky-800">
                        {{ $countHadirSubj == $countSubj ? '✓ Lengkap Hadir' : '⚠️ Ada Izin/Absen' }}
                    </span>
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Mengikuti KBM</span>
                @endif
            </div>
        </div>

        <!-- 5. Skor Karakter -->
        @php
            $charLogs = $this->characterLogsToday;
            $plusPoints = $charLogs->where('point_change', '>', 0)->sum('point_change');
            $minusPoints = $charLogs->where('point_change', '<', 0)->sum('point_change');
        @endphp
        <div class="bg-white rounded-xl p-3.5 border border-purple-200 bg-purple-50/40 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">5. Skor Karakter</span>
                    <span>⭐</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    {{ $activeStudent->character_score ?? ($schoolScoreTotal + $homeScoreTotal) }} Poin
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1 flex-wrap">
                @if($plusPoints > 0)
                    <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800">+{{ $plusPoints }}</span>
                @endif
                @if($minusPoints < 0)
                    <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-800">{{ $minusPoints }}</span>
                @endif
                @if($plusPoints == 0 && $minusPoints == 0)
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Stabil</span>
                @endif
            </div>
        </div>

        <!-- 6. Pelanggaran -->
        @php
            $violations = $this->violationsToday;
            $countViolations = $violations->count();
            $sikap = $this->sikapPredikat;
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $countViolations > 0 ? 'border-rose-300 bg-rose-50/40' : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">6. Pelanggaran</span>
                    <span>⚠️</span>
                </div>
                <div class="text-sm font-black text-gray-900">
                    {{ $countViolations > 0 ? "{$countViolations} Catatan" : '0 Pelanggaran' }}
                </div>
            </div>
            <div class="mt-2">
                @if($countViolations > 0)
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-rose-100 text-rose-800">⚠️ Ada Poin Minus</span>
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800">✓ Sikap {{ $sikap['predikat'] }}</span>
                @endif
            </div>
        </div>

        <!-- 7. Capaian Al-Qur'an -->
        @php
            $quranToday = $this->quranAchievementsToday->first() ?: $this->recentQuranAchievements->first();
        @endphp
        <div class="bg-white rounded-xl p-3.5 border {{ $quranToday ? 'border-amber-200 bg-amber-50/40' : 'border-gray-200' }} shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span class="font-bold uppercase tracking-wider text-[10px]">7. Al-Qur'an</span>
                    <span>📖</span>
                </div>
                <div class="text-xs font-black text-gray-900 truncate">
                    @if($quranToday)
                        {{ $quranToday->surah ?: 'Ziyadah' }} ({{ $quranToday->ayah ?: '-' }})
                    @else
                        Belum Ada Setoran
                    @endif
                </div>
            </div>
            <div class="mt-2">
                @if($quranToday)
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded bg-amber-100 text-amber-800">
                        {{ $quranToday->predikat ?: 'Mumtaz' }}
                    </span>
                @else
                    <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">Menunggu</span>
                @endif
            </div>
        </div>

    </div>

    <!-- TABS NAVIGASI 7 FITUR LENGKAP -->
    <div class="flex items-center gap-2 overflow-x-auto border-b border-gray-200 pb-2">
        <button type="button" wire:click="setActiveTab('semua')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'semua' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            📑 Tampilkan Semua
        </button>
        <button type="button" wire:click="setActiveTab('presensi')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'presensi' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            🕒 Presensi & Sholat (1-2)
        </button>
        <button type="button" wire:click="setActiveTab('mapel')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'mapel' ? 'bg-sky-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            📚 Kehadiran Mapel (3)
        </button>
        <button type="button" wire:click="setActiveTab('karakter')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'karakter' ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            ⭐ Skor Karakter (4)
        </button>
        <button type="button" wire:click="setActiveTab('pelanggaran')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'pelanggaran' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            ⚠️ Pelanggaran Siswa (5)
        </button>
        <button type="button" wire:click="setActiveTab('nilai')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'nilai' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            📝 Nilai Harian (6)
        </button>
        <button type="button" wire:click="setActiveTab('quran')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'quran' ? 'bg-amber-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            📖 Capaian Qur'an (7)
        </button>
        <button type="button" wire:click="setActiveTab('amaliyah')" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition {{ $activeTab === 'amaliyah' ? 'bg-teal-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
            🏡 Checklist Rumah & Rapor
        </button>
    </div>

    <!-- DETAIL SECTIONS -->
    <div class="space-y-6">

        <!-- 1 & 2. PRESENSI MASUK & SHOLAT BERJAMAAH -->
        @if($activeTab === 'semua' || $activeTab === 'presensi')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">🕒</span>
                        1. Kehadiran Masuk Sekolah & 2. Absensi Sholat Berjamaah
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Waktu kedatangan di gerbang sekolah serta ketepatan waktu sholat berjamaah Dhuhur & Ashar.</p>
                </div>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Waktu Kedatangan -->
                <div class="bg-gradient-to-br from-emerald-50/50 to-emerald-100/30 rounded-xl p-5 border border-emerald-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Presensi Masuk</span>
                            <span class="text-xl">🚪</span>
                        </div>
                        <div class="mt-3">
                            @if($arrival)
                                <div class="text-3xl font-black text-gray-900">
                                    {{ $arrival->waktu_kedatangan ? \Carbon\Carbon::parse($arrival->waktu_kedatangan)->format('H:i') . ' WIB' : ucfirst($arrival->status) }}
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    @if($arrival->is_late)
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-800 border border-rose-200">
                                            ⚠️ Terlambat Masuk
                                        </span>
                                    @elseif($arrival->status === 'hadir')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ✓ Tepat Waktu (Sebelum 06.45)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-800">
                                            {{ ucfirst($arrival->status) }}
                                        </span>
                                    @endif
                                </div>
                                @if($arrival->keterangan)
                                    <p class="text-xs text-gray-600 mt-2 bg-white/70 p-2 rounded-lg border border-emerald-100">
                                        Catatan: {{ $arrival->keterangan }}
                                    </p>
                                @endif
                            @else
                                <div class="text-xl font-bold text-gray-400">Belum Tercatat</div>
                                <p class="text-xs text-gray-500 mt-1">Belum ada scan presensi masuk pada tanggal ini.</p>
                            @endif
                        </div>
                    </div>
                    @if($this->latestArrival && !$arrival)
                        <div class="mt-4 pt-3 border-t border-emerald-200/60 text-[11px] text-gray-500">
                            Terakhir tercatat: <strong>{{ \Carbon\Carbon::parse($this->latestArrival->tanggal)->translatedFormat('d M Y') }}</strong> ({{ $this->latestArrival->waktu_kedatangan ? \Carbon\Carbon::parse($this->latestArrival->waktu_kedatangan)->format('H:i') . ' WIB' : ucfirst($this->latestArrival->status) }})
                        </div>
                    @endif
                </div>

                <!-- Sholat Dhuhur -->
                <div class="bg-gradient-to-br from-teal-50/50 to-teal-100/30 rounded-xl p-5 border border-teal-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-teal-800 uppercase tracking-wider">Sholat Dhuhur</span>
                            <span class="text-xl">🕌</span>
                        </div>
                        <div class="mt-3">
                            @if($dhuhur)
                                <div class="text-2xl font-black text-gray-900">
                                    @if($dhuhur->status === 'halangan')
                                        🌸 Berhalangan / Haid
                                    @elseif(in_array($dhuhur->status, ['hadir', 'masuk']))
                                        {{ $dhuhur->is_khusyu ? '✓ Tepat Waktu & Khusyu' : '⏳ Masbuk' }}
                                    @else
                                        {{ ucfirst($dhuhur->status) }}
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <span class="inline-block px-2.5 py-1 text-xs font-bold rounded-lg {{ $dhuhur->status === 'halangan' ? 'bg-purple-100 text-purple-800' : ($dhuhur->is_khusyu ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $dhuhur->keterangan ?: ($dhuhur->is_khusyu ? 'Hadir Berjamaah Tepat Waktu' : 'Terlambat / Masbuk') }}
                                    </span>
                                </div>
                                @if(!empty($dhuhur->teacher))
                                    <p class="text-[11px] text-gray-500 mt-2">Guru Pendamping: {{ $dhuhur->teacher->name }}</p>
                                @endif
                            @else
                                <div class="text-xl font-bold text-gray-400">Belum Tercatat</div>
                                <p class="text-xs text-gray-500 mt-1">Presensi sholat Dhuhur belum diinputkan oleh guru pembimbing.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sholat Ashar -->
                <div class="bg-gradient-to-br from-cyan-50/50 to-cyan-100/30 rounded-xl p-5 border border-cyan-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-cyan-800 uppercase tracking-wider">Sholat Ashar</span>
                            <span class="text-xl">🕌</span>
                        </div>
                        <div class="mt-3">
                            @if($ashar)
                                <div class="text-2xl font-black text-gray-900">
                                    @if($ashar->status === 'halangan')
                                        🌸 Berhalangan / Haid
                                    @elseif(in_array($ashar->status, ['hadir', 'masuk']))
                                        {{ $ashar->is_khusyu ? '✓ Tepat Waktu & Khusyu' : '⏳ Masbuk' }}
                                    @else
                                        {{ ucfirst($ashar->status) }}
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <span class="inline-block px-2.5 py-1 text-xs font-bold rounded-lg {{ $ashar->status === 'halangan' ? 'bg-purple-100 text-purple-800' : ($ashar->is_khusyu ? 'bg-cyan-100 text-cyan-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $ashar->keterangan ?: ($ashar->is_khusyu ? 'Hadir Berjamaah Tepat Waktu' : 'Terlambat / Masbuk') }}
                                    </span>
                                </div>
                                @if(!empty($ashar->teacher))
                                    <p class="text-[11px] text-gray-500 mt-2">Guru Pendamping: {{ $ashar->teacher->name }}</p>
                                @endif
                            @else
                                <div class="text-xl font-bold text-gray-400">Belum Tercatat</div>
                                <p class="text-xs text-gray-500 mt-1">Presensi sholat Ashar belum diinputkan oleh guru pembimbing.</p>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- Mini Strip 7 Hari Terakhir -->
            @if($this->recentWeeklyAttendances->isNotEmpty())
            <div class="pt-4 border-t border-gray-100">
                <span class="text-xs font-bold text-gray-600 block mb-2">Riwayat Kehadiran 7 Hari Terakhir:</span>
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    @foreach($this->recentWeeklyAttendances as $wAtt)
                        <div class="flex-shrink-0 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-center text-xs min-w-[90px]">
                            <div class="text-[10px] text-gray-500 font-semibold">{{ \Carbon\Carbon::parse($wAtt->tanggal)->translatedFormat('D, d M') }}</div>
                            <div class="font-extrabold mt-0.5 {{ $wAtt->is_late ? 'text-amber-600' : ($wAtt->status === 'hadir' ? 'text-emerald-600' : 'text-blue-600') }}">
                                {{ $wAtt->waktu_kedatangan ? \Carbon\Carbon::parse($wAtt->waktu_kedatangan)->format('H:i') : ucfirst($wAtt->status) }}
                            </div>
                            <div class="text-[9px] font-bold mt-0.5">
                                {{ $wAtt->is_late ? 'Terlambat' : ($wAtt->status === 'hadir' ? 'Tepat Waktu' : ucfirst($wAtt->status)) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- 3. KEHADIRAN PER MATA PELAJARAN (JURNAL MENGAJAR GURU) -->
        @if($activeTab === 'semua' || $activeTab === 'mapel')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-5">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-sky-100 text-sky-700 rounded-lg">📚</span>
                        3. Presensi Kehadiran Tiap Jam Mata Pelajaran (Mapel)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Catatan presensi per jam pelajaran yang diinput langsung oleh guru pengampu saat KBM.</p>
                </div>
            </div>

            @php
                $subjectAtts = $this->subjectAttendances;
            @endphp

            @if($subjectAtts->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-sky-50/70 text-sky-900 border-b border-sky-100 font-bold">
                                <th class="px-4 py-3">Jam Ke</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="px-4 py-3">Guru Pengajar</th>
                                <th class="px-4 py-3 text-center">Status Kehadiran</th>
                                <th class="px-4 py-3">Materi Pelajaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($subjectAtts as $sa)
                                @php
                                    $tj = $sa->teachingJournal;
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 font-bold text-gray-700 whitespace-nowrap">
                                        Jam ke {{ $tj?->jam_ke ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-black text-gray-900 whitespace-nowrap">
                                        {{ $tj?->subject?->name ?? 'Mata Pelajaran' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $tj?->teacher?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($sa->status === 'hadir')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                ✓ Hadir di Kelas
                                            </span>
                                        @elseif($sa->status === 'izin')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                ⏳ Izin
                                            </span>
                                        @elseif($sa->status === 'sakit')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800">
                                                🏥 Sakit
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                ❌ {{ ucfirst($sa->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $tj?->materi ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-6 text-center">
                    <p class="text-sm font-bold text-gray-700">Belum ada inputan jurnal mapel pada tanggal ini.</p>
                    <p class="text-xs text-gray-500 mt-1">KBM mungkin sedang libur atau guru belum selesai menginput jurnal mengajar hari ini.</p>

                    @if($this->recentSubjectAttendances->isNotEmpty())
                        <div class="mt-4 pt-4 border-t border-gray-200 text-left">
                            <span class="text-xs font-bold text-gray-600 block mb-2">Riwayat Presensi Mapel Terakhir:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($this->recentSubjectAttendances as $rsa)
                                    @php $rtj = $rsa->teachingJournal; @endphp
                                    <div class="bg-white p-3 rounded-xl border border-gray-200 text-xs shadow-sm">
                                        <div class="flex items-center justify-between text-gray-500 font-semibold mb-1">
                                            <span>{{ $rtj?->tanggal ? \Carbon\Carbon::parse($rtj->tanggal)->translatedFormat('d M Y') : '-' }}</span>
                                            <span class="font-bold text-emerald-600 uppercase">{{ $rsa->status }}</span>
                                        </div>
                                        <div class="font-extrabold text-gray-900">{{ $rtj?->subject?->name ?? 'Mapel' }}</div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $rtj?->teacher?->name ?? '-' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        @endif

        <!-- 4. SKOR KARAKTER DARI JURNAL MENGAJAR GURU -->
        @if($activeTab === 'semua' || $activeTab === 'karakter')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-purple-100 text-purple-700 rounded-lg">⭐</span>
                        4. Catatan Skor Karakter Harian dari Guru
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Detail skor tambah (kebaikan/prestasi) dan skor kurang (evaluasi sikap) yang diinputkan guru di jurnal mengajar.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Total Poin Karakter:</span>
                    <span class="text-xl font-black text-purple-700">{{ $activeStudent->character_score ?? ($schoolScoreTotal + $homeScoreTotal) }} Poin</span>
                </div>
            </div>

            @php
                $charToday = $this->characterLogsToday;
                $charRecent = $this->recentCharacterLogs;
            @endphp

            @if($charToday->isNotEmpty())
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Catatan Karakter Hari Ini:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($charToday as $cLog)
                            <div class="p-4 rounded-xl border {{ $cLog->point_change > 0 ? 'border-emerald-200 bg-emerald-50/50' : 'border-rose-200 bg-rose-50/50' }} flex items-start gap-3">
                                <span class="text-2xl mt-0.5">{{ $cLog->point_change > 0 ? '🌟' : '⚠️' }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black {{ $cLog->point_change > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $cLog->point_change > 0 ? "+{$cLog->point_change} Skor Tambah" : "{$cLog->point_change} Skor Kurang" }}
                                        </span>
                                        <span class="text-[10px] text-gray-500 uppercase font-bold">{{ $cLog->category }}</span>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800 mt-1">{{ $cLog->description }}</p>
                                    <p class="text-[11px] text-gray-500 mt-1">Dicatat oleh: <strong>{{ $cLog->submitter?->name ?? 'Guru Sekolah' }}</strong></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center text-xs text-gray-500">
                    Tidak ada catatan perubahan poin karakter pada tanggal yang dipilih.
                </div>
            @endif

            <!-- Riwayat 10 Poin Karakter Terakhir -->
            @if($charRecent->isNotEmpty())
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Riwayat Catatan Karakter Sekolah Terakhir:</h4>
                    <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                        @foreach($charRecent as $rLog)
                            <div class="py-2.5 flex items-center justify-between text-xs gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded font-black text-xs {{ $rLog->point_change > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $rLog->point_change > 0 ? "+{$rLog->point_change}" : $rLog->point_change }}
                                    </span>
                                    <div>
                                        <span class="font-bold text-gray-800">{{ $rLog->description }}</span>
                                        <span class="text-[10px] text-gray-500 block">Guru: {{ $rLog->submitter?->name ?? 'Guru' }} • {{ \Carbon\Carbon::parse($rLog->tanggal)->translatedFormat('d M Y') }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">{{ $rLog->category }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif

        <!-- 5. PELANGGARAN SISWA -->
        @if($activeTab === 'semua' || $activeTab === 'pelanggaran')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-rose-100 text-rose-700 rounded-lg">⚠️</span>
                        5. Catatan Pelanggaran Siswa & Kedisiplinan
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Bentuk pelanggaran tata tertib sekolah, bobot poin, dan tindak lanjut pembinaan.</p>
                </div>
                @php $sikapInfo = $this->sikapPredikat; @endphp
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Predikat Sikap:</span>
                    <span class="text-base font-black px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">
                        {{ $sikapInfo['label'] }}
                    </span>
                </div>
            </div>

            @php
                $allViolations = $this->allViolations;
            @endphp

            @if($allViolations->isEmpty())
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center space-y-2">
                    <div class="text-3xl">🎉</div>
                    <h4 class="text-base font-bold text-emerald-900">Alhamdulillah! Tidak Ada Catatan Pelanggaran</h4>
                    <p class="text-xs text-emerald-700 max-w-md mx-auto">
                        Ananda memiliki catatan kedisiplinan yang sangat baik dan senantiasa menaati tata tertib sekolah. Terus dampingi dan berikan apresiasi kepada ananda!
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-rose-50/70 text-rose-900 border-b border-rose-100 font-bold">
                                <th class="px-4 py-3">Tanggal & Jam</th>
                                <th class="px-4 py-3">Bentuk Pelanggaran</th>
                                <th class="px-4 py-3 text-center">Poin</th>
                                <th class="px-4 py-3">Guru Pelapor</th>
                                <th class="px-4 py-3">Tindak Lanjut Langsung</th>
                                <th class="px-4 py-3">Penanganan Wali Kelas / BK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($allViolations as $v)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-700 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($v->tanggal)->translatedFormat('d M Y') }}
                                        <span class="block text-[11px] text-gray-400">{{ $v->jam ?: '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-rose-900">
                                        {{ $v->bentuk_pelanggaran }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800">
                                            -{{ $v->poin }} Poin
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $v->teacher?->name ?? 'Guru Piket' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $v->tindak_lanjut_langsung ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $v->penanganan_wali_kelas ?: ($v->penanganan_bk ?: '-') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif

        <!-- 6. NILAI SISWA PER HARI (PENILAIAN HARIAN MAPEL) -->
        @if($activeTab === 'semua' || $activeTab === 'nilai')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg">📝</span>
                        6. Nilai Penilaian Harian (PH / UH / Tugas)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Hasil asesmen harian mata pelajaran beserta feedback umpan balik guru.</p>
                </div>
            </div>

            @php
                $gradesToday = $this->dailyGradesToday;
                $recentGrades = $this->recentGrades;
            @endphp

            @if($gradesToday->isNotEmpty())
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Nilai Penilaian Hari Ini:</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($gradesToday as $g)
                            <div class="bg-gradient-to-br from-indigo-50/60 to-purple-50/60 rounded-xl p-4 border border-indigo-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-indigo-800 uppercase">{{ $g->type ?: 'Penilaian Harian' }}</span>
                                    <span class="text-2xl font-black text-indigo-900">{{ $g->score }}</span>
                                </div>
                                <div class="font-extrabold text-sm text-gray-900 mt-1">{{ $g->subject?->name ?? 'Mapel' }}</div>
                                @if($g->feedback)
                                    <p class="text-xs text-gray-600 mt-2 bg-white/70 p-2 rounded-lg">
                                        Feedback Guru: "{{ $g->feedback }}"
                                    </p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-2">Pengajar: {{ $g->teacher?->name ?? '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($recentGrades->isNotEmpty())
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Daftar Penilaian Harian Terbaru:</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-indigo-50/60 text-indigo-900 border-b border-indigo-100 font-bold">
                                    <th class="px-4 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Mata Pelajaran</th>
                                    <th class="px-4 py-2.5">Jenis Penilaian</th>
                                    <th class="px-4 py-2.5 text-center">Nilai</th>
                                    <th class="px-4 py-2.5">Guru</th>
                                    <th class="px-4 py-2.5">Catatan / Umpan Balik</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentGrades as $rg)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($rg->tanggal)->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="px-4 py-2.5 font-bold text-gray-900 whitespace-nowrap">
                                            {{ $rg->subject?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 uppercase text-xs font-bold whitespace-nowrap">
                                            {{ $rg->type ?: 'PH' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full font-black text-xs {{ $rg->score >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $rg->score }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">
                                            {{ $rg->teacher?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-700">
                                            {{ $rg->feedback ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-xs text-gray-500">
                    Belum ada rekaman nilai harian untuk ananda pada sistem.
                </div>
            @endif
        </div>
        @endif

        <!-- 7. CAPAIAN PENILAIAN AL-QUR'AN -->
        @if($activeTab === 'semua' || $activeTab === 'quran')
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 bg-amber-100 text-amber-700 rounded-lg">📖</span>
                        7. Capaian Pembelajaran Al-Qur'an (Tahsin & Tahfidz)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pantauan hafalan baru (Ziyadah), pengulangan hafalan (Muraja'ah), dan mutu bacaan tajwid/makhraj.</p>
                </div>
            </div>

            @php
                $qTodayList = $this->quranAchievementsToday;
                $qRecentList = $this->recentQuranAchievements;
            @endphp

            @if($qTodayList->isNotEmpty())
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Setoran Al-Qur'an Hari Ini:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($qTodayList as $qa)
                            <div class="bg-gradient-to-br from-amber-50/70 to-yellow-50/50 rounded-xl p-5 border border-amber-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-200 text-amber-900 uppercase">
                                        Program: {{ ucfirst($qa->program) }}
                                    </span>
                                    <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-emerald-100 text-emerald-800">
                                        {{ $qa->predikat ?: 'Mumtaz' }}
                                    </span>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="text-sm">
                                        <span class="text-gray-500 text-xs font-bold">Setoran Ziyadah:</span>
                                        <span class="font-extrabold text-gray-900">{{ $qa->surah ?: '-' }} (Ayat: {{ $qa->ayah ?: '-' }})</span>
                                        @if($qa->juz) <span class="text-xs text-amber-800 font-bold">• Juz {{ $qa->juz }}</span> @endif
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500 text-xs font-bold">Setoran Muraja'ah:</span>
                                        <span class="font-bold text-gray-800">{{ $qa->surah_murajaah ?: '-' }} (Ayat: {{ $qa->ayah_murajaah ?: '-' }})</span>
                                    </div>
                                </div>
                                @if($qa->notes)
                                    <div class="bg-white/80 p-2.5 rounded-lg border border-amber-100 text-xs text-gray-700">
                                        <span class="font-bold block text-amber-900">Catatan Tajwid / Guru:</span>
                                        {{ $qa->notes }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-gray-500">
                                    Guru Pengampu: <strong>{{ $qa->teacher?->name ?? 'Guru Al-Qur\'an' }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($qRecentList->isNotEmpty())
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Riwayat Setoran Al-Qur'an Terakhir:</h4>
                    <div class="space-y-3">
                        @foreach($qRecentList as $rqa)
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-900 text-sm">{{ $rqa->surah ?: 'Ziyadah' }} (Ayat {{ $rqa->ayah ?: '-' }})</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px] uppercase">{{ $rqa->program }}</span>
                                    </div>
                                    <div class="text-gray-500 mt-1">
                                        Muraja'ah: <strong>{{ $rqa->surah_murajaah ?: '-' }}</strong> • Guru: {{ $rqa->teacher?->name ?? 'Guru Al-Qur\'an' }}
                                    </div>
                                    @if($rqa->notes)
                                        <p class="text-gray-600 mt-1 italic">"{{ $rqa->notes }}"</p>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 block">
                                        {{ $rqa->predikat ?: 'Mumtaz' }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 mt-1 block">
                                        {{ \Carbon\Carbon::parse($rqa->tanggal)->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-xs text-gray-500">
                    Belum ada catatan setoran Al-Qur'an tersimpan untuk ananda.
                </div>
            @endif
        </div>
        @endif

        <!-- CHECKLIST AMALIYAH RUMAH & E-RAPOR -->
        @if($activeTab === 'semua' || $activeTab === 'amaliyah')
        <div class="space-y-6">
            <!-- E-Rapor Section -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>📑</span> E-Rapor Akademik Ananda
                    </h3>
                </div>
                @if($activeStudent->is_administrasi_lunas)
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h4 class="text-sm font-bold text-emerald-900">Administrasi Sekolah Lunas</h4>
                            <p class="text-xs text-emerald-700 mt-0.5">Anda dapat mengunduh dokumen E-Rapor hasil belajar ananda.</p>
                        </div>
                        <a href="/portal/rapor/{{ $activeStudent->id }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-bold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 transition">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh E-Rapor PDF
                        </a>
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-3">
                        <div class="text-xl">ℹ️</div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-900">Akses E-Rapor Memerlukan Penyelesaian Administrasi</h4>
                            <p class="text-xs text-amber-700 mt-0.5">Silakan menyelesaikan administrasi sekolah ananda atau menghubungi bagian Tata Usaha / Keuangan sekolah untuk mengaktifkan akses unduh E-Rapor.</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Checklists Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kegiatan Pagi -->
                <div class="bg-white shadow-sm rounded-2xl p-6 sm:p-7 border border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <span class="p-1.5 bg-amber-100 text-amber-700 rounded-lg">☀️</span>
                        Checklist Amaliyah Pagi (Di Rumah)
                    </h3>
                    @if (session()->has('message_morning'))
                        <div class="px-3 py-2 mb-4 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg">
                            ✓ {{ session('message_morning') }}
                        </div>
                    @endif
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:border-amber-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Sholat Subuh Tepat Waktu</span>
                            <input type="checkbox" wire:model="morning_sholat_subuh" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:border-amber-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Merapikan Tempat Tidur Mandiri</span>
                            <input type="checkbox" wire:model="morning_merapikan_tempat_tidur" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:border-amber-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Meminta Doa Restu Orang Tua Sebelum Berangkat</span>
                            <input type="checkbox" wire:model="morning_meminta_doa" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <div class="pt-2">
                            <button wire:click="saveMorning" class="w-full bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition">
                                Simpan Amaliyah Pagi (+Poin Karakter)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kegiatan Sore/Malam -->
                <div class="bg-white shadow-sm rounded-2xl p-6 sm:p-7 border border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <span class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg">🌙</span>
                        Checklist Amaliyah Sore & Malam (Di Rumah)
                    </h3>
                    @if (session()->has('message_evening'))
                        <div class="px-3 py-2 mb-4 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg">
                            ✓ {{ session('message_evening') }}
                        </div>
                    @endif
                    <div class="space-y-2.5">
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Sholat Maghrib</span>
                            <input type="checkbox" wire:model="evening_sholat_maghrib" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Sholat Isya'</span>
                            <input type="checkbox" wire:model="evening_sholat_isya" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Muraja'ah Hafalan Al-Qur'an</span>
                            <input type="checkbox" wire:model="evening_murajaah" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Mengaji / Membaca Al-Qur'an</span>
                            <input type="checkbox" wire:model="evening_mengaji" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Membantu Orang Tua di Rumah</span>
                            <input type="checkbox" wire:model="evening_membantu_ortu" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <label class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 bg-gray-50/50 cursor-pointer transition">
                            <span class="text-xs sm:text-sm font-semibold text-gray-800">Persiapan Peralatan Sekolah Esok Hari</span>
                            <input type="checkbox" wire:model="evening_persiapan_sekolah" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        </label>
                        <div class="pt-2">
                            <button wire:click="saveEvening" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition">
                                Simpan Amaliyah Malam (+Poin Karakter)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

</div>
