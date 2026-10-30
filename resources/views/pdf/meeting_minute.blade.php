<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulen Rapat - {{ $meetingMinute->judul }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.45;
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
            width: 22%;
            font-weight: bold;
            color: #334155;
        }
        .table-identity .colon {
            width: 2%;
            text-align: center;
        }
        .table-identity .val {
            width: 76%;
            color: #0f172a;
        }
        .content-box {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 10px 12px;
            font-size: 9.5px;
            line-height: 1.5;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

    @include('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">BERITA ACARA & NOTULEN RAPAT DINAS</div>
        <div class="doc-title-sub">DOKUMEN RESMI PELAKSANAAN & KEPUTUSAN RAPAT SEKOLAH</div>
    </div>

    <!-- I. KETERANGAN RAPAT -->
    <div class="section-header">I. KETERANGAN PELAKSANAAN RAPAT</div>
    <table class="table-identity">
        <tr>
            <td class="label">Agenda / Judul Rapat</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 11px; color: #047857;">{{ $meetingMinute->judul }}</strong></td>
        </tr>
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ \Carbon\Carbon::parse($meetingMinute->tanggal)->translatedFormat('l, d F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Sifat & Ruang Lingkup</td>
            <td class="colon">:</td>
            <td class="val">Rapat {{ strtoupper($meetingMinute->visibility ?? 'INTERNAL') }}</td>
        </tr>
    </table>

    <!-- II. PESERTA & DAFTAR HADIR -->
    @if(!empty($meetingMinute->attendees) && is_array($meetingMinute->attendees))
        <div class="section-header">II. DAFTAR PESERTA RAPAT</div>
        <div class="content-box">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($meetingMinute->attendees as $att)
                    <li>{{ is_array($att) ? ($att['nama'] ?? ($att['name'] ?? '-')) : $att }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- III. NOTULEN & HASIL PEMBAHASAN -->
    <div class="section-header">III. JALANNYA RAPAT, PEMBAHASAN & KEPUTUSAN</div>
    <div class="content-box">
        {!! nl2br(e($meetingMinute->isi)) !!}
    </div>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Pimpinan Rapat / Kepala Sekolah</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('kepala_sekolah')->first()?->name ?? 'Kepala SMP Islam Baitul Makmur' }}</u></strong><br>
                NIP/NUPTK: 19780512 200501 1 003
            </td>
            <td>
                Jawa Timur, {{ \Carbon\Carbon::parse($meetingMinute->tanggal)->translatedFormat('d F Y') }}<br>
                <strong>Notulis Rapat</strong>
                <br><br><br><br>
                <strong><u>{{ auth()->user()?->name ?? 'Notulis Resmi' }}</u></strong><br>
                NIP/NUPTK: {{ auth()->user()?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
