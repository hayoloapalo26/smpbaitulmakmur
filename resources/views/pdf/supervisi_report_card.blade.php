<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Report Card Supervisi - {{ $supervision->teacher->name ?? 'Guru' }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2.5px double #1f2937;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h3 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            color: #d97706;
            letter-spacing: 0.5px;
        }
        .header h1 {
            margin: 2px 0;
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }
        .header h2 {
            margin: 2px 0 0 0;
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            text-decoration: underline;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 9px;
            color: #4b5563;
        }
        .identity-box {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .identity-box td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 10.5px;
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
            margin-bottom: 14px;
            border: 1.5px solid #d97706;
            background-color: #fffbeb;
            border-radius: 6px;
            border-collapse: collapse;
        }
        .score-summary-card td {
            padding: 8px 10px;
            text-align: center;
            vertical-align: middle;
        }
        .score-num {
            font-size: 18px;
            font-weight: 800;
            color: #b45309;
        }
        .score-sub {
            font-size: 9px;
            color: #6b7280;
            font-weight: normal;
        }
        .score-title {
            font-size: 10px;
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
            font-size: 11px;
            letter-spacing: 0.3px;
        }
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            background-color: #f3f4f6;
            padding: 5px 8px;
            border-left: 4px solid #d97706;
            margin-top: 12px;
            margin-bottom: 6px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        .table-data th, .table-data td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            vertical-align: top;
        }
        .table-data th {
            background-color: #f9fafb;
            color: #1f2937;
            font-weight: bold;
            text-align: center;
        }
        .table-subcluster {
            background-color: #fef3c7;
            font-weight: bold;
            color: #92400e;
            padding: 3px 6px;
        }
        .cluster-footer {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .qualitative-box {
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 6px;
        }
        .qualitative-title {
            font-weight: bold;
            color: #92400e;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .qualitative-content {
            font-size: 10px;
            color: #374151;
            white-space: pre-line;
        }
        .signatures {
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
        }
        .signatures td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            padding: 0 10px;
            font-size: 10px;
        }
        .sig-space {
            height: 55px;
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
            body { font-size: 10pt; }
        }
    </style>
