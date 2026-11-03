<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Beban & Kehadiran Jam Mengajar Guru - {{ $namaBulan }}</title>
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
        .th-blue {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-semibold { font-weight: 600; }
        .text-green { color: #15803d; }
        .text-cyan { color: #0e7490; }
        .text-blue { color: #1d4ed8; }
        .text-amber { color: #b45309; }
        .text-rose { color: #be123c; }
        .text-red { color: #b91c1c; }
        .text-purple { color: #7e22ce; }
        .bg-light-blue { background-color: #f0f9ff; }
        .bg-subtle-blue { background-color: #eff6ff; }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
        }
        .badge-green { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-amber { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-red { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .notes-box {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.35;
            margin-top: 6px;
            padding: 4px 8px;
            background-color: #f8fafc;
            border-left: 3px solid #047857;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 8.5px;
            text-align: center;
            width: 50%;
            border: none;
        }
        .sign-space {
            height: 48px;
        }
    </style>
</head>
<body>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $size = 7.5;
            $font = $fontMetrics->getFont("Arial, Helvetica, sans-serif");
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $width - 28, $pdf->get_height() - 22, $text, $font, $size, [0.35, 0.35, 0.35]);
            
            $printedAt = "Dicetak melalui SIM Baitul Makmur: " . date('d/m/Y H:i') . " WIB";
            $pdf->page_text(28, $pdf->get_height() - 22, $printedAt, $font, $size, [0.35, 0.35, 0.35]);
        }
    </script>

    @include('pdf.kop_surat', ['institutionName' => 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">LAPORAN REKAPITULASI BEBAN & KEHADIRAN JAM MENGAJAR GURU BULANAN</div>
        <div class="doc-title-sub">
            Periode: <strong>{{ $namaBulan }}</strong> &bull; Total Hari Efektif Belajar: <strong>{{ $totalHariAktif }} Hari</strong>
        </div>
    </div>

    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 18%;" class="text-left">Nama Guru & NIP</th>
                <th style="width: 6%;">Beban Mgg</th>
                <th style="width: 9%;" class="th-primary">Tanggung Jawab Bulan Ini</th>
                <th style="width: 7%;">Hadir Mandiri</th>
                <th style="width: 6%;">Tugas Inval</th>
                <th style="width: 8%;" class="th-blue">Total Hadir Mengajar</th>
                <th style="width: 5%;">Izin</th>
                <th style="width: 5%;">Sakit</th>
                <th style="width: 5%;">Alpa</th>
                <th style="width: 6%;">Dinas Luar</th>
                <th style="width: 6%;">Selisih</th>
                <th style="width: 6%;">% Capaian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportData as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">{{ $row['name'] }}</div>
                        <div style="font-size: 7px; color: #64748b;">NIP/NUPTK: {{ $row['nip'] }}</div>
                    </td>
                    <td class="text-center">{{ $row['weekly_jp'] }} JP</td>
                    <td class="text-center font-bold bg-light-blue" style="color: #0369a1;">
                        {{ $row['monthly_target_jp'] }} JP
                    </td>
                    <td class="text-center text-green font-semibold">{{ $row['hadir_jp'] }} JP</td>
                    <td class="text-center text-cyan font-semibold">{{ $row['inval_jp'] }} JP</td>
                    <td class="text-center font-bold bg-subtle-blue" style="color: #1d4ed8;">
                        {{ $row['total_kehadiran_jp'] }} JP
                    </td>
                    <td class="text-center text-amber">{{ $row['izin_jp'] }} JP</td>
                    <td class="text-center text-rose">{{ $row['sakit_jp'] }} JP</td>
                    <td class="text-center text-red">{{ $row['alpa_jp'] }} JP</td>
                    <td class="text-center text-purple">{{ $row['dinas_jp'] }} JP</td>
                    <td class="text-center font-bold {{ $row['selisih_jp'] >= 0 ? 'text-green' : 'text-red' }}">
                        {{ $row['selisih_jp'] > 0 ? '+' : '' }}{{ $row['selisih_jp'] }} JP
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $row['persen'] >= 90 ? 'badge-green' : ($row['persen'] >= 75 ? 'badge-amber' : 'badge-red') }}">
                            {{ $row['persen'] }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center" style="padding: 12px; color: #64748b;">
                        Belum ada data beban mengajar atau presensi pada periode bulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #475569;">
                <td colspan="2" class="text-center" style="font-weight: 800; text-transform: uppercase;">
                    Total Keseluruhan (Sekolah)
                </td>
                <td class="text-center">{{ $totals['weekly_jp'] }} JP</td>
                <td class="text-center text-blue font-bold">{{ $totals['monthly_target_jp'] }} JP</td>
                <td class="text-center text-green">{{ $totals['hadir_jp'] }} JP</td>
                <td class="text-center text-cyan">{{ $totals['inval_jp'] }} JP</td>
                <td class="text-center text-blue font-bold">{{ $totals['total_kehadiran_jp'] }} JP</td>
                <td class="text-center text-amber">{{ $totals['izin_jp'] }} JP</td>
                <td class="text-center text-rose">{{ $totals['sakit_jp'] }} JP</td>
                <td class="text-center text-red">{{ $totals['alpa_jp'] }} JP</td>
                <td class="text-center text-purple">{{ $totals['dinas_jp'] }} JP</td>
                <td class="text-center font-bold {{ $totals['selisih_jp'] >= 0 ? 'text-green' : 'text-red' }}">
                    {{ $totals['selisih_jp'] > 0 ? '+' : '' }}{{ $totals['selisih_jp'] }} JP
                </td>
                <td class="text-center font-bold text-blue">
                    {{ $avgPersen }}%
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="notes-box">
        <strong>Catatan Pedoman Perhitungan:</strong><br>
        1. <strong>Tanggung Jawab Bulan Ini:</strong> Akumulasi jam wajib mengajar (JP) yang dihitung berdasarkan jumlah pertemuan hari efektif kalender aktif sekolah dalam bulan bersangkutan.<br>
        2. <strong>Total Hadir Mengajar:</strong> Jam Tatap Muka Mandiri di kelas ditambah dengan jam tugas menggantikan guru lain (Inval).<br>
        3. <strong>Selisih Jam:</strong> Total Hadir Mengajar dikurangi Tanggung Jawab Bulan Ini. Tanda minus (-) menandakan kewajiban jam mengajar yang belum terpenuhi.
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div style="color: #64748b;">Mengetahui,</div>
                <div style="font-weight: bold; color: #0f172a; margin-top: 2px;">Kepala Sekolah SMPIT Baitul Makmur</div>
                <div class="sign-space"></div>
                <div style="font-weight: bold; text-decoration: underline; color: #0f172a;">Drs. Syamsul Arifin, M.Ed</div>
                <div style="font-size: 7.5px; color: #64748b;">NIP. 19750815 200501 1 002</div>
            </td>
            <td>
                <div style="color: #64748b;">Penanggung Jawab Kurikulum,</div>
                <div style="font-weight: bold; color: #0f172a; margin-top: 2px;">Waka Kurikulum</div>
                <div class="sign-space"></div>
                <div style="font-weight: bold; text-decoration: underline; color: #0f172a;">Nadila Choirunnisa' Intsani, M.Pd</div>
                <div style="font-size: 7.5px; color: #64748b;">NUPTK. 8452763665210032</div>
            </td>
        </tr>
    </table>

</body>
</html>
