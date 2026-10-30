<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>RPPM / Modul Ajar - {{ $rppm->topik ?? 'Pembelajaran' }}</title>
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
            font-size: 10px;
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
            padding: 2.5px 4px;
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
        .content-box {
            border: 1px solid #e2e8f0;
            background-color: #fafafa;
            padding: 6px 8px;
            font-size: 9px;
            line-height: 1.45;
            margin-bottom: 6px;
            border-radius: 3px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
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

    @include('pdf.kop_surat', ['institutionName' => $rppm->guru?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">RENCANA PELAKSANAAN PEMBELAJARAN MINGGUAN (RPPM)</div>
        <div class="doc-title-sub">MODUL AJAR KURIKULUM MERDEKA • TAHUN PELAJARAN 2026/2027</div>
    </div>

    <!-- I. IDENTITAS MODUL -->
    <div class="section-header">I. IDENTITAS MODUL & PERANGKAT PEMBELAJARAN</div>
    <table class="table-identity">
        <tr>
            <td class="label">Satuan Pendidikan</td>
            <td class="colon">:</td>
            <td class="val">{{ $rppm->guru?->institution?->name ?? 'SMP Islam Baitul Makmur' }}</td>
            <td class="label">Penyusun / Guru</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $rppm->guru?->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $rppm->mata_pelajaran ?? '-' }}</strong></td>
            <td class="label">Fase / Kelas</td>
            <td class="colon">:</td>
            <td class="val">{{ $rppm->kelas_fase ?? 'Kelas 7 / Fase D' }}</td>
        </tr>
        <tr>
            <td class="label">Topik / Materi</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $rppm->topik ?? '-' }}</strong></td>
            <td class="label">Alokasi Waktu</td>
            <td class="colon">:</td>
            <td class="val">{{ $rppm->alokasi_waktu ?? '2 x 40 Menit' }}</td>
        </tr>
        <tr>
            <td class="label">Lintas Disiplin</td>
            <td class="colon">:</td>
            <td class="val">{{ $rppm->content['lintas_disiplin'] ?? '-' }}</td>
            <td class="label">Status Dokumen</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">{{ strtoupper($rppm->status ?? 'PUBLISHED') }}</span></td>
        </tr>
    </table>

    <!-- II. KOMPONEN INTI & TUJUAN PEMBELAJARAN -->
    <div class="section-header">II. KOMPONEN INTI & RUMUSAN TUJUAN PEMBELAJARAN</div>

    <table class="table-grid">
        <tr>
            <th style="width: 25%;">Komponen Acuan</th>
            <th style="width: 75%;">Uraian / Deskripsi Capaian & Alur</th>
        </tr>
        <tr>
            <td>
                <strong>Capaian Pembelajaran (CP)</strong><br>
                @if($rppm->capaianPembelajaran?->kode_cp)
                    <span class="badge badge-blue">CP {{ $rppm->capaianPembelajaran->kode_cp }}</span>
                @endif
                <span class="badge badge-green">{{ $rppm->capaianPembelajaran?->elemen ?? 'Materi Inti' }}</span>
            </td>
            <td>
                {{ $rppm->content['capaian_pembelajaran'] ?? $rppm->capaianPembelajaran?->deskripsi_cp ?? '-' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Alur Tujuan Pembelajaran (ATP)</strong><br>
                @if($rppm->alurTujuanPembelajaran?->kode_atp)
                    <span class="badge badge-blue">ATP {{ $rppm->alurTujuanPembelajaran->kode_atp }}</span>
                @endif
            </td>
            <td>
                {{ $rppm->alurTujuanPembelajaran?->judul_atp ?? 'Alur Pembelajaran Terpadu' }}
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; font-size: 9.5px; margin: 6px 0 3px 0; color: #0f172a;">🎯 Daftar Tujuan Pembelajaran (TP) yang Dicapai:</div>
    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 8%;">Kode</th>
                <th style="width: 25%;">Materi / Pokok Bahasan</th>
                <th style="width: 67%;">Rumusan Tujuan Pembelajaran (TP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($selectedTps as $tp)
                <tr>
                    <td style="text-align: center; font-weight: bold;"><span class="badge badge-green">{{ $tp->kode_tp }}</span></td>
                    <td><strong>{{ $tp->materi ?? '-' }}</strong></td>
                    <td>{{ $tp->deskripsi_tp }}</td>
                </tr>
            @empty
                @if(!empty($rppm->content['tujuan_pembelajaran']) && is_array($rppm->content['tujuan_pembelajaran']))
                    @foreach($rppm->content['tujuan_pembelajaran'] as $tpItem)
                        <tr>
                            <td style="text-align: center; font-weight: bold;">-</td>
                            <td>Materi Pembelajaran</td>
                            <td>{{ is_array($tpItem) ? ($tpItem['rumusan'] ?? '-') : $tpItem }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; color: #64748b;">Belum ada Tujuan Pembelajaran yang dipilih.</td>
                    </tr>
                @endif
            @endforelse
        </tbody>
    </table>

    <!-- III. PROFIL PELAJAR & MODEL PEMBELAJARAN -->
    <div class="section-header">III. DIMENSI PROFIL PELAJAR, METODE & PEDAGOGIS</div>
    <table class="table-grid">
        <tr>
            <td style="width: 50%;">
                <strong>Dimensi Profil Pelajar Pancasila / Karakter:</strong>
                <div style="margin-top: 3px; color: #334155;">
                    @if(!empty($rppm->content['profil_lulusan']) && is_array($rppm->content['profil_lulusan']))
                        <ul style="margin: 2px 0 0 15px; padding: 0;">
                            @foreach($rppm->content['profil_lulusan'] as $prof)
                                <li>{{ is_array($prof) ? ($prof['dimensi'] ?? ($prof['elemen'] ?? 'Karakter')) : $prof }}</li>
                            @endforeach
                        </ul>
                    @else
                        Beriman & Bertakwa kepada Tuhan YME, Mandiri, Bernalar Kritis, Bergotong Royong.
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <strong>Praktik Pedagogis & Model:</strong>
                <div style="margin-top: 3px; color: #334155;">
                    {{ $rppm->content['praktik_pedagogis'] ?? 'Problem-Based Learning (PBL) / Eksplorasi Konsep Terbimbing' }}<br>
                    <strong>Pemanfaatan Digital:</strong> {{ $rppm->content['pemanfaatan_digital'] ?? 'Media Presentasi Interaktif & Modul Pembelajaran Online' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- IV. LANGKAH-LANGKAH PEMBELAJARAN -->
    <div class="section-header">IV. LANGKAH-LANGKAH KEGIATAN PEMBELAJARAN</div>
    @if(!empty($rppm->content['pengalaman_belajar']) && is_array($rppm->content['pengalaman_belajar']))
        <table class="table-grid">
            <thead>
                <tr>
                    <th style="width: 20%;">Tahap Pembelajaran</th>
                    <th style="width: 70%;">Deskripsi Kegiatan Pembelajaran</th>
                    <th style="width: 10%;">Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rppm->content['pengalaman_belajar'] as $keg)
                    <tr>
                        <td style="font-weight: bold; background-color: #fafafa;">{{ $keg['tahap'] ?? 'Aktivitas' }}</td>
                        <td>{!! nl2br(e($keg['kegiatan'] ?? '-')) !!}</td>
                        <td style="text-align: center;">{{ $keg['alokasi_waktu'] ?? '15 Menit' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="table-grid">
            <tr>
                <td style="width: 25%; font-weight: bold; background-color: #f8fafc;">1. Pendahuluan (15 Menit)</td>
                <td style="width: 75%;">Guru membuka kelas dengan salam, doa bersama, presensi kehadiran, apersepsi mengaitkan materi sebelumnya, serta menyampaikan tujuan pembelajaran dan motivasi belajar.</td>
            </tr>
            <tr>
                <td style="width: 25%; font-weight: bold; background-color: #f8fafc;">2. Kegiatan Inti (55 Menit)</td>
                <td style="width: 75%;">Siswa mengamati stimulus materi, berdiskusi dalam kelompok, melakukan eksplorasi konsep terpandu, mempresentasikan hasil kerja, dan menerima umpan balik penguatan dari guru.</td>
            </tr>
            <tr>
                <td style="width: 25%; font-weight: bold; background-color: #f8fafc;">3. Penutup (10 Menit)</td>
                <td style="width: 75%;">Guru dan siswa menyimpulkan hasil pembelajaran hari ini, melakukan refleksi singkat bersama, menyampaikan arahan tugas/materi berikutnya, dan berdoa bersama.</td>
            </tr>
        </table>
    @endif

    <!-- V. ASESMEN & EVALUASI -->
    <div class="section-header">V. ASESMEN & KRITERIA KETERCAPAIAN (KKTP)</div>
    <table class="table-grid">
        <tr>
            <td style="width: 33%;">
                <strong>1. Asesmen Diagnostik:</strong><br>
                Pertanyaan pemantik lisan di awal pembelajaran untuk memetakan kesiapan murid.
            </td>
            <td style="width: 33%;">
                <strong>2. Asesmen Formatif:</strong><br>
                Observasi keaktifan diskusi, lembar kerja siswa (LKS), dan unjuk pemahaman.
            </td>
            <td style="width: 34%;">
                <strong>3. Asesmen Sumatif:</strong><br>
                Tes tertulis / penilaian performa di akhir lingkup materi atau akhir bab.
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN -->
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
                Jawa Timur, {{ \Carbon\Carbon::parse($rppm->updated_at ?? now())->translatedFormat('d F Y') }}<br>
                <strong>Guru Mata Pelajaran</strong>
                <br><br><br><br>
                <strong><u>{{ $rppm->guru?->name ?? 'Guru Pengampu' }}</u></strong><br>
                NIP/NUPTK: {{ $rppm->guru?->nip ?? ($rppm->guru?->nuptk ?? '-') }}
            </td>
        </tr>
    </table>

</body>
</html>
