<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jurnal Mengajar - {{ $journal->teacher->name ?? 'Guru' }} - {{ $journal->tanggal }}</title>
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
            padding: 4px 6px;
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
        .badge-yellow { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-red { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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

    @include('pdf.kop_surat', ['institutionName' => $journal->teacher?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LEMBAR JURNAL MENGAJAR & PRESENSI KELAS</div>
        <div class="doc-title-sub">BUKU CATATAN PERKEMBANGAN BELAJAR MENGAJAR HARIAN GURU</div>
    </div>

    <!-- I. DATA PERTEMUAN -->
    <div class="section-header">I. DATA PERTEMUAN & JADWAL PEMBELAJARAN</div>
    <table class="table-identity">
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ \Carbon\Carbon::parse($journal->tanggal)->translatedFormat('l, d F Y') }}</strong></td>
            <td class="label">Guru Pengampu</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $journal->teacher->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Jam Pelajaran</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">{{ \App\Models\Schedule::getJamLabelWithTime($journal->jam_ke) }}</span></td>
            <td class="label">Mata Pelajaran</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $journal->subject->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Rombongan Belajar</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-blue">Kelas {{ $journal->classroom->name ?? '-' }}</span></td>
            <td class="label">Status Presensi Guru</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">HADIR MENGAJAR</span></td>
        </tr>
    </table>

    <!-- II. MATERI & TUJUAN PEMBELAJARAN -->
    <div class="section-header">II. RENCANA MATERI & TUJUAN PEMBELAJARAN (TP)</div>
    <table class="table-grid">
        <tr>
            <td style="width: 22%; font-weight: bold; background-color: #f8fafc;">Topik / Pokok Bahasan</td>
            <td style="width: 78%; font-size: 10px; font-weight: bold; color: #047857;">{{ $journal->materi }}</td>
        </tr>
        @if($journal->rppm)
            <tr>
                <td style="font-weight: bold; background-color: #f8fafc;">Rujukan Dokumen RPPM</td>
                <td>
                    <strong>{{ $journal->rppm->topik }}</strong> • Kelas {{ $journal->rppm->kelas_fase }} • Penyusun: {{ $journal->rppm->guru?->name }}
                </td>
            </tr>
        @endif
    </table>

    <div style="font-weight: bold; font-size: 9.5px; margin: 4px 0 2px 0;">🎯 Tujuan Pembelajaran (TP) yang Dilaksanakan:</div>
    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 10%;">Kode TP</th>
                <th style="width: 25%;">Lingkup Materi</th>
                <th style="width: 65%;">Rumusan Tujuan Pembelajaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($selectedTps as $tp)
                <tr>
                    <td style="text-align: center; font-weight: bold;"><span class="badge badge-blue">{{ $tp->kode_tp }}</span></td>
                    <td><strong>{{ $tp->materi ?? '-' }}</strong></td>
                    <td>{{ $tp->deskripsi_tp }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #64748b;">Materi terintegrasi dalam pelaksanaan pembelajaran harian.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- III. AKTIVITAS & REFLEKSI PEMBELAJARAN -->
    <div class="section-header">III. PELAKSANAAN KEGIATAN & REFLEKSI PEMBELAJARAN</div>
    <table class="table-grid">
        <tr>
            <td style="width: 50%;">
                <strong>Rencana & Kegiatan Guru:</strong><br>
                <div style="margin-top: 3px; color: #334155;">{{ $journal->rencana_kegiatan_guru ?? 'Menjelaskan konsep materi, memberikan contoh soal, dan membimbing latihan terarah.' }}</div>
            </td>
            <td style="width: 50%;">
                <strong>Aktivitas & Keterlibatan Siswa:</strong><br>
                <div style="margin-top: 3px; color: #334155;">{{ $journal->kegiatan_siswa ?? 'Siswa aktif menyimak pemaparan, mengerjakan lembar kerja, dan bertanya jawab.' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Refleksi Proses Pembelajaran Guru:</strong><br>
                <div style="margin-top: 3px; color: #334155;">{{ $journal->refleksi_guru ?? 'Pembelajaran berjalan kondusif, siswa antusias dan target materi tercapai dengan baik.' }}</div>
            </td>
            <td>
                <strong>Rencana Tindak Lanjut:</strong><br>
                <div style="margin-top: 3px; color: #334155;">{{ $journal->tindak_lanjut ?? 'Melanjutkan materi berikutnya dan memberikan penguatan konsep pada pertemuan depan.' }}</div>
            </td>
        </tr>
    </table>

    <!-- IV. REKAP KEHADIRAN SISWA -->
    <div class="section-header">IV. REKAPITULASI PRESENSI KEHADIRAN SISWA DI KELAS</div>
    @php
        $attendances = $journal->subjectAttendances;
        $totalSiswa = $attendances->count();
        $hadir = $attendances->where('status', 'hadir')->count();
        $sakit = $attendances->where('status', 'sakit')->count();
        $izin = $attendances->where('status', 'izin')->count();
        $alpa = $attendances->where('status', 'alpa')->count();
        $absenList = $attendances->whereIn('status', ['sakit', 'izin', 'alpa']);
    @endphp

    <table class="table-grid" style="text-align: center;">
        <tr>
            <th style="width: 20%; background-color: #f1f5f9;">Total Murid di Rombel</th>
            <th style="width: 20%; background-color: #d1fae5; color: #065f46;">Hadir</th>
            <th style="width: 20%; background-color: #fef3c7; color: #92400e;">Sakit (S)</th>
            <th style="width: 20%; background-color: #e0e7ff; color: #3730a3;">Izin (I)</th>
            <th style="width: 20%; background-color: #fee2e2; color: #991b1b;">Alpa / Tanpa Ket (A)</th>
        </tr>
        <tr style="font-size: 11px; font-weight: bold;">
            <td>{{ $totalSiswa }} Siswa</td>
            <td style="color: #065f46;">{{ $hadir }}</td>
            <td style="color: #92400e;">{{ $sakit }}</td>
            <td style="color: #3730a3;">{{ $izin }}</td>
            <td style="color: #991b1b;">{{ $alpa }}</td>
        </tr>
    </table>

    @if($absenList->count() > 0)
        <div style="font-weight: bold; font-size: 9px; margin-top: 4px; margin-bottom: 2px;">Daftar Siswa Tidak Hadir Pada Jam Pelajaran Ini:</div>
        <table class="table-grid">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">NIS</th>
                    <th style="width: 50%;">Nama Siswa</th>
                    <th style="width: 30%;">Keterangan Ketidakhadiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absenList as $idx => $att)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td style="text-align: center;">{{ $att->student?->nis ?? '-' }}</td>
                        <td><strong>{{ $att->student?->name ?? 'Siswa' }}</strong></td>
                        <td style="text-align: center;">
                            @if($att->status === 'sakit')
                                <span class="badge badge-yellow">SAKIT</span>
                            @elseif($att->status === 'izin')
                                <span class="badge badge-blue">IZIN</span>
                            @else
                                <span class="badge badge-red">ALPA / TANPA KETERANGAN</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah / Waka Kurikulum</strong>
                <br><br><br><br>
                <strong><u>{{ \App\Models\User::role(['kepala_sekolah', 'waka_kurikulum'])->first()?->name ?? 'Kepala SMP Islam Baitul Makmur' }}</u></strong><br>
                NIP/NUPTK: 19780512 200501 1 003
            </td>
            <td>
                Jawa Timur, {{ \Carbon\Carbon::parse($journal->tanggal)->translatedFormat('d F Y') }}<br>
                <strong>Guru Pengampu Mata Pelajaran</strong>
                <br><br><br><br>
                <strong><u>{{ $journal->teacher->name ?? 'Guru Mata Pelajaran' }}</u></strong><br>
                NIP/NUPTK: {{ $journal->teacher?->nip ?? ($journal->teacher?->nuptk ?? '-') }}
            </td>
        </tr>
    </table>

</body>
</html>
