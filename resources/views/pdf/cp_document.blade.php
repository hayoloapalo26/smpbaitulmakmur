<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Capaian Pembelajaran (CP) - {{ $cp->subject->name ?? 'Mapel' }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .doc-title-box {
            text-align: center;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            margin-bottom: 12px;
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
            margin-top: 2px;
        }
        .section-header {
            background-color: #f1f5f9;
            border-left: 3.5px solid #047857;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .table-identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .table-identity td {
            padding: 3px 5px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .table-identity .label {
            width: 20%;
            font-weight: bold;
            color: #334155;
        }
        .table-identity .colon {
            width: 2%;
            text-align: center;
        }
        .table-identity .val {
            width: 28%;
            color: #0f172a;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .table-grid th, .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }
        .table-grid th {
            background-color: #f8fafc;
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
            margin-top: 18px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 9.5px;
            text-align: center;
            width: 50%;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => $cp->guru?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">DOKUMEN CAPAIAN PEMBELAJARAN (CP)</div>
        <div class="doc-title-sub">PEDOMAN STANDAR KOMPETENSI KURIKULUM MERDEKA</div>
    </div>

    <!-- IDENTITAS CP -->
    <div class="section-header">I. IDENTITAS CAPAIAN PEMBELAJARAN</div>
    <table class="table-identity">
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $cp->subject->name ?? '-' }}</strong></td>
            <td class="label">Penyusun / Guru</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $cp->guru->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Fase / Kelas Tingkat</td>
            <td class="colon">:</td>
            <td class="val">{{ $cp->fase ?? 'Fase D (SMP)' }} / Kelas {{ $cp->tingkat ?? '7' }}</td>
            <td class="label">Kode CP & Elemen</td>
            <td class="colon">:</td>
            <td class="val">
                <span class="badge badge-blue">CP {{ $cp->kode_cp ?? '1' }}</span>
                <span class="badge badge-green">{{ $cp->elemen }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran / Semester</td>
            <td class="colon">:</td>
            <td class="val">{{ $cp->tahun_ajaran ?? '2026/2027' }} / {{ $cp->semester ?? 'Ganjil' }}</td>
            <td class="label">Masa Berlaku</td>
            <td class="colon">:</td>
            <td class="val">{{ $cp->masa_berlaku ?? '1 Tahun Ajaran' }}</td>
        </tr>
    </table>

    <!-- DESKRIPSI CP -->
    <div class="section-header">II. NARASI & DESKRIPSI CAPAIAN PEMBELAJARAN</div>
    <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 10px; line-height: 1.5; font-size: 10px; margin-bottom: 10px; border-radius: 4px;">
        <strong style="color: #047857;">Elemen: {{ $cp->elemen }}</strong><br>
        <div style="margin-top: 4px; color: #1e293b;">
            {{ $cp->deskripsi_cp }}
        </div>
    </div>

    <!-- TUJUAN PEMBELAJARAN TURUNAN -->
    <div class="section-header">III. DAFTAR TUJUAN PEMBELAJARAN (TP) TURUNAN DARI CP INI</div>
    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 12%;">Kode TP</th>
                <th style="width: 25%;">Lingkup Materi</th>
                <th style="width: 55%;">Rumusan Tujuan Pembelajaran (TP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cp->tujuanPembelajarans as $idx => $tp)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td style="text-align: center;"><span class="badge badge-blue">{{ $tp->kode_tp }}</span></td>
                    <td><strong>{{ $tp->materi ?? '-' }}</strong></td>
                    <td>{{ $tp->deskripsi_tp }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 10px;">Belum ada Tujuan Pembelajaran yang dikaitkan dengan CP ini.</td>
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
                Jawa Timur, {{ \Carbon\Carbon::parse($cp->updated_at ?? now())->translatedFormat('d F Y') }}<br>
                <strong>Guru Mata Pelajaran</strong>
                <br><br><br><br>
                <strong><u>{{ $cp->guru->name ?? 'Guru Mata Pelajaran' }}</u></strong><br>
                NIP/NUPTK: {{ $cp->guru?->nip ?? ($cp->guru?->nuptk ?? '-') }}
            </td>
        </tr>
    </table>

</body>
</html>
