<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Harian & Sholat Siswa</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.35; color: #1f2937; margin: 0; padding: 15px; }
        .doc-title { text-align: center; margin-bottom: 12px; }
        .doc-title h2 { margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .doc-title p { margin: 2px 0 0; font-size: 9px; color: #64748b; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data-table th, table.data-table td { border: 1px solid #94a3b8; padding: 5px 4px; text-align: center; font-size: 9.5px; }
        table.data-table td.left { text-align: left; }
        table.data-table th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.kop_surat', ['institutionName' => $institutionName ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title">
        <h2>REKAPITULASI PRESENSI HARIAN & KEDISIPLINAN SHOLAT SISWA</h2>
        <p>Laporan Terpadu Presensi Masuk Pagi & Pembiasaan Sholat Berjamaah</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">NIS</th>
                <th rowspan="2" style="width: 24%;">Nama Siswa</th>
                <th rowspan="2" style="width: 10%;">Kelas</th>
                <th colspan="5" style="background-color: #e2e8f0;">Kehadiran Harian Siswa</th>
                <th colspan="4" style="background-color: #dbeafe;">Absensi Sholat</th>
            </tr>
            <tr>
                <th style="width: 7%;">Hadir</th>
                <th style="width: 7%;">Telat</th>
                <th style="width: 6%;">Sakit</th>
                <th style="width: 6%;">Izin</th>
                <th style="width: 6%;">Alpa</th>
                <th style="width: 7%;">Hadir</th>
                <th style="width: 9%;">Izin/Udzur</th>
                <th style="width: 6%;">Alpa</th>
                <th style="width: 8%;">Tdk Khusyu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            @php
                $daily = $student->dailyAttendances->where('tipe_absen', 'masuk');
                $hadirMasuk = $daily->where('status', 'hadir')->count();
                $terlambat = $daily->where('status', 'hadir')->where('is_late', true)->count();
                $sakit = $daily->where('status', 'sakit')->count();
                $izin = $daily->where('status', 'izin')->count();
                $alpa = $daily->where('status', 'alpa')->count();

                $sholatHadir = $student->prayerAttendances->where('status', 'hadir')->count();
                $sholatIzin = $student->prayerAttendances->whereIn('status', ['sakit', 'izin', 'halangan'])->count();
                $sholatAlpa = $student->prayerAttendances->where('status', 'alpa')->count();
                $tdkKhusyu = $student->prayerAttendances->where('is_khusyu', false)->where('status', 'hadir')->count();
            @endphp
            <tr>
                <td>{{ $student->nis ?? '-' }}</td>
                <td class="left"><strong>{{ $student->name }}</strong></td>
                <td>{{ $student->classroom->name ?? '-' }}</td>
                <td>{{ $hadirMasuk }}</td>
                <td>{{ $terlambat }}</td>
                <td>{{ $sakit }}</td>
                <td>{{ $izin }}</td>
                <td>{{ $alpa }}</td>
                <td>{{ $sholatHadir }}</td>
                <td>{{ $sholatIzin }}</td>
                <td>{{ $sholatAlpa }}</td>
                <td>{{ $tdkKhusyu }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
