<?php
$file = 'resources/views/filament/staff/pages/work-program-report-page.blade.php';
$content = <<<'HTML'
<x-filament-panels::page>
    <!-- Statistik Status Program -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-filament::section class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 rounded-lg dark:bg-red-900/30">
                    <x-heroicon-o-clock class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum Dimulai / Draft</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rekapStatus['Belum Dimulai'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 rounded-lg dark:bg-yellow-900/30">
                    <x-heroicon-o-arrow-path class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Sedang Berjalan</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rekapStatus['Sedang Berjalan'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 rounded-lg dark:bg-green-900/30">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Selesai</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rekapStatus['Selesai'] }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <!-- Statistik Per Divisi -->
    <x-filament::section class="mb-6">
        <x-slot name="heading">Sebaran Program Kerja per Divisi (Total: {{ $totalProgram }})</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mt-2">
            @foreach($rekapDivisi as $divisi => $jumlah)
            <div class="p-4 border rounded-lg text-center bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ $divisi }}</h4>
                <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $jumlah }}</p>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    <!-- Tabel Daftar Semua Program -->
    <x-filament::section>
        <x-slot name="heading">Daftar Rekapitulasi Seluruh Program Kerja</x-slot>
        
        <div class="overflow-x-auto mt-4">
            <table class="w-full text-sm text-left border rounded-lg">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 border-b">No</th>
                        <th class="px-4 py-3 border-b">Divisi / Penanggung Jawab</th>
                        <th class="px-4 py-3 border-b">Nama Program Kerja</th>
                        <th class="px-4 py-3 border-b">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarProgram as $index => $prog)
                    <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ $prog->divisi }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $prog->nama }}</td>
                        <td class="px-4 py-3">
                            @php
                                $status = strtolower($prog->status);
                                $color = 'bg-red-100 text-red-800';
                                if (str_contains($status, 'jalan') || str_contains($status, 'proses')) $color = 'bg-yellow-100 text-yellow-800';
                                if (str_contains($status, 'selesai')) $color = 'bg-green-100 text-green-800';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                {{ ucfirst($prog->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada program kerja yang ditambahkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
HTML;
if (!is_dir(dirname($file))) {
    mkdir(dirname($file), 0755, true);
}
file_put_contents($file, $content);
echo "View created.\n";
