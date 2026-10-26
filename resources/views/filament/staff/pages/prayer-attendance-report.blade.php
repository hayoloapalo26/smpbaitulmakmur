<x-filament-panels::page>
    @php
        $data = $this->reportData;
        $summary = $data['summary'];
        $rows = $data['rows'];
        $institutionName = $data['institution_name'];
        $isSuperadmin = auth()->user()->hasRole('superadmin');
    @endphp

    <div class="space-y-6">
        {{-- HEADER LEMBAGA & FILTER --}}
        <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 uppercase tracking-wider">
                        Waka Kesiswaan &bull; {{ $institutionName }}
                    </span>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mt-2">
                        Seluruh Rekap Absensi Siswa (Kehadiran Harian, Sakit, Izin, Alpha, & Absensi Sholat)
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan data rekapitulasi absensi khusus siswa <strong>{{ $institutionName }}</strong>.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @if($isSuperadmin)
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase mb-1">Pilih Lembaga</label>
                        <select wire:model.live="selectedInstitutionId" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            <option value="">-- Semua Lembaga --</option>
                            @foreach($this->institutions as $inst)
                                <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase mb-1">Filter Kelas</label>
                    <select wire:model.live="selectedClassroomId" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                        <option value="">-- Semua Kelas ({{ $institutionName }}) --</option>
                        @foreach($this->classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase mb-1">Periode Rekap</label>
                    <select wire:model.live="selectedPeriod" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                        <option value="semua">Semua Waktu (Kumulatif)</option>
                        <option value="bulan_ini">Bulan Ini ({{ now()->translatedFormat('F Y') }})</option>
                        <option value="hari_ini">Hari Ini ({{ now()->translatedFormat('d M Y') }})</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 uppercase mb-1">Cari Siswa (Nama / NIS)</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Ketik nama atau NIS siswa..." class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                </div>
            </div>
        </div>

        {{-- KARTU RINGKASAN STATISTIK LEMBAGA --}}
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm">
                <p class="text-xs font-bold text-gray-500 uppercase">Total Siswa</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $summary['total_siswa'] }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 shadow-sm">
                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase">Total Hadir</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1">{{ $summary['total_hadir'] }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 shadow-sm">
                <p class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase">Total Sakit (S)</p>
                <p class="text-2xl font-black text-amber-700 dark:text-amber-400 mt-1">{{ $summary['total_sakit'] }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-sky-50 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-800 shadow-sm">
                <p class="text-xs font-bold text-sky-700 dark:text-sky-300 uppercase">Total Izin (I)</p>
                <p class="text-2xl font-black text-sky-700 dark:text-sky-400 mt-1">{{ $summary['total_izin'] }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 shadow-sm">
                <p class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase">Total Alpha (A)</p>
                <p class="text-2xl font-black text-rose-700 dark:text-rose-400 mt-1">{{ $summary['total_alpa'] }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 shadow-sm">
                <p class="text-xs font-bold text-purple-700 dark:text-purple-300 uppercase">Hadir Sholat</p>
                <p class="text-2xl font-black text-purple-700 dark:text-purple-400 mt-1">{{ $summary['total_sholat_hadir'] }}</p>
            </div>
        </div>

        {{-- TABEL REKAP ABSENSI SETIAP SISWA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 dark:bg-gray-900 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300 border-b dark:border-gray-700">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 border-r dark:border-gray-700">NIS</th>
                            <th rowspan="2" class="px-4 py-3 border-r dark:border-gray-700">Nama Siswa</th>
                            <th rowspan="2" class="px-4 py-3 border-r dark:border-gray-700">Kelas</th>
                            <th colspan="5" class="px-4 py-2 text-center bg-blue-50/70 dark:bg-blue-950/40 border-b border-r dark:border-gray-700 font-extrabold text-blue-900 dark:text-blue-300">
                                Kehadiran Harian Siswa
                            </th>
                            <th colspan="4" class="px-4 py-2 text-center bg-emerald-50/70 dark:bg-emerald-950/40 border-b dark:border-gray-700 font-extrabold text-emerald-900 dark:text-emerald-300">
                                Absensi Sholat (Dhuha / Dhuhur / Ashar)
                            </th>
                        </tr>
                        <tr>
                            <th class="px-3 py-2 text-center text-emerald-700 dark:text-emerald-400">Hadir</th>
                            <th class="px-3 py-2 text-center text-orange-600">Terlambat</th>
                            <th class="px-3 py-2 text-center text-amber-600">Sakit (S)</th>
                            <th class="px-3 py-2 text-center text-sky-600">Izin (I)</th>
                            <th class="px-3 py-2 text-center text-rose-600 border-r dark:border-gray-700">Alpha (A)</th>
                            <th class="px-3 py-2 text-center text-emerald-700 dark:text-emerald-400">Hadir Sholat</th>
                            <th class="px-3 py-2 text-center text-sky-600">Izin/Sakit/Halangan</th>
                            <th class="px-3 py-2 text-center text-rose-600">Alpha Sholat</th>
                            <th class="px-3 py-2 text-center text-orange-600">Tdk Khusyu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($rows as $row)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-mono text-xs border-r dark:border-gray-800">{{ $row['nis'] }}</td>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white border-r dark:border-gray-800">
                                    {{ $row['name'] }}
                                    @if($isSuperadmin)
                                        <div class="text-[11px] font-normal text-gray-400">{{ $row['institution'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 border-r dark:border-gray-800">
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-xs font-bold">{{ $row['classroom'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-center font-bold text-emerald-600">{{ $row['hadir_masuk'] }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-orange-500">{{ $row['terlambat'] }}</td>
                                <td class="px-3 py-3 text-center font-bold {{ $row['sakit'] > 0 ? 'text-amber-600 bg-amber-50/50 dark:bg-amber-950/20' : 'text-gray-400' }}">{{ $row['sakit'] }}</td>
                                <td class="px-3 py-3 text-center font-bold {{ $row['izin'] > 0 ? 'text-sky-600 bg-sky-50/50 dark:bg-sky-950/20' : 'text-gray-400' }}">{{ $row['izin'] }}</td>
                                <td class="px-3 py-3 text-center font-bold border-r dark:border-gray-800 {{ $row['alpa'] > 0 ? 'text-rose-600 bg-rose-50/60 dark:bg-rose-950/30' : 'text-gray-400' }}">{{ $row['alpa'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-emerald-600">{{ $row['sholat_hadir'] }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-sky-600">{{ $row['sholat_sakit_izin'] }}</td>
                                <td class="px-3 py-3 text-center font-bold {{ $row['sholat_alpa'] > 0 ? 'text-rose-600 bg-rose-50/60 dark:bg-rose-950/30' : 'text-gray-400' }}">{{ $row['sholat_alpa'] }}</td>
                                <td class="px-3 py-3 text-center font-semibold {{ $row['tdk_khusyu'] > 0 ? 'text-orange-600' : 'text-gray-400' }}">{{ $row['tdk_khusyu'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-gray-400 italic">
                                    Belum ada data siswa pada lembaga / kelas yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
