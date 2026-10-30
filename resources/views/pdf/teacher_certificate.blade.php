<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat Guru - {{ $certificate->teacher->name ?? 'Guru' }}</title>
    <style>
        @page {
            margin: 15mm 18mm 15mm 18mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .doc-title-box {
            text-align: center;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 6px;
            margin-bottom: 15px;
        }
        .doc-title-main {
            font-size: 13px;
            font-weight: 800;
            color: #6d28d9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .table-data td {
            padding: 4px 6px;
            font-size: 10px;
            vertical-align: top;
        }
        .table-data .label {
            width: 28%;
            font-weight: bold;
            color: #334155;
        }
        .table-data .colon {
            width: 3%;
            text-align: center;
        }
        .table-data .val {
            width: 69%;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-purple { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .badge-green { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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

    @include('pdf.kop_surat', ['institutionName' => $certificate->teacher?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LEMBAR PENGESAHAN SERTIFIKAT & PENGEMBANGAN DIRI GURU</div>
        <div class="doc-title-sub">ARSIP DATA PENINGKATAN KOMPETENSI BERKELANJUTAN (PKB) TAHUN {{ date('Y') }}</div>
    </div>

    <p>Berikut adalah catatan resmi bukti keikutsertaan kegiatan pengembangan kompetensi pendidik:</p>

    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap Guru</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 12px; color: #6d28d9;">{{ $certificate->teacher->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIP / NUPTK</td>
            <td class="colon">:</td>
            <td class="val">{{ $certificate->teacher?->nip ?? ($certificate->teacher?->nuptk ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Kegiatan / Sertifikat</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 11px; color: #0f172a;">{{ $certificate->nama_kegiatan ?? ($certificate->nama_sertifikat ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Kategori Kegiatan</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-purple">{{ $certificate->kategori }}</span></td>
        </tr>
        <tr>
            <td class="label">Lembaga Penyelenggara</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $certificate->penyelenggara }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nomor Sertifikat / Registrasi</td>
            <td class="colon">:</td>
            <td class="val"><code>{{ $certificate->nomor_sertifikat ?? '-' }}</code></td>
        </tr>
        <tr>
            <td class="label">Tanggal Pelaksanaan</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $certificate->tanggal_pelaksanaan ? \Carbon\Carbon::parse($certificate->tanggal_pelaksanaan)->translatedFormat('d F Y') : '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Beban Belajar / Durasi</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">{{ $certificate->jumlah_jam ?? 32 }} Jam Pelajaran (JP)</span></td>
        </tr>
        <tr>
            <td class="label">Keterangan Tambahan</td>
            <td class="colon">:</td>
            <td class="val">{{ $certificate->keterangan ?? 'Telah menyelesaikan seluruh rangkaian kegiatan pengembangan kompetensi dengan predikat memuaskan.' }}</td>
        </tr>
    </table>

    <p style="margin-top: 15px;">
        Dokumen ini diterbitkan sebagai bukti fisik dan portofolio resmi bagi guru yang bersangkutan untuk keperluan pembinaan karier, penilaian kinerja guru (PKG), dan administrasi kepegawaian yayasan.
    </p>

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
                <strong>Guru Pemilik Portofolio</strong>
                <br><br><br><br>
                <strong><u>{{ $certificate->teacher->name ?? 'Guru Pengampu' }}</u></strong><br>
                NIP/NUPTK: {{ $certificate->teacher?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
