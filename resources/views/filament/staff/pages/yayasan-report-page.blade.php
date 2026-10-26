<x-filament-panels::page>
    <div class="space-y-6">

        <!-- KPI SUMMARY SECTION -->
        <x-filament::section>
            <x-slot name="heading">
                Ringkasan KPI (Key Performance Indicator) Sekolah
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl text-white shadow-lg">
                    <p class="text-sm font-semibold opacity-80 uppercase tracking-wider">KPI Keseluruhan</p>
                    <h2 class="text-4xl font-bold mt-2">{{ number_format($overallKpi, 1) }} <span class="text-xl opacity-70">/ 100</span></h2>
                </div>
                
                @foreach($kpiDivisi as $kpi)
                <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">Divisi {{ ucwords(str_replace('_', ' ', $kpi->divisi)) }}</p>
                    <div class="flex items-end justify-between mt-2">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($kpi->rata_rata, 1) }}</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $kpi->total_program }} Program</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3 dark:bg-gray-700">
                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(100, max(0, $kpi->rata_rata)) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- TO-DO LIST YAYASAN -->
        <x-filament::section>
            <x-slot name="heading">
                Daftar Laporan Mingguan
            </x-slot>
            
            <div class="space-y-4">
                @forelse($mingguan as $program)
                    <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-white/10">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $program->nama_program }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Divisi: {{ ucwords(str_replace('_', ' ', $program->divisi)) }} | Penanggung Jawab: {{ $program->penanggungJawab->name }}</p>
                            </div>
                            <div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full dark:bg-green-900 dark:text-green-200">
                                    Skor: {{ $program->skor_penilaian ?? 'Belum Dinilai' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            <strong>Target Pencapaian:</strong> {{ $program->target_pencapaian }}
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 italic">Tidak ada laporan mingguan.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Daftar Laporan Bulanan
            </x-slot>
            
            <div class="space-y-4">
                @forelse($bulanan as $program)
                    <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-white/10">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $program->nama_program }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Divisi: {{ ucwords(str_replace('_', ' ', $program->divisi)) }} | Penanggung Jawab: {{ $program->penanggungJawab->name }}</p>
                            </div>
                            <div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full dark:bg-green-900 dark:text-green-200">
                                    Skor: {{ $program->skor_penilaian ?? 'Belum Dinilai' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            <strong>Target Pencapaian:</strong> {{ $program->target_pencapaian }}
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 italic">Tidak ada laporan bulanan.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Daftar Laporan Tahunan
            </x-slot>
            
            <div class="space-y-4">
                @forelse($tahunan as $program)
                    <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-900 dark:border-white/10">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $program->nama_program }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Divisi: {{ ucwords(str_replace('_', ' ', $program->divisi)) }} | Penanggung Jawab: {{ $program->penanggungJawab->name }}</p>
                            </div>
                            <div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full dark:bg-green-900 dark:text-green-200">
                                    Skor: {{ $program->skor_penilaian ?? 'Belum Dinilai' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            <strong>Target Pencapaian:</strong> {{ $program->target_pencapaian }}
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 italic">Tidak ada laporan tahunan.</p>
                @endforelse
            </div>
        </x-filament::section>
        
        <div class="flex justify-end mt-4">
            <x-filament::button icon="heroicon-o-printer" onclick="window.print()">
                Cetak Laporan
            </x-filament::button>
        </div>

    </div>
</x-filament-panels::page>
