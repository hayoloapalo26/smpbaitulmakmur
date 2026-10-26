<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi Harian & Sholat Siswa</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        td.left { text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2 style="text-align: center; margin-bottom: 4px;">Rekap Absensi Harian & Sholat Siswa</h2>
    <p style="text-align: center; margin-top: 0;"><strong>{{ $institutionName ?? 'Lembaga' }}</strong></p>
    <hr>

    <table>
        <thead>
            <tr>
                <th rowspan="2">NIS</th>
                <th rowspan="2">Nama Siswa</th>
                <th rowspan="2">Kelas</th>
                <th colspan="5">Kehadiran Harian Siswa</th>
                <th colspan="4">Absensi Sholat</th>
            </tr>
            <tr>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Sakit (S)</th>
                <th>Izin (I)</th>
                <th>Alpha (A)</th>
                <th>Hadir</th>
                <th>Izin/Sakit/Halangan</th>
                <th>Alpha</th>
                <th>Tdk Khusyu</th>
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
                <td>{{ $student->nis }}</td>
                <td class="left">{{ $student->name }}</td>
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
