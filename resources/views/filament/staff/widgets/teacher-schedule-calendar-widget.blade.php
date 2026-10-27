<x-filament-widgets::widget>
    @php
        $data = $this->getScheduleData();
    @endphp

    <x-filament::section>
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
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            
            {{-- Days Header --}}
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                    <div class="py-2 sm:py-3 text-center text-[10px] sm:text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <span class="sm:hidden">{{ substr($day, 0, 3) }}</span>
                        <span class="hidden sm:inline">{{ $day }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Dates Grid --}}
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
                @foreach($data['calendar'] as $cell)
                    <div class="min-h-[60px] sm:min-h-[100px] bg-white dark:bg-gray-900 p-1 sm:p-2 relative flex flex-col {{ $cell['date'] === null ? 'opacity-30' : '' }} {{ $cell['is_today'] ? 'ring-2 ring-inset ring-[#FF8200] bg-orange-50/20 dark:bg-orange-900/10' : '' }}">
                        
                        @if($cell['date'])
                            <span class="text-xs sm:text-sm font-semibold {{ $cell['is_today'] ? 'text-[#FF8200] font-black' : 'text-gray-700 dark:text-gray-300' }} mb-1 sm:mb-2">
                                {{ $cell['date'] }}
                            </span>

                            @if($cell['jp'] > 0)
                                <div class="mt-auto inline-flex items-center justify-center bg-[#7D1B13] text-white text-[10px] sm:text-xs font-bold px-1 sm:px-2 py-0.5 sm:py-1.5 rounded shadow-sm w-full">
                                    <span class="mr-0.5 sm:mr-1">🎯</span> {{ $cell['jp'] }}<span class="hidden sm:inline">&nbsp;JP</span>
                                </div>
                            @endif
                        @endif

                    </div>
                @endforeach
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
