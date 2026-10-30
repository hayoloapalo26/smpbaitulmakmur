<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Prestasi Siswa - {{ $studentAchievement->nama_prestasi }}</title>
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
            border-bottom: 2px solid #d97706;
            padding-bottom: 6px;
            margin-bottom: 15px;
        }
        .doc-title-main {
            font-size: 13px;
            font-weight: 800;
            color: #b45309;
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
        .badge-gold { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
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

    @include('pdf.kop_surat', ['institutionName' => $studentAchievement->student?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">SURAT KETERANGAN PENCAPAIAN PRESTASI SISWA</div>
        <div class="doc-title-sub">Nomor Piagam/Sertifikat: REG-PRS/{{ date('Y') }}/{{ str_pad($studentAchievement->id, 4, '0', STR_PAD_LEFT) }}</div>
    </div>

    <p>Kepala SMP Islam Baitul Makmur menerangkan dengan sesungguhnya bahwa:</p>

    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap Siswa</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 12px; color: #b45309;">{{ $studentAchievement->student->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Siswa (NIS)</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $studentAchievement->student->nis ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Kelas / Rombel</td>
            <td class="colon">:</td>
            <td class="val">Kelas {{ $studentAchievement->student->classroom->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Prestasi / Kejuaraan</td>
            <td class="colon">:</td>
            <td class="val"><strong style="font-size: 11px; color: #0f172a;">{{ $studentAchievement->nama_prestasi }}</strong></td>
        </tr>
        <tr>
            <td class="label">Peringkat / Capaian</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-gold">{{ strtoupper($studentAchievement->peringkat ?? 'JUARA') }}</span></td>
        </tr>
        <tr>
            <td class="label">Bidang / Kategori</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ strtoupper(str_replace('_', ' ', $studentAchievement->kategori ?? 'AKADEMIK')) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Tingkat Penyelenggaraan</td>
            <td class="colon">:</td>
            <td class="val">Tingkat {{ ucfirst($studentAchievement->tingkat ?? 'Sekolah') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pencapaian</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $studentAchievement->tanggal ? \Carbon\Carbon::parse($studentAchievement->tanggal)->translatedFormat('d F Y') : '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Penyelenggara / Tempat</td>
            <td class="colon">:</td>
            <td class="val">{{ $studentAchievement->penyelenggara ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Poin Karakter / Apresiasi</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">+{{ $studentAchievement->poin_reward ?? 10 }} Poin Karakter</span></td>
        </tr>
    </table>

    <p style="margin-top: 15px;">
        Surat keterangan ini diberikan sebagai bentuk apresiasi dan penghargaan atas dedikasi dan capaian prestasi yang membanggakan nama sekolah dan keluarga. Semoga menjadi motivasi untuk terus berprestasi.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Waka Kesiswaan</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('waka_kesiswaan')->first()?->name ?? 'Waka Kesiswaan' }}</u></strong><br>
                NIP/NUPTK: 19850420 201001 1 012
            </td>
            <td>
                Jawa Timur, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                <strong>Kepala Sekolah</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('kepala_sekolah')->first()?->name ?? 'Kepala SMP Islam Baitul Makmur' }}</u></strong><br>
                NIP/NUPTK: 19780512 200501 1 003
            </td>
        </tr>
    </table>

</body>
</html>
