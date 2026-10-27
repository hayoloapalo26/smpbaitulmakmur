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

    <div class="space-y-8" wire:poll.60s>

        {{-- 1. HEADER & LEGEND CARD --}}
        <div class="relative overflow-hidden rounded-3xl bmos-dashboard-header p-8 text-white shadow-xl">
            <!-- Decorative SVG -->
            <div class="absolute -right-20 -top-20 opacity-20">
                <svg width="300" height="300" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="10"/><circle cx="50" cy="50" r="20" fill="currentColor"/></svg>
            </div>
            <div class="absolute -left-10 -bottom-10 opacity-10">
                <svg width="200" height="200" viewBox="0 0 100 100"><rect width="80" height="80" x="10" y="10" fill="currentColor" rx="20" transform="rotate(45 50 50)"/></svg>
            </div>
            
            <div class="relative z-10 flex flex-col md:flex-row gap-8 justify-between">
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
                    @endphp
                    
                    <div class="flex items-center gap-5 mb-6">
                        <img src="{{ filament()->getUserAvatarUrl(auth()->user()) }}" alt="Avatar" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover border-4 border-white/30 shadow-md shrink-0" />
                        <div>
                            <div class="text-lg font-bold mb-1 uppercase tracking-widest drop-shadow-md" style="color: #F4EEDC;">Dashboard Utama</div>
                            <h2 class="text-3xl font-bold text-white drop-shadow-md leading-tight">Selamat {{ $waktu }}, {{ auth()->user()->name }}!</h2>
                        </div>
                    </div>
                    
                    {{-- AI SUMMARY SECTION --}}
                    <div class="bg-black/10 backdrop-blur-md rounded-2xl p-5 border border-white/10 mb-6 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="p-3 rounded-xl mt-0.5 shadow-sm bg-white text-[#D84315]">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-xs mb-2 uppercase tracking-wider drop-shadow-md text-white/90">Ringkasan Analisis AI BM-OS</p>
                                <div class="text-sm text-white leading-relaxed font-medium [&>p]:mb-3 last:[&>p]:mb-0">
                                    {!! str()->markdown($this->aiSummary) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    
                </div>

                <!-- OVERALL STATS -->
                @php
                    $allTasks = collect($this->progressData)->pluck('tasks')->flatten();
                    $totalSelesai = collect($this->progressData)->sum('selesai');
                    $totalSemua = collect($this->progressData)->sum('total');
                    $percentageOverall = $totalSemua > 0 ? round(($totalSelesai / $totalSemua) * 100) : 0;
                    
                    // SVG Circle Math
                    $circumference = 2 * pi() * 45;
                    $offset = $circumference - ($percentageOverall / 100) * $circumference;
                @endphp
                <div class="flex flex-col items-center justify-center bg-white/20 backdrop-blur-xl p-6 rounded-3xl border border-white/30 shadow-2xl self-start" style="min-width: 200px;">
                    <p class="text-sm font-bold mb-2 tracking-wide uppercase text-center" style="color: #FCFBF8;">Ketercapaian<br>Global</p>
                    <div class="relative w-32 h-32 flex items-center justify-center drop-shadow-xl mt-2">
                        <!-- Background Circle -->
                        <svg class="absolute inset-0 w-full h-full transform -rotate-90">
                            <circle cx="64" cy="64" r="45" stroke="rgba(255,255,255,0.15)" stroke-width="12" fill="none" />
                            <!-- Progress Circle -->
                            <circle cx="64" cy="64" r="45" stroke="#ffbb88" stroke-width="12" fill="none" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" class="transition-all duration-1000 ease-out drop-shadow-lg" />
                        </svg>
                        <span class="text-4xl font-black" style="color: #FCFBF8;">{{ $percentageOverall }}%</span>
                    </div>
                    <p class="mt-4 text-sm font-bold bg-black/20 px-4 py-1.5 rounded-full" style="color: #FCFBF8;">{{ $totalSelesai }} / {{ $totalSemua }} Tugas</p>
                </div>
            </div>
        </div>



        {{-- WIDGETS STATISTIK (Siswa, Guru, Top Guru) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Widget Jumlah Siswa --}}
            <div class="block bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-5 hover:shadow-lg hover:-translate-y-1 hover:border-blue-300 transition-all cursor-pointer">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Siswa Aktif</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($this->studentCount) }}</h3>
                </div>
            </div>

            {{-- Widget Jumlah Guru & Supervisi Lembaga --}}
            <a href="{{ url('/yayasan/penilaian-guru?activeTab=inst_' . $this->institution->id) }}" class="block bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-5 hover:shadow-lg hover:-translate-y-1 hover:border-emerald-300 transition-all cursor-pointer">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Guru Pengajar</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($this->teacherCount) }}</h3>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Klik untuk Daftar & Supervisi Guru &rarr;</span>
                </div>
            </a>

            {{-- Widget Highlight Peringkat 1 --}}
            <a href="{{ \App\Filament\Yayasan\Pages\TeacherPerformanceDashboard::getUrl(['institution_id' => $this->institution->id]) }}" class="block bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-3xl p-6 shadow-md border border-yellow-300 flex items-center gap-5 relative overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer">
                <div class="absolute -right-6 -top-6 opacity-20 group-hover:scale-110 transition-transform duration-500">
                    <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.55 18.55 20 18 20H6C5.45 20 5 19.55 5 19V18H19V19Z"/></svg>
                </div>
                
                @php
                    $top = collect($this->topTeachers)->first();
                @endphp
                
                @if($top)
                <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white flex items-center justify-center shrink-0 shadow-inner z-10">
                    <span class="text-xl font-black text-white">{{ substr($top->teacher->name, 0, 2) }}</span>
                </div>
                <div class="z-10">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-yellow-800 text-yellow-100 text-[10px] font-black uppercase px-2 py-0.5 rounded">Peringkat 1</span>
                        <span class="text-xs font-bold text-yellow-900">Kinerja Guru</span>
                    </div>
                    <h3 class="text-lg font-black text-yellow-900 leading-tight line-clamp-1">{{ $top->teacher->name }}</h3>
                    <p class="text-sm font-bold text-yellow-800 mt-0.5">{{ $top->tugas_selesai }} Tugas Tuntas ({{ $top->persentase }}%)</p>
                </div>
                @else
                <div class="z-10">
                    <p class="text-sm font-bold text-yellow-900 mb-1">Peringkat Kinerja Guru</p>
                    <h3 class="text-lg font-black text-yellow-900">Belum Ada Data</h3>
                </div>
                @endif
            </a>
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
            @foreach($this->progressData as $pillar)
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
                    
                    <div class="p-6 cursor-pointer flex gap-5 items-center relative overflow-hidden" @click="expanded = !expanded" style="background: linear-gradient(135deg, white 0%, {{$lightHex}} 100%);">
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
</x-filament-panels::page>



