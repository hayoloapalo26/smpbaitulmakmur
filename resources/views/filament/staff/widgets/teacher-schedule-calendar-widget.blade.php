<x-filament-widgets::widget>
    @php
        $data = $this->getScheduleData();
    @endphp

    <x-filament::section x-data="{ openDayModal: false, activeDay: null }">
        {{-- Header & Summary --}}
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            
            <div class="flex items-center space-x-4">
                <button wire:click="previousMonth" class="p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-full transition-colors">
                    <x-heroicon-o-chevron-left class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                </button>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white min-w-[150px] text-center">
                    {{ $data['monthName'] }}
                </h2>
                <button wire:click="nextMonth" class="p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-full transition-colors">
                    <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-600 dark:text-gray-300"/>
                </button>
            </div>

            <div class="flex space-x-3">
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-2 flex flex-col items-center">
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Minggu Ini</span>
                    <span class="text-lg font-black text-blue-700 dark:text-blue-300">{{ $data['totalJpThisWeek'] }} JP</span>
                </div>
                <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-2 flex flex-col items-center">
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider">Bulan Ini</span>
                    <span class="text-lg font-black text-green-700 dark:text-green-300">{{ $data['totalJpThisMonth'] }} JP</span>
                </div>
            </div>

        </div>

        {{-- Calendar Grid --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
            
            {{-- Days Header --}}
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                    <div class="py-2.5 sm:py-3 text-center text-[11px] sm:text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <span class="sm:hidden">{{ substr($day, 0, 3) }}</span>
                        <span class="hidden sm:inline">{{ $day }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Dates Grid --}}
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700/80">
                @foreach($data['calendar'] as $cell)
                    <div 
                        @if(!empty($cell['classes']))
                            @click="activeDay = @js($cell); openDayModal = true"
                            class="min-h-[85px] sm:min-h-[120px] bg-white dark:bg-gray-900 p-1.5 sm:p-2.5 relative flex flex-col cursor-pointer hover:bg-orange-50/40 dark:hover:bg-gray-800/80 transition-all duration-200 group {{ $cell['is_today'] ? 'ring-2 ring-inset ring-[#FF8200] bg-orange-50/30 dark:bg-orange-950/20' : '' }}"
                        @else
                            class="min-h-[85px] sm:min-h-[120px] bg-white dark:bg-gray-900 p-1.5 sm:p-2.5 relative flex flex-col {{ $cell['date'] === null ? 'opacity-25 bg-gray-50/50 dark:bg-gray-950/50' : '' }} {{ $cell['is_today'] ? 'ring-2 ring-inset ring-[#FF8200] bg-orange-50/30 dark:bg-orange-950/20' : '' }}"
                        @endif
                    >
                        
                        @if($cell['date'])
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs sm:text-sm font-bold {{ $cell['is_today'] ? 'text-[#FF8200] font-black' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $cell['date'] }}
                                </span>
                                @if(!empty($cell['classes']))
                                    <span class="hidden sm:inline-block text-[9px] font-semibold text-gray-400 group-hover:text-orange-500 transition-colors" title="Klik untuk rincian jadwal">
                                        🔍 Detail
                                    </span>
                                @endif
                            </div>

                            {{-- Keterangan Mengajar di Kelas Mana Saja per Harinya --}}
                            @if(!empty($cell['classes']))
                                <div class="my-1 flex flex-col gap-1 w-full flex-grow">
                                    @foreach($cell['classes'] as $cls)
                                        <div class="flex items-center justify-between gap-1 text-[10px] sm:text-[11px] bg-amber-500/10 hover:bg-amber-500/20 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 border border-amber-500/25 dark:border-amber-400/25 rounded px-1.5 py-0.5 text-gray-800 dark:text-gray-200 transition-colors shadow-2xs"
                                             title="Kelas: {{ $cls['classroom_name'] }} • Mapel: {{ $cls['subject_name'] }} • {{ $cls['jam_text'] }} ({{ $cls['jp'] }} JP)">
                                            <span class="font-bold truncate flex items-center gap-1 min-w-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                <span class="truncate">{{ $cls['classroom_name'] }}</span>
                                            </span>
                                            <span class="text-[9px] sm:text-[10px] font-bold text-amber-700 dark:text-amber-300 shrink-0">
                                                {{ $cls['jp'] }} JP
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex-grow"></div>
                            @endif

                            {{-- Badge Total JP Harian --}}
                            @if($cell['jp'] > 0)
                                <div class="mt-auto inline-flex items-center justify-center bg-[#7D1B13] hover:bg-[#962016] text-white text-[10px] sm:text-xs font-bold px-1 sm:px-2 py-0.5 sm:py-1 rounded shadow-sm w-full tracking-wide transition-colors">
                                    <span class="mr-1">🎯</span> Total {{ $cell['jp'] }}<span class="hidden sm:inline">&nbsp;JP</span>
                                </div>
                            @endif
                        @endif

                    </div>
                @endforeach
            </div>

        </div>

        {{-- Modal Popup Rincian Mengajar Hari yang Dipilih --}}
        <div 
            x-show="openDayModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div 
                    x-show="openDayModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="openDayModal = false"
                    class="fixed inset-0 bg-gray-950/75 backdrop-blur-xs transition-opacity" 
                    aria-hidden="true"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Box --}}
                <div 
                    x-show="openDayModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200 dark:border-gray-800"
                >
                    <template x-if="activeDay">
                        <div>
                            {{-- Header Modal --}}
                            <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-6 py-4 text-white flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-bold flex items-center gap-2">
                                        <span>📅</span>
                                        <span x-text="activeDay.formatted_date || activeDay.day_name"></span>
                                    </h3>
                                    <p class="text-xs text-orange-100 mt-0.5">
                                        Rincian jadwal mengajar lengkap per jam pelajaran
                                    </p>
                                </div>
                                <button @click="openDayModal = false" class="text-white/80 hover:text-white p-1 rounded-full hover:bg-white/10 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Body Modal --}}
                            <div class="p-6">
                                {{-- Ringkasan Beban Mengajar Hari Ini --}}
                                <div class="mb-4 flex items-center justify-between bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900/50 rounded-xl p-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">🎯</span>
                                        <div>
                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Beban Hari Ini:</span>
                                            <div class="text-sm font-black text-orange-700 dark:text-orange-400">
                                                <span x-text="activeDay.jp"></span> Jam Pelajaran (JP)
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <span x-text="activeDay.classes ? activeDay.classes.length : 0"></span> Rombel / Kelas
                                    </span>
                                </div>

                                {{-- Daftar Kelas & Rincian Jam --}}
                                <div class="space-y-3">
                                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Daftar Kelas yang Diampu:
                                    </h4>

                                    <template x-for="(cls, index) in activeDay.classes" :key="index">
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/80 dark:border-gray-700/80">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center font-bold text-sm">
                                                    🏫
                                                </div>
                                                <div>
                                                    <h5 class="text-sm font-bold text-gray-900 dark:text-white" x-text="cls.classroom_name"></h5>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="cls.subject_name"></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                                    <span x-text="cls.jp"></span> JP
                                                </span>
                                                <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5" x-text="cls.jam_text"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Urutan Jam Pelajaran Lengkap --}}
                                <template x-if="activeDay.details && activeDay.details.length > 0">
                                    <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800">
                                        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5">
                                            Urutan Jam Pelajaran:
                                        </h4>
                                        <div class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                            <template x-for="(detail, idx) in activeDay.details" :key="idx">
                                                <div class="py-2 flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-orange-600 dark:text-orange-400" x-text="'Jam ' + detail.jam_ke"></span>
                                                        <span class="text-gray-400 dark:text-gray-500" x-text="'(' + detail.jam_time + ')'"></span>
                                                    </div>
                                                    <div class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                                        <span x-text="detail.classroom"></span>
                                                        <span class="text-gray-300 dark:text-gray-600">•</span>
                                                        <span class="text-gray-500 dark:text-gray-400" x-text="detail.subject"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Footer Modal --}}
                            <div class="bg-gray-50 dark:bg-gray-800/80 px-6 py-3 flex justify-end border-t border-gray-100 dark:border-gray-800">
                                <button 
                                    @click="openDayModal = false"
                                    type="button" 
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors"
                                >
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
