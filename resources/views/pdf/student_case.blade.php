<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Kasus - {{ $studentCase->nomor_kasus }}</title>
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
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .doc-title-main {
            font-size: 12px;
            font-weight: 800;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title-sub {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .section-header {
            background-color: #f1f5f9;
            border-left: 3.5px solid #991b1b;
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
            width: 24%;
            font-weight: bold;
            color: #334155;
        }
        .table-identity .colon {
            width: 2%;
            text-align: center;
        }
        .table-identity .val {
            width: 74%;
            color: #0f172a;
        }
        .content-box {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 8px 10px;
            font-size: 9.5px;
            line-height: 1.45;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-red { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-green { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 9px;
            text-align: center;
            width: 33.3%;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => $studentCase->student?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LEMBAR CATATAN PELANGGARAN & PEMBINAAN SISWA</div>
        <div class="doc-title-sub">Nomor Berkas: {{ $studentCase->nomor_kasus ?? 'KSS-RESMI' }}</div>
    </div>

    <!-- I. IDENTITAS SISWA -->
    <div class="section-header">I. IDENTITAS SISWA YANG BERSANGKUTAN</div>
    <table class="table-identity">
        <tr>
            <td class="label">Nama Lengkap Siswa</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 11px; color: #991b1b;">{{ $studentCase->student->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIS / NISN</td>
            <td class="colon">:</td>
            <td class="val">{{ $studentCase->student->nis ?? '-' }} / {{ $studentCase->student->nisn ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Rombel</td>
            <td class="colon">:</td>
            <td class="val"><strong>Kelas {{ $studentCase->student->classroom->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Wali Kelas Pengampu</td>
            <td class="colon">:</td>
            <td class="val">{{ $studentCase->waliKelas->name ?? '-' }}</td>
        </tr>
    </table>

    <!-- II. DATA KEJADIAN & PELANGGARAN -->
    <div class="section-header">II. RINCIAN KEJADIAN & KATEGORI PELANGGARAN</div>
    <table class="table-identity">
        <tr>
            <td class="label">Tanggal Kejadian</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ \Carbon\Carbon::parse($studentCase->tanggal_kejadian)->translatedFormat('l, d F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Kategori / Jenis Kasus</td>
            <td class="colon">:</td>
            <td class="val">
                @if(is_array($studentCase->kategori_kasus))
                    @foreach($studentCase->kategori_kasus as $kat)
                        <span class="badge badge-red">{{ $kat }}</span>
                    @endforeach
                @else
                    <span class="badge badge-red">{{ $studentCase->kategori_kasus ?? 'Kedisiplinan' }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Poin Pelanggaran</td>
            <td class="colon">:</td>
            <td class="val"><strong style="color: #991b1b;">-{{ $studentCase->poin_pelanggaran ?? 5 }} Poin</strong></td>
        </tr>
        <tr>
            <td class="label">Status Penanganan</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">{{ strtoupper($studentCase->status ?? 'SELESAI') }}</span></td>
        </tr>
    </table>

    <div style="font-weight: bold; font-size: 9.5px; margin: 4px 0 2px 0;">Kronologi & Uraian Kejadian:</div>
    <div class="content-box">
        {!! nl2br(e($studentCase->deskripsi ?? 'Tidak ada deskripsi detail kejadian.')) !!}
    </div>

    <!-- III. TINDAK LANJUT & PEMBINAAN -->
    <div class="section-header">III. TINDAKAN PEMBINAAN & TINDAK LANJUT</div>
    <div class="content-box">
        <strong>Tindakan yang Diberikan:</strong><br>
        {!! nl2br(e($studentCase->tindak_lanjut ?? 'Siswa telah diberikan pembinaan adab dan surat peringatan serta berjanji tidak mengulangi perbuatannya.')) !!}
    </div>

    <table class="signature-table">
        <tr>
            <td>
                Siswa yang Bersangkutan,<br><br><br><br>
                <strong><u>{{ $studentCase->student->name ?? 'Siswa' }}</u></strong><br>
                NIS: {{ $studentCase->student->nis ?? '-' }}
            </td>
            <td>
                Orang Tua / Wali Siswa,<br><br><br><br>
                <strong><u>( .................................................. )</u></strong><br>
                Tanda Tangan & Nama Terang
            </td>
            <td>
                Guru BK / Wali Kelas,<br><br><br><br>
                <strong><u>{{ $studentCase->waliKelas->name ?? (auth()->user()?->name ?? 'Guru Pendamping') }}</u></strong><br>
                NIP/NUPTK: {{ $studentCase->waliKelas?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
