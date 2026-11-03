@php
    $month = $selectedMonth ?? (int) request('bulan', date('m'));
    $year = $selectedYear ?? (int) request('tahun', date('Y'));
    $selectedTeacherId = $selectedTeacherId ?? request('teacher_id');

    $reportState = \App\Services\TeacherAttendanceReportService::getReportData($month, $year, $selectedTeacherId);
    $reportData = $reportState['reportData'];
    $totals = $reportState['totals'];
    $avgPersen = $reportState['avgPersen'];
    $namaBulan = $reportState['namaBulan'];
    $totalHariAktif = $reportState['totalHariAktif'];
@endphp

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm 10mm 10mm 10mm;
    }
    nav, aside, header, .fi-topbar, .fi-sidebar, .fi-modal-close-overlay, .fi-modal-header, .fi-modal-footer, .print\:hidden, button, input {
        display: none !important;
        visibility: hidden !important;
    }
    html, body, .fi-modal, .fi-modal-window, .fi-modal-content, .fi-page, .fi-main {
        position: static !important;
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
        color: black !important;
    }
    #attendance-report-printable {
        display: block !important;
        position: static !important;
        width: 100% !important;
        overflow: visible !important;
        background: white !important;
        color: black !important;
    }
    table {
        width: 100% !important;
        page-break-inside: auto !important;
        border-collapse: collapse !important;
    }
    thead {
        display: table-header-group !important;
    }
    tfoot {
        display: table-footer-group !important;
    }
    tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
    .avoid-page-break {
        page-break-inside: avoid !important;
    }
}
</style>

