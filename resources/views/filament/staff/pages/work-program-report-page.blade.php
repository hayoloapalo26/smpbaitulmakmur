<x-filament-panels::page>
    <!-- Progress Keterlaksanaan & Statistik Ringkas -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Progres Keterlaksanaan Program Sekolah</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total akumulasi seluruh program kerja lintas divisi tahun ajaran aktif</p>
            </div>
            <div class="text-right">
                <span class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ $persenSelesai }}%</span>
                <span class="text-sm text-gray-500 dark:text-gray-400 block font-medium">Terlaksana ({{ $rekapStatus['Selesai'] }} dari {{ $totalProgram }} program)</span>
            </div>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3.5 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-green-500 h-3.5 rounded-full transition-all duration-500" style="width: {{ $persenSelesai }}%"></div>
        </div>
    </div>

    <!-- Statistik Status Program -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-filament::section class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 rounded-lg dark:bg-red-900/30">
                    <x-heroicon-o-clock class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Rencana / Belum Mulai</h3>
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
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Selesai / Terlaksana</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rekapStatus['Selesai'] }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <!-- Statistik Per Divisi -->
    <x-filament::section class="mb-6">
        <x-slot name="heading">Sebaran Program Kerja per Divisi (Total: {{ $totalProgram }})</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-7 gap-3 mt-2">
            @foreach($rekapDivisi as $divisi => $jumlah)
            <div class="p-3 border rounded-lg text-center bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700">
                <h4 class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1 truncate">{{ $divisi }}</h4>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $jumlah }}</p>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    <!-- Tabel Monitoring Semua Program dengan Filter Interaktif Alpine.js -->
    <div x-data="{ search: '', filterDivisi: 'Semua', filterStatus: 'Semua' }">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <span>Monitoring Realisasi Program Kerja</span>
                </div>
            </x-slot>

            <!-- Filter Controls -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Cari Program Kerja:</label>
                    <input 
                        type="text" 
                        x-model="search" 
                        placeholder="Ketik nama program..." 
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Filter Divisi:</label>
                    <select 
                        x-model="filterDivisi" 
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="Semua">Semua Divisi</option>
                        @foreach(array_keys($rekapDivisi) as $div)
                        <option value="{{ $div }}">{{ $div }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Filter Status:</label>
                    <select 
                        x-model="filterStatus" 
                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="Semua">Semua Status</option>
                        <option value="Belum">Belum Dimulai / Rencana</option>
                        <option value="Jalan">Sedang Berjalan</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </div>
            
            <div class="overflow-x-auto mt-2">
                <table class="w-full text-sm text-left border rounded-lg">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 border-b">No</th>
                            <th class="px-4 py-3 border-b">Divisi</th>
                            <th class="px-4 py-3 border-b">Nama Program Kerja</th>
                            <th class="px-4 py-3 border-b">Kategori / Tipe</th>
                            <th class="px-4 py-3 border-b">Waktu Pelaksanaan</th>
                            <th class="px-4 py-3 border-b">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarProgram as $index => $prog)
                        @php
                            $status = strtolower($prog->status);
                            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                            $statusCategory = 'Belum';
                            if (str_contains($status, 'jalan') || str_contains($status, 'proses')) {
                                $badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
                                $statusCategory = 'Jalan';
                            } elseif (str_contains($status, 'selesai')) {
                                $badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                                $statusCategory = 'Selesai';
                            }
                        @endphp
                        <tr 
                            class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                            x-show="(
                                (search === '' || '{{ strtolower($prog->nama) }}'.includes(search.toLowerCase())) &&
                                (filterDivisi === 'Semua' || '{{ $prog->divisi }}'.toLowerCase().includes(filterDivisi.toLowerCase())) &&
                                (filterStatus === 'Semua' || '{{ $statusCategory }}' === filterStatus)
                            )"
                        >
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ $prog->divisi }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                {{ $prog->nama }}
                                @if(!empty($prog->dokumen))
                                <span class="ml-1 text-xs text-blue-500">📎</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $prog->kategori }} • {{ $prog->tipe }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs font-medium">
                                {{ $prog->waktu }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                    {{ ucfirst($prog->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada program kerja yang ditambahkan di lembaga ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>