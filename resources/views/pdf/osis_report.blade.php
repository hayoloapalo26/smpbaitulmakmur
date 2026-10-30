<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Program OSIS</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4; color: #1f2937; margin: 0; padding: 15px; }
        .doc-title { text-align: center; margin-bottom: 14px; }
        .doc-title h2 { margin: 0; font-size: 14px; font-weight: 800; text-transform: uppercase; color: #0f172a; }
        .doc-title p { margin: 2px 0 0; font-size: 9.5px; color: #64748b; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 10.5px; }
        table.data-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR MALANG'])

    <div class="doc-title">
        <h2>LAPORAN REKAPITULASI PROGRAM KERJA OSIS & LPJ</h2>
        <p>Organisasi Siswa Intra Sekolah (OSIS) SMP Islam Baitul Makmur</p>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Nama Program Kegiatan</th>
                <th style="width: 18%;">Tanggal Pelaksanaan</th>
                <th style="width: 16%; text-align: center;">Status</th>
                <th style="width: 17%; text-align: center;">Berkas Proposal</th>
                <th style="width: 17%; text-align: center;">Berkas LPJ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($programs as $prog)
            <tr>
                <td><strong>{{ $prog->nama_program }}</strong></td>
                <td>{{ $prog->tanggal ? \Carbon\Carbon::parse($prog->tanggal)->translatedFormat('d F Y') : '-' }}</td>
                <td style="text-align: center;">
                    <span style="font-weight: bold; color: {{ $prog->status === 'selesai' ? '#047857' : ($prog->status === 'berjalan' ? '#0284c7' : '#d97706') }};">
                        {{ ucfirst($prog->status) }}
                    </span>
                </td>
                <td style="text-align: center;">{{ $prog->proposal_file ? 'Tersedia' : '-' }}</td>
                <td style="text-align: center;">{{ $prog->lpj_file ? 'Tersedia' : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 12px;">Belum ada data program kerja OSIS.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
