<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Tujuan Pembelajaran (TP)</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            line-height: 1.4;
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
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .table-grid th, .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
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
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-green { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
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
        <div class="doc-title-main">DAFTAR REKAPITULASI TUJUAN PEMBELAJARAN (TP)</div>
        <div class="doc-title-sub">KURIKULUM MERDEKA • TAHUN PELAJARAN 2026/2027</div>
    </div>

    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 8%;">Kode TP</th>
                <th style="width: 10%;">Rujukan CP</th>
                <th style="width: 15%;">Mata Pelajaran & Kelas</th>
                <th style="width: 20%;">Materi Pokok</th>
                <th style="width: 43%;">Rumusan Tujuan Pembelajaran (TP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tps as $idx => $tp)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td style="text-align: center;"><span class="badge badge-blue">{{ $tp->kode_tp }}</span></td>
                    <td style="text-align: center;">
                        @if($tp->capaianPembelajaran)
                            <span class="badge badge-green">CP {{ $tp->capaianPembelajaran->kode_cp ?? '-' }}</span><br>
                            <span style="font-size: 7.5px; color: #475569;">{{ $tp->capaianPembelajaran->elemen }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <strong>{{ $tp->subject->name ?? '-' }}</strong><br>
                        <span style="font-size: 8px; color: #475569;">Kelas {{ $tp->kelas_tingkat }}</span>
                    </td>
                    <td><strong>{{ $tp->materi ?? '-' }}</strong></td>
                    <td>{{ $tp->deskripsi_tp }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 10px;">Belum ada data Tujuan Pembelajaran.</td>
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
                <strong>Waka Kurikulum</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('waka_kurikulum')->first()?->name ?? 'Waka Kurikulum' }}</u></strong><br>
                NIP/NUPTK: 19820315 200801 2 007
            </td>
        </tr>
    </table>

</body>
</html>
