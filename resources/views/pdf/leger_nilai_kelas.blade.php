<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leger Nilai Rapor - {{ $leger['classroom']->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            line-height: 1.25;
            color: #111827;
            margin: 0;
            padding: 0;
        }
        .header-kop {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header-kop h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header-kop p {
            font-size: 7.5pt;
            margin: 1px 0 0;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            margin: 6px 0 8px 0;
        }
        .doc-title h3 {
            font-size: 9.5pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 7.5pt;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1px 4px;
        }
        .leger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
        }
        .leger-table th, .leger-table td {
            border: 1px solid #374151;
            padding: 3px 2px;
            vertical-align: middle;
            text-align: center;
        }
        .leger-table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        .bg-blue { background-color: #eff6ff; }
        .bg-green { background-color: #f0fdf4; }
        .bg-amber { background-color: #fffbeb; }
        
        .sign-table {
            width: 100%;
            margin-top: 15px;
            font-size: 8pt;
            border-collapse: collapse;
        }
        .sign-table td {
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .sign-space {
            height: 45px;
        }
    </style>
</head>
<body>
    @php
        $cls = $leger['classroom'];
        $instName = $cls->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR MALANG';
    @endphp

    <div class="header-kop">
        <h2>{{ $instName }}</h2>
        <p>Jl. Sawojajar No. 17 B, Kec. Kedungkandang, Kota Malang, Jawa Timur</p>
    </div>

    <div class="doc-title">
        <h3>LEGER REKAPITULASI NILAI RAPOR SISWA (FORMAT RESMI)</h3>
        @if(!empty($leger['mode']) && $leger['mode'] === 'asli')
            <p style="font-size: 7.5pt; color: #b91c1c; font-weight: bold; margin: 2px 0 0 0; text-transform: uppercase;">
                [ ARSIP REKAPITULASI: NILAI ASLI MURNI DARI INPUT GURU MAPEL ]
            </p>
        @else
            <p style="font-size: 7.5pt; color: #1e3a8a; font-weight: bold; margin: 2px 0 0 0; text-transform: uppercase;">
                [ DOKUMEN RESMI: MENGGUNAKAN NILAI RAPOR OLAHAN / BANTUAN ]
            </p>
        @endif
    </div>

    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Kelas / Rombel</strong></td>
            <td width="35%">: {{ $cls->name }} (Tingkat {{ $cls->tingkat ?? '-' }})</td>
            <td width="15%"><strong>Tahun Pelajaran</strong></td>
            <td width="35%">: {{ $leger['academic_year']?->name ?? '2026/2027' }} (Semester {{ $leger['semester'] }})</td>
        </tr>
        <tr>
            <td><strong>Wali Kelas</strong></td>
            <td>: {{ $cls->waliKelas?->name ?? '-' }}</td>
            <td><strong>Tanggal Cetak</strong></td>
            <td>: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="leger-table">
        <thead>
            <tr>
                <th rowspan="3" width="2%">No</th>
                <th rowspan="3" width="14%" class="text-left">Nama Siswa</th>
                <th rowspan="3" width="5%">NIS</th>

                @foreach($leger['subjects'] as $sub)
                    <th colspan="7" class="bg-blue" style="font-size: 6.5pt;">{{ $sub->name }}</th>
                @endforeach

                <th colspan="3" class="bg-green">Absen</th>
                <th rowspan="3" width="5%" class="bg-amber">JML</th>
                <th rowspan="3" width="4%" class="bg-amber">RANK</th>
            </tr>
            <tr>
                @foreach($leger['subjects'] as $sub)
                    <th colspan="4" class="bg-blue" style="font-size: 6pt;">Pengetahuan (KI-3)</th>
                    <th colspan="2" class="bg-green" style="font-size: 6pt;">Keterampilan (KI-4)</th>
                    <th rowspan="2" style="font-size: 6pt;">Sikap</th>
                @endforeach
                <th rowspan="2" width="2%">S</th>
                <th rowspan="2" width="2%">I</th>
                <th rowspan="2" width="2%">A</th>
            </tr>
            <tr>
                @foreach($leger['subjects'] as $sub)
                    <th style="font-size: 5.5pt;">RT</th>
                    <th style="font-size: 5.5pt;">UTS</th>
                    <th style="font-size: 5.5pt;" class="font-bold">NA</th>
                    <th style="font-size: 5.5pt;">Pred</th>

                    <th style="font-size: 5.5pt;" class="font-bold">NA</th>
                    <th style="font-size: 5.5pt;">Pred</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($leger['rows'] as $idx => $row)
                @php
                    $st = $row['student'];
                    $subMap = collect($row['subjects'])->keyBy('subject.id');
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="text-left font-bold" style="white-space: nowrap;">{{ $st->name }}</td>
                    <td class="font-mono">{{ $st->nis }}</td>

                    @foreach($leger['subjects'] as $sub)
                        @php
                            $sData = $subMap->get($sub->id);
                            $p = $sData['pengetahuan'] ?? [];
                            $k = $sData['keterampilan'] ?? [];
                            $skp = $sData['sikap'] ?? [];
                        @endphp
                        {{-- Pengetahuan --}}
                        <td>{{ $p['rt_nh'] !== null ? $p['rt_nh'] : '-' }}</td>
                        <td>{{ $p['uts'] !== null ? $p['uts'] : '-' }}</td>
                        <td class="font-bold bg-blue">{{ $p['na'] !== null ? $p['na'] : '-' }}</td>
                        <td class="font-bold">{{ $p['predikat'] ?: '-' }}</td>

                        {{-- Keterampilan --}}
                        <td class="font-bold bg-green">{{ $k['na'] !== null ? $k['na'] : '-' }}</td>
                        <td class="font-bold">{{ $k['predikat'] ?: '-' }}</td>

                        {{-- Sikap --}}
                        <td>{{ $skp['predikat'] ?? 'B' }}</td>
                    @endforeach

                    {{-- Absen --}}
                    <td>{{ $row['attendance']['sakit'] }}</td>
                    <td>{{ $row['attendance']['izin'] }}</td>
                    <td>{{ $row['attendance']['alpa'] }}</td>

                    {{-- Total & Rank --}}
                    <td class="font-bold bg-amber">{{ number_format($row['total_na'], 1) }}</td>
                    <td class="font-bold bg-amber">{{ $row['class_rank'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="50" style="padding: 10px;">Belum ada data nilai pada kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign-table">
        <tr>
            <td width="50%">
                Mengetahui,<br>
                Kepala Sekolah
                <div class="sign-space"></div>
                <strong>( {{ $cls->waliKelas?->name ?? 'Kepala Sekolah' }} )</strong>
            </td>
            <td width="50%">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas {{ $cls->name }}
                <div class="sign-space"></div>
                <strong>( {{ $cls->waliKelas?->name ?? 'Wali Kelas' }} )</strong>
            </td>
        </tr>
    </table>
</body>
</html>
