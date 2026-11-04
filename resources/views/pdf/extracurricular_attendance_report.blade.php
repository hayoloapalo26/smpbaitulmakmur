<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Presensi Ekstrakurikuler</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.3; color: #1f2937; margin: 0; padding: 15px; }
        .doc-title { text-align: center; margin-bottom: 12px; }
        .doc-title h2 { margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .doc-title p { margin: 2px 0 0; font-size: 9.5px; color: #475569; }
        .info-table { width: 100%; margin-bottom: 12px; font-size: 10px; border-collapse: collapse; }
        .info-table td { padding: 3px 4px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 5px 6px; font-size: 9.5px; }
        table.data-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 8.5px; font-weight: bold; border-radius: 4px; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef9c3; color: #854d0e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    @include('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR MALANG'])

    <div class="doc-title">
        <h2>LAPORAN REKAPITULASI PRESENSI KEHADIRAN EKSTRAKURIKULER</h2>
        <p>Bidang Kesiswaan SMP Islam Baitul Makmur Malang</p>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 18%; font-weight: bold;">Ekstrakurikuler</td>
            <td style="width: 32%;">: {{ $extracurricular ? $extracurricular->name : 'Seluruh Ekstrakurikuler' }}</td>
            <td style="width: 18%; font-weight: bold;">Hari & Jam Latihan</td>
            <td style="width: 32%;">: {{ $extracurricular ? "{$extracurricular->hari}, {$extracurricular->jam_mulai} - {$extracurricular->jam_selesai} WIB" : '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Pembina / Pelatih</td>
            <td>: {{ $extracurricular?->pembina?->name ?? 'Tim Pembina Kesiswaan' }}</td>
            <td style="font-weight: bold;">Periode / Bulan</td>
            <td>: {{ $periodeLabel }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">NIS</th>
                <th style="width: 28%; text-align: left;">Nama Santri / Siswa</th>
                <th style="width: 10%;">Kelas</th>
                <th style="width: 7%;">Hadir</th>
                <th style="width: 7%;">Izin</th>
                <th style="width: 7%;">Sakit</th>
                <th style="width: 7%;">Alpa</th>
                <th style="width: 8%;">Total</th>
                <th style="width: 9%;">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recapData as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $row['nis'] ?: '-' }}</td>
                <td class="text-left font-bold"><strong>{{ $row['name'] }}</strong></td>
                <td class="text-center">{{ $row['kelas'] }}</td>
                <td class="text-center" style="font-weight: bold; color: #047857;">{{ $row['hadir'] }}</td>
                <td class="text-center" style="color: #d97706;">{{ $row['izin'] }}</td>
                <td class="text-center" style="color: #0284c7;">{{ $row['sakit'] }}</td>
                <td class="text-center" style="font-weight: bold; color: #dc2626;">{{ $row['alpa'] }}</td>
                <td class="text-center" style="font-weight: bold;">{{ $row['total'] }}</td>
                <td class="text-center" style="font-weight: bold;">
                    <span class="badge {{ $row['percent'] >= 75 ? 'badge-success' : ($row['percent'] >= 50 ? 'badge-warning' : 'badge-danger') }}">
                        {{ $row['percent'] }}%
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 15px; color: #94a3b8;">
                    Belum ada catatan presensi kehadiran pada kegiatan dan periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 35px; border: none; font-size: 10.5px;">
        <tr>
            <td style="width: 50%; text-align: center; border: none;">
                Mengetahui,<br>
                <strong>Waka Kesiswaan</strong>
                <br><br><br><br>
                <u><strong>( .................................................. )</strong></u>
            </td>
            <td style="width: 50%; text-align: center; border: none;">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                <strong>Guru Pembina Ekstrakurikuler</strong>
                <br><br><br><br>
                <u><strong>{{ $extracurricular?->pembina?->name ?? '( .................................................. )' }}</strong></u>
            </td>
        </tr>
    </table>
</body>
</html>