<div x-data="{
    searchQuery: '',
    filterType: 'all',
    matches(name, nip, selisih, persen) {
        let q = (this.searchQuery || '').toLowerCase().trim();
        let matchText = true;
        if (q) {
            matchText = ((name || '').toLowerCase().includes(q)) || ((nip || '').toLowerCase().includes(q));
        }
        let matchFilter = true;
        if (this.filterType === 'minus') {
            matchFilter = parseFloat(selisih) < 0;
        } else if (this.filterType === 'tuntas') {
            matchFilter = parseFloat(persen) >= 100;
        }
        return matchText && matchFilter;
    }
}" class="space-y-6 p-4 text-gray-900 dark:text-gray-100" id="attendance-report-printable">
    <!-- Action Header (Hidden saat Cetak) -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b pb-4 print:hidden">
        <div>
            <h3 class="text-lg font-bold">Laporan Rekapitulasi Beban & Kehadiran Jam Guru</h3>
            <p class="text-xs text-gray-500">Periode: <strong class="text-primary-600">{{ $namaBulan }}</strong> ({{ $totalHariAktif }} Hari Efektif Belajar)</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('academic.teacher-attendance.rekap.pdf', ['bulan' => $month, 'tahun' => $year, 'teacher_id' => $selectedTeacherId]) }}"
               target="_blank"
               class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Unduh / Cetak PDF Resmi
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center px-3.5 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Langsung
            </button>
        </div>
    </div>

    <!-- Panel Pencarian Guru & Filter Cepat (Hidden saat Cetak) -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm print:hidden">
        <div class="flex items-center gap-2 w-full sm:w-auto flex-1 max-w-md">
            <div class="relative w-full">
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="🔍 Ketik nama guru atau NIP untuk mencari..."
                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white pl-9 pr-4 py-2 focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                />
                <span class="absolute left-2.5 top-2.5 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari</span>
            </button>
        </div>
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-[11px] text-gray-500 font-medium mr-1">Filter:</span>
            <button
                type="button"
                @click="filterType = 'all'"
                :class="filterType === 'all' ? 'bg-primary-600 text-white font-bold shadow-sm' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600'"
                class="px-2.5 py-1.5 rounded-lg text-xs transition">
                Semua ({{ count($reportData) }})
            </button>
            <button
                type="button"
                @click="filterType = 'minus'"
                :class="filterType === 'minus' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600'"
                class="px-2.5 py-1.5 rounded-lg text-xs transition">
                ⚠️ Jam Kurang
            </button>
            <button
                type="button"
                @click="filterType = 'tuntas'"
                :class="filterType === 'tuntas' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600'"
                class="px-2.5 py-1.5 rounded-lg text-xs transition">
                ✓ Tuntas (≥100%)
            </button>
            <button
                type="button"
                x-show="searchQuery || filterType !== 'all'"
                @click="searchQuery = ''; filterType = 'all'"
                class="px-2 py-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs transition">
                Reset
            </button>
        </div>
    </div>

    <!-- Kop Cetak Resmi -->
    <div class="flex items-center justify-between pb-4 border-b-2 border-gray-800 dark:border-gray-200">
        <div class="w-20 flex-shrink-0 text-center">
            <img src="{{ asset('images/logo-sekolah.png') }}" class="w-16 h-auto mx-auto object-contain" alt="Logo">
        </div>
        <div class="flex-1 text-center px-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">YAYASAN PENDIDIKAN ISLAM BAITUL MAKMUR</h2>
            <h3 class="text-base font-extrabold text-gray-900 dark:text-white uppercase tracking-tight">SMP ISLAM BAITUL MAKMUR MALANG</h3>
            <p class="text-[10px] text-gray-600 dark:text-gray-400 leading-tight mt-0.5">
                Gg. 17B Jl. Raya Sawojajar No.58, Sawojajar, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139<br>
                <span>Email: <strong>adminsmp@baitulmakmur.sch.id</strong></span> &bull; <span>Instagram: <strong>@smpibaitulmakmurmlg</strong></span>
            </p>
        </div>
        <div class="w-20 flex-shrink-0">
            {{-- Spacer penyeimbang --}}
        </div>
    </div>
    <div class="text-center my-3">
        <div class="py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-bold uppercase tracking-wide">
            LAPORAN REKAPITULASI JAM MENGAJAR (JP) & PRESENSI GURU BULANAN
        </div>
        <div class="text-[11px] text-gray-500 mt-1">
            Periode: <strong>{{ $namaBulan }}</strong> • Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y - H:i') }} WIB
        </div>
    </div>

    <!-- Tabel Rekap Per JP -->
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse border border-gray-300 dark:border-gray-700">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-bold">
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center w-8">No</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-3 py-2">Nama Guru</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">Beban Mgg</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center bg-primary-50 dark:bg-primary-950 text-primary-800 dark:text-primary-300 font-extrabold">
                        Tanggung Jawab Bulan Ini
                    </th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-green-700">Hadir Mandiri</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-cyan-700">Tugas Inval</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center bg-blue-50 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-extrabold">
                        Total Hadir Mengajar
                    </th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-amber-700">Izin</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-rose-700">Sakit</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-red-700">Alpa</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-purple-700">Dinas Luar</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">Selisih</th>
                    <th class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">% Capaian</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reportData as $index => $row)
                    <tr x-show="matches('{{ addslashes($row['name']) }}', '{{ addslashes($row['nip']) }}', '{{ $row['selisih_jp'] }}', '{{ $row['persen'] }}')" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 dark:border-gray-700 px-3 py-2 font-medium">
                            <div class="font-semibold">{{ $row['name'] }}</div>
                            <div class="text-[10px] text-gray-500">NIP/NUPTK: {{ $row['nip'] }}</div>
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">
                            {{ $row['weekly_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center bg-primary-50/50 dark:bg-primary-950/30 font-bold text-primary-700 dark:text-primary-300">
                            {{ $row['monthly_target_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-green-700 font-semibold">
                            {{ $row['hadir_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-cyan-700 font-semibold">
                            {{ $row['inval_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center bg-blue-50/50 dark:bg-blue-950/30 font-bold text-blue-700 dark:text-blue-300">
                            {{ $row['total_kehadiran_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-amber-700">
                            {{ $row['izin_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-rose-700">
                            {{ $row['sakit_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-red-700">
                            {{ $row['alpa_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-purple-700">
                            {{ $row['dinas_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center font-bold {{ $row['selisih_jp'] >= 0 ? 'text-green-600' : 'text-rose-600' }}">
                            {{ $row['selisih_jp'] > 0 ? '+' : '' }}{{ $row['selisih_jp'] }} JP
                        </td>
                        <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center font-semibold">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] {{ $row['persen'] >= 90 ? 'bg-green-100 text-green-800' : ($row['persen'] >= 75 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                {{ $row['persen'] }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="border border-gray-300 dark:border-gray-700 px-3 py-4 text-center text-gray-500">
                            Belum ada data beban mengajar atau presensi pada periode bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 dark:bg-gray-800 font-bold border-t-2 border-gray-400">
                    <td colspan="2" class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center uppercase">
                        Total Keseluruhan (Sekolah)
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center">{{ $totals['weekly_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-primary-700">{{ $totals['monthly_target_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-green-700">{{ $totals['hadir_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-cyan-700">{{ $totals['inval_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-blue-700 font-extrabold">{{ $totals['total_kehadiran_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-amber-700">{{ $totals['izin_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-rose-700">{{ $totals['sakit_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-red-700">{{ $totals['alpa_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center text-purple-700">{{ $totals['dinas_jp'] }} JP</td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center {{ $totals['selisih_jp'] >= 0 ? 'text-green-600' : 'text-rose-600' }}">
                        {{ $totals['selisih_jp'] > 0 ? '+' : '' }}{{ $totals['selisih_jp'] }} JP
                    </td>
                    <td class="border border-gray-300 dark:border-gray-700 px-2 py-2 text-center font-bold text-primary-700">
                        {{ $avgPersen }}%
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Catatan Pedoman -->
    <div class="text-[11px] text-gray-500 space-y-1">
        <p><strong>Catatan Perhitungan:</strong></p>
        <p>1. <strong>Tanggung Jawab Bulan Ini:</strong> Total jam wajib mengajar (JP) yang dihitung berdasarkan jumlah pertemuan hari efektif kalender aktif dalam bulan bersangkutan.</p>
        <p>2. <strong>Total Hadir Mengajar:</strong> Jam Tatap Muka Mandiri di kelas ditambah dengan jam tugas menggantikan guru lain (Inval).</p>
        <p>3. <strong>Selisih Jam:</strong> Total Hadir Mengajar dikurangi Tanggung Jawab Bulan Ini. Tanda minus (-) menunjukkan jam yang belum/tidak terlaksana.</p>
    </div>

    <!-- Tanda Tangan -->
    <div class="grid grid-cols-2 pt-6 text-center text-xs print:pt-10">
        <div>
            <p class="text-gray-500">Mengetahui,</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">Kepala Sekolah SMPIT Baitul Makmur</p>
            <div class="h-14"></div>
            <p class="font-bold underline text-gray-900 dark:text-gray-100">Drs. Syamsul Arifin, M.Ed</p>
            <p class="text-[10px] text-gray-500">NIP. 19750815 200501 1 002</p>
        </div>
        <div>
            <p class="text-gray-500">Penanggung Jawab Kurikulum,</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">Waka Kurikulum</p>
            <div class="h-14"></div>
            <p class="font-bold underline text-gray-900 dark:text-gray-100">Nadila Choirunnisa' Intsani, M.Pd</p>
            <p class="text-[10px] text-gray-500">NUPTK. 8452763665210032</p>
        </div>
    </div>
</div>
