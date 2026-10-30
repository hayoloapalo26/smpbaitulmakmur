<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>E-Rapor Akademik - {{ $student->name }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4; color: #1f2937; margin: 0; padding: 15px; }
        .doc-title { text-align: center; margin-bottom: 14px; }
        .doc-title h2 { margin: 0; font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .doc-title p { margin: 2px 0 0; font-size: 9.5px; color: #64748b; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 3px 5px; font-size: 11px; }
        .grade-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .grade-table th, .grade-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: center; font-size: 10.5px; }
        .grade-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .grade-table .left { text-align: left; }
        .footer { margin-top: 30px; width: 100%; }
        .footer td { text-align: center; font-size: 11px; }
    </style>
</head>
<body>
    @include('pdf.kop_surat', ['institutionName' => $student->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title">
        <h2>E-RAPOR AKADEMIK SISWA</h2>
        <p>Laporan Capaian Hasil Pembelajaran Kurikulum Merdeka</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="18%"><strong>Nama Siswa</strong></td>
            <td width="32%">: <strong style="color: #047857;">{{ $student->name }}</strong></td>
            <td width="18%"><strong>Kelas / Rombel</strong></td>
            <td width="32%">: {{ $student->classroom->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>NIS / NISN</strong></td>
            <td>: {{ $student->nis ?? '-' }} / {{ $student->nisn ?? '-' }}</td>
            <td><strong>Tahun Pelajaran</strong></td>
            <td>: 2026/2027</td>
        </tr>
    </table>

    <table class="grade-table">
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="42%" class="left">Mata Pelajaran</th>
                <th width="25%">Jenis Asesmen / Nilai</th>
                <th width="25%">Nilai Akhir (0-100)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $index => $grade)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="left"><strong>{{ $grade->subject->name ?? '-' }}</strong></td>
                <td>{{ ucfirst($grade->type) }}</td>
                <td><strong style="color: {{ $grade->score >= 75 ? '#047857' : '#dc2626' }};">{{ $grade->score }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding: 12px; color: #94a3b8;">Belum ada data nilai akademik.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td style="width: 50%;">
                Mengetahui,<br>
                Orang Tua / Wali Siswa
                <div style="height: 50px;"></div>
                ( .................................................. )
            </td>
            <td style="width: 50%;">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas
                <div style="height: 50px;"></div>
                ( <strong>{{ $student->classroom?->waliKelas?->name ?? 'Wali Kelas' }}</strong> )
            </td>
        </tr>
    </table>
</body>
</html>
