<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Rapor - {{ $rapor['student']->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #111827;
            margin: 0;
            padding: 0;
        }
        .header-kop {
            text-align: center;
            border-bottom: 2.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-kop h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-kop h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .header-kop p {
            font-size: 8pt;
            margin: 0;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            margin: 10px 0 12px 0;
        }
        .doc-title h3 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .identity-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .identity-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        .main-table th, .main-table td {
            border: 1px solid #1f2937;
            padding: 4px 5px;
            vertical-align: middle;
        }
        .main-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .sub-table-grid {
            width: 100%;
            margin-bottom: 12px;
        }
        .page-break {
            page-break-before: always;
        }
        .sign-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .sign-table td {
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .sign-space {
            height: 55px;
        }
    </style>
</head>
<body>
    @php
        $st = $rapor['student'];
        $instName = $st->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR MALANG';
    @endphp

    {{-- KOP SEKOLAH --}}
    <div class="header-kop">
        <h1>YAYASAN PENDIDIKAN ISLAM BAITUL MAKMUR</h1>
        <h2>{{ $instName }}</h2>
        <p>Jl. Sawojajar No. 17 B, Kec. Kedungkandang, Kota Malang, Jawa Timur 65138</p>
    </div>

    <div class="doc-title">
        <h3>LAPORAN HASIL PENILAIAN CAPAIAN KOMPETENSI PESERTA DIDIK</h3>
        @if(!empty($rapor['mode']) && $rapor['mode'] === 'asli')
            <p style="font-size: 8pt; color: #b91c1c; font-weight: bold; margin: 2px 0 0 0; text-transform: uppercase;">
                [ ARSIP REKAPITULASI: NILAI ASLI MURNI DARI INPUT GURU MAPEL ]
            </p>
        @endif
    </div>

    {{-- IDENTITAS SISWA (Persis Format Sheet 1 Excel) --}}
    <table class="identity-table">
        <tr>
            <td width="20%">Nama Sekolah</td>
            <td width="2%">:</td>
            <td width="38%"><strong>{{ $instName }}</strong></td>
            <td width="18%">Nama Siswa</td>
            <td width="2%">:</td>
            <td width="20%"><strong>{{ $st->name }}</strong></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>Jl. Sawojajar 17 B Malang</td>
            <td>Nomor Induk (NIS)</td>
            <td>:</td>
            <td>{{ $st->nis }}</td>
        </tr>
        <tr>
            <td>Jenjang / Program</td>
            <td>:</td>
            <td>{{ $st->classroom?->name ?? 'Reguler' }}</td>
            <td>NISN</td>
            <td>:</td>
            <td>{{ $st->nisn ?: '-' }}</td>
        </tr>
        <tr>
            <td>Kelas / Semester</td>
            <td>:</td>
            <td>{{ $st->classroom?->name ?? '-' }} / {{ $rapor['semester'] }}</td>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td>{{ $rapor['academic_year']?->name ?? '2026/2027' }}</td>
        </tr>
    </table>

    {{-- TABEL CAPAIAN KOMPETENSI (Sheet 1) --}}
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="3" width="4%">NO</th>
                <th rowspan="3" width="30%">MATA PELAJARAN</th>
                <th colspan="4" width="30%">Pengetahuan (KI-3)</th>
                <th colspan="2" width="18%">Keterampilan / Tugas (KI-4)</th>
                <th colspan="2" width="18%">Sikap Sosial & Spiritual</th>
            </tr>
            <tr>
                <th colspan="2">UTS</th>
                <th colspan="2">Nilai Akhir (NA)</th>
                <th rowspan="2">Angka</th>
                <th rowspan="2">Predikat</th>
                <th rowspan="2">Angka</th>
                <th rowspan="2">Predikat</th>
            </tr>
            <tr>
                <th>Angka</th>
                <th>Pred</th>
                <th>Angka</th>
                <th>Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rapor['subjects'] as $s)
                @php
                    $p = $s['pengetahuan'];
                    $k = $s['keterampilan'];
                    $skp = $s['sikap'];
                @endphp
                <tr>
                    <td class="text-center">{{ $s['no'] }}</td>
                    <td class="font-bold">{{ $s['name'] }}</td>
                    
                    {{-- Pengetahuan UTS --}}
                    <td class="text-center">{{ $p['uts'] !== null ? $p['uts'] : '-' }}</td>
                    <td class="text-center">{{ $p['uts_predikat'] ?: '-' }}</td>

                    {{-- Pengetahuan NA --}}
                    <td class="text-center font-bold">{{ $p['na'] !== null ? $p['na'] : '-' }}</td>
                    <td class="text-center font-bold">
                        {{ $p['konversi'] ? number_format($p['konversi'], 2) . ' (' . $p['predikat'] . ')' : '-' }}
                    </td>

                    {{-- Keterampilan --}}
                    <td class="text-center font-bold">{{ $k['na'] !== null ? $k['na'] : '-' }}</td>
                    <td class="text-center font-bold">
                        {{ $k['konversi'] ? number_format($k['konversi'], 2) . ' (' . $k['predikat'] . ')' : '-' }}
                    </td>

                    {{-- Sikap --}}
                    <td class="text-center">{{ $skp['score'] }}</td>
                    <td class="text-center font-bold">{{ $skp['predikat'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- EKSTRAKURIKULER & KETIDAKHADIRAN --}}
    <table class="sub-table-grid" style="border-collapse: collapse;">
        <tr>
            {{-- Ekstrakurikuler --}}
            <td style="width: 55%; vertical-align: top; padding-right: 8px;">
                <table class="main-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th width="8%">No</th>
                            <th width="42%">Kegiatan Ekstrakurikuler</th>
                            <th width="15%">Nilai</th>
                            <th width="35%">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rapor['extracurriculars'] as $eIdx => $e)
                            <tr>
                                <td class="text-center">{{ $eIdx + 1 }}</td>
                                <td class="font-bold">{{ $e['name'] }}</td>
                                <td class="text-center font-bold">{{ $e['nilai'] }}</td>
                                <td style="font-size: 7.5pt;">{{ $e['deskripsi'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">Tidak ada ekstrakurikuler</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            {{-- Ketidakhadiran & KKM --}}
            <td style="width: 45%; vertical-align: top; padding-left: 8px;">
                <table class="main-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th width="10%">No</th>
                            <th width="45%">Ketidakhadiran</th>
                            <th width="20%">Hari</th>
                            <th width="25%">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td>Sakit</td>
                            <td class="text-center font-bold">{{ $rapor['attendance']['sakit'] }}</td>
                            <td rowspan="3" style="font-size: 7pt; vertical-align: top;">
                                Kriteria Ketuntasan Minimal (KKM) adalah <strong>75</strong>.
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">2</td>
                            <td>Ijin</td>
                            <td class="text-center font-bold">{{ $rapor['attendance']['izin'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-center">3</td>
                            <td>Tanpa Keterangan</td>
                            <td class="text-center font-bold">{{ $rapor['attendance']['alpa'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- CATATAN WALI KELAS & PERINGKAT --}}
    <table class="main-table" style="margin-top: 4px;">
        <tr>
            <td width="20%" class="font-bold" style="background-color: #f3f4f6;">Peringkat Kelas</td>
            <td width="80%">
                Peringkat ke-<strong>{{ $rapor['class_rank'] }}</strong> dari <strong>{{ $rapor['total_students'] }}</strong> siswa di kelas dengan Total Nilai: <strong>{{ number_format($rapor['total_na'], 2) }}</strong> (Rata-rata: <strong>{{ number_format($rapor['average_na'], 2) }}</strong>).
            </td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f3f4f6;">Catatan Wali Kelas</td>
            <td style="font-style: italic;">
                "{{ $rapor['catatan_wali_kelas'] }}"
            </td>
        </tr>
    </table>

    {{-- TANDA TANGAN (HALAMAN 1) --}}
    <table class="sign-table">
        <tr>
            <td width="33%">
                Mengetahui,<br>
                Orang Tua / Wali Murid
                <div class="sign-space"></div>
                ( .................................................. )
            </td>
            <td width="33%">
                <br>
                Kepala Sekolah
                <div class="sign-space"></div>
                <strong>( {{ $st->classroom?->waliKelas?->name ?? 'Kepala Sekolah' }} )</strong>
            </td>
            <td width="34%">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas
                <div class="sign-space"></div>
                <strong>( {{ $st->classroom?->waliKelas?->name ?? 'Wali Kelas' }} )</strong>
            </td>
        </tr>
    </table>

    {{-- HALAMAN 2: LEMBAR DESKRIPSI KOMPETENSI (Sheet 3 / Deskripsi Excel) --}}
    <div class="page-break"></div>

    <div class="header-kop">
        <h1>YAYASAN PENDIDIKAN ISLAM BAITUL MAKMUR</h1>
        <h2>{{ $instName }}</h2>
        <p>Jl. Sawojajar No. 17 B, Kec. Kedungkandang, Kota Malang, Jawa Timur 65138</p>
    </div>

    <div class="doc-title">
        <h3>DESKRIPSI CAPAIAN KOMPETENSI PESERTA DIDIK</h3>
    </div>

    <table class="identity-table">
        <tr>
            <td width="15%">Nama Siswa</td>
            <td width="2%">:</td>
            <td width="40%"><strong>{{ $st->name }}</strong></td>
            <td width="15%">Kelas / Sem</td>
            <td width="2%">:</td>
            <td width="26%">{{ $st->classroom?->name }} / {{ $rapor['semester'] }}</td>
        </tr>
        <tr>
            <td>Nomor Induk / NISN</td>
            <td>:</td>
            <td>{{ $st->nis }} / {{ $st->nisn ?: '-' }}</td>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td>{{ $rapor['academic_year']?->name ?? '2026/2027' }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="24%">Mata Pelajaran</th>
                <th width="16%">Aspek Kompetensi</th>
                <th width="56%">Catatan / Deskripsi Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rapor['subjects'] as $s)
                <tr>
                    <td rowspan="3" class="text-center font-bold" style="vertical-align: top;">{{ $s['no'] }}</td>
                    <td rowspan="3" class="font-bold" style="vertical-align: top;">{{ $s['name'] }}</td>
                    <td class="font-bold" style="background-color: #f9fafb;">Pengetahuan</td>
                    <td>{{ $s['pengetahuan']['deskripsi'] }}</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background-color: #f9fafb;">Keterampilan</td>
                    <td>{{ $s['keterampilan']['deskripsi'] }}</td>
                </tr>
                <tr>
                    <td class="font-bold" style="background-color: #f9fafb;">Sosial & Spiritual</td>
                    <td>{{ $s['sikap']['deskripsi'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign-table">
        <tr>
            <td width="50%">
                Mengetahui,<br>
                Orang Tua / Wali Murid
                <div class="sign-space"></div>
                ( .................................................. )
            </td>
            <td width="50%">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas
                <div class="sign-space"></div>
                <strong>( {{ $st->classroom?->waliKelas?->name ?? 'Wali Kelas' }} )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
