<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ ($mode ?? 'harian') === 'rentang' ? 'Rekapitulasi Presensi Rentang Tanggal' : 'Laporan Presensi Harian Siswa' }} - {{ $tanggalFormatted }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            line-height: 1.3;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-logo {
            width: 55px;
            text-align: center;
            vertical-align: middle;
        }
        .header-text {
            text-align: center;
            vertical-align: middle;
        }
        .header-title-yayasan {
            font-size: 11px;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .header-title-school {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2px 0 0 0;
        }
        .header-address {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
        }
        .doc-title-box {
            text-align: center;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .doc-title-main {
            font-size: 11px;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-title-sub {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-info-table {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8.5px;
        }
        .meta-info-table td {
            vertical-align: top;
            padding: 1px 0;
        }
        .stats-box {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .stats-box td {
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            padding: 4px 6px;
            font-size: 8px;
            text-align: center;
        }
        .stats-value {
            font-weight: 800;
            font-size: 10px;
            display: block;
            margin-top: 1px;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8px;
        }
        .table-grid thead {
            display: table-header-group;
        }
        .table-grid tfoot {
            display: table-footer-group;
        }
        .table-grid tr {
            page-break-inside: avoid;
        }
        .table-grid th, .table-grid td {
            border: 1px solid #94a3b8;
            padding: 4px 3px;
            vertical-align: middle;
        }
        .table-grid th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
        }
        .th-primary {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
        }
        .th-emerald {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
        }
        .th-amber {
            background-color: #fef3c7 !important;
            color: #92400e !important;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-semibold { font-weight: 600; }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
        }
        .badge-green { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-rose { background-color: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
        .badge-amber { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-blue { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-purple { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-gray { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        .sign-table {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .sign-table td {
            text-align: center;
            vertical-align: top;
            font-size: 8.5px;
        }
        .sign-space {
            height: 40px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI LEMBAGA -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height: 48px; width: auto;" onerror="this.style.display='none'">
            </td>
            <td class="header-text">
                <div class="header-title-yayasan">Lembaga Pendidikan Islam Terpadu Baitul Makmur Malang</div>
                <div class="header-title-school">SMP ISLAM BAITUL MAKMUR</div>
                <div class="header-address">Jl. Sawojajar No. 17, Kedungkandang, Kota Malang, Jawa Timur | Telp. (0341) 712345 | Web: www.baitulmakmur.sch.id</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title-box">
        <div class="doc-title-main">
            @if(($mode ?? 'harian') === 'rentang')
                REKAPITULASI PRESENSI & KETAATAN IBADAH SHOLAT SISWA (RENTANG TANGGAL)
            @else
                REKAPITULASI PRESENSI & KETAATAN IBADAH SHOLAT HARIAN SISWA
            @endif
        </div>
        <div class="doc-title-sub">Integrasi Presensi Sidik Jari (Fingerprint) & Monitoring Kesiswaan</div>
    </div>

    <!-- META DATA -->
    <table class="meta-info-table">
        <tr>
            <td style="width: 12%;"><strong>{{ ($mode ?? 'harian') === 'rentang' ? 'Periode' : 'Hari / Tanggal' }}</strong></td>
            <td style="width: 38%;">: {{ $tanggalFormatted }}</td>
            <td style="width: 15%;"><strong>Kelas / Rombel</strong></td>
            <td style="width: 35%;">: {{ $classroom ? $classroom->name : 'Semua Kelas (Seluruh Siswa)' }}</td>
        </tr>
        <tr>
            <td><strong>Waktu Cetak</strong></td>
            <td>: {{ now()->translatedFormat('d F Y, H:i') }} WIB (Oleh: {{ auth()->user()->name ?? 'Petugas' }})</td>
            <td><strong>Aturan Jam Resmi</strong></td>
            <td>: Masuk (s.d 06:45, terlambat mulai 06:46) &bull; Dhuhur (s.d 12:15, Keputrian Putri s.d 12:30) &bull; Ashar (s.d 15:15)</td>
        </tr>
        @if(!empty($guruPiket1) || !empty($guruPiket2))
        <tr>
            <td><strong>Guru Piket Bertugas</strong></td>
            <td colspan="3">: 
                1. {{ $guruPiket1?->name ?? '-' }}
                @if(!empty($guruPiket2))
                    &nbsp;&bull;&nbsp; 2. {{ $guruPiket2->name }}
                @endif
            </td>
        </tr>
        @endif
    </table>

    <!-- RINGKASAN STATISTIK -->
    <table class="stats-box">
        <tr>
            <td>
                Total Siswa
                <span class="stats-value" style="color: #0f172a;">{{ $stats['total_siswa'] }}</span>
            </td>
            <td>
                Kedatangan Hadir
                <span class="stats-value" style="color: #047857;">{{ $stats['masuk_hadir'] }}</span>
            </td>
            <td>
                Datang Tepat Waktu
                <span class="stats-value" style="color: #15803d;">{{ $stats['masuk_tepat'] }}</span>
            </td>
            <td>
                Datang Terlambat
                <span class="stats-value" style="color: #be123c;">{{ $stats['masuk_terlambat'] }}</span>
            </td>
            <td>
                {{ \Carbon\Carbon::parse($tanggal ?? now())->isFriday() ? 'Sholat Jum\'at & Keputrian' : 'Sholat Dhuhur' }}
                <span class="stats-value" style="color: #0284c7;">{{ $stats['dhuhur_hadir'] }}</span>
            </td>
            <td>
                Sholat Ashar
                <span class="stats-value" style="color: #4f46e5;">{{ $stats['ashar_hadir'] }}</span>
            </td>
            <td>
                Halangan (Siswi)
                <span class="stats-value" style="color: #7e22ce;">{{ max($stats['dhuhur_halangan'], $stats['ashar_halangan']) }}</span>
            </td>
            <td>
                Sakit / Izin / Alpa
                <span class="stats-value" style="color: #b45309;">{{ $stats['sakit_count'] }} S / {{ $stats['izin_count'] }} I / {{ $stats['alpa_count'] }} A</span>
            </td>
        </tr>
    </table>

    @if(($mode ?? 'harian') === 'rentang')
        {{-- TABEL REKAPITULASI RENTANG TANGGAL --}}
        <table class="table-grid">
            <thead>
                <tr>
                    <th style="width: 25px;" rowspan="2">No</th>
                    <th style="width: 55px;" rowspan="2">NIS / Finger</th>
                    <th rowspan="2">Nama Siswa</th>
                    <th style="width: 25px;" rowspan="2">L/P</th>
                    <th style="width: 50px;" rowspan="2">Kelas</th>
                    <th colspan="2" class="th-primary">1. Rekap Kedatangan (Masuk)</th>
                    <th colspan="3" class="th-emerald">2. Rekap Sholat Dhuhur</th>
                    <th colspan="3" class="th-amber">3. Rekap Sholat Ashar</th>
                    <th colspan="3">4. Izin / Sakit / Alpa</th>
                </tr>
                <tr>
                    <th style="width: 45px;" class="th-primary">Tepat</th>
                    <th style="width: 45px;" class="th-primary">Telat</th>
                    <th style="width: 40px;" class="th-emerald">Tepat</th>
                    <th style="width: 40px;" class="th-emerald">Telat</th>
                    <th style="width: 40px;" class="th-emerald">Udzur</th>
                    <th style="width: 40px;" class="th-amber">Tepat</th>
                    <th style="width: 40px;" class="th-amber">Telat</th>
                    <th style="width: 40px;" class="th-amber">Udzur</th>
                    <th style="width: 25px;">S</th>
                    <th style="width: 25px;">I</th>
                    <th style="width: 25px;">A</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $idx => $student)
                    @php
                        $atts = $student->dailyAttendances;
                        
                        // 1. Masuk
                        $masukAtts = $atts->where('tipe_absen', 'masuk');
                        $masukTepat = $masukAtts->where('status', 'hadir')->where('is_late', false)->count();
                        $masukTelat = $masukAtts->where('status', 'hadir')->where('is_late', true)->count();

                        // 2. Dhuhur
                        $dhuhurAtts = $atts->where('tipe_absen', 'sholat_dhuhur');
                        $dhuhurTepat = $dhuhurAtts->where('status', 'hadir')->where('is_late', false)->count();
                        $dhuhurTelat = $dhuhurAtts->where('status', 'hadir')->where('is_late', true)->count();
                        $dhuhurUdzur = $dhuhurAtts->where('status', 'halangan')->count();

                        // 3. Ashar
                        $asharAtts = $atts->filter(fn ($a) => in_array($a->tipe_absen, ['sholat_ashar', 'pulang']));
                        $asharTepat = $asharAtts->where('status', 'hadir')->where('is_late', false)->count();
                        $asharTelat = $asharAtts->where('status', 'hadir')->where('is_late', true)->count();
                        $asharUdzur = $asharAtts->where('status', 'halangan')->count();

                        // 4. SIA
                        $sakit = $masukAtts->where('status', 'sakit')->count();
                        $izin = $masukAtts->where('status', 'izin')->count();
                        $alpa = $masukAtts->where('status', 'alpa')->count();
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $student->nis ?? '-' }} / {{ $student->fingerspot_pin ?? '-' }}</td>
                        <td class="font-semibold">{{ $student->name }}</td>
                        <td class="text-center">{{ $student->jenis_kelamin ?? '-' }}</td>
                        <td class="text-center">{{ $student->classroom?->name ?? '-' }}</td>

                        <!-- Masuk -->
                        <td class="text-center font-bold text-green">{{ $masukTepat }}</td>
                        <td class="text-center font-bold {{ $masukTelat > 0 ? 'text-rose' : '' }}">{{ $masukTelat }}</td>

                        <!-- Dhuhur -->
                        <td class="text-center font-bold text-green">{{ $dhuhurTepat }}</td>
                        <td class="text-center font-bold {{ $dhuhurTelat > 0 ? 'text-rose' : '' }}">{{ $dhuhurTelat }}</td>
                        <td class="text-center font-bold {{ $dhuhurUdzur > 0 ? 'text-purple' : '' }}">{{ $dhuhurUdzur }}</td>

                        <!-- Ashar -->
                        <td class="text-center font-bold text-green">{{ $asharTepat }}</td>
                        <td class="text-center font-bold {{ $asharTelat > 0 ? 'text-rose' : '' }}">{{ $asharTelat }}</td>
                        <td class="text-center font-bold {{ $asharUdzur > 0 ? 'text-purple' : '' }}">{{ $asharUdzur }}</td>

                        <!-- SIA -->
                        <td class="text-center">{{ $sakit }}</td>
                        <td class="text-center">{{ $izin }}</td>
                        <td class="text-center {{ $alpa > 0 ? 'font-bold text-rose' : '' }}">{{ $alpa }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center" style="padding: 15px; color: #64748b;">
                            Tidak ada data siswa ditemukan untuk kriteria ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        {{-- TABEL 5 VARIABEL HARIAN --}}
        <table class="table-grid">
            <thead>
                <tr>
                    <th style="width: 25px;" rowspan="2">No</th>
                    <th style="width: 55px;" rowspan="2">NIS / Finger</th>
                    <th rowspan="2">Nama Siswa</th>
                    <th style="width: 25px;" rowspan="2">L/P</th>
                    <th style="width: 50px;" rowspan="2">Kelas</th>
                    <th colspan="2" class="th-primary">1. Kedatangan di Sekolah (Finger)</th>
                    <th colspan="2" class="th-emerald">{{ \Carbon\Carbon::parse($tanggal)->isFriday() ? '2. Sholat Jum\'at (Putra) / Keputrian (Putri)' : '2. Sholat Dhuhur (Finger)' }}</th>
                    <th colspan="2" class="th-amber">3. Sholat Ashar (Finger)</th>
                    <th style="width: 85px;" rowspan="2">Status & Keterangan</th>
                </tr>
                <tr>
                    <th style="width: 45px;" class="th-primary">Jam Scan</th>
                    <th style="width: 65px;" class="th-primary">Ketepatan</th>
                    <th style="width: 45px;" class="th-emerald">Jam Scan</th>
                    <th style="width: 65px;" class="th-emerald">Ketepatan</th>
                    <th style="width: 45px;" class="th-amber">Jam Scan</th>
                    <th style="width: 65px;" class="th-amber">Ketepatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $idx => $student)
                    @php
                        $isFriday = \Carbon\Carbon::parse($tanggal)->isFriday();
                        $targetSiang = ($isFriday && $student->jenis_kelamin === 'P') ? ['keputrian', 'sholat_dhuhur'] : ['sholat_dhuhur', 'keputrian'];
                        $attMasuk = $student->getAttendanceOnDate($tanggal, 'masuk');
                        $attDhuhur = $student->getAttendanceOnDate($tanggal, $targetSiang);
                        $attAshar = $student->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $excused = $student->getGeneralExcusedOnDate($tanggal);
                        $isHalangan = $student->jenis_kelamin === 'P' && \App\Models\StudentHalangan::isHalangan($student->id, $tanggal);

                        // 1. Kedatangan
                        $jamMasuk = '-';
                        $ketMasuk = 'Belum Scan';
                        $badgeMasuk = 'badge-gray';
                        if ($attMasuk && $attMasuk->status === 'hadir') {
                            $jamMasuk = $attMasuk->waktu_kedatangan ? \Carbon\Carbon::parse($attMasuk->waktu_kedatangan)->format('H:i') : 'Hadir';
                            $jamMasukStr = $attMasuk->waktu_kedatangan ? \Carbon\Carbon::parse($attMasuk->waktu_kedatangan)->format('H:i') : null;
                            $isLate = $jamMasukStr ? ($jamMasukStr >= '06:46') : (bool) $attMasuk->is_late;
                            $ketMasuk = $isLate ? 'Terlambat' : 'Tepat Waktu';
                            if ($attDhuhur && in_array($attDhuhur->status, ['sakit', 'izin'])) {
                                $ketMasuk .= ' (Pulang ' . ucfirst($attDhuhur->status) . ')';
                            }
                            $badgeMasuk = $isLate ? 'badge-rose' : 'badge-green';
                        } elseif ($attMasuk && in_array($attMasuk->status, ['sakit', 'izin', 'alpa'])) {
                            $jamMasuk = strtoupper($attMasuk->status);
                            $ketMasuk = $attMasuk->keterangan ?: ucfirst($attMasuk->status);
                            $badgeMasuk = $attMasuk->status === 'sakit' ? 'badge-amber' : ($attMasuk->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        } elseif ($excused) {
                            $jamMasuk = strtoupper($excused->status);
                            $ketMasuk = $excused->keterangan ?: ucfirst($excused->status);
                            $badgeMasuk = $excused->status === 'sakit' ? 'badge-amber' : ($excused->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        }

                        // 2. Dhuhur / Keputrian / Sholat Jum'at
                        $isKeputrian = $isFriday && $student->jenis_kelamin === 'P';
                        $jamDhuhur = '-';
                        $ketDhuhur = $isKeputrian ? 'Belum Scan Keputrian' : ($isFriday ? 'Belum Sholat Jum\'at' : 'Belum Scan');
                        $badgeDhuhur = 'badge-gray';
                        if ($isHalangan && (!$attDhuhur || $attDhuhur->status !== 'hadir')) {
                            $jamDhuhur = 'Udzur';
                            $ketDhuhur = ($attDhuhur && $attDhuhur->keterangan) ? $attDhuhur->keterangan : 'Sedang Halangan';
                            $badgeDhuhur = 'badge-purple';
                        } elseif ($attDhuhur && in_array($attDhuhur->status, ['sakit', 'izin', 'alpa'])) {
                            $jamDhuhur = strtoupper($attDhuhur->status);
                            $ketDhuhur = $attDhuhur->keterangan ?: ucfirst($attDhuhur->status);
                            $badgeDhuhur = $attDhuhur->status === 'sakit' ? 'badge-amber' : ($attDhuhur->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        } elseif ($attDhuhur && $attDhuhur->status === 'hadir') {
                            $jamDhuhur = $attDhuhur->waktu_kedatangan ? \Carbon\Carbon::parse($attDhuhur->waktu_kedatangan)->format('H:i') : 'Hadir';
                            $cutoff = $isKeputrian ? '12:30' : '12:15';
                            $isLate = $attDhuhur->is_late || ($attDhuhur->waktu_kedatangan && \Carbon\Carbon::parse($attDhuhur->waktu_kedatangan)->format('H:i') > $cutoff);
                            if ($isKeputrian) {
                                $ketDhuhur = $isLate ? 'Terlambat Keputrian' : 'Tepat Waktu (Keputrian)';
                            } else {
                                $ketDhuhur = $isLate ? ($isFriday ? 'Terlambat Jum\'at' : 'Terlambat') : ($isFriday ? 'Tepat Waktu (Sholat Jum\'at)' : 'Tepat Waktu');
                            }
                            $badgeDhuhur = $isLate ? 'badge-rose' : 'badge-green';
                        } elseif ($excused) {
                            $jamDhuhur = strtoupper($excused->status);
                            $ketDhuhur = $excused->keterangan ?: ucfirst($excused->status);
                            $badgeDhuhur = $excused->status === 'sakit' ? 'badge-amber' : ($excused->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        }

                        // 3. Ashar
                        $jamAshar = '-';
                        $ketAshar = 'Belum Scan';
                        $badgeAshar = 'badge-gray';
                        if ($isHalangan || ($attAshar && $attAshar->status === 'halangan')) {
                            $jamAshar = 'Udzur';
                            $ketAshar = ($attAshar && $attAshar->keterangan) ? $attAshar->keterangan : 'Sedang Halangan';
                            $badgeAshar = 'badge-purple';
                        } elseif ($attAshar && in_array($attAshar->status, ['sakit', 'izin', 'alpa'])) {
                            $jamAshar = strtoupper($attAshar->status);
                            $ketAshar = $attAshar->keterangan ?: ucfirst($attAshar->status);
                            $badgeAshar = $attAshar->status === 'sakit' ? 'badge-amber' : ($attAshar->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        } elseif ($attAshar && $attAshar->status === 'hadir') {
                            $jamAshar = $attAshar->waktu_kedatangan ? \Carbon\Carbon::parse($attAshar->waktu_kedatangan)->format('H:i') : 'Hadir';
                            $isLate = $attAshar->is_late || ($attAshar->waktu_kedatangan && \Carbon\Carbon::parse($attAshar->waktu_kedatangan)->format('H:i') > '15:15');
                            $ketAshar = $isLate ? 'Terlambat' : 'Tepat Waktu';
                            $badgeAshar = $isLate ? 'badge-rose' : 'badge-green';
                        } elseif ($excused) {
                            $jamAshar = strtoupper($excused->status);
                            $ketAshar = $excused->keterangan ?: ucfirst($excused->status);
                            $badgeAshar = $excused->status === 'sakit' ? 'badge-amber' : ($excused->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        }

                        // Status Akhir
                        $statusRingkas = 'Belum Lengkap';
                        $badgeRingkas = 'badge-gray';
                        if ($attMasuk && $attMasuk->status === 'hadir') {
                            if ($attDhuhur && in_array($attDhuhur->status, ['sakit', 'izin'])) {
                                $statusRingkas = 'Pulang ' . ucfirst($attDhuhur->status);
                                $badgeRingkas = $attDhuhur->status === 'sakit' ? 'badge-amber' : 'badge-blue';
                            } elseif ($isHalangan) {
                                $statusRingkas = 'Hadir (Halangan)';
                                $badgeRingkas = 'badge-purple';
                            } elseif (($attDhuhur && $attDhuhur->status === 'hadir') && ($attAshar && $attAshar->status === 'hadir')) {
                                $statusRingkas = 'Lengkap (3/3)';
                                $badgeRingkas = 'badge-green';
                            } else {
                                $statusRingkas = 'Masuk';
                                $badgeRingkas = 'badge-blue';
                            }
                        } elseif ($attMasuk && in_array($attMasuk->status, ['sakit', 'izin', 'alpa'])) {
                            $statusRingkas = ucfirst($attMasuk->status);
                            $badgeRingkas = $attMasuk->status === 'sakit' ? 'badge-amber' : ($attMasuk->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        } elseif ($excused) {
                            $statusRingkas = ucfirst($excused->status);
                            $badgeRingkas = $excused->status === 'sakit' ? 'badge-amber' : ($excused->status === 'izin' ? 'badge-blue' : 'badge-rose');
                        } elseif ($isHalangan) {
                            $statusRingkas = 'Sedang Halangan';
                            $badgeRingkas = 'badge-purple';
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $student->nis ?? '-' }} / {{ $student->fingerspot_pin ?? '-' }}</td>
                        <td class="font-semibold">{{ $student->name }}</td>
                        <td class="text-center">{{ $student->jenis_kelamin ?? '-' }}</td>
                        <td class="text-center">{{ $student->classroom?->name ?? '-' }}</td>
                        
                        <!-- 1. Kedatangan -->
                        <td class="text-center font-bold">{{ $jamMasuk }}</td>
                        <td class="text-center"><span class="badge {{ $badgeMasuk }}">{{ $ketMasuk }}</span></td>

                        <!-- 2. Dhuhur -->
                        <td class="text-center font-bold">{{ $jamDhuhur }}</td>
                        <td class="text-center"><span class="badge {{ $badgeDhuhur }}">{{ $ketDhuhur }}</span></td>

                        <!-- 3. Ashar -->
                        <td class="text-center font-bold">{{ $jamAshar }}</td>
                        <td class="text-center"><span class="badge {{ $badgeAshar }}">{{ $ketAshar }}</span></td>

                        <!-- Ringkasan -->
                        <td class="text-center">
                            <span class="badge {{ $badgeRingkas }}">{{ $statusRingkas }}</span>
                            @if($excused && $excused->keterangan)
                                <div style="font-size: 7px; color: #64748b; margin-top: 2px;">{{ Str::limit($excused->keterangan, 30) }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center" style="padding: 15px; color: #64748b;">
                            Tidak ada data siswa ditemukan untuk kriteria ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- TANDA TANGAN / PENGESAHAN (4 KOLOM STANDAR: GURU PIKET I & II, WAKA KESISWAAN, KEPALA SEKOLAH) -->
    <table class="sign-table">
        <tr>
            @if(!empty($guruPiket2) || !empty($guruPiket1))
                <td style="width: 25%;">
                    Mengetahui,<br>
                    <strong>Guru Piket I</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $guruPiket1->name ?? $guruPiket->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $guruPiket1->nip ?? $guruPiket->nip ?? '-' }}</span>
                </td>
                <td style="width: 25%;">
                    Mengetahui,<br>
                    <strong>Guru Piket II</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $guruPiket2->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $guruPiket2->nip ?? '-' }}</span>
                </td>
                <td style="width: 25%;">
                    Mengetahui / Memeriksa,<br>
                    <strong>Waka Kesiswaan</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $wakaKesiswaan->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $wakaKesiswaan->nip ?? '-' }}</span>
                </td>
                <td style="width: 25%;">
                    Malang, {{ $tanggalTtd ?? $tanggalFormatted }}<br>
                    <strong>Kepala SMP Islam Baitul Makmur</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $kepalaSekolah->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $kepalaSekolah->nip ?? '-' }}</span>
                </td>
            @else
                <td style="width: 33.33%;">
                    Mengetahui,<br>
                    <strong>Guru Piket Harian</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $guruPiket1->name ?? $guruPiket->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $guruPiket1->nip ?? $guruPiket->nip ?? '-' }}</span>
                </td>
                <td style="width: 33.33%;">
                    Mengetahui / Memeriksa,<br>
                    <strong>Waka Kesiswaan</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $wakaKesiswaan->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $wakaKesiswaan->nip ?? '-' }}</span>
                </td>
                <td style="width: 33.33%;">
                    Malang, {{ $tanggalTtd ?? $tanggalFormatted }}<br>
                    <strong>Kepala SMP Islam Baitul Makmur</strong>
                    <div class="sign-space"></div>
                    <strong>{{ $kepalaSekolah->name ?? '....................................' }}</strong><br>
                    <span>NIP / ID: {{ $kepalaSekolah->nip ?? '-' }}</span>
                </td>
            @endif
        </tr>
    </table>

</body>
</html>
