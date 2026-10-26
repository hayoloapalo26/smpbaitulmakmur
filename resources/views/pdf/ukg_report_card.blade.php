<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Raport PKG - {{ $ukg->teacher->name ?? 'Guru' }}</title>
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2.5px double #1f2937;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header h3 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
            color: #d97706;
            letter-spacing: 0.5px;
        }
        .header h1 {
            margin: 2px 0;
            font-size: 15px;
            font-weight: 800;
            color: #111827;
        }
        .header h2 {
            margin: 2px 0 0 0;
            font-size: 11.5px;
            font-weight: bold;
            color: #374151;
            text-decoration: underline;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 8.5px;
            color: #4b5563;
        }
        .identity-box {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .identity-box td {
            padding: 2.5px 4px;
            vertical-align: top;
            font-size: 9.5px;
        }
        .identity-label {
            width: 18%;
            font-weight: bold;
            color: #374151;
        }
        .identity-colon {
            width: 2%;
            text-align: center;
        }
        .identity-value {
            width: 30%;
        }
        .score-summary-card {
            width: 100%;
            margin-bottom: 12px;
            border: 1.5px solid #d97706;
            background-color: #fffbeb;
            border-radius: 6px;
            border-collapse: collapse;
        }
        .score-summary-card td {
            padding: 6px 10px;
            text-align: center;
            vertical-align: middle;
        }
        .score-num {
            font-size: 17px;
            font-weight: 800;
            color: #b45309;
        }
        .score-sub {
            font-size: 9px;
            color: #6b7280;
            font-weight: normal;
        }
        .score-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 2px;
        }
        .predicate-badge {
            display: inline-block;
            background-color: #d97706;
            color: #ffffff;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10.5px;
            letter-spacing: 0.3px;
        }
        .section-heading {
            font-size: 10.5px;
            font-weight: bold;
            color: #111827;
            background-color: #f3f4f6;
            padding: 4px 8px;
            border-left: 4px solid #d97706;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .table-data th, .table-data td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
        }
        .table-data th {
            background-color: #f9fafb;
            color: #1f2937;
            font-weight: bold;
            text-align: center;
        }
        .cluster-footer {
            background-color: #fef3c7;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .signatures {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signatures td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            padding: 0 8px;
            font-size: 9px;
        }
        .sig-space {
            height: 48px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .page-break {
            page-break-before: always;
        }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 9.5pt; }
        }
    </style>
