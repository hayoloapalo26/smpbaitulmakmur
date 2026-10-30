<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Biodata Siswa - {{ $student->name }} ({{ $student->nis }})</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: A4 portrait;
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
            padding: 3.5px 8px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .table-data td {
            padding: 3px 4px;
            font-size: 9px;
            vertical-align: top;
            border-bottom: 1px dashed #e2e8f0;
        }
        .table-data .label {
            width: 25%;
            font-weight: bold;
            color: #334155;
        }
        .table-data .colon {
            width: 2%;
            text-align: center;
        }
        .table-data .val {
            width: 73%;
            color: #0f172a;
        }
        .photo-box {
            width: 70px;
            height: 90px;
            border: 1px solid #94a3b8;
            background-color: #f8fafc;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
            color: #64748b;
            padding: 2px;
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
            font-size: 9px;
            text-align: center;
            width: 50%;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => $student->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LEMBAR BUKU INDUK & BIODATA LENGKAP SISWA</div>
        <div class="doc-title-sub">ARSIP DATA POKOK PESERTA DIDIK • TAHUN PELAJARAN 2026/2027</div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
        <tr>
            <td style="width: 80%; vertical-align: top;">
                <!-- I. IDENTITAS PRIBADI -->
                <div class="section-header">I. DATA PRIBADI PESERTA DIDIK</div>
                <table class="table-data">
                    <tr>
                        <td class="label">Nama Lengkap Siswa</td>
                        <td class="colon">:</td>
                        <td class="val"><strong style="font-size: 11px; color: #047857;">{{ $student->name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nomor Induk Siswa (NIS)</td>
                        <td class="colon">:</td>
                        <td class="val"><strong>{{ $student->nis ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">NISN (Nasional)</td>
                        <td class="colon">:</td>
                        <td class="val"><strong>{{ $student->nisn ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">NIK Siswa (KTP/KK)</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $student->nik ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Kelamin</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $student->jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat, Tanggal Lahir</td>
                        <td class="colon">:</td>
                        <td class="val">
                            {{ $student->tempat_lahir ?? '-' }}, 
                            {{ $student->tanggal_lahir ? \Carbon\Carbon::parse($student->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Tempat Tinggal</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $student->alamat_siswa ?? ($student->alamat ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="label">No. Telepon / WhatsApp</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $student->no_telepon_siswa ?? ($student->no_hp ?? '-') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 20%; vertical-align: top; text-align: center; padding-left: 10px;">
                <div class="photo-box" style="margin: 25px auto 0 auto;">
                    <br><br>
                    <strong>FOTO SISWA</strong><br>
                    3 x 4 cm
                </div>
                <div style="margin-top: 6px; font-size: 8px;">
                    <span class="badge badge-green">{{ strtoupper($student->status_siswa ?? 'AKTIF') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- II. DATA PENDIDIKAN & AKADEMIK -->
    <div class="section-header">II. DATA AKADEMIK & KELAS</div>
    <table class="table-data">
        <tr>
            <td class="label">Rombongan Belajar (Kelas)</td>
            <td class="colon">:</td>
            <td class="val"><strong>Kelas {{ $student->classroom->name ?? '-' }} (Tingkat {{ $student->classroom->tingkat ?? '7' }})</strong></td>
        </tr>
        <tr>
            <td class="label">Kelompok Al-Qur'an</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->kelas_quran_label ?? ($student->kelas_quran ?? 'Tahsin / Tahfidz') }}</td>
        </tr>
        <tr>
            <td class="label">Sekolah Asal (SD/MI)</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->asal_sekolah ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Ijazah / SKHU</td>
            <td class="colon">:</td>
            <td class="val">Ijazah: {{ $student->no_ijazah ?? '-' }} • SKHU: {{ $student->no_skhu ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Skor Indeks Karakter</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $student->character_score ?? 80 }} Poin (Sangat Baik)</strong></td>
        </tr>
    </table>

    <!-- III. DATA ORANG TUA / WALI -->
    <div class="section-header">III. DATA ORANG TUA & KELUARGA</div>
    <table class="table-data">
        <tr>
            <td class="label">Nama Ayah Kandung</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $student->nama_ayah ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Pekerjaan & Pendidikan Ayah</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->pekerjaan_ayah ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Ibu Kandung</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $student->nama_ibu ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Pekerjaan & Pendidikan Ibu</td>
            <td class="colon">:</td>
            <td class="val">{{ $student->pekerjaan_ibu ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. Telepon / HP Orang Tua</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $student->no_telepon_orang_tua ?? ($student->no_hp_ortu ?? '-') }}</strong></td>
        </tr>
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
                <strong>Petugas Tata Usaha / Wali Kelas</strong>
                <br><br><br><br>
                <strong><u>{{ auth()->user()?->name ?? 'Petugas Tata Usaha' }}</u></strong><br>
                NIP/NUPTK: {{ auth()->user()?->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
