@php
    $month = $selectedMonth ?? (int) request('bulan', date('m'));
    $year = $selectedYear ?? (int) request('tahun', date('Y'));

    $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
    $endOfMonth = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

    // Hitung kemunculan hari dalam bulan
    $dayCounts = [
        'Senin' => 0,
        'Selasa' => 0,
        'Rabu' => 0,
        'Kamis' => 0,
        'Jumat' => 0,
        'Sabtu' => 0,
        'Minggu' => 0,
    ];

    $cursor = $startOfMonth->copy();
    $totalHariAktif = 0;
    while ($cursor->lte($endOfMonth)) {
        $dayName = \App\Models\Schedule::getIndonesianDayName($cursor);
        if (isset($dayCounts[$dayName])) {
            $dayCounts[$dayName]++;
        }
        if (in_array($cursor->dayOfWeek, [1, 2, 3, 4, 5])) {
            $totalHariAktif++;
        }
        $cursor->addDay();
    }

    // Ambil guru riil (tanpa dummy @smp.com dan tanpa akun Guru Piket)
    $teachers = \App\Models\User::withoutGlobalScopes()
        ->where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum']))
              ->orWhereHas('schedules');
        })
        ->where('name', 'not like', '%piket%')
        ->where('username', 'not like', '%piket%')
        ->where('email', 'not like', '%piket%')
        ->where('email', 'not like', '%@smp.com')
        ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32])
        ->orderBy('name')
        ->get();

    $reportData = [];
    $totals = [
        'weekly_jp' => 0,
        'monthly_target_jp' => 0,
        'hadir_jp' => 0,
        'inval_jp' => 0,
        'total_kehadiran_jp' => 0,
        'izin_jp' => 0,
        'sakit_jp' => 0,
        'alpa_jp' => 0,
        'dinas_jp' => 0,
        'selisih_jp' => 0,
    ];

    foreach ($teachers as $t) {
        if (str_contains(strtolower($t->name), 'piket') || str_contains(strtolower($t->username ?? ''), 'piket') || str_contains(strtolower($t->email ?? ''), 'piket')) {
            continue;
        }
        $schedules = \App\Models\Schedule::withoutGlobalScopes()->where('teacher_id', $t->id)->get();
        $weeklyJP = $schedules->count();

        // Tanggung jawab bulanan (target JP wajib sesuai kalender aktif)
        $monthlyTargetJP = 0;
        foreach ($schedules as $s) {
            $monthlyTargetJP += ($dayCounts[$s->hari] ?? 4);
        }

        $attendances = \App\Models\TeacherAttendance::withoutGlobalScopes()
            ->where('teacher_id', $t->id)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get();

        $hadirJP = $attendances->where('status', 'hadir')->count();
        $izinJP = $attendances->where('status', 'izin')->count();
        $sakitJP = $attendances->where('status', 'sakit')->count();
        $alpaJP = $attendances->where('status', 'alpa')->count();
        $dinasJP = $attendances->where('status', 'dinas_luar')->count();

        $invalJP = \App\Models\TeacherAttendance::withoutGlobalScopes()
            ->where('substitute_teacher_id', $t->id)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->count();

        $totalKehadiranJP = $hadirJP + $invalJP;
        $selisihJP = $totalKehadiranJP - $monthlyTargetJP;
        $persen = $monthlyTargetJP > 0 ? round(($totalKehadiranJP / $monthlyTargetJP) * 100, 1) : ($totalKehadiranJP > 0 ? 100 : 0);

        $totals['weekly_jp'] += $weeklyJP;
        $totals['monthly_target_jp'] += $monthlyTargetJP;
        $totals['hadir_jp'] += $hadirJP;
        $totals['inval_jp'] += $invalJP;
        $totals['total_kehadiran_jp'] += $totalKehadiranJP;
        $totals['izin_jp'] += $izinJP;
        $totals['sakit_jp'] += $sakitJP;
        $totals['alpa_jp'] += $alpaJP;
        $totals['dinas_jp'] += $dinasJP;
        $totals['selisih_jp'] += $selisihJP;

        $reportData[] = [
            'name' => $t->name,
            'nip' => $t->nuptk ?: ($t->no_induk_yayasan ?: '-'),
            'weekly_jp' => $weeklyJP,
            'monthly_target_jp' => $monthlyTargetJP,
            'hadir_jp' => $hadirJP,
            'inval_jp' => $invalJP,
            'total_kehadiran_jp' => $totalKehadiranJP,
            'izin_jp' => $izinJP,
            'sakit_jp' => $sakitJP,
            'alpa_jp' => $alpaJP,
            'dinas_jp' => $dinasJP,
            'selisih_jp' => $selisihJP,
            'persen' => $persen,
        ];
    }

    $avgPersen = $totals['monthly_target_jp'] > 0
        ? round(($totals['total_kehadiran_jp'] / $totals['monthly_target_jp']) * 100, 1)
        : 0;

    $namaBulan = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y');
@endphp

<div class="space-y-6 p-4 text-gray-900 dark:text-gray-100">
    <!-- Action Header (Hidden saat Cetak) -->
    <div class="flex justify-between items-center border-b pb-4 print:hidden">
        <div>
            <h3 class="text-lg font-bold">Laporan Rekapitulasi Beban & Kehadiran Jam Guru</h3>
            <p class="text-xs text-gray-500">Periode: <strong class="text-primary-600">{{ $namaBulan }}</strong> ({{ $totalHariAktif }} Hari Efektif Belajar)</p>
        </div>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Dokumen / PDF
        </button>
    </div>

    <!-- Kop Cetak Resmi -->
    <div class="text-center pb-4 border-b-2 border-gray-800 dark:border-gray-200">
        <h2 class="text-lg font-bold uppercase tracking-wider">YAYASAN PENDIDIKAN ISLAM BAITUL MAKMUR</h2>
        <h3 class="text-base font-semibold text-primary-600">SMP ISLAM TERPADU BAITUL MAKMUR</h3>
        <p class="text-[11px] text-gray-500">Jl. Raya Baitul Makmur, Jawa Timur • NSS/NPSN Terakreditasi</p>
        <div class="mt-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-xs font-bold uppercase tracking-wide">
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
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
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
