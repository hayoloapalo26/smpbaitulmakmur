<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Permohonan Izin / Cuti - {{ $leaveRequest->teacher->name ?? 'Guru' }}</title>
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
            border-bottom: 2px solid #047857;
            padding-bottom: 6px;
            margin-bottom: 15px;
        }
        .doc-title-main {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .doc-title-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
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
        .badge-green { background-color: #d1fae5; color: #065f46; }
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
            width: 33.3%;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => $leaveRequest->teacher?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">SURAT PERMOHONAN IZIN / CUTI GURU</div>
        <div class="doc-title-sub">Nomor Registrasi: PERM-CUTI/{{ date('Y') }}/{{ str_pad($leaveRequest->id, 4, '0', STR_PAD_LEFT) }}</div>
    </div>

    <p>Yang bertanda tangan di bawah ini:</p>

    <table class="table-data">
        <tr>
            <td class="label">Nama Lengkap Guru</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $leaveRequest->teacher->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIP / NUPTK</td>
            <td class="colon">:</td>
            <td class="val">{{ $leaveRequest->teacher?->nip ?? ($leaveRequest->teacher?->nuptk ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan / Tugas Mengajar</td>
            <td class="colon">:</td>
            <td class="val">Guru Mata Pelajaran</td>
        </tr>
        <tr>
            <td class="label">Jenis Perizinan / Cuti</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ strtoupper($leaveRequest->jenis_cuti ?? ($leaveRequest->tipe_izin ?? 'IZIN')) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Tanggal Mulai Izin</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ \Carbon\Carbon::parse($leaveRequest->tanggal_mulai)->translatedFormat('l, d F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Tanggal Selesai Izin</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ \Carbon\Carbon::parse($leaveRequest->tanggal_selesai ?? $leaveRequest->tanggal_mulai)->translatedFormat('l, d F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Alasan / Keperluan</td>
            <td class="colon">:</td>
            <td class="val">{{ $leaveRequest->alasan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Guru Pengganti (Inval)</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $leaveRequest->substituteTeacher->name ?? 'Dikoordinasikan oleh Guru Piket' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Status Verifikasi & ACC</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">{{ strtoupper($leaveRequest->status ?? 'DISETUJUI') }}</span></td>
        </tr>
    </table>

    <p style="margin-top: 15px;">
        Demikian surat permohonan ini saya ajukan dengan sebenar-benarnya. Atas perhatian, pengertian, dan izin yang diberikan, saya menyampaikan terima kasih.
    </p>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Waka Kurikulum</strong>
                <br><br><br><br>
                <strong><u>{{ $leaveRequest->wakakurApprover->name ?? (\App\Models\User::role('waka_kurikulum')->first()?->name ?? 'Waka Kurikulum') }}</u></strong><br>
                NIP/NUPTK: {{ $leaveRequest->wakakurApprover?->nip ?? '-' }}
            </td>
            <td>
                Menyetujui,<br>
                <strong>Kepala Sekolah</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role('kepala_sekolah')->first()?->name ?? 'Kepala SMP Islam Baitul Makmur' }}</u></strong><br>
                NIP/NUPTK: 19780512 200501 1 003
            </td>
            <td>
                Jawa Timur, {{ \Carbon\Carbon::parse($leaveRequest->created_at ?? now())->translatedFormat('d F Y') }}<br>
                <strong>Guru Pemohon</strong>
                <br><br><br><br>
                <strong><u>{{ $leaveRequest->teacher->name ?? 'Guru Pemohon' }}</u></strong><br>
                NIP/NUPTK: {{ $leaveRequest->teacher?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
