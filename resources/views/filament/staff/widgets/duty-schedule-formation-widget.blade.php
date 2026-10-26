<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="p-2 text-primary-600 bg-primary-50 rounded-lg dark:bg-primary-950/50 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6" />
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Formasi Rutin Jadwal Piket Guru - Semester {{ $semester }} ({{ $academicYearName }})
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Dirouting oleh <span class="font-semibold text-primary-600 dark:text-primary-400">Waka Kesiswaan</span> • Berlaku 1 Semester penuh (Diacak ulang pada setiap pergantian semester)
                        </p>
                    </div>
                </div>
            </div>

            @if(!empty($todayTeachers))
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 rounded-lg text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span>Piket Hari Ini ({{ $todayDay }}): <strong>{{ implode(', ', $todayTeachers) }}</strong></span>
                </div>
            @endif
        </div>

        @if(empty($formation))
            <div class="py-6 text-center text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-calendar-days" class="w-10 h-10 mx-auto mb-2 text-gray-400 opacity-60" />
                <p class="font-medium text-sm">Belum ada formasi jadwal piket untuk Semester {{ $semester }} ini.</p>
                <p class="text-xs text-gray-400 mt-1">
                    Waka Kesiswaan dapat menekan tombol <strong>"Acak & Generate Jadwal Piket (1 Semester)"</strong> di atas untuk membuat jadwal otomatis.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 pt-4">
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
                    <div class="rounded-xl p-3.5 border transition-all duration-200 {{ $isToday ? 'bg-primary-50/70 dark:bg-primary-950/30 border-primary-300 dark:border-primary-700 shadow-sm ring-1 ring-primary-400/30' : 'bg-gray-50/70 dark:bg-gray-800/40 border-gray-200/80 dark:border-gray-700/60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm {{ $isToday ? 'text-primary-700 dark:text-primary-300' : 'text-gray-800 dark:text-gray-200' }}">
                                {{ $day }}
                            </span>
                            @if($isToday)
                                <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-primary-600 text-white dark:bg-primary-500">
                                    Hari Ini
                                </span>
                            @else
                                <span class="text-[11px] text-gray-400 font-medium">
                                    {{ count($teachers) }} Guru
                                </span>
                            @endif
                        </div>

                        <ul class="space-y-1.5 text-xs">
                            @forelse($teachers as $teacherName)
                                <li class="flex items-center gap-1.5 py-1 px-2 rounded-md bg-white dark:bg-gray-800/90 border border-gray-100 dark:border-gray-700/80 shadow-2xs font-medium text-gray-700 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-user" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                    <span class="truncate" title="{{ $teacherName }}">{{ $teacherName }}</span>
                                </li>
                            @empty
                                <li class="text-[11px] italic text-gray-400 py-1">- Belum ada guru -</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