</head>
<body>
    @if(request()->has('print'))
        <div class="no-print" style="margin-bottom: 12px; text-align: right;">
            <button onclick="window.print()" style="background-color: #f97316; color: white; border: none; padding: 7px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px;">
                🖨️ Cetak Raport PKG / Simpan PDF
            </button>
        </div>
        <script>
            window.onload = function() {
                setTimeout(function() { window.print(); }, 400);
            };
        </script>
    @endif

    <!-- Header Dokumen -->
    <div class="header">
        <h3>LEMBAGA PENDIDIKAN ISLAM (LPI) BAITUL MAKMUR</h3>
        <h1>TIM PENGEMBANG & PENJAMIN MUTU PENDIDIKAN YAYASAN</h1>
        <h2>RAPOR HASIL PENILAIAN KINERJA GURU (PKG)</h2>
        <p>Instrumen Standar Kinerja Pendidik Berbasis 8 Klaster Kompetensi (70 Indikator Evaluasi)</p>
    </div>

    <!-- Data Identitas Pendidik (Header Form) -->
    <table class="identity-box">
        <tr>
            <td class="identity-label">Nama Lengkap Guru</td>
            <td class="identity-colon">:</td>
            <td class="identity-value"><strong>{{ $ukg->teacher->name ?? '-' }}</strong></td>

            <td class="identity-label">Unit Satuan Pendidikan</td>
            <td class="identity-colon">:</td>
            <td class="identity-value"><strong>{{ $ukg->institution->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="identity-label">Tempat / Tgl Lahir</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">
                {{ $ukg->tempat_lahir ?? '-' }}, 
                {{ $ukg->tanggal_lahir ? $ukg->tanggal_lahir->translatedFormat('d F Y') : '-' }}
            </td>

            <td class="identity-label">Status Kepegawaian</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->status_kepegawaian ?? 'Guru Tetap Yayasan (GTY)' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Nomor Induk Yayasan (NIY)</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->niy ?? '-' }}</td>

            <td class="identity-label">Tgl / Tahun Masuk</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->tanggal_masuk ?? '-' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Mata Pelajaran Diampu</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->mata_pelajaran ?? '-' }}</td>

            <td class="identity-label">Lama Mengajar</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->lama_mengajar ?? '-' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Waktu Pelaksanaan PKG</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->tanggal ? $ukg->tanggal->translatedFormat('l, d F Y') : '-' }}</td>

            <td class="identity-label">Asesor / Penilai</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $ukg->assessor_name ?: ($ukg->assessor->name ?? 'Tim Penjamin Mutu Yayasan') }}</td>
        </tr>
    </table>

    <!-- Ringkasan Nilai Akhir & Predikat -->
    @php
        $calc = $ukg->komponen_penilaian
            ? \App\Models\TeacherCompetencyTest::calculateScoresAndPredicate($ukg->komponen_penilaian)
            : [
                'total_skor' => $ukg->total_skor ?? 0,
                'nilai_akhir' => $ukg->nilai_akhir ?? ($ukg->score ?? 0),
                'predikat' => $ukg->predikat ?? \App\Models\TeacherCompetencyTest::determinePredicate($ukg->nilai_akhir ?? $ukg->score),
            ];
        $totalSkor = $calc['total_skor'];
        $na = $calc['nilai_akhir'];
        $predikat = $calc['predikat'];
        $clusters = \App\Models\TeacherCompetencyTest::getClustersStructure();
        $komponen = $ukg->komponen_penilaian ?: [];
    @endphp

    <table class="score-summary-card">
        <tr>
            <td width="22%">
                <div class="score-title">TOTAL SKOR PEROLEHAN</div>
                <div class="score-num">{{ $totalSkor }} <span class="score-sub">/ 420</span></div>
            </td>
            <td width="26%" style="border-left: 1px dashed #d97706; border-right: 1px dashed #d97706;">
                <div class="score-title">NILAI AKHIR PKG (NA)</div>
                <div class="score-num" style="color: #047857;">{{ number_format($na, 2) }} <span class="score-sub">/ 100</span></div>
                <div style="font-size: 8px; color:#6b7280;">Formula: ({{ $totalSkor }} / 420) × 100</div>
            </td>
            <td width="52%">
                <div class="score-title">PREDIKAT & STATUS KELULUSAN</div>
                <div style="margin-top: 3px;">
                    <span class="predicate-badge">{{ $predikat }}</span>
                </div>
                <div style="font-size: 8.5px; color: #4b5563; margin-top: 3px;">
                    Akumulasi 8 Klaster Standar: A (126) &bull; B (132) &bull; C (30) &bull; D (18) &bull; E (30) &bull; F (42) &bull; G (24) &bull; H (18)
                </div>
            </td>
        </tr>
    </table>

    <!-- FORMAT RAPORT / OUTPUT TABEL REKAPITULASI 8 KLASTER KOMPONEN -->
    <div class="section-heading">
        TABEL REKAPITULASI HASIL KINERJA PENDIDIK PER KLASTER KOMPONEN
    </div>
    <table class="table-data">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="26%">Klaster Komponen Penilaian</th>
                <th width="12%">Skor / Max</th>
                <th width="10%">Nilai (100)</th>
                <th width="16%">Kategori Predikat</th>
                <th width="14%">Profil Capaian</th>
                <th width="18%">Rekomendasi Perbaikan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clusters as $cKey => $cInfo)
                @php
                    $summary = $ukg->getClusterSummary($cKey);
                    $cPredikat = \App\Models\TeacherCompetencyTest::determinePredicate($summary['percentage']);
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $cInfo['code'] }}</td>
                    <td>
                        <strong>{{ $cInfo['name'] }}</strong>
                        <div style="font-size: 7.5px; color: #6b7280;">{{ $cInfo['count'] }} Indikator (Skala 1-6)</div>
                    </td>
                    <td class="text-center font-bold">{{ $summary['score'] }} / {{ $summary['max'] }}</td>
                    <td class="text-center font-bold" style="color: #047857;">{{ number_format($summary['percentage'], 2) }}</td>
                    <td class="text-center">
                        <span style="font-weight: bold;">{{ $cPredikat }}</span>
                    </td>
                    <td>{{ $summary['profile'] }}</td>
                    <td>{{ $summary['rekomendasi'] ?: '-' }}</td>
                </tr>
            @endforeach
            <tr class="cluster-footer">
                <td colspan="2" class="text-right">TOTAL / RATA-RATA KESELURUHAN:</td>
                <td class="text-center">{{ $totalSkor }} / 420</td>
                <td class="text-center" style="color: #047857; font-size: 10px;">{{ number_format($na, 2) }}</td>
                <td class="text-center">{{ $predikat }}</td>
                <td colspan="2">Status: <strong>{{ $na >= 76 ? 'MEMENUHI STANDAR KINERJA (LULUS)' : 'PERLU PEMBINAAN & PENDAMPINGAN' }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($ukg->catatan_umum)
    <div style="border: 1px solid #d1d5db; background-color: #ffffff; border-radius: 4px; padding: 6px 10px; margin-top: 8px;">
        <strong style="color: #92400e; font-size: 9.5px;">Catatan Umum & Rekomendasi Khusus dari Asesor Yayasan:</strong>
        <div style="font-size: 9px; color: #374151; margin-top: 2px; white-space: pre-line;">{{ $ukg->catatan_umum }}</div>
    </div>
    @endif

    <!-- Lembar Pengesahan -->
    <table class="signatures">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div><strong>Kepala Satuan Pendidikan</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">( ............................................ )</div>
                <div style="font-size: 8px; color:#6b7280;">Kepala Sekolah / Madrasah</div>
            </td>
            <td>
                <div>Disahkan oleh,</div>
                <div><strong>Asesor / Penjamin Mutu Yayasan</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $ukg->assessor_name ?: ($ukg->assessor->name ?? 'Tim Penjamin Mutu') }}</div>
                <div style="font-size: 8px; color:#6b7280;">Tim Pengembang & Penjamin Mutu Yayasan</div>
            </td>
            <td>
                <div>Diterima oleh,</div>
                <div><strong>Guru yang Dinilai</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $ukg->teacher->name ?? 'Guru' }}</div>
                <div style="font-size: 8px; color:#6b7280;">NIY: {{ $ukg->niy ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- HALAMAN LAMPIRAN RINCIAN 70 INDIKATOR -->
    <div class="page-break"></div>

    <div class="header">
        <h3 style="font-size: 11px;">LAMPIRAN RINCIAN PENILAIAN INDIKATOR PKG</h3>
        <h2 style="font-size: 12px; margin-top: 0;">DATA 70 INDIKATOR PENILAIAN KINERJA PENDIDIK</h2>
        <p>Guru: <strong>{{ $ukg->teacher->name ?? '-' }}</strong> &bull; Unit: <strong>{{ $ukg->institution->name ?? '-' }}</strong> &bull; Mapel: {{ $ukg->mata_pelajaran ?? '-' }}</p>
    </div>

    <table class="table-data" style="font-size: 8px;">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="32%">Indikator Penilaian</th>
                <th width="38%">Deskripsi & Fokus Pengamatan</th>
                <th width="8%">Teknik</th>
                <th width="6%">Skor (1-6)</th>
                <th width="12%">Bukti / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clusters as $cKey => $cInfo)
                <tr style="background-color: #fff7ed; font-weight: bold; color: #9a3412;">
                    <td colspan="6">{{ $cInfo['code'] }}. {{ strtoupper($cInfo['name']) }} (Indikator {{ $cInfo['start_idx'] }} s.d. {{ $cInfo['end_idx'] }}) &mdash; Skor: {{ $ukg->{'skor_' . $cKey} ?? 0 }}/{{ $cInfo['max_score'] }}</td>
                </tr>
                @foreach($cInfo['indicators'] as $iNum => $iData)
                    @php
                        $skorInd = isset($komponen["i{$iNum}"]['score']) ? $komponen["i{$iNum}"]['score'] : (isset($komponen[$iNum]['score']) ? $komponen[$iNum]['score'] : '-');
                        $teknikInd = isset($komponen["i{$iNum}"]['teknik']) ? $komponen["i{$iNum}"]['teknik'] : (isset($komponen[$iNum]['teknik']) ? $komponen[$iNum]['teknik'] : $iData['teknik']);
                        $catatanInd = isset($komponen["i{$iNum}"]['catatan']) ? $komponen["i{$iNum}"]['catatan'] : (isset($komponen[$iNum]['catatan']) ? $komponen[$iNum]['catatan'] : '');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $iNum }}</td>
                        <td><strong>{{ $iData['title'] }}</strong></td>
                        <td>{{ $iData['desc'] }}</td>
                        <td class="text-center font-bold">{{ $teknikInd }}</td>
                        <td class="text-center font-bold" style="background-color: #fafaf9;">{{ $skorInd }}</td>
                        <td>{{ $catatanInd ?: '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
