<x-filament-panels::page>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; }
        
        /* Robust Header Gradient */
        .bmos-dashboard-header { background: linear-gradient(135deg, #FF8200 0%, #D84315 100%); }
    </style>

    @if($this->upcomingSchedules->count() > 0)
        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50/90 dark:bg-amber-950/40 dark:border-amber-700/80 p-5 shadow-sm">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-amber-200/80 dark:border-amber-800/60">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-amber-500 text-white shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-amber-900 dark:text-amber-200 flex items-center gap-2">
                            <span>Informasi Jadwal Supervisi & UKG Guru</span>
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100">
                                {{ $this->upcomingSchedules->count() }} Agenda
                            </span>
                        </h3>
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            Agenda jadwal supervisi pembelajaran & uji kompetensi guru yang ditugaskan khusus untuk Anda.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($this->upcomingSchedules as $item)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-white/90 dark:bg-gray-900/80 border border-amber-200 dark:border-amber-800/50 shadow-xs">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($item['type'] === 'supervisi')
                                    <span class="px-2 py-0.5 text-[11px] font-bold rounded bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
                                        Supervisi Guru
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[11px] font-bold rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 border border-indigo-300 dark:border-indigo-700">
                                        Uji Kompetensi (UKG)
                                    </span>
                                @endif

                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                    {{ $item['teacher_name'] }}
                                </span>

                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    &bull; Penilai: <strong>{{ $item['assessor_name'] }}</strong>
                                </span>

                                <span class="text-xs font-semibold text-amber-900 dark:text-amber-300">
                                    &bull; Jadwal: <strong>{{ $item['jadwal_formatted'] }}</strong>
                                </span>
                            </div>

                            @if($item['catatan'])
                                <div class="text-xs text-gray-600 dark:text-gray-300 italic pl-1">
                                    Catatan: "{{ $item['catatan'] }}"
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
                            @if(!empty($item['detail_url']))
                                <a href="{{ $item['detail_url'] }}" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold transition-colors">
                                    Detail &rarr;
                                </a>
                            @endif

                            <button 
                                type="button" 
                                wire:click="dismissSchedule('{{ $item['type'] }}', {{ $item['id'] }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer"
                                title="Tandai telah selesai dilaksanakan dan hilangkan notifikasi"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Selesai Dilaksanakan</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($this->pillarsData) > 0 || auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']))
    <div class="space-y-8" wire:poll.60s>

                        {{-- 1. HEADER & LEGEND CARD (with AlpineJS state for expanding summary) --}}
        <div x-data="{ showSummary: false }" class="relative overflow-hidden rounded-3xl bmos-dashboard-header p-8 text-white shadow-xl">
            <!-- Decorative SVG -->
            <div class="absolute -right-20 -top-20 opacity-20 pointer-events-none">
                <svg width="300" height="300" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="10"/><circle cx="50" cy="50" r="20" fill="currentColor"/></svg>
            </div>
            <div class="absolute -left-10 -bottom-10 opacity-10 pointer-events-none">
                <svg width="200" height="200" viewBox="0 0 100 100"><rect width="80" height="80" x="10" y="10" fill="currentColor" rx="20" transform="rotate(45 50 50)"/></svg>
            </div>
            
            <div class="relative z-10 flex flex-col lg:flex-row gap-8 justify-between">
                <div class="flex-1">
                    @php
                        $hour = now()->format('H');
                        if ($hour >= 0 && $hour < 11) {
                            $waktu = 'Pagi';
                        } elseif ($hour >= 11 && $hour < 15) {
                            $waktu = 'Siang';
                        } elseif ($hour >= 15 && $hour < 18) {
                            $waktu = 'Sore';
                        } else {
                            $waktu = 'Malam';
                        }
                        
                        $displayName = auth()->user()->name;
                        if (($displayName === 'Guru Piket' || auth()->user()->username === 'gurupiket') && auth()->user()->hasRole('waka_kurikulum')) {
                            $displayName = 'Siti Aminah, M.Pd';
                        }
                    @endphp
                    
                    <div class="flex items-center gap-5 mb-6">
                        <img src="{{ filament()->getUserAvatarUrl(auth()->user()) }}" alt="Avatar" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border-4 border-white/30 shadow-md shrink-0" />
                        <div>
                            <div class="text-lg font-bold mb-1 uppercase tracking-widest drop-shadow-md" style="color: #F4EEDC;">Dashboard Utama</div>
                            <h2 class="text-3xl font-bold text-white drop-shadow-md leading-tight">Selamat {{ $waktu }}, {{ $displayName }}!</h2>

                            {{-- STATUS PIKET HARI INI & ROLE BADGES --}}
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if(auth()->user()->isPiketToday() && !auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']))
                                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-amber-400 text-amber-950 shadow-md">
                                        🛡️ Guru Piket Hari Ini
                                    </span>
                                @endif
                                @if(auth()->user()->username === 'petugas_piket' && !auth()->user()->hasAnyRole(['waka_kurikulum', 'guru_mapel', 'kepala_sekolah', 'superadmin']))
                                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-amber-400 text-amber-950 shadow-md">
                                        🏫 Akun Sentral Piket (Inval)
                                    </span>
                                @endif
                                @foreach(auth()->user()->roles->take(3) as $r)
                                    @if($r->name !== 'guru_piket')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-white/20 text-white backdrop-blur-sm">
                                            {{ ucwords(str_replace('_', ' ', $r->name)) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- HERO BANNER PENUGASAN GURU PIKET HARI INI --}}
                    @if(auth()->user()->isPiketToday() && !auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']))
                        <div class="mb-6 p-4 bg-black/25 backdrop-blur-md border border-amber-300/40 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-white shadow-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-amber-400 text-amber-950 rounded-xl font-bold text-xl shadow shrink-0">
                                    🛡️
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black uppercase tracking-wider text-amber-300">Penugasan Piket Aktif</span>
                                        <span class="text-xs text-white/80">&bull; {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-white mt-0.5">
                                        Anda bertugas sebagai <u>Guru Piket</u> hari ini. Semua menu monitoring kesiswaan dan presensi telah aktif di sidebar.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-stretch sm:self-auto shrink-0">
                                <a href="{{ \App\Filament\Staff\Pages\PiketDashboard::getUrl() }}" class="flex-1 sm:flex-initial text-center px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-amber-950 text-xs font-black shadow transition-all">
                                    Buka Dasbor Piket &rarr;
                                </a>
                                @if(class_exists(\App\Filament\Staff\Pages\AbsensiManual::class))
                                    <a href="{{ \App\Filament\Staff\Pages\AbsensiManual::getUrl() }}" class="px-3 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-all">
                                        Presensi Cepat
                                    </a>
                                @endif
                            </div>
                        </div>
                    @elseif(auth()->user()->username === 'petugas_piket' && !auth()->user()->hasAnyRole(['waka_kurikulum', 'guru_mapel', 'kepala_sekolah', 'superadmin']))
                        @php
                            $todayPiketTeachers = \App\Models\DutySchedule::with('teacher')
                                ->whereDate('tanggal', today())
                                ->get()
                                ->pluck('teacher.name')
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        <div class="mb-6 p-4 bg-black/25 backdrop-blur-md border border-amber-300/40 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-white shadow-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-amber-400 text-amber-950 rounded-xl font-bold text-xl shadow shrink-0">
                                    🏫
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black uppercase tracking-wider text-amber-300">Pos / Sentral Piket Bersama</span>
                                        <span class="text-xs text-white/80">&bull; {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                                    </div>
                                    <p class="text-sm font-semibold text-white mt-0.5">
                                        @if($todayPiketTeachers->isNotEmpty())
                                            Guru yang bertugas piket hari ini: <strong class="text-amber-200">{{ $todayPiketTeachers->implode(', ') }}</strong>
                                        @else
                                            Jadwal piket guru untuk hari ini belum digenerate oleh Waka Kesiswaan.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-stretch sm:self-auto shrink-0">
                                <a href="{{ \App\Filament\Staff\Pages\PiketDashboard::getUrl() }}" class="flex-1 sm:flex-initial text-center px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-amber-950 text-xs font-black shadow transition-all">
                                    Buka Dasbor Piket &rarr;
                                </a>
                                <a href="{{ \App\Filament\Staff\Resources\DutyScheduleResource::getUrl() }}" class="px-3 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-all">
                                    Jadwal 1 Semester
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    {{-- TOTAL SISWA & GURU (Inside Terracotta) --}}
                    <div class="flex flex-col sm:flex-row gap-4 mb-6">
                        <a href="{{ \App\Filament\Staff\Resources\StudentResource::getUrl() }}" class="bg-black/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex items-center gap-4 flex-1 shadow-sm hover:bg-black/20 transition-all cursor-pointer group">
                            <div class="p-3 rounded-full shadow-md bg-white text-[#D84315] group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wider font-extrabold text-white/80">Total Siswa Aktif</p>
                                <p class="text-3xl font-black text-white drop-shadow-sm">{{ $this->studentCount }}</p>
                            </div>
                        </a>
                        
                        <a href="{{ class_exists(\App\Filament\Staff\Resources\TeacherProfileResource::class) ? \App\Filament\Staff\Resources\TeacherProfileResource::getUrl() : '#' }}" class="bg-black/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex items-center gap-4 flex-1 shadow-sm hover:bg-black/20 transition-all cursor-pointer group">
                            <div class="p-3 rounded-full shadow-md bg-white text-[#D84315] group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wider font-extrabold text-white/80">Total Guru Pengajar</p>
                                <p class="text-3xl font-black text-white drop-shadow-sm">{{ $this->teacherCount }}</p>
                            </div>
                        </a>

                        @if(auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']))
                            <a href="{{ \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl() }}" class="bg-black/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex items-center gap-4 flex-1 shadow-sm hover:bg-black/20 transition-all cursor-pointer group">
                                <div class="p-3 rounded-full shadow-md bg-white text-[#D84315] group-hover:scale-110 transition-transform flex items-center justify-center w-13 h-13">
                                    <span class="text-2xl">🎓</span>
                                </div>
                                <div>
                                    <p class="text-[11px] uppercase tracking-wider font-extrabold text-white/80">Profil Lulusan</p>
                                    <p class="text-xs font-bold text-amber-200 drop-shadow-sm flex items-center gap-1">Evaluasi 3 Aspek <span>&rarr;</span></p>
                                </div>
                            </a>
                        @endif
                    </div>

                    {{-- HIGHLIGHT PENGUMUMAN YAYASAN --}}
                    <a href="{{ class_exists(\App\Filament\Staff\Resources\NewsResource::class) ? \App\Filament\Staff\Resources\NewsResource::getUrl() : '#' }}" class="block bg-black/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 shadow-sm hover:bg-black/20 transition-all cursor-pointer group">
                        <div class="flex items-start gap-4">
                            <div class="p-3 rounded-full bg-white text-[#D84315] shadow-md group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-xs mb-2 uppercase tracking-wider text-white/90 drop-shadow-md flex items-center gap-2">
                                    Info & Pengumuman Terbaru 
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </p>
                                @if(trim(strip_tags(str()->markdown($this->latestNews))) === '')
                                    <p class="text-sm text-white/80 font-medium italic">Tidak ada pengumuman terbaru.</p>
                                @else
                                    <div class="text-sm text-white font-bold leading-relaxed max-w-none [&>ul]:list-disc [&>ul]:ml-4 [&>p]:mb-1 last:[&>p]:mb-0">
                                        {!! str()->markdown($this->latestNews) !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>

                <!-- OVERALL STATS (ENLARGED & CLICKABLE) -->
                @php
                    $allTasks = collect($this->pillarsData)->pluck('tasks')->flatten();
                    $totalSelesai = collect($this->pillarsData)->sum('selesai');
                    $totalSemua = collect($this->pillarsData)->sum('total');
                    $percentageOverall = $totalSemua > 0 ? round(($totalSelesai / $totalSemua) * 100) : 0;
                    
                    $radius = 65;
                    $circumference = 2 * pi() * $radius;
                    $offset = $circumference - ($percentageOverall / 100) * $circumference;
                @endphp
                <div class="flex flex-col items-center justify-start self-start md:sticky md:top-8" style="min-width: 320px;">
                    <!-- The Clickable Indicator -->
                    <div @click="showSummary = !showSummary" class="flex flex-col items-center justify-center bg-black/10 hover:bg-black/20 transition-all cursor-pointer backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl w-full group">
                        <p class="text-xl font-black mb-4 tracking-wider uppercase text-center drop-shadow-md text-white/90 group-hover:text-white transition-colors">
                            Ketercapaian<br>Global
                        </p>
                        <div class="relative w-48 h-48 flex items-center justify-center drop-shadow-2xl my-2 group-hover:scale-105 transition-transform">
                            <!-- Background Circle -->
                            <svg class="absolute inset-0 w-full h-full transform -rotate-90" viewBox="0 0 160 160">
                                <circle cx="80" cy="80" r="65" stroke="rgba(255,255,255,0.1)" stroke-width="14" fill="none" />
                                <!-- Progress Circle -->
                                <circle cx="80" cy="80" r="65" stroke="#ffffff" stroke-width="14" fill="none" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" class="transition-all duration-1000 ease-out drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]" />
                            </svg>
                            <span class="text-6xl font-black text-white drop-shadow-lg">{{ $percentageOverall }}%</span>
                        </div>
                        <p class="mt-6 text-sm font-bold bg-white text-[#D84315] px-6 py-2.5 rounded-full shadow-md uppercase tracking-wider">{{ $totalSelesai }} / {{ $totalSemua }} Tugas Tuntas</p>
                        
                        <div class="mt-4 text-xs font-bold text-white/80 hover:text-white transition-colors flex items-center gap-1">
                            Klik untuk detail AI 
                            <svg class="w-3 h-3 transform transition-transform" :class="showSummary ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    {{-- HIDDEN AI SUMMARY SECTION (Tied to the indicator) --}}
                    <div x-show="showSummary" x-collapse x-cloak class="mt-4 w-full">
                        <div class="bg-black/30 backdrop-blur-md rounded-2xl p-5 border border-white/10 shadow-inner">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="p-1.5 rounded-lg shadow-sm" style="background-color: #ffbb88; color: #34332E;">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg>
                                </div>
                                <p class="font-bold text-xs uppercase tracking-wider drop-shadow-sm" style="color: #ffbb88;">Analisis AI BM-OS</p>
                            </div>
                            <div class="text-xs text-white leading-relaxed font-semibold max-w-none [&>ul]:list-disc [&>ul]:ml-4 [&>p]:mb-2 last:[&>p]:mb-0">
                                {!! str()->markdown($this->aiSummary) !!}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @if(auth()->user()->hasRole('yayasan'))
            <div class="mb-6">
                @livewire(\App\Filament\Yayasan\Widgets\InstitutionOverviewWidget::class)
            </div>
        @endif

        {{-- WIDGETS STATISTIK (Good News, Bad News, Top Guru) --}}
        @php
            $canAccessRanking = \App\Filament\Staff\Pages\TeacherPerformanceDashboard::canAccess();
        @endphp
        <div x-data="{ activeNews: null }" class="space-y-4">
            <div class="grid grid-cols-1 {{ $canAccessRanking ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-4">
                {{-- Compact Button Good News --}}
                <button type="button"
                        @click="activeNews = (activeNews === 'good' ? null : 'good')"
                        class="w-full bg-gradient-to-r from-emerald-500 to-green-600 rounded-2xl p-4 shadow-sm hover:shadow-md border border-green-400/50 text-white flex items-center justify-between transition-all group cursor-pointer hover:-translate-y-0.5"
                        :class="activeNews === 'good' ? 'ring-2 ring-emerald-300 ring-offset-2 dark:ring-offset-gray-900 shadow-md' : ''">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="text-left">
                            <span class="text-base font-black tracking-wide drop-shadow-sm block">Good News</span>
                            <span class="text-[11px] text-green-100 font-medium" x-text="activeNews === 'good' ? 'Klik untuk menutup rangkuman' : 'Klik untuk melihat rangkuman'"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold bg-white/20 group-hover:bg-white/30 px-3 py-1.5 rounded-xl transition-colors">
                        <span x-text="activeNews === 'good' ? 'Tutup' : 'Lihat'"></span>
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="activeNews === 'good' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </button>

                {{-- Compact Button Bad News --}}
                <button type="button"
                        @click="activeNews = (activeNews === 'bad' ? null : 'bad')"
                        class="w-full bg-gradient-to-r from-rose-500 to-red-600 rounded-2xl p-4 shadow-sm hover:shadow-md border border-red-400/50 text-white flex items-center justify-between transition-all group cursor-pointer hover:-translate-y-0.5"
                        :class="activeNews === 'bad' ? 'ring-2 ring-rose-300 ring-offset-2 dark:ring-offset-gray-900 shadow-md' : ''">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="text-left">
                            <span class="text-base font-black tracking-wide drop-shadow-sm block">Bad News</span>
                            <span class="text-[11px] text-red-100 font-medium" x-text="activeNews === 'bad' ? 'Klik untuk menutup rangkuman' : 'Klik untuk melihat rangkuman'"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold bg-white/20 group-hover:bg-white/30 px-3 py-1.5 rounded-xl transition-colors">
                        <span x-text="activeNews === 'bad' ? 'Tutup' : 'Lihat'"></span>
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="activeNews === 'bad' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </button>

                @if($canAccessRanking)
                {{-- Widget Highlight Peringkat 1 --}}
                @php
                    $top = collect($this->topTeachers)->first();
                @endphp
                <a href="{{ \App\Filament\Staff\Pages\TeacherPerformanceDashboard::getUrl() }}" class="w-full bg-gradient-to-r from-yellow-400 to-amber-500 rounded-2xl p-4 shadow-sm hover:shadow-md border border-yellow-300 flex items-center justify-between group hover:-translate-y-0.5 transition-all cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-white/30 flex items-center justify-center shrink-0 border border-white/40">
                            <span class="text-base">👑</span>
                        </div>
                        <div class="min-w-0 text-left">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-black uppercase text-yellow-900 tracking-wider">Top 1 Kinerja Guru</span>
                            </div>
                            <h4 class="text-sm font-black text-yellow-950 truncate leading-tight">
                                {{ $top ? $top->teacher->name : 'Belum Ada Data' }}
                            </h4>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-xs font-bold text-yellow-950 bg-white/30 group-hover:bg-white/50 px-3 py-1.5 rounded-xl shrink-0 transition-colors">
                        <span>Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </a>
                @endif
            </div>

            {{-- PANEL PENJELASAN GOOD NEWS (Muncul saat diklik) --}}
            <div x-show="activeNews === 'good'" x-collapse x-cloak>
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-6 shadow-xl border border-green-400/50 text-white relative">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black tracking-wide drop-shadow-sm">Ringkasan & Penjelasan Good News</h3>
                                <p class="text-xs text-green-100 font-medium">Kabar baik, pencapaian positif, dan tren performa sekolah</p>
                            </div>
                        </div>
                        <button type="button" @click="activeNews = null" class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer">
                            <span>Tutup</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="text-sm leading-relaxed text-green-50 font-medium max-w-none [&>ul]:list-disc [&>ul]:ml-4 [&>p]:mb-3 last:[&>p]:mb-0">
                        {!! str()->markdown($this->goodNews) !!}
                    </div>
                </div>
            </div>

            {{-- PANEL PENJELASAN BAD NEWS (Muncul saat diklik) --}}
            <div x-show="activeNews === 'bad'" x-collapse x-cloak>
                <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-6 shadow-xl border border-red-400/50 text-white relative">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black tracking-wide drop-shadow-sm">Ringkasan & Penjelasan Bad News</h3>
                                <p class="text-xs text-red-100 font-medium">Perhatian khusus, kendala operasional, dan hal yang butuh tindak lanjut</p>
                            </div>
                        </div>
                        <button type="button" @click="activeNews = null" class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer">
                            <span>Tutup</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="text-sm leading-relaxed text-red-50 font-medium max-w-none [&>ul]:list-disc [&>ul]:ml-4 [&>p]:mb-3 last:[&>p]:mb-0">
                        {!! str()->markdown($this->badNews) !!}
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. GRID KARTU PER PILAR DENGAN GRAFIK BERWARNA --}}
        @php
            $palettes = [
                1 => ['base' => '#8B5CF6', 'light' => '#EDE9FE', 'name' => 'violet'],
                2 => ['base' => '#EC4899', 'light' => '#FCE7F3', 'name' => 'pink'],
                3 => ['base' => '#0EA5E9', 'light' => '#E0F2FE', 'name' => 'sky'],
                4 => ['base' => '#10B981', 'light' => '#D1FAE5', 'name' => 'emerald'],
                5 => ['base' => '#F59E0B', 'light' => '#FEF3C7', 'name' => 'amber'],
                6 => ['base' => '#F97316', 'light' => '#FFEDD5', 'name' => 'orange'],
                7 => ['base' => '#F43F5E', 'light' => '#FFE4E6', 'name' => 'rose'],
                8 => ['base' => '#6366F1', 'light' => '#E0E7FF', 'name' => 'indigo'],
                9 => ['base' => '#14B8A6', 'light' => '#CCFBF1', 'name' => 'teal'],
            ];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            @foreach($this->pillarsData as $pillar)
                @php
                    $tasks = $pillar['tasks'];
                    $selesai = $pillar['selesai'];
                    $total = $pillar['total'];
                    $percentage = $pillar['percentage'];
                    
                    $circ = 2 * pi() * 32;
                    $dashoffset = $circ - ($percentage / 100) * $circ;
                    
                    $color = $palettes[$pillar['order'] % 10] ?? $palettes[1];
                    $baseHex = $color['base'];
                    $lightHex = $color['light'];
                    
                    if ($percentage == 100) {
                        $baseHex = '#10B981'; // Emerald for 100%
                        $lightHex = '#D1FAE5'; 
                    }

                    $icons = [
                        1 => 'heroicon-o-briefcase',
                        2 => 'heroicon-o-academic-cap',
                        3 => 'heroicon-o-book-open',
                        4 => 'heroicon-o-users',
                        5 => 'heroicon-o-user-group',
                        6 => 'heroicon-o-building-office-2',
                        7 => 'heroicon-o-banknotes',
                        8 => 'heroicon-o-megaphone',
                        9 => 'heroicon-o-squares-plus',
                    ];
                    $pillarIcon = $icons[$pillar['order'] % 10] ?? 'heroicon-o-check-circle';
                @endphp
                
                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg border-2 border-transparent flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl group" 
                     x-data="{ expanded: false }"
                     x-on:mouseenter="$el.style.borderColor = '{{ $baseHex }}'"
                     x-on:mouseleave="$el.style.borderColor = 'transparent'">
                    
                    <div class="flex-1 p-6 cursor-pointer flex gap-5 items-center relative overflow-hidden" @click="expanded = !expanded" style="background: linear-gradient(135deg, white 0%, {{$lightHex}} 100%);">
                        <div class="absolute -right-6 -bottom-6 opacity-10">
                            <svg class="w-32 h-32" fill="{{$baseHex}}" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg>
                        </div>
                        
                        <div class="relative w-24 h-24 shrink-0 flex items-center justify-center bg-white rounded-full shadow-md z-10">
                            <svg class="w-full h-full transform -rotate-90 p-1">
                                <circle cx="40" cy="40" r="32" stroke="{{$lightHex}}" stroke-width="8" fill="none" />
                                <circle cx="40" cy="40" r="32" stroke="{{$baseHex}}" stroke-width="8" fill="none" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $dashoffset }}" stroke-linecap="round" class="transition-all duration-1000 ease-out" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-black" style="color: {{$baseHex}}">{{ $percentage }}%</span>
                        </div>
                        <div class="flex-1 z-10">
                            <div class="mb-2">
                                <x-dynamic-component :component="$pillarIcon" class="w-8 h-8 drop-shadow-sm" style="color: {{$baseHex}}" />
                            </div>
                            <h3 class="font-extrabold text-gray-900 leading-tight text-lg mb-1">{{ $pillar['name'] }}</h3>
                            <p class="text-sm font-semibold text-gray-600">{{ $selesai }} dari {{ $total }} tugas tuntas</p>
                        </div>
                        <div class="shrink-0 z-10">
                            <button class="w-10 h-10 rounded-full bg-white shadow flex items-center justify-center text-gray-400 group-hover:text-gray-700 transition-colors">
                                <svg class="w-6 h-6 transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="expanded" x-collapse x-cloak>
                        <div class="p-4 bg-gray-50/50 dark:bg-gray-800/20">
                            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($tasks as $task)
                                    @if(isset($task->url))
                                    <a href="{{ $task->url }}" class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow transition-shadow relative overflow-hidden hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    @else
                                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow transition-shadow relative overflow-hidden">
                                    @endif
                                        
                                        @if($task->status === 'selesai')
                                            <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: {{$baseHex}}"></div>
                                        @endif

                                        <div class="shrink-0 mt-0.5">
                                            @if($task->status === 'selesai')
                                                <div class="focus:outline-none cursor-default" style="color: {{$baseHex}}">
                                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                </div>
                                            @else
                                                <div class="text-gray-300 dark:text-gray-600 focus:outline-none cursor-default">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2.5"></circle></svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold leading-snug {{ $task->status === 'selesai' ? 'text-gray-400 line-through' : 'text-gray-800 dark:text-gray-200' }}">
                                                <span class="text-[10px] uppercase tracking-wider bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded mr-1.5 font-extrabold">{{ $task->code }}</span>
                                                {{ $task->name }}
                                            </p>
                                            
                                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $task->desc ?? 'Tugas Standar SOP Yayasan' }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap gap-2 items-center">
                                                @if($task->assigned_role)
                                                    @foreach(explode(', ', $task->assigned_role) as $roleBadge)
                                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-bold bg-white border shadow-sm" style="color: {{$baseHex}}; border-color: {{$lightHex}}">
                                                            {{ trim($roleBadge) }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                                
                                                @if($task->is_sop_merah)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-red-100 text-red-700 border border-red-200 shadow-sm animate-pulse">
                                                        SOP KHUSUS
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                    @if(isset($task->url))
                                    </a>
                                    @else
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
        
    </div>
    @endif

    @if(auth()->user()->hasAnyRole(['guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas']))
        <div class="mt-8">
            <x-filament-widgets::widgets
                :columns="$this->getColumns()"
                :data="$this->getWidgetData()"
                :widgets="$this->getVisibleWidgets()"
            />
        </div>
    @endif
</x-filament-panels::page>

