<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Jurnal Mengajar</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .doc-title-box {
            text-align: center;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .doc-title-main {
            font-size: 12px;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-title-sub {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .table-grid th, .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            vertical-align: top;
        }
        .table-grid th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 9px;
            text-align: center;
            width: 50%;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LAPORAN REKAPITULASI BUKU JURNAL MENGAJAR HARIAN GURU</div>
        <div class="doc-title-sub">DOKUMEN MONITORING KINERJA & KETERLAKSANAAN PEMBELAJARAN KURIKULUM MERDEKA</div>
    </div>

    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 11%;">Hari / Tanggal</th>
                <th style="width: 6%;">Jam Ke</th>
                <th style="width: 6%;">Kelas</th>
                <th style="width: 14%;">Guru Pengampu</th>
                <th style="width: 14%;">Mata Pelajaran</th>
                <th style="width: 26%;">Topik Materi & Tujuan Pembelajaran</th>
                <th style="width: 20%;">Catatan / Refleksi Pembelajaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $index => $j)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('d M Y') }}</strong><br>
                        <span style="color: #64748b;">{{ \App\Models\Schedule::getIndonesianDayName($j->tanggal) }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-green">Jam {{ $j->jam_ke }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-blue">{{ $j->classroom->name ?? '-' }}</span>
                    </td>
                    <td><strong>{{ $j->teacher->name ?? '-' }}</strong></td>
                    <td>{{ $j->subject->name ?? '-' }}</td>
                    <td>
                        <strong style="color: #047857;">{{ $j->materi }}</strong>
                        @if(is_array($j->tp_ids) && count($j->tp_ids) > 0)
                            <div style="font-size: 7.5px; color: #475569; margin-top: 2px;">
                                🎯 {{ count($j->tp_ids) }} Tujuan Pembelajaran (TP) tercatat
                            </div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $j->refleksi_guru ?? ($j->rencana_kegiatan_guru ?? 'Pembelajaran terlaksana dengan baik.') }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 12px;">Tidak ada data jurnal mengajar pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('kepala_sekolah')->first()?->name ?? 'Kepala SMP Islam Baitul Makmur' }}</u></strong><br>
                NIP/NUPTK: 19780512 200501 1 003
            </td>
            <td>
                Jawa Timur, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                <strong>Waka Kurikulum / Pengawas Akademik</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('waka_kurikulum')->first()?->name ?? 'Waka Kurikulum' }}</u></strong><br>
                NIP/NUPTK: 19820315 200801 2 007
            </td>
        </tr>
    </table>

</body>
</html>