</head>
<body>
    @if(request()->has('print'))
        <div class="no-print" style="margin-bottom: 15px; text-align: right;">
            <button onclick="window.print()" style="background-color: #f97316; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                🖨️ Cetak Dokumen / Simpan PDF
            </button>
        </div>
        <script>
            window.onload = function() {
                // Auto trigger print if opened in print mode
                setTimeout(function() { window.print(); }, 400);
            };
        </script>
    @endif

    @php
        $logoPath = public_path('images/logo-sekolah.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('images/logo.png');
        }
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    <!-- Header Dokumen / Kop Resmi -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 3px double #1f2937; padding-bottom: 8px;">
        <tr>
            <td style="width: 15%; text-align: center; vertical-align: middle;">
                @if(!empty($logoBase64))
                    <img src="data:image/png;base64,{{ $logoBase64 }}" style="width: 70px; height: auto;" alt="Logo">
                @else
                    <div style="font-size: 32px; font-weight: bold; color: #d97706;">🎓</div>
                @endif
            </td>
            <td style="width: 70%; text-align: center; vertical-align: middle; line-height: 1.25;">
                <div style="font-size: 11px; font-weight: bold; letter-spacing: 0.5px; color: #d97706; text-transform: uppercase;">
                    LEMBAGA PENDIDIKAN ISLAM (LPI) BAITUL MAKMUR
                </div>
                <div style="font-size: 14px; font-weight: 800; color: #111827; text-transform: uppercase; margin: 2px 0;">
                    TIM PENGEMBANG & PENJAMIN MUTU PENDIDIKAN YAYASAN
                </div>
                <div style="font-size: 11.5px; font-weight: bold; color: #374151; text-decoration: underline; margin-bottom: 2px;">
                    REPORT CARD & EVALUASI SUPERVISI PEMBELAJARAN GURU
                </div>
                <div style="font-size: 8px; color: #4b5563; line-height: 1.35;">
                    Gg. 17B Jl. Raya Sawojajar No.58, Sawojajar, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139<br>
                    <span>Email: <strong>adminsmp@baitulmakmur.sch.id</strong></span>
                    &nbsp;&bull;&nbsp;
                    <span>Instagram: <strong>@smpibaitulmakmurmlg</strong></span>
                </div>
            </td>
            <td style="width: 15%; text-align: center; vertical-align: middle;">
                {{-- Penyeimbang simetris --}}
            </td>
        </tr>
    </table>

    <!-- Data Identitas Pembelajaran -->
    <table class="identity-box">
        <tr>
            <td class="identity-label">Unit / Lembaga</td>
            <td class="identity-colon">:</td>
            <td class="identity-value"><strong>{{ $supervision->institution->name ?? 'SMP Islam Baitul Makmur' }}</strong></td>

            <td class="identity-label">Hari / Tanggal</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $supervision->tanggal ? $supervision->tanggal->translatedFormat('l, d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Program Sekolah</td>
            <td class="identity-colon">:</td>
            <td class="identity-value"><span style="background-color:#fed7aa; padding:1px 6px; border-radius:3px; font-weight:bold;">{{ $supervision->unit_sekolah_program ?? 'Reguler' }}</span></td>

            <td class="identity-label">Kelas / Semester</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $supervision->kelas_semester ?? '-' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Nama Guru</td>
            <td class="identity-colon">:</td>
            <td class="identity-value"><strong>{{ $supervision->teacher->name ?? '-' }}</strong></td>

            <td class="identity-label">Mata Pelajaran</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $supervision->mata_pelajaran ?? '-' }}</td>
        </tr>
        <tr>
            <td class="identity-label">Supervisor / Penilai</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $supervision->supervisor_name ?: ($supervision->supervisor->name ?? 'Tim Penjamin Mutu Yayasan') }}</td>

            <td class="identity-label">Jalur Supervisi</td>
            <td class="identity-colon">:</td>
            <td class="identity-value">{{ $supervision->jalur_supervisi === 'penjamin_mutu' ? 'Penjamin Mutu Yayasan' : 'Pengajuan Mandiri Guru' }}</td>
        </tr>
    </table>

    <!-- Ringkasan Nilai Akhir & Predikat -->
    @php
        $calc = $supervision->komponen_penilaian 
            ? \App\Models\TeacherSupervision::calculateScoresFromComponents($supervision->komponen_penilaian)
            : [
                'total_skor' => $supervision->total_skor ?? 0,
                'nilai_akhir' => $supervision->nilai_akhir ?? ($supervision->score ?? 0),
                'predikat' => $supervision->predikat ?? \App\Models\TeacherSupervision::determinePredicate($supervision->nilai_akhir ?? $supervision->score),
                'skor_perencanaan' => $supervision->skor_perencanaan ?? 0,
                'skor_pelaksanaan' => $supervision->skor_pelaksanaan ?? 0,
                'skor_evaluasi' => $supervision->skor_evaluasi ?? 0,
            ];
        $totalSkor = $calc['total_skor'];
        $na = $calc['nilai_akhir'];
        $predikat = $calc['predikat'];
        $komponen = $supervision->komponen_penilaian ?: [];
    @endphp

    <table class="score-summary-card">
        <tr>
            <td width="22%">
                <div class="score-title">TOTAL SKOR PEROLEHAN</div>
                <div class="score-num">{{ $totalSkor }} <span class="score-sub">/ 88</span></div>
            </td>
            <td width="26%" style="border-left: 1px dashed #d97706; border-right: 1px dashed #d97706;">
                <div class="score-title">NILAI AKHIR (NA)</div>
                <div class="score-num" style="color: #047857;">{{ number_format($na, 2) }} <span class="score-sub">/ 100</span></div>
                <div style="font-size: 8.5px; color:#6b7280;">Formula: ({{ $totalSkor }} / 88) × 100</div>
            </td>
            <td width="52%">
                <div class="score-title">PREDIKAT & KATEGORI KETERCAPAIAN</div>
                <div style="margin-top: 4px;">
                    <span class="predicate-badge">{{ $predikat }}</span>
                </div>
                <div style="font-size: 8.5px; color: #4b5563; margin-top: 4px;">
                    A: Perencanaan ({{ $calc['skor_perencanaan'] }}/20) | B: Pelaksanaan ({{ $calc['skor_pelaksanaan'] }}/60) | B.6: Evaluasi ({{ $calc['skor_evaluasi'] }}/8)
                </div>
            </td>
        </tr>
    </table>

    <!-- KLASTER 1: PERENCANAAN & DESAIN PEMBELAJARAN (A1 - A5) -->
    <div class="section-heading">
        A. PERENCANAAN & DESAIN PEMBELAJARAN (MODUL AJAR / RPP) &mdash; Skor Maks: 20
    </div>
    @php
        $sumPerencanaan = $supervision->getClusterSummary('perencanaan');
    @endphp
    <table class="table-data">
        <thead>
            <tr>
                <th width="8%">Kode</th>
                <th width="32%">Indikator Penilaian</th>
                <th width="32%">Deskripsi / Fokus Pengamatan</th>
                <th width="8%">Skor (1-4)</th>
                <th width="20%">Catatan Pengamatan Kualitatif</th>
            </tr>
        </thead>
        <tbody>
            @php
                $indicatorsA = [
                    1 => ['code' => 'A.1', 'title' => 'Analisis CP, TP, & KKTP', 'desc' => 'Keselarasan dan keterukuran TP/KKTP dengan Capaian Pembelajaran.'],
                    2 => ['code' => 'A.2', 'title' => 'Desain Pembelajaran Berdiferensiasi', 'desc' => 'Variasi konten, proses, dan produk sesuai kesiapan, minat, dan profil murid.'],
                    3 => ['code' => 'A.3', 'title' => 'Desain Pembelajaran Deep Learning', 'desc' => 'Pemantik bernalar kritis, alur runtut (Memahami > Mengaplikasi > Merefleksi), serta media digital interaktif.'],
                    4 => ['code' => 'A.4', 'title' => 'Integrasi Bahan Ajar & Karakter', 'desc' => 'Transformasi nilai moral, etika, dan pembiasaan karakter.'],
                    5 => ['code' => 'A.5', 'title' => 'Perencanaan Bahasa & Al-Qur\'an', 'desc' => 'Penggunaan Classroom Language (Bahasa Inggris) & pembiasaan Tahsin & Tahfidz (BMQ) terstruktur.'],
                ];
            @endphp
            @foreach($indicatorsA as $idx => $ind)
                @php
                    $skorInd = isset($komponen["i{$idx}"]['score']) ? (int)$komponen["i{$idx}"]['score'] : (isset($komponen[$idx]['score']) ? (int)$komponen[$idx]['score'] : '-');
                    $catatanInd = isset($komponen["i{$idx}"]['catatan']) ? $komponen["i{$idx}"]['catatan'] : (isset($komponen[$idx]['catatan']) ? $komponen[$idx]['catatan'] : '-');
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $ind['code'] }}</td>
                    <td><strong>{{ $ind['title'] }}</strong></td>
                    <td>{{ $ind['desc'] }}</td>
                    <td class="text-center"><strong>{{ $skorInd }}</strong></td>
                    <td>{{ $catatanInd ?: '-' }}</td>
                </tr>
            @endforeach
            <tr class="cluster-footer">
                <td colspan="3" class="text-right">TOTAL SKOR KOMPONEN PERENCANAAN:</td>
                <td class="text-center font-bold" style="background-color: #fef3c7;">{{ $sumPerencanaan['score'] }} / 20</td>
                <td>Profil Capaian: <strong>{{ $sumPerencanaan['percentage'] }}% ({{ $sumPerencanaan['profile'] }})</strong></td>
            </tr>
            @if($supervision->rekomendasi_perencanaan)
            <tr style="background-color: #fffbeb;">
                <td colspan="5">
                    <strong>Rekomendasi Khusus Komponen Perencanaan:</strong> {{ $supervision->rekomendasi_perencanaan }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- KLASTER 2: PELAKSANAAN PEMBELAJARAN (B1 - B5: Indikator 6 - 20) -->
    <div class="section-heading">
        B. PELAKSANAAN PEMBELAJARAN &mdash; Skor Maks: 60
    </div>
    @php
        $sumPelaksanaan = $supervision->getClusterSummary('pelaksanaan');
        $structure = \App\Models\TeacherSupervision::getIndicatorsStructure();
        $subclustersB = $structure['pelaksanaan']['subclusters'];
    @endphp
    <table class="table-data">
        <thead>
            <tr>
                <th width="8%">Kode</th>
                <th width="32%">Indikator Penilaian</th>
                <th width="32%">Deskripsi / Fokus Pengamatan</th>
                <th width="8%">Skor (1-4)</th>
                <th width="20%">Catatan Pengamatan Kualitatif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subclustersB as $subCode => $subData)
                <tr>
                    <td colspan="5" class="table-subcluster">{{ $subCode }}. {{ strtoupper($subData['name']) }}</td>
                </tr>
                @foreach($subData['indicators'] as $num => $ind)
                    @php
                        $skorInd = isset($komponen["i{$num}"]['score']) ? (int)$komponen["i{$num}"]['score'] : (isset($komponen[$num]['score']) ? (int)$komponen[$num]['score'] : '-');
                        $catatanInd = isset($komponen["i{$num}"]['catatan']) ? $komponen["i{$num}"]['catatan'] : (isset($komponen[$num]['catatan']) ? $komponen[$num]['catatan'] : '-');
                    @endphp
                    <tr>
                        <td class="text-center font-bold">{{ $ind['code'] }}</td>
                        <td><strong>{{ $ind['title'] }}</strong></td>
                        <td>{{ $ind['description'] }}</td>
                        <td class="text-center"><strong>{{ $skorInd }}</strong></td>
                        <td>{{ $catatanInd ?: '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="cluster-footer">
                <td colspan="3" class="text-right">TOTAL SKOR KOMPONEN PELAKSANAAN:</td>
                <td class="text-center font-bold" style="background-color: #fef3c7;">{{ $sumPelaksanaan['score'] }} / 60</td>
                <td>Profil Capaian: <strong>{{ $sumPelaksanaan['percentage'] }}% ({{ $sumPelaksanaan['profile'] }})</strong></td>
            </tr>
            @if($supervision->rekomendasi_pelaksanaan)
            <tr style="background-color: #fffbeb;">
                <td colspan="5">
                    <strong>Rekomendasi Khusus Komponen Pelaksanaan:</strong> {{ $supervision->rekomendasi_pelaksanaan }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- KLASTER 3: PENUTUP PEMBELAJARAN / EVALUASI (B6: Indikator 21 - 22) -->
    <div class="section-heading">
        B.6 PENUTUP PEMBELAJARAN / EVALUASI &mdash; Skor Maks: 8
    </div>
    @php
        $sumEvaluasi = $supervision->getClusterSummary('evaluasi');
        $indicatorsC = [
            21 => ['code' => 'Indikator 21', 'title' => 'Asesmen Formatif & Umpan Balik', 'desc' => 'Pengecekan pemahaman berkelanjutan (real-time feedback).'],
            22 => ['code' => 'Indikator 22', 'title' => 'Analisis Ketuntasan & Tindak Lanjut', 'desc' => 'Pengukuran ketercapaian, pengayaan, atau program intervensi/remedial.'],
        ];
    @endphp
    <table class="table-data">
        <thead>
            <tr>
                <th width="8%">Kode</th>
                <th width="32%">Indikator Penilaian</th>
                <th width="32%">Deskripsi / Fokus Pengamatan</th>
                <th width="8%">Skor (1-4)</th>
                <th width="20%">Catatan Pengamatan Kualitatif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($indicatorsC as $idx => $ind)
                @php
                    $skorInd = isset($komponen["i{$idx}"]['score']) ? (int)$komponen["i{$idx}"]['score'] : (isset($komponen[$idx]['score']) ? (int)$komponen[$idx]['score'] : '-');
                    $catatanInd = isset($komponen["i{$idx}"]['catatan']) ? $komponen["i{$idx}"]['catatan'] : (isset($komponen[$idx]['catatan']) ? $komponen[$idx]['catatan'] : '-');
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $ind['code'] }}</td>
                    <td><strong>{{ $ind['title'] }}</strong></td>
                    <td>{{ $ind['desc'] }}</td>
                    <td class="text-center"><strong>{{ $skorInd }}</strong></td>
                    <td>{{ $catatanInd ?: '-' }}</td>
                </tr>
            @endforeach
            <tr class="cluster-footer">
                <td colspan="3" class="text-right">TOTAL SKOR KOMPONEN EVALUASI:</td>
                <td class="text-center font-bold" style="background-color: #fef3c7;">{{ $sumEvaluasi['score'] }} / 8</td>
                <td>Profil Capaian: <strong>{{ $sumEvaluasi['percentage'] }}% ({{ $sumEvaluasi['profile'] }})</strong></td>
            </tr>
            @if($supervision->rekomendasi_evaluasi)
            <tr style="background-color: #fffbeb;">
                <td colspan="5">
                    <strong>Rekomendasi Khusus Komponen Evaluasi:</strong> {{ $supervision->rekomendasi_evaluasi }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- EVALUASI KUALITATIF & RENCANA TINDAK LANJUT -->
    <div class="section-heading">
        EVALUASI KUALITATIF & RENCANA TINDAK LANJUT
    </div>
    <div class="qualitative-box">
        <div class="qualitative-title">1. Praktik Baik (Best Practice)</div>
        <div class="qualitative-content">{{ $supervision->praktik_baik ?: ($supervision->hasil ?: 'Menunjukkan komitmen dan kesiapan mengajar yang baik sesuai pedoman yayasan.') }}</div>
    </div>
    <div class="qualitative-box">
        <div class="qualitative-title">2. Area Fokus Perbaikan</div>
        <div class="qualitative-content">{{ $supervision->area_fokus_perbaikan ?: 'Penguatan variasi diferensiasi dan pembiasaan Classroom Language secara konsisten.' }}</div>
    </div>
    <div class="qualitative-box">
        <div class="qualitative-title">3. Rencana Coaching / Action Plan</div>
        <div class="qualitative-content">{{ $supervision->rencana_coaching ?: 'Pendampingan supervisi klinis lanjutan bersama koordinator kurikulum dan penjamin mutu.' }}</div>
    </div>

    <!-- Tanda Tangan Pengesahan -->
    <table class="signatures">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div><strong>Kepala Sekolah</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">( ............................................ )</div>
                <div style="font-size: 8.5px; color:#6b7280;">NIP / NUPTK</div>
            </td>
            <td>
                <div>Disupervisi oleh,</div>
                <div><strong>Supervisor / Penjamin Mutu</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $supervision->supervisor_name ?: ($supervision->supervisor->name ?? 'Tim Penjamin Mutu') }}</div>
                <div style="font-size: 8.5px; color:#6b7280;">Tim Pengembang & Penjamin Mutu Yayasan</div>
            </td>
            <td>
                <div>Diterima oleh,</div>
                <div><strong>Guru yang Disupervisi</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $supervision->teacher->name ?? 'Guru' }}</div>
                <div style="font-size: 8.5px; color:#6b7280;">NIP / NUPTK</div>
            </td>
        </tr>
    </table>
</body>
</html>
