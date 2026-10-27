<x-filament-panels::page>
    <style>
        /* BM-OS Color Palette & Custom Styles */
        .bmos-gradient-primary { background: linear-gradient(135deg, #FF8200 0%, #D84315 100%); }
        .bmos-gradient-gold { background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); }
        .bmos-gradient-teal { background: linear-gradient(135deg, #0F766E 0%, #14B8A6 100%); }
        .bmos-gradient-blue { background: linear-gradient(135deg, #D84315 0%, #FF8200 100%); }
        
        .bmos-text-primary { color: #FF8200; }
        .bmos-text-gold { color: #F59E0B; }
        
        .bmos-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .dark .bmos-card {
            background-color: #1F2937;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        /* Podium Styles */
        .podium-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            border-radius: 2rem;
            padding: 1.5rem;
            padding-top: 4rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .podium-1 { background: linear-gradient(180deg, #FEF3C7 0%, #FDE68A 100%); border: 2px solid #F59E0B; height: 320px; }
        .podium-2 { background: linear-gradient(180deg, #F3F4F6 0%, #E5E7EB 100%); border: 2px solid #9CA3AF; height: 280px; }
        .podium-3 { background: linear-gradient(180deg, #FFEDD5 0%, #FED7AA 100%); border: 2px solid #EA580C; height: 260px; }
        
        .dark .podium-1 { background: linear-gradient(180deg, #78350F 0%, #92400E 100%); border-color: #F59E0B; }
        .dark .podium-2 { background: linear-gradient(180deg, #374151 0%, #4B5563 100%); border-color: #9CA3AF; }
        .dark .podium-3 { background: linear-gradient(180deg, #7C2D12 0%, #9A3412 100%); border-color: #EA580C; }

        .rank-badge {
            position: absolute;
            top: -3rem;
            left: 50%;
            transform: translateX(-50%);
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            border: 4px solid white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }
        .rank-1-badge { background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); }
        .rank-2-badge { background: linear-gradient(135deg, #9CA3AF 0%, #4B5563 100%); }
        .rank-3-badge { background: linear-gradient(135deg, #EA580C 0%, #C2410C 100%); }

        /* Custom Avatars */
        .avatar-initial {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
            background: linear-gradient(135deg, #FF8200 0%, #D84315 100%);
        }
        
        /* Progress Bar */
        .progress-track { background-color: #E5E7EB; border-radius: 9999px; height: 0.75rem; width: 100%; overflow: hidden; }
        .dark .progress-track { background-color: #374151; }
        .progress-fill { height: 100%; border-radius: 9999px; transition: width 1s ease-in-out; }
    </style>

    <div class="space-y-8 pb-10">

        {{-- FILTER LEMBAGA YAYASAN --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-orange-100 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pilih Unit Lembaga Sekolah</h3>
                    <p class="text-xs text-gray-500">Khusus Pihak Yayasan: Pembina, Ketua Yayasan, Tim Penjamin Mutu & Sekretaris</p>
                </div>
            </div>
            <div class="w-full sm:w-auto min-w-[240px]">
                <select wire:model.live="selectedInstitutionId" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm font-semibold shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    @foreach($this->institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- HEADER CARD --}}
        <div class="bmos-gradient-primary p-8 rounded-[2rem] shadow-xl flex flex-col md:flex-row justify-between items-center gap-6 relative overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute -right-10 -bottom-10 opacity-20 transform rotate-12">
                <svg width="250" height="250" viewBox="0 0 24 24" fill="white"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
            </div>
            
            <div class="relative z-10 text-center md:text-left">
                <h2 class="text-3xl font-black text-white flex flex-col md:flex-row items-center gap-4 drop-shadow-lg mb-2">
                    <span class="bg-white/20 p-3 rounded-2xl shadow-inner backdrop-blur-md">
                        <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                    </span>
                    Ranking Kinerja Guru Yayasan
                </h2>
                <p class="font-medium text-lg max-w-xl" style="color: #F4EEDC;">Pemantauan progres dan pencapaian tugas harian guru secara real-time untuk pembinaan dan peningkatan mutu.</p>
            </div>
            
            <div class="relative z-10 w-full md:w-auto" style="min-width: 300px;">
                <x-filament::tabs label="Periode">
                    <x-filament::tabs.item wire:click="$set('activeTab', 'harian')" :active="$activeTab === 'harian'" class="bg-white/80 dark:bg-gray-800 backdrop-blur-md rounded-xl">
                        Harian
                    </x-filament::tabs.item>
                    <x-filament::tabs.item wire:click="$set('activeTab', 'mingguan')" :active="$activeTab === 'mingguan'" class="bg-white/80 dark:bg-gray-800 backdrop-blur-md rounded-xl">
                        Mingguan
                    </x-filament::tabs.item>
                    <x-filament::tabs.item wire:click="$set('activeTab', 'bulanan')" :active="$activeTab === 'bulanan'" class="bg-white/80 dark:bg-gray-800 backdrop-blur-md rounded-xl">
                        Bulanan
                    </x-filament::tabs.item>
                </x-filament::tabs>
            </div>
        </div>

        @php
            $leaderboard = collect($this->leaderboardData ?? []);
            $top3 = $leaderboard->take(3);
            $others = $leaderboard->skip(3);
        @endphp

        {{-- TOP 3 PODIUM --}}
        @if($top3->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end pt-16 pb-8 px-2 md:px-8">
            
            {{-- Rank 2 (Silver) --}}
            @if($top3->count() >= 2)
            <div class="order-2 md:order-1 transform transition-all duration-300 hover:-translate-y-2 cursor-pointer" wire:click="openDetail({{ $top3[1]->teacher_id }})">
                <div class="podium-card podium-2">
                    <div class="rank-badge rank-2-badge">2</div>
                    <div class="bg-white/80 dark:bg-black/40 backdrop-blur-md rounded-xl p-5 shadow-sm">
                        <h3 class="font-black text-xl text-gray-900 dark:text-white truncate mb-1" title="{{ $top3[1]->teacher->name }}">{{ $top3[1]->teacher->name }}</h3>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400 uppercase mb-4">{{ $top3[1]->tugas_selesai }} Tugas Selesai</p>
                        <div class="inline-flex items-center justify-center bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900 rounded-xl px-5 py-2 font-black text-xl shadow-md w-full">
                            {{ $top3[1]->persentase }}%
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="hidden md:block order-1"></div>
            @endif

            {{-- Rank 1 (Gold) --}}
            @if($top3->count() >= 1)
            <div class="order-1 md:order-2 transform transition-all duration-300 hover:-translate-y-2 cursor-pointer -mt-8" wire:click="openDetail({{ $top3[0]->teacher_id }})">
                <div class="podium-card podium-1">
                    <div class="rank-badge rank-1-badge">1</div>
                    <div class="bg-white/90 dark:bg-black/50 backdrop-blur-md rounded-xl p-6 shadow-md border border-yellow-300 dark:border-yellow-600">
                        <h3 class="font-black text-2xl text-gray-900 dark:text-white truncate mb-1" title="{{ $top3[0]->teacher->name }}">{{ $top3[0]->teacher->name }}</h3>
                        <p class="text-sm font-extrabold text-amber-700 dark:text-amber-400 uppercase mb-4">{{ $top3[0]->tugas_selesai }} Tugas Selesai</p>
                        <div class="inline-flex items-center justify-center bg-amber-500 text-white rounded-xl px-6 py-2.5 font-black text-2xl shadow-lg w-full">
                            {{ $top3[0]->persentase }}%
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Rank 3 (Bronze) --}}
            @if($top3->count() >= 3)
            <div class="order-3 md:order-3 transform transition-all duration-300 hover:-translate-y-2 cursor-pointer" wire:click="openDetail({{ $top3[2]->teacher_id }})">
                <div class="podium-card podium-3">
                    <div class="rank-badge rank-3-badge">3</div>
                    <div class="bg-white/80 dark:bg-black/40 backdrop-blur-md rounded-xl p-5 shadow-sm">
                        <h3 class="font-black text-xl text-gray-900 dark:text-white truncate mb-1" title="{{ $top3[2]->teacher->name }}">{{ $top3[2]->teacher->name }}</h3>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400 uppercase mb-4">{{ $top3[2]->tugas_selesai }} Tugas Selesai</p>
                        <div class="inline-flex items-center justify-center bg-orange-700 text-white rounded-xl px-5 py-2 font-black text-xl shadow-md w-full">
                            {{ $top3[2]->persentase }}%
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="hidden md:block order-3"></div>
            @endif

        </div>
        @else
        <div class="bmos-card p-12 text-center my-8">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-2">Belum Ada Data Kinerja Guru</h3>
            <p class="text-gray-500 max-w-md mx-auto text-sm">Tidak ada catatan penyelesaian tugas guru yang tercatat pada lembaga dan periode yang dipilih.</p>
        </div>
        @endif

        {{-- OTHER TEACHERS LIST --}}
        @if($others->count() > 0)
        <div class="bmos-card p-6 md:p-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="p-2 bg-orange-100 dark:bg-orange-950/40 text-orange-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    </span>
                    Peringkat Guru Lainnya
                </h3>
                <span class="text-xs font-bold px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-full">
                    {{ $others->count() }} Guru
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($others as $item)
                <div class="py-4 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 px-3 rounded-xl transition-colors cursor-pointer" wire:click="openDetail({{ $item->teacher_id }})">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="w-8 text-center font-black text-gray-400 dark:text-gray-500 text-lg">#{{ $item->rank }}</span>
                        <div class="w-10 h-10 avatar-initial text-sm">
                            {{ substr($item->teacher->name, 0, 2) }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-gray-900 dark:text-white text-base truncate">{{ $item->teacher->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $item->tugas_selesai }} tugas terselesaikan</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <div class="w-24 md:w-48 hidden sm:block">
                            <div class="progress-track">
                                <div class="progress-fill bg-gradient-to-r from-orange-400 to-orange-600" style="width: {{ min(100, $item->persentase) }}%"></div>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center font-black text-base px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-800 dark:text-gray-200 min-w-[3.5rem]">
                            {{ $item->persentase }}%
                        </span>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- MODAL DETAIL TUGAS GURU --}}
    <x-filament::modal id="detail-tugas-modal" width="3xl">
        <x-slot name="header">
            <div class="flex items-center justify-between pr-8">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Detail Tugas: {{ $selectedTeacherName }}
                    </h3>
                    <p class="text-xs text-gray-500">Daftar tugas yang diselesaikan pada periode ini.</p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Nama Tugas</th>
                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($detailTasks as $task)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($task['tanggal'])->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                {{ $task['tugas'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($task['is_done'])
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-800">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Belum Dikerjakan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data tugas untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <x-slot name="footer">
            <x-filament::button color="gray" x-on:click="close()">Tutup</x-filament::button>
        </x-slot>
    </x-filament::modal>

</x-filament-panels::page>
