<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 sm:pb-4 border-b border-gray-100 dark:border-gray-800 gap-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="p-2 text-primary-600 bg-primary-50 rounded-xl dark:bg-primary-950/50 dark:text-primary-400 shrink-0">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-5 h-5 sm:w-6 sm:h-6" />
                    </span>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-tight">
                            Formasi Rutin Piket — Semester {{ $semester }} ({{ $academicYearName }})
                        </h2>
                        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Routing oleh <span class="font-semibold text-primary-600 dark:text-primary-400">Waka Kesiswaan</span> • 1 Semester Penuh (Diacak ulang tiap semester)
                        </p>
                    </div>
                </div>
            </div>

            @if(!empty($todayTeachers))
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 rounded-xl text-emerald-800 dark:text-emerald-300 text-xs font-semibold shadow-2xs">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="leading-tight">Piket Hari Ini ({{ $todayDay }}): <strong>{{ implode(', ', $todayTeachers) }}</strong></span>
                </div>
            @endif
        </div>

        @if(empty($formation))
            <div class="py-6 sm:py-8 text-center text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-calendar-days" class="w-10 h-10 mx-auto mb-2 text-gray-400 opacity-50" />
                <p class="font-semibold text-sm text-gray-700 dark:text-gray-300">Belum ada formasi jadwal piket untuk Semester {{ $semester }}</p>
                <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">
                    Waka Kesiswaan dapat menekan tombol <strong>"Acak & Generate Jadwal Piket (1 Semester)"</strong> di atas untuk mengacak dan membuat jadwal otomatis.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3 pt-3 sm:pt-4">
                @php
                    $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    if (isset($formation['Sabtu'])) {
                        $daysList[] = 'Sabtu';
                    }
                @endphp

                @foreach($daysList as $day)
                    @php
                        $isToday = ($day === $todayDay);
                        $teachers = $formation[$day] ?? [];
                    @endphp
                    <div class="rounded-xl p-3 sm:p-3.5 border transition-all duration-200 {{ $isToday ? 'bg-primary-50/70 dark:bg-primary-950/40 border-primary-400 dark:border-primary-600 shadow-sm ring-2 ring-primary-400/20' : 'bg-gray-50/60 dark:bg-gray-800/40 border-gray-200/80 dark:border-gray-700/60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-black text-sm {{ $isToday ? 'text-primary-700 dark:text-primary-300' : 'text-gray-800 dark:text-gray-200' }}">
                                {{ $day }}
                            </span>
                            @if($isToday)
                                <span class="text-[9px] uppercase font-black tracking-wider px-2 py-0.5 rounded-full bg-primary-600 text-white shadow-2xs">
                                    Hari Ini
                                </span>
                            @else
                                <span class="text-[11px] text-gray-400 font-semibold">
                                    {{ count($teachers) }} Guru
                                </span>
                            @endif
                        </div>

                        <ul class="space-y-1.5 text-xs">
                            @forelse($teachers as $teacherName)
                                <li class="flex items-center gap-2 py-1.5 px-2.5 rounded-lg bg-white dark:bg-gray-800/90 border border-gray-100 dark:border-gray-700/80 shadow-2xs font-semibold text-gray-800 dark:text-gray-200">
                                    <x-filament::icon icon="heroicon-m-user" class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                                    <span class="truncate" title="{{ $teacherName }}">{{ $teacherName }}</span>
                                </li>
                            @empty
                                <li class="text-[11px] italic text-gray-400 py-1.5 px-2">- Belum ada guru -</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
