<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Alur Tujuan Pembelajaran (ATP) - {{ $atp->subject->name ?? 'Mapel' }}</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 landscape;
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
            padding: 5px 8px;
            margin-bottom: 10px;
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
            margin-top: 1px;
        }
        .section-header {
            background-color: #f1f5f9;
            border-left: 3.5px solid #047857;
            padding: 4px 8px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 8px;
            margin-bottom: 6px;
        }
        .table-identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .table-identity td {
            padding: 2.5px 4px;
            font-size: 9px;
            vertical-align: top;
        }
        .table-identity .label {
            width: 18%;
            font-weight: bold;
            color: #334155;
        }
        .table-identity .colon {
            width: 2%;
            text-align: center;
        }
        .table-identity .val {
            width: 30%;
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
            background-color: #f1f5f9;
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
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
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

    @include('pdf.kop_surat', ['institutionName' => $atp->guru?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR'])

    <div class="doc-title-box">
        <div class="doc-title-main">ALUR TUJUAN PEMBELAJARAN (ATP) KURIKULUM MERDEKA</div>
        <div class="doc-title-sub">DOKUMEN PERENCANAAN URUTAN & RANGKAIAN PEMBELAJARAN TAHUN PELAJARAN 2026/2027</div>
    </div>

    <!-- IDENTITAS ATP -->
    <table class="table-identity">
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $atp->subject->name ?? '-' }}</strong></td>
            <td class="label">Penyusun / Guru</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ $atp->guru->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Fase / Kelas Tingkat</td>
            <td class="colon">:</td>
            <td class="val">Fase D / Kelas {{ $atp->tingkat ?? '7' }}</td>
            <td class="label">Kode Dokumen ATP</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge badge-green">ATP {{ $atp->kode_atp ?? '1' }}</span></td>
        </tr>
        <tr>
            <td class="label">Rujukan Capaian (CP)</td>
            <td class="colon">:</td>
            <td class="val">
                @if($atp->capaianPembelajaran)
                    <span class="badge badge-blue">CP {{ $atp->capaianPembelajaran->kode_cp ?? '1' }}</span>
                    <strong>{{ $atp->capaianPembelajaran->elemen }}</strong>
                @else
                    Semua CP Terakumulasi
                @endif
            </td>
            <td class="label">Semester / Tahun</td>
            <td class="colon">:</td>
            <td class="val">{{ $atp->semester ?? 'Ganjil' }} / {{ $atp->tahun_ajaran ?? '2026/2027' }}</td>
        </tr>
    </table>

    @if($atp->capaianPembelajaran)
        <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 5px 8px; margin-bottom: 8px; border-radius: 3px; font-size: 8.5px;">
            <strong style="color: #047857;">Capaian Pembelajaran (CP) Acuan [Elemen: {{ $atp->capaianPembelajaran->elemen }}]:</strong><br>
            {{ $atp->capaianPembelajaran->deskripsi_cp }}
        </div>
    @endif

    <!-- MATRIKS ALUR TUJUAN PEMBELAJARAN -->
    <div class="section-header">MATRIKS ALUR TUJUAN PEMBELAJARAN (ATP)</div>
    <table class="table-grid">
        <thead>
            <tr>
                <th style="width: 5%;">No Urut</th>
                <th style="width: 8%;">Kode TP</th>
                <th style="width: 20%;">Materi Pokok / Lingkup Bahasan</th>
                <th style="width: 42%;">Rumusan Tujuan Pembelajaran (TP)</th>
                <th style="width: 15%;">Profil Pelajar Pancasila</th>
                <th style="width: 10%;">Alokasi Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accumulatedTps as $index => $tp)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="text-align: center;"><span class="badge badge-blue">{{ $tp->kode_tp }}</span></td>
                    <td><strong>{{ $tp->materi ?? '-' }}</strong></td>
                    <td>{{ $tp->deskripsi_tp }}</td>
                    <td style="font-size: 8px; color: #475569;">
                        Bernalar Kritis, Mandiri, Bergotong Royong
                    </td>
                    <td style="text-align: center; font-weight: bold; color: #047857;">
                        {{ $tp->alokasi_jp ?? '4 JP' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 10px;">Belum ada Tujuan Pembelajaran yang terangkum dalam ATP ini.</td>
                </tr>
            @endforelse
        </tbody>
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
                Jawa Timur, {{ \Carbon\Carbon::parse($atp->updated_at ?? now())->translatedFormat('d F Y') }}<br>
                <strong>Guru Penyusun ATP</strong>
                <br><br><br><br>
                <strong><u>{{ $atp->guru->name ?? 'Guru Mata Pelajaran' }}</u></strong><br>
                NIP/NUPTK: {{ $atp->guru?->nip ?? ($atp->guru?->nuptk ?? '-') }}
            </td>
        </tr>
    </table>

</body>
</html>
