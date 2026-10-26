<x-filament-panels::page>
    <style>
        [x-cloak] { display: none !important; }
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
                    <h2 class="text-3xl font-black mb-2 drop-shadow-md" style="color: #FCFBF8;">9 Pilar Manajemen Baitul Makmur</h2>
                    <p class="mb-0 font-medium" style="color: #F4EEDC;">Monitoring progres dan ketercapaian target manajemen sekolah secara real-time.</p>
                </div>

                <!-- OVERALL STATS -->
                @php
                    $allTasks = collect($this->pillars)->pluck('tasks')->flatten();
                    $totalSelesai = $allTasks->where('status', 'selesai')->count();
                    $totalSemua = $allTasks->count();
                    $percentageOverall = $totalSemua > 0 ? round(($totalSelesai / $totalSemua) * 100) : 0;
                    
                    // SVG Circle Math
                    $circumference = 2 * pi() * 45;
                    $offset = $circumference - ($percentageOverall / 100) * $circumference;
                @endphp
                <div class="flex flex-col items-center justify-center bg-black/10 hover:bg-black/20 transition-colors backdrop-blur-xl p-6 rounded-3xl border border-white/10 shadow-2xl group" style="min-width: 200px;">
                    <p class="text-sm font-black mb-2 tracking-wider uppercase text-white/90 group-hover:text-white transition-colors">Ketercapaian Global</p>
                    <div class="relative w-32 h-32 flex items-center justify-center drop-shadow-xl group-hover:scale-105 transition-transform">
                        <!-- Background Circle -->
                        <svg class="absolute inset-0 w-full h-full transform -rotate-90">
                            <circle cx="64" cy="64" r="45" stroke="rgba(255,255,255,0.1)" stroke-width="12" fill="none" />
                            <!-- Progress Circle -->
                            <circle cx="64" cy="64" r="45" stroke="#ffffff" stroke-width="12" fill="none" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" class="transition-all duration-1000 ease-out drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]" />
                        </svg>
                        <span class="text-4xl font-black text-white">{{ $percentageOverall }}%</span>
                    </div>
                    <p class="mt-4 text-xs font-bold bg-white text-[#D84315] px-4 py-1.5 rounded-full uppercase tracking-wider">{{ $totalSelesai }} / {{ $totalSemua }} Tugas</p>
                </div>
            </div>
        </div>

        {{-- 2. GRID KARTU PER PILAR DENGAN GRAFIK BERWARNA --}}
        @php
            // Palet Warna Hangat (Colorful Palette)
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
            @foreach($this->pillars as $pillar)
                @php
                    $tasks = $pillar->tasks;
                    $selesai = $tasks->where('status', 'selesai')->count();
                    $total = $tasks->count();
                    $percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;
                    
                    $circ = 2 * pi() * 32;
                    $dashoffset = $circ - ($percentage / 100) * $circ;
                    
                    // Ambil warna berdasarkan nomor order pilar (1-9)
                    $color = $palettes[$pillar->order % 10] ?? $palettes[1];
                    $baseHex = $color['base'];
                    $lightHex = $color['light'];
                    
                    // Jika 100%, ubah semua jadi hijau sukses cerah
                    if ($percentage == 100) {
                        $baseHex = '#10B981'; // Emerald
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
                    $pillarIcon = $icons[$pillar->order % 10] ?? 'heroicon-o-check-circle';
                @endphp
                
                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg border-2 border-transparent flex flex-col overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl group"
                     x-data="{ expanded: false }"
                     x-on:mouseenter="$el.style.borderColor = '{{ $baseHex }}'"
                     x-on:mouseleave="$el.style.borderColor = 'transparent'">
                     
                    <!-- Grafik & Judul (Header Card) -->
                    <div class="flex-1 p-6 cursor-pointer flex gap-5 items-center relative overflow-hidden" @click="expanded = !expanded" style="background: linear-gradient(135deg, white 0%, {{$lightHex}} 100%);">
                        <!-- BG Accent Icon / shape -->
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
                            <h3 class="font-extrabold text-gray-900 leading-tight text-lg mb-1">{{ $pillar->name }}</h3>
                            <p class="text-sm font-semibold text-gray-600">{{ $selesai }} dari {{ $total }} tugas tuntas</p>
                        </div>
                        <div class="shrink-0 z-10">
                            <button class="w-10 h-10 rounded-full bg-white shadow flex items-center justify-center text-gray-400 group-hover:text-gray-700 transition-colors">
                                <svg class="w-6 h-6 transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Accordion Tugas -->
                    <div x-show="expanded" x-collapse x-cloak>
                        <div class="p-4 bg-gray-50/50 dark:bg-gray-800/20">
                            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($tasks as $task)
                                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow transition-shadow relative overflow-hidden">
                                        
                                        @if($task->status === 'selesai')
                                            <!-- Garis indikator selesai -->
                                            <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: {{$baseHex}}"></div>
                                        @endif

                                        <!-- Tombol Centang -->
                                        <div class="shrink-0 mt-0.5">
                                            @if($task->status === 'selesai')
                                                <button wire:click="markTask({{ $task->id }}, 'belum')" class="focus:outline-none transition-transform hover:scale-110 active:scale-95" style="color: {{$baseHex}}">
                                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                </button>
                                            @else
                                                <button wire:click="markTask({{ $task->id }}, 'selesai')" class="text-gray-300 dark:text-gray-600 focus:outline-none transition-transform hover:scale-110 active:scale-95 hover:text-gray-400">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2.5"></circle></svg>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Detail Tugas -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold leading-snug {{ $task->status === 'selesai' ? 'text-gray-400 line-through' : 'text-gray-800 dark:text-gray-200' }}">
                                                <span class="text-[10px] uppercase tracking-wider bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded mr-1.5 font-extrabold">{{ $task->code }}</span>
                                                {{ $task->name }}
                                            </p>
                                            
                                            <div class="mt-2.5 flex flex-wrap gap-2 items-center">
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

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
        
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; }
    </style>
</x-filament-panels::page>
