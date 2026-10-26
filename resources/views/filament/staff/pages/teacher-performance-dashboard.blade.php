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
        .dark .rank-badge { border-color: #111827; }
        
        .rank-1-badge { background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); width: 7rem; height: 7rem; top: -3.5rem; font-size: 3.5rem; }
        .rank-2-badge { background: linear-gradient(135deg, #9CA3AF 0%, #6B7280 100%); }
        .rank-3-badge { background: linear-gradient(135deg, #EA580C 0%, #C2410C 100%); }
        
        /* Table enhancements */
        .bmos-table-row:hover { background-color: rgba(255, 130, 0, 0.05); }
        .dark .bmos-table-row:hover { background-color: rgba(255, 130, 0, 0.1); }
        
        .avatar-circle {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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
                    Ranking Kinerja Guru
                </h2>
                <p class="font-medium text-lg max-w-xl" style="color: #F4EEDC;">Pemantauan progres dan pencapaian tugas harian guru secara real-time untuk meningkatkan produktivitas.</p>
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
                <div style="display:none">
                    <option value="harian">🏆 Harian</option>
                    <option value="mingguan">🏆 Mingguan</option>
                    <option value="bulanan">🏆 Bulanan</option>
                    <option value="semesteran">🏆 Semesteran</option>
                    <option value="tahunan">🏆 Tahunan</option>
                    <option value="semua">🌟 Sepanjang Waktu</option>
                </select>
                </div>
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
            <div class="order-1 md:order-2 transform transition-all duration-300 hover:-translate-y-2 z-10 md:-mt-10 cursor-pointer" wire:click="openDetail({{ $top3[0]->teacher_id }})">
                <div class="podium-card podium-1 relative">
                    <!-- Crown -->
                    <div class="absolute -top-24 left-1/2 transform -translate-x-1/2 text-yellow-500 drop-shadow-2xl z-20">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5M19 19C19 19.55 18.55 20 18 20H6C5.45 20 5 19.55 5 19V18H19V19Z"/></svg>
                    </div>
                    <div class="rank-badge rank-1-badge">1</div>
                    
                    <div class="bg-white/90 dark:bg-black/50 backdrop-blur-md rounded-xl p-6 shadow-lg border border-yellow-200 dark:border-yellow-900/50">
                        <div class="inline-block bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-3">Terbaik</div>
                        <h3 class="font-black text-2xl text-gray-900 dark:text-white truncate mb-1" title="{{ $top3[0]->teacher->name }}">{{ $top3[0]->teacher->name }}</h3>
                        <p class="text-sm font-bold text-yellow-700 dark:text-yellow-400 uppercase mb-5">{{ $top3[0]->tugas_selesai }} dari {{ $top3[0]->total_tugas }} Tuntas</p>
                        <div class="inline-flex items-center justify-center bg-gradient-to-r from-[#F59E0B] to-[#D97706] text-white rounded-xl px-6 py-3 font-black text-3xl shadow-xl w-full border border-yellow-400">
                            {{ $top3[0]->persentase }}%
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rank 3 (Bronze) --}}
            @if($top3->count() >= 3)
            <div class="order-3 md:order-3 transform transition-all duration-300 hover:-translate-y-2 cursor-pointer" wire:click="openDetail({{ $top3[2]->teacher_id }})">
                <div class="podium-card podium-3">
                    <div class="rank-badge rank-3-badge">3</div>
                    <div class="bg-white/80 dark:bg-black/40 backdrop-blur-md rounded-xl p-5 shadow-sm">
                        <h3 class="font-black text-xl text-gray-900 dark:text-white truncate mb-1" title="{{ $top3[2]->teacher->name }}">{{ $top3[2]->teacher->name }}</h3>
                        <p class="text-sm font-bold text-orange-700 dark:text-orange-400 uppercase mb-4">{{ $top3[2]->tugas_selesai }} Tugas Selesai</p>
                        <div class="inline-flex items-center justify-center bg-orange-800 text-white rounded-xl px-5 py-2 font-black text-xl shadow-md w-full">
                            {{ $top3[2]->persentase }}%
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @else
        <div class="bmos-card p-16 text-center mt-8">
            <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <p class="text-gray-900 dark:text-white font-black text-xl mb-2">Belum ada data pada periode ini</p>
            <p class="text-gray-500 dark:text-gray-400 text-base max-w-md mx-auto">Daftar peringkat akan otomatis muncul saat guru mulai menyelesaikan tugas-tugas mereka.</p>
        </div>
        @endif

        {{-- LIST LEADERBOARD LAINNYA --}}
        @if($others->count() > 0)
        <div class="bmos-card overflow-hidden mt-8">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="font-black text-lg text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="p-2 rounded-lg" style="background-color: #F4EEDC; color: #FF8200;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </span>
                    Peringkat 4 dan Seterusnya
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400 border-collapse">
                    <thead class="bg-white dark:bg-gray-900 text-xs tracking-widest uppercase text-gray-400 font-bold border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-5 text-center w-24">Rank</th>
                            <th scope="col" class="px-6 py-5">Guru</th>
                            <th scope="col" class="px-6 py-5 text-center">Tugas Tuntas</th>
                            <th scope="col" class="px-6 py-5 w-1/3" style="min-width: 250px;">Progres Penyelesaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        @foreach($others as $item)
                        <tr class="bmos-table-row transition-colors cursor-pointer" wire:click="openDetail({{ $item->teacher_id }})">
                            <td class="px-6 py-5 text-center">
                                <span class="inline-block bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-black px-4 py-2 rounded-xl text-sm shadow-sm">
                                    #{{ $item->rank }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="avatar-circle shadow-md">
                                        {{ substr($item->teacher->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white text-base">{{ $item->teacher->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="font-black text-lg text-gray-900 dark:text-white">{{ $item->tugas_selesai }}</span>
                                    <span class="text-gray-400">/</span>
                                    <span class="font-bold text-gray-500">{{ $item->total_tugas }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="progress-track">
                                        @php
                                            $barColor = $item->persentase >= 80 ? 'background-color: #FF8200; box-shadow: 0 0 10px rgba(255, 130, 0, 0.5);' : ($item->persentase >= 50 ? 'background-color: #ffbb88; box-shadow: 0 0 10px rgba(255, 187, 136, 0.5);' : 'background-color: #D84315; box-shadow: 0 0 10px rgba(216, 67, 21, 0.5);');
                                        @endphp
                                        <div class="progress-fill" style="width: {{ $item->persentase }}%; {{ $barColor }}"></div>
                                    </div>
                                    <span class="text-sm font-black w-14 text-right" style="color: {{ $item->persentase >= 80 ? '#FF8200' : ($item->persentase >= 50 ? '#ffbb88' : '#D84315') }};">
                                        {{ $item->persentase }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- MODAL DETAIL TUGAS --}}
    <x-filament::modal id="detail-tugas-modal" width="3xl">
        <x-slot name="heading">
            Rincian Tugas: {{ $selectedTeacherName }}
        </x-slot>
        
        <x-slot name="description">
            Berikut adalah detail tugas To-Do List untuk periode yang dipilih. Anda dapat memantau atau mengingatkan guru yang bersangkutan.
        </x-slot>

        <div class="mt-4 mb-4 flex justify-end">
            <select wire:model.live="detailPeriode" class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <option value="harian">Harian</option>
                <option value="mingguan">Mingguan</option>
                <option value="bulanan">Bulanan</option>
                <option value="semesteran">Semesteran</option>
                <option value="tahunan">Tahunan</option>
                <option value="semua">Sepanjang Waktu</option>
            </select>
        </div>

        <div class="mt-2 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
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



