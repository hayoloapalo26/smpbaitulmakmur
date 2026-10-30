@php
    $logoPath = public_path('images/logo-sekolah.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo.png');
    }
    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
@endphp
<table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 3px double #0f172a; padding-bottom: 8px;">
    <tr>
        <td style="width: 15%; text-align: center; vertical-align: middle;">
            @if(!empty($logoBase64))
                <img src="data:image/png;base64,{{ $logoBase64 }}" style="width: 72px; max-height: 80px; height: auto;" alt="Logo Sekolah">
            @else
                <div style="font-size: 32px; font-weight: bold; color: #047857;">🎓</div>
            @endif
        </td>
        <td style="width: 70%; text-align: center; vertical-align: middle; line-height: 1.25;">
            <div style="font-size: 11px; font-weight: bold; letter-spacing: 1px; color: #047857; text-transform: uppercase;">
                YAYASAN PENDIDIKAN ISLAM BAITUL MAKMUR
            </div>
            <div style="font-size: 15px; font-weight: 800; letter-spacing: 0.5px; color: #0f172a; text-transform: uppercase; margin: 2px 0;">
                {{ $institutionName ?? 'SMP ISLAM BAITUL MAKMUR MALANG' }}
            </div>
            <div style="font-size: 8px; color: #334155; line-height: 1.4; margin-top: 2px;">
                Gg. 17B Jl. Raya Sawojajar No.58, Sawojajar, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139<br>
                <span>Email: <strong style="color: #0f172a;">adminsmp@baitulmakmur.sch.id</strong></span>
                &nbsp;&bull;&nbsp;
                <span>Instagram: <strong style="color: #0f172a;">@smpibaitulmakmurmlg</strong></span>
            </div>
        </td>
        <td style="width: 15%; text-align: center; vertical-align: middle;">
            {{-- Spacer penyeimbang simetris agar teks tengah presisi --}}
        </td>
    </tr>
</table>
