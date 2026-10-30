<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapor Karakter - {{ $student->name }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4; color: #1f2937; margin: 0; padding: 15px; }
        .doc-title { text-align: center; margin-bottom: 14px; }
        .doc-title h2 { margin: 0; font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .doc-title p { margin: 2px 0 0; font-size: 9.5px; color: #64748b; }
        .student-info { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .student-info td { padding: 3px 6px; font-size: 11px; }
        .student-info .label { width: 18%; font-weight: bold; color: #475569; }
        .student-info .colon { width: 2%; text-align: center; }
        .student-info .val { width: 80%; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 10px; }
        table.data-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .badge-positive { color: #047857; font-weight: bold; }
        .badge-negative { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.kop_surat', ['institutionName' => $student->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title">
        <h2>RAPOR REKAPITULASI SKOR KARAKTER SISWA</h2>
        <p>Laporan Catatan Perkembangan Karakter & Kedisiplinan Peserta Didik</p>
    </div>

    <table class="student-info">
        <tr>
            <td class="label">Nama Lengkap Siswa</td>
            <td class="colon">:</td>
            <td class="val"><strong style="color: #047857; font-size: 12px;">{{ $student->name }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIS / NISN</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->nis ?? '-' }} / {{ $student->nisn ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Rombel</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->classroom->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Total Skor Karakter</td>
            <td class="colon">:</td>
            <td class="val">
                <strong style="font-size: 12px; color: {{ ($student->character_score ?? 100) >= 85 ? '#047857' : (($student->character_score ?? 100) >= 70 ? '#d97706' : '#dc2626') }};">
                    {{ $student->character_score ?? 100 }} Poin
                </strong>
            </td>
        </tr>
    </table>
    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 14%;">Tanggal</th>
                <th style="width: 16%;">Sumber</th>
                <th style="width: 16%;">Kategori</th>
                <th style="width: 14%; text-align: center;">Perubahan Poin</th>
                <th style="width: 40%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $log->source }}</td>
                <td>{{ ucfirst($log->category) }}</td>
                <td style="text-align: center;">
                    <span class="{{ $log->point_change > 0 ? 'badge-positive' : 'badge-negative' }}">
                        {{ $log->point_change > 0 ? '+'.$log->point_change : $log->point_change }}
                    </span>
                </td>
                <td>{{ $log->description }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 12px;">Belum ada riwayat catatan poin karakter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
