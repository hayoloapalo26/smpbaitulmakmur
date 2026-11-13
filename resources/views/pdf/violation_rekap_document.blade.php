<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle ?? 'Rekapitulasi Pelanggaran Siswa' }}</title>
    <style>
        @page {
            margin: 1.2cm 1cm 1.2cm 1cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .doc-header {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-header h2 {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .doc-header p {
            margin: 2px 0 0;
            font-size: 9px;
            color: #475569;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .meta-table .label {
            width: 15%;
            font-weight: bold;
            color: #334155;
        }
        .meta-table .colon {
            width: 2%;
            text-align: center;
        }
        .meta-table .val {
            width: 33%;
            color: #0f172a;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 12px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            font-size: 8.5px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-primary { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        .rubrik-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8px;
            line-height: 1.4;
        }
        .rubrik-box strong { color: #0f172a; }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            text-align: center;
            font-size: 8.5px;
            padding: 6px 8px;
        }
        .acc-stamp {
            display: inline-block;
            margin: 4px auto;
            padding: 3px 6px;
            border: 1.5px dashed #059669;
            border-radius: 4px;
            background-color: #ecfdf5;
            color: #047857;
            font-size: 7.5px;
            font-weight: bold;
            line-height: 1.25;
        }
        .acc-stamp-pending {
            display: inline-block;
            margin: 4px auto;
            padding: 3px 6px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            background-color: #f8fafc;
            color: #64748b;
            font-size: 7.5px;
            line-height: 1.25;
        }
    </style>
</head>
<body>

    @include('pdf.kop_surat', ['institutionName' => $institutionName ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-header">
        <h2>{{ strtoupper($docTitle ?? 'REKAPITULASI SKOR POIN PELANGGARAN') }}</h2>
        <p>{{ $subTitle ?? 'Lembaga Pendidikan Islam Terpadu Baitul Makmur Malang' }}</p>
    </div>

    <!-- Metadata Informasi Dokumen -->
    <table class="meta-table">
        <tr>
            <td class="label">Kelas / Rombel</td>
            <td class="colon">:</td>
            <td class="val"><strong style="color: #047857; font-size: 10px;">{{ $classroom ? $classroom->name : 'Seluruh Kelas' }}</strong></td>

            <td class="label">Periode Rekap</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $periodeLabel ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Wali Kelas</td>
            <td class="colon">:</td>
            <td class="val">{{ $classroom?->waliKelas?->name ?? 'Belum Ditentukan' }}</td>

            <td class="label">Tanggal Cetak</td>
            <td class="colon">:</td>
            <td class="val">{{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
    </table>

    <!-- ==================== TABEL HARIAN ==================== -->
    @if($tab === 'harian')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 8%;">Waktu (Jam)</th>
                    <th style="width: 18%;">Nama Murid</th>
                    <th style="width: 10%;">Kelas</th>
                    <th style="width: 26%;">Bentuk / Deskripsi Pelanggaran</th>
                    <th style="width: 6%;">Poin</th>
                    <th style="width: 14%;">Nama Guru Pencatat</th>
                    <th style="width: 14%;">Tindak Lanjut Langsung</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center font-bold">{{ $row->jam ?? '-' }}</td>
                        <td><strong>{{ $row->student->name ?? '-' }}</strong></td>
                        <td class="text-center">{{ $row->classroom->name ?? ($row->student->classroom->name ?? '-') }}</td>
                        <td>{{ $row->bentuk_pelanggaran }}</td>
                        <td class="text-center"><span class="badge badge-danger">+{{ $row->poin }}</span></td>
                        <td>{{ $row->teacher->name ?? '-' }}</td>
                        <td>{{ $row->tindak_lanjut_langsung ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 14px; color: #64748b;">
                            Tidak ada catatan pelanggaran siswa pada tanggal ini. Kedisiplinan siswa terpantau sangat baik.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    <!-- ==================== TABEL MINGGUAN ==================== -->
    @elseif($tab === 'mingguan')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Nama Murid</th>
                    <th style="width: 10%;">Kelas</th>
                    <th style="width: 24%;">Jenis Pelanggaran yang Dilakukan</th>
                    <th style="width: 8%;">Akumulasi Poin</th>
                    <th style="width: 17%;">Catatan Perkembangan (Wali Kelas)</th>
                    <th style="width: 17%;">Tindak Lanjut Minggu Ini (Guru Piket)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['student']->name }}</strong><br>
                            <span style="font-size: 7.5px; color: #64748b;">NIS: {{ $row['student']->nis ?? '-' }}</span>
                        </td>
                        <td class="text-center">{{ $row['student']->classroom->name ?? '-' }}</td>
                        <td>
                            @if(!empty($row['jenis_pelanggaran']) && $row['jenis_pelanggaran'] !== '-')
                                <ul style="margin: 0; padding-left: 12px;">
                                    @foreach(explode(' | ', $row['jenis_pelanggaran']) as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span style="color: #059669; font-style: italic;">Tidak ada pelanggaran (Nihil)</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row['total_poin'] > 0)
                                <span class="badge badge-danger">+{{ $row['total_poin'] }} Poin</span>
                            @else
                                <span class="badge badge-success">0 Poin</span>
                            @endif
                        </td>
                        <td>{{ $row['catatan_wali_kelas'] ?? '-' }}</td>
                        <td>{{ $row['tindak_lanjut_piket'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 14px; color: #64748b;">
                            Tidak ada data siswa untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    <!-- ==================== TABEL BULANAN ==================== -->
    @elseif($tab === 'bulanan')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Nama Murid</th>
                    <th style="width: 9%;">Kelas</th>
                    <th style="width: 8%;">Poin Bulan Lalu</th>
                    <th style="width: 8%;">Poin Bulan Ini</th>
                    <th style="width: 9%;">Total Akumulasi</th>
                    <th style="width: 21%;">Kategori / Status Sanksi (BK)</th>
                    <th style="width: 21%;">Tindak Lanjut / Agenda Pembinaan (BK)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['student']->name }}</strong><br>
                            <span style="font-size: 7.5px; color: #64748b;">NIS: {{ $row['student']->nis ?? '-' }}</span>
                        </td>
                        <td class="text-center">{{ $row['student']->classroom->name ?? '-' }}</td>
                        <td class="text-center font-bold">{{ $row['poin_bulan_lalu'] }}</td>
                        <td class="text-center font-bold {{ $row['poin_bulan_ini'] > 0 ? 'text-danger' : '' }}">{{ $row['poin_bulan_ini'] }}</td>
                        <td class="text-center">
                            @if($row['total_akumulasi'] > 0)
                                <span class="badge badge-danger">+{{ $row['total_akumulasi'] }} Poin</span>
                            @else
                                <span class="badge badge-success">0 Poin</span>
                            @endif
                        </td>
                        <td>{{ $row['status_sanksi_bk'] ?? '-' }}</td>
                        <td>{{ $row['tindak_lanjut_bk'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 14px; color: #64748b;">
                            Tidak ada data siswa untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    <!-- ==================== TABEL TRIWULAN (3 BULAN) ==================== -->
    @elseif($tab === 'triwulan')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%;">Nama Siswa</th>
                    <th style="width: 8%;">Kelas</th>
                    <th style="width: 7%;">Poin {{ $bulanHeaders[0] ?? 'Bulan 1' }}</th>
                    <th style="width: 7%;">Poin {{ $bulanHeaders[1] ?? 'Bulan 2' }}</th>
                    <th style="width: 7%;">Poin {{ $bulanHeaders[2] ?? 'Bulan 3' }}</th>
                    <th style="width: 8%;">Total Poin 3 Bulan</th>
                    <th style="width: 20%;">Status Peringatan / Sanksi (Waka Kesiswaan)</th>
                    <th style="width: 21%;">Tindak Lanjut & Proses Pembinaan (Waka Kesiswaan)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['student']->name }}</strong><br>
                            <span style="font-size: 7.5px; color: #64748b;">NIS: {{ $row['student']->nis ?? '-' }}</span>
                        </td>
                        <td class="text-center">{{ $row['student']->classroom->name ?? '-' }}</td>
                        <td class="text-center">{{ $row['poin_bulan_1'] }}</td>
                        <td class="text-center">{{ $row['poin_bulan_2'] }}</td>
                        <td class="text-center">{{ $row['poin_bulan_3'] }}</td>
                        <td class="text-center">
                            @if($row['total_3_bulan'] > 0)
                                <span class="badge badge-danger">+{{ $row['total_3_bulan'] }}</span>
                            @else
                                <span class="badge badge-success">0</span>
                            @endif
                        </td>
                        <td>{{ $row['status_sanksi_kesiswaan'] ?? '-' }}</td>
                        <td>{{ $row['tindak_lanjut_kesiswaan'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 14px; color: #64748b;">
                            Tidak ada data siswa untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    <!-- ==================== TABEL SEMESTER (6 BULAN) ==================== -->
    @elseif($tab === 'semester')
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 16%;">Nama Siswa</th>
                    <th style="width: 6%;">Kelas</th>
                    <th style="width: 5%;">{{ $bulanHeaders[0] ?? 'Bln 1' }}</th>
                    <th style="width: 5%;">{{ $bulanHeaders[1] ?? 'Bln 2' }}</th>
                    <th style="width: 5%;">{{ $bulanHeaders[2] ?? 'Bln 3' }}</th>
                    <th style="width: 5%;">{{ $bulanHeaders[3] ?? 'Bln 4' }}</th>
                    <th style="width: 5%;">{{ $bulanHeaders[4] ?? 'Bln 5' }}</th>
                    <th style="width: 5%;">{{ $bulanHeaders[5] ?? 'Bln 6' }}</th>
                    <th style="width: 7%;">Total Poin</th>
                    <th style="width: 10%;">Predikat Sikap</th>
                    <th style="width: 28%;">Catatan Kepribadian Rapor (Wali Kelas)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['student']->name }}</strong><br>
                            <span style="font-size: 7.5px; color: #64748b;">NIS: {{ $row['student']->nis ?? '-' }}</span>
                        </td>
                        <td class="text-center">{{ $row['student']->classroom->name ?? '-' }}</td>
                        <td class="text-center">{{ $row['poin_m1'] }}</td>
                        <td class="text-center">{{ $row['poin_m2'] }}</td>
                        <td class="text-center">{{ $row['poin_m3'] }}</td>
                        <td class="text-center">{{ $row['poin_m4'] }}</td>
                        <td class="text-center">{{ $row['poin_m5'] }}</td>
                        <td class="text-center">{{ $row['poin_m6'] }}</td>
                        <td class="text-center font-bold">
                            @if($row['total_semester'] > 0)
                                <span class="badge badge-danger">+{{ $row['total_semester'] }}</span>
                            @else
                                <span class="badge badge-success">0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $pred = $row['predikat']; @endphp
                            <span class="badge {{ $pred['predikat'] === 'A' || $pred['predikat'] === 'A-' ? 'badge-success' : ($pred['predikat'] === 'B+' || $pred['predikat'] === 'B' || $pred['predikat'] === 'B-' ? 'badge-primary' : ($pred['predikat'] === 'C+' || $pred['predikat'] === 'C' || $pred['predikat'] === 'C-' ? 'badge-warning' : 'badge-danger')) }}">
                                {{ $pred['predikat'] }} ({{ $pred['label'] }})
                            </span>
                        </td>
                        <td>{{ $row['catatan_wali_kelas'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center" style="padding: 14px; color: #64748b;">
                            Tidak ada data siswa untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Rubrik Indikator Nilai Sikap -->
        <div class="rubrik-box">
            <strong>Indikator Penilaian Predikat Sikap Berdasarkan Akumulasi Poin Pelanggaran:</strong><br>
            <span><strong>A</strong> (0 - 5 Poin)</span> &bull;
            <span><strong>A-</strong> (6 - 15 Poin)</span> &bull;
            <span><strong>B+</strong> (16 - 30 Poin)</span> &bull;
            <span><strong>B</strong> (31 - 45 Poin)</span> &bull;
            <span><strong>B-</strong> (46 - 60 Poin)</span> &bull;
            <span><strong>C+</strong> (61 - 85 Poin)</span> &bull;
            <span><strong>C</strong> (86 - 105 Poin)</span> &bull;
            <span><strong>C-</strong> (106 - 120 Poin)</span> &bull;
            <span><strong>D</strong> (121 - 140 Poin)</span> &bull;
            <span><strong>E</strong> (141 - 150+ Poin)</span>
        </div>
    @endif

    <!-- ==================== LEMBAR TANDA TANGAN ACC ==================== -->
    <div style="margin-top: 10px; page-break-inside: avoid;">
        <table class="signature-table">
            <!-- TAB HARIAN: ACC GURU PIKET -->
            @if($tab === 'harian')
                <tr>
                    <td style="width: 50%;"></td>
                    <td style="width: 50%;">
                        Malang, {{ \Carbon\Carbon::parse($selectedTanggal ?? now())->translatedFormat('d F Y') }}<br>
                        Guru Piket yang Bertugas,<br>
                        @if($approval && $approval->acc_piket)
                            <div class="acc-stamp">
                                &#x2705; TELAH DI-ACC RESMI OLEH GURU PIKET<br>
                                Pada: {{ $approval->acc_piket_at?->translatedFormat('d F Y, H:i') }} WIB<br>
                                ID Petugas: {{ $approval->piketUser?->id ?? '-' }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">
                                [ BELUM DI-ACC GURU PIKET ]
                            </div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->piketUser?->name ?? ($currentUser?->name ?? 'Guru Piket') }}</u></strong><br>
                        NIP/NIY: {{ $approval?->piketUser?->nip ?? ($currentUser?->nip ?? '-') }}
                    </td>
                </tr>

            <!-- TAB MINGGUAN: ACC WALI KELAS -->
            @elseif($tab === 'mingguan')
                <tr>
                    <td style="width: 50%;"></td>
                    <td style="width: 50%;">
                        Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                        Wali Kelas,<br>
                        @if($approval && $approval->acc_wali_kelas)
                            <div class="acc-stamp">
                                &#x2705; TELAH DI-ACC OLEH WALI KELAS<br>
                                Pada: {{ $approval->acc_wali_kelas_at?->translatedFormat('d F Y, H:i') }} WIB
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">
                                [ BELUM DI-ACC WALI KELAS ]
                            </div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->waliKelasUser?->name ?? ($classroom?->waliKelas?->name ?? 'Wali Kelas') }}</u></strong><br>
                        NIP/NIY: {{ $approval?->waliKelasUser?->nip ?? ($classroom?->waliKelas?->nip ?? '-') }}
                    </td>
                </tr>

            <!-- TAB BULANAN: ACC BK & WALI KELAS -->
            @elseif($tab === 'bulanan')
                <tr>
                    <td style="width: 50%;">
                        Mengetahui,<br>
                        Guru Bimbingan Konseling (BK),<br>
                        @if($approval && $approval->acc_bk)
                            <div class="acc-stamp">
                                &#x2705; TELAH DI-ACC OLEH GURU BK<br>
                                Pada: {{ $approval->acc_bk_at?->translatedFormat('d F Y, H:i') }} WIB
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">
                                [ BELUM DI-ACC BK ]
                            </div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->bkUser?->name ?? ($defaultBk?->name ?? 'Guru Bimbingan Konseling') }}</u></strong><br>
                        NIP/NIY: {{ $approval?->bkUser?->nip ?? ($defaultBk?->nip ?? '-') }}
                    </td>
                    <td style="width: 50%;">
                        Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                        Wali Kelas,<br>
                        @if($approval && $approval->acc_wali_kelas)
                            <div class="acc-stamp">
                                &#x2705; TELAH DI-ACC OLEH WALI KELAS<br>
                                Pada: {{ $approval->acc_wali_kelas_at?->translatedFormat('d F Y, H:i') }} WIB
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">
                                [ BELUM DI-ACC WALI KELAS ]
                            </div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->waliKelasUser?->name ?? ($classroom?->waliKelas?->name ?? 'Wali Kelas') }}</u></strong><br>
                        NIP/NIY: {{ $approval?->waliKelasUser?->nip ?? ($classroom?->waliKelas?->nip ?? '-') }}
                    </td>
                </tr>

            <!-- TAB TRIWULAN: ACC BK, WALI KELAS, & WAKA KESISWAAN -->
            @elseif($tab === 'triwulan')
                <tr>
                    <td style="width: 33%;">
                        Wali Kelas,<br>
                        @if($approval && $approval->acc_wali_kelas)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC WALI KELAS<br>
                                {{ $approval->acc_wali_kelas_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->waliKelasUser?->name ?? ($classroom?->waliKelas?->name ?? 'Wali Kelas') }}</u></strong><br>
                        NIP: {{ $approval?->waliKelasUser?->nip ?? ($classroom?->waliKelas?->nip ?? '-') }}
                    </td>
                    <td style="width: 34%;">
                        Guru Bimbingan Konseling (BK),<br>
                        @if($approval && $approval->acc_bk)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC GURU BK<br>
                                {{ $approval->acc_bk_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->bkUser?->name ?? ($defaultBk?->name ?? 'Guru BK') }}</u></strong><br>
                        NIP: {{ $approval?->bkUser?->nip ?? ($defaultBk?->nip ?? '-') }}
                    </td>
                    <td style="width: 33%;">
                        Waka Kesiswaan,<br>
                        @if($approval && $approval->acc_waka_kesiswaan)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC WAKA KESISWAAN<br>
                                {{ $approval->acc_waka_kesiswaan_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->kesiswaanUser?->name ?? ($defaultKesiswaan?->name ?? 'Waka Kesiswaan') }}</u></strong><br>
                        NIP: {{ $approval?->kesiswaanUser?->nip ?? ($defaultKesiswaan?->nip ?? '-') }}
                    </td>
                </tr>

            <!-- TAB SEMESTER: ACC WALI KELAS, BK, WAKA KESISWAAN, & KEPALA SEKOLAH -->
            @elseif($tab === 'semester')
                <tr>
                    <td style="width: 25%;">
                        Wali Kelas,<br>
                        @if($approval && $approval->acc_wali_kelas)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC WALI KELAS<br>
                                {{ $approval->acc_wali_kelas_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->waliKelasUser?->name ?? ($classroom?->waliKelas?->name ?? 'Wali Kelas') }}</u></strong><br>
                        NIP: {{ $approval?->waliKelasUser?->nip ?? ($classroom?->waliKelas?->nip ?? '-') }}
                    </td>
                    <td style="width: 25%;">
                        Guru BK,<br>
                        @if($approval && $approval->acc_bk)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC GURU BK<br>
                                {{ $approval->acc_bk_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->bkUser?->name ?? ($defaultBk?->name ?? 'Guru BK') }}</u></strong><br>
                        NIP: {{ $approval?->bkUser?->nip ?? ($defaultBk?->nip ?? '-') }}
                    </td>
                    <td style="width: 25%;">
                        Waka Kesiswaan,<br>
                        @if($approval && $approval->acc_waka_kesiswaan)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC KESISWAAN<br>
                                {{ $approval->acc_waka_kesiswaan_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->kesiswaanUser?->name ?? ($defaultKesiswaan?->name ?? 'Waka Kesiswaan') }}</u></strong><br>
                        NIP: {{ $approval?->kesiswaanUser?->nip ?? ($defaultKesiswaan?->nip ?? '-') }}
                    </td>
                    <td style="width: 25%;">
                        Mengetahui,<br>
                        Kepala Sekolah,<br>
                        @if($approval && $approval->acc_kepala_sekolah)
                            <div class="acc-stamp">
                                &#x2705; DI-ACC KEPALA SEKOLAH<br>
                                {{ $approval->acc_kepala_sekolah_at?->format('d/m/Y') }}
                            </div><br>
                        @else
                            <div class="acc-stamp-pending">[ BELUM DI-ACC ]</div><br><br><br>
                        @endif
                        <strong><u>{{ $approval?->kepsekUser?->name ?? ($defaultKepsek?->name ?? 'Kepala Sekolah') }}</u></strong><br>
                        NIP: {{ $approval?->kepsekUser?->nip ?? ($defaultKepsek?->nip ?? '-') }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

</body>
</html>
