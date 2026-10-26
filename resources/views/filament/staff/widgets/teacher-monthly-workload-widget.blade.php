<x-filament-widgets::widget>
    @php
        $data = $this->workloadData;
        $rows = $data['rows'];
        $totals = $data['totals'];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    @endphp

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-5">
        <!-- Widget Header & Filter -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
            <div>
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-primary-50 rounded-lg text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            Tabel Rekapitulasi Jam Pelajaran (JP) & Beban Mengajar Bulanan
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Evaluasi pemenuhan target jam wajib mengajar bulanan dan realisasi kehadiran tatap muka serta tugas guru pengganti (inval).
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 pl-2">Periode:</label>
                    <select wire:model.live="selectedMonth" class="text-xs rounded-md border-0 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 py-1 pl-2.5 pr-7 focus:ring-1 focus:ring-primary-500 shadow-sm font-medium">
                        @foreach ($months as $num => $nama)
                            <option value="{{ $num }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="selectedYear" class="text-xs rounded-md border-0 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 py-1 pl-2.5 pr-7 focus:ring-1 focus:ring-primary-500 shadow-sm font-medium">
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary Highlights -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3 border border-gray-100 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400">Hari Efektif Belajar</span>
                <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $data['total_hari_aktif'] }} Hari</p>
                <span class="text-[10px] text-gray-400">Senin - Jumat</span>
            </div>
            <div class="rounded-lg bg-primary-50/50 dark:bg-primary-950/30 p-3 border border-primary-100 dark:border-primary-900/50">
                <span class="text-xs text-primary-700 dark:text-primary-400 font-medium">Total Tanggung Jawab Sekolah</span>
                <p class="text-lg font-bold text-primary-700 dark:text-primary-300">{{ $totals['monthly_target_jp'] }} JP</p>
                <span class="text-[10px] text-primary-600/70 dark:text-primary-400/70">Akumulasi target seluruh guru</span>
            </div>
            <div class="rounded-lg bg-green-50/50 dark:bg-green-950/30 p-3 border border-green-100 dark:border-green-900/50">
                <span class="text-xs text-green-700 dark:text-green-400 font-medium">Realisasi Jam Terlaksana</span>
                <p class="text-lg font-bold text-green-700 dark:text-green-300">{{ $totals['total_kehadiran_jp'] }} JP</p>
                <span class="text-[10px] text-green-600/70 dark:text-green-400/70">Hadir: {{ $totals['hadir_jp'] }} JP • Inval: {{ $totals['inval_jp'] }} JP</span>
            </div>
            <div class="rounded-lg bg-amber-50/50 dark:bg-amber-950/30 p-3 border border-amber-100 dark:border-amber-900/50">
                <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Tingkat Ketercapaian Sekolah</span>
                <p class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $data['avg_persen'] }}%</p>
                <span class="text-[10px] text-amber-600/70 dark:text-amber-400/70">Dari total beban terjadwal</span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-2.5 text-center w-10">No</th>
                        <th class="px-3 py-2.5">Nama Guru</th>
                        <th class="px-3 py-2.5 text-center">Beban Mingguan</th>
                        <th class="px-3 py-2.5 text-center bg-primary-50/70 dark:bg-primary-950/40 text-primary-800 dark:text-primary-300 font-bold">
                            Tanggung Jawab Bulan Ini
                        </th>
                        <th class="px-3 py-2.5 text-center text-green-700 dark:text-green-400">Hadir Mandiri</th>
                        <th class="px-3 py-2.5 text-center text-cyan-700 dark:text-cyan-400">Tugas Inval</th>
                        <th class="px-3 py-2.5 text-center bg-blue-50/80 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 font-bold">
                            Total Hadir Mengajar
                        </th>
                        <th class="px-3 py-2.5 text-center text-amber-600 dark:text-amber-400">Izin</th>
                        <th class="px-3 py-2.5 text-center text-rose-600 dark:text-rose-400">Sakit</th>
                        <th class="px-3 py-2.5 text-center text-red-600 dark:text-red-400">Alpa</th>
                        <th class="px-3 py-2.5 text-center text-indigo-600 dark:text-indigo-400">Dinas Luar</th>
                        <th class="px-3 py-2.5 text-center">Selisih Jam</th>
                        <th class="px-3 py-2.5 text-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $idx => $r)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                            <td class="px-3 py-2.5 text-center text-gray-500 font-medium">{{ $idx + 1 }}</td>
                            <td class="px-3 py-2.5 font-medium text-gray-900 dark:text-gray-100">
                                <div>{{ $r['name'] }}</div>
                                <div class="text-[10px] text-gray-400">NIP/NUPTK: {{ $r['nip'] }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-center text-gray-700 dark:text-gray-300">
                                <span class="font-semibold">{{ $r['weekly_jp'] }}</span> JP/Mgg
                            </td>
                            <td class="px-3 py-2.5 text-center bg-primary-50/40 dark:bg-primary-950/20 font-bold text-primary-700 dark:text-primary-300">
                                {{ $r['monthly_target_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center font-medium text-green-700 dark:text-green-400">
                                {{ $r['hadir_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center font-medium text-cyan-700 dark:text-cyan-400">
                                {{ $r['inval_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center bg-blue-50/50 dark:bg-blue-950/30 font-bold text-blue-700 dark:text-blue-300">
                                {{ $r['total_kehadiran_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center text-amber-600 dark:text-amber-400">
                                {{ $r['izin_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center text-rose-600 dark:text-rose-400">
                                {{ $r['sakit_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center text-red-600 dark:text-red-400">
                                {{ $r['alpa_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center text-indigo-600 dark:text-indigo-400">
                                {{ $r['dinas_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center font-semibold {{ $r['selisih_jp'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $r['selisih_jp'] > 0 ? '+' : '' }}{{ $r['selisih_jp'] }} JP
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $r['persen'] >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' : ($r['persen'] >= 75 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300') }}">
                                    {{ $r['persen'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-3 py-6 text-center text-gray-500">
                                Belum ada data guru atau jam mengajar yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100/90 dark:bg-gray-800/90 font-bold text-gray-900 dark:text-gray-100 border-t-2 border-gray-300 dark:border-gray-700">
                        <td colspan="2" class="px-3 py-2.5 text-center uppercase text-[11px] tracking-wide">
                            Total Keseluruhan (Sekolah)
                        </td>
                        <td class="px-3 py-2.5 text-center">{{ $totals['weekly_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-primary-700 dark:text-primary-300">{{ $totals['monthly_target_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-green-700 dark:text-green-400">{{ $totals['hadir_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-cyan-700 dark:text-cyan-400">{{ $totals['inval_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-blue-700 dark:text-blue-300">{{ $totals['total_kehadiran_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-amber-600 dark:text-amber-400">{{ $totals['izin_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-rose-600 dark:text-rose-400">{{ $totals['sakit_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-red-600 dark:text-red-400">{{ $totals['alpa_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center text-indigo-600 dark:text-indigo-400">{{ $totals['dinas_jp'] }} JP</td>
                        <td class="px-3 py-2.5 text-center {{ $totals['selisih_jp'] >= 0 ? 'text-green-600' : 'text-rose-600' }}">
                            {{ $totals['selisih_jp'] > 0 ? '+' : '' }}{{ $totals['selisih_jp'] }} JP
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300">
                                {{ $data['avg_persen'] }}%
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800 gap-2">
            <div>
                💡 <strong>Keterangan:</strong>
                <span class="ml-1"><strong>Tanggung Jawab Bulan Ini</strong> = Akumulasi beban JP wajib berdasarkan hari efektif kalender aktif bulan terpilih.</span>
                <span class="ml-2"><strong>Total Hadir Mengajar</strong> = Jam Tatap Muka Mandiri + Jam Tugas Inval (Pengganti).</span>
            </div>
            <div class="italic">
                Sinkron otomatis dengan Jurnal Mengajar & Permohonan Izin Guru.
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
