@php
    $record = $getRecord() ?? $record ?? null;
    if (!$record) return;

    // 1. Nama Program
    $namaProgram = $record->nama_program ?? $record->judul_program ?? $record->name ?? '-';

    // 2. Divisi / Bidang
    $divisi = match(true) {
        $record instanceof \App\Models\ProgramKurikulum => 'Kurikulum',
        $record instanceof \App\Models\ProgramKesiswaan => 'Kesiswaan',
        $record instanceof \App\Models\ProgramSarpras => 'Sarana & Prasarana (Sarpras)',
        $record instanceof \App\Models\ProgramHumas || $record instanceof \App\Models\HumasProgram => 'Hubungan Masyarakat (Humas)',
        $record instanceof \App\Models\ProgramKepsek => 'Kepala Sekolah',
        $record instanceof \App\Models\BkWorkProgram => 'Bimbingan Konseling (BK)',
        $record instanceof \App\Models\ClassProgram => 'Program Kelas' . ($record->classroom ? ' (' . $record->classroom->name . ')' : ''),
        $record instanceof \App\Models\OsisProgram => 'Organisasi Siswa (OSIS)',
        $record instanceof \App\Models\WorkProgram => ucwords(str_replace('_', ' ', $record->divisi ?? 'Kurikulum')),
        default => $record->divisi ?? 'Program Kerja',
    };

    // 3. Tahun Ajaran
    $tahunAjaran = $record->tahun_ajaran ?? ($record->semester ? 'Semester ' . $record->semester : '-');

    // 4. Kategori & Tipe
    $kategori = $record->kategori ?? $record->bidang_layanan ?? '-';
    $tipe = $record->tipe_pelaksanaan ?? ($record->tipe ?? '-');
    $sifat = $record->sifat ?? '-';

    // 5. Sasaran & Waktu
    $sasaran = $record->sasaran ?? '-';
    $waktu = $record->waktu_pelaksanaan 
        ?? ($record->tanggal ? \Carbon\Carbon::parse($record->tanggal)->translatedFormat('d F Y') : null)
        ?? ($record->tanggal_mulai ? \Carbon\Carbon::parse($record->tanggal_mulai)->translatedFormat('d F Y') : '-');

    // 6. Anggaran
    $anggaranRaw = $record->estimasi_anggaran ?? null;
    $anggaranFormatted = ($anggaranRaw && is_numeric($anggaranRaw) && $anggaranRaw > 0)
        ? 'Rp ' . number_format($anggaranRaw, 0, ',', '.')
        : 'Tidak Ada Anggaran / Mandiri';

    // 7. Status Pelaksanaan
    $statusPelaksanaan = $record->status_pelaksanaan ?? $record->status ?? 'Rencana';
    $statusLower = strtolower((string) $statusPelaksanaan);
    $statusColor = match(true) {
        str_contains($statusLower, 'selesai') || str_contains($statusLower, 'terlaksana') => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
        str_contains($statusLower, 'jalan') || str_contains($statusLower, 'proses') => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        str_contains($statusLower, 'batal') || str_contains($statusLower, 'tidak') => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border-rose-300 dark:border-rose-700',
        default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
    };

    // 8. Status Persetujuan KS (Approval)
    $hasPersetujuan = isset($record->status_persetujuan) || isset($record->status_pengajuan);
    $statusPersetujuan = $record->status_persetujuan ?? $record->status_pengajuan ?? null;
    $approvalLower = strtolower((string) $statusPersetujuan);
    $approvalColor = match(true) {
        str_contains($approvalLower, 'setuju') || str_contains($approvalLower, 'acc') => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
        str_contains($approvalLower, 'perlu') || str_contains($approvalLower, 'revisi') => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        str_contains($approvalLower, 'tolak') => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border-rose-300 dark:border-rose-700',
        default => 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300 border-sky-300 dark:border-sky-700',
    };

    $catatanKepsek = $record->catatan_kepsek ?? null;
    $tanggalPersetujuan = $record->tanggal_persetujuan ? \Carbon\Carbon::parse($record->tanggal_persetujuan)->translatedFormat('d F Y, H:i WIB') : null;

    // 9. Tujuan & Teknis
    $tujuan = $record->tujuan ?? $record->keterangan ?? '-';
    $teknis = $record->teknis_pelaksanaan ?? $record->uraian ?? '-';

    // 10. Dokumen Terkait
    $dokumenTerkait = $record->dokumen_terkait ?? null;

    // 11. Penanggung Jawab / Pembuat
    $pembuat = $record->user?->name ?? $record->waliKelas?->name ?? 'Staf Terkait';
    $tanggalDibuat = $record->created_at ? $record->created_at->translatedFormat('d F Y, H:i WIB') : '-';
@endphp

<div class="w-full my-2">
    <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                    <th colspan="2" class="px-5 py-4">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-950/70 text-primary-700 dark:text-primary-300 flex items-center justify-center shrink-0 border border-primary-200 dark:border-primary-800 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-black text-gray-900 dark:text-white leading-tight">
                                        {{ $namaProgram }}
                                    </h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
                                        Ringkasan Dokumen Program Kerja &bull; Tahun Ajaran: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $tahunAjaran }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-wide bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                                    Divisi {{ $divisi }}
                                </span>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                {{-- Row 1: Nama Program --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="w-48 sm:w-56 px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Nama Program Kerja
                    </td>
                    <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white text-sm">
                        {{ $namaProgram }}
                    </td>
                </tr>

                {{-- Row 2: Divisi / Bidang --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Bidang / Divisi
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ $divisi }}
                    </td>
                </tr>

                {{-- Row 3: Tahun Ajaran --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Tahun Ajaran / Semester
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ $tahunAjaran }}
                    </td>
                </tr>

                {{-- Row 4: Kategori Program --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Kategori Program
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            {{ $kategori }}
                        </span>
                    </td>
                </tr>

                {{-- Row 5: Frekuensi / Tipe Pelaksanaan --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Tipe / Frekuensi
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ $tipe }}
                    </td>
                </tr>

                {{-- Row 6: Sifat Kegiatan --}}
                @if($sifat !== '-')
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Sifat Kegiatan
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold {{ $sifat === 'Wajib' ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                            {{ $sifat }}
                        </span>
                    </td>
                </tr>
                @endif

                {{-- Row 7: Sasaran Peserta --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Sasaran Peserta
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ $sasaran }}
                    </td>
                </tr>

                {{-- Row 8: Waktu Pelaksanaan --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Waktu / Jadwal
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ $waktu }}
                    </td>
                </tr>

                {{-- Row 9: Estimasi Anggaran --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Estimasi Anggaran
                    </td>
                    <td class="px-5 py-3.5 font-black text-gray-900 dark:text-white">
                        {{ $anggaranFormatted }}
                    </td>
                </tr>

                {{-- Row 10: Status Pelaksanaan --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Status Pelaksanaan
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black border {{ $statusColor }}">
                            {{ ucfirst($statusPelaksanaan) }}
                        </span>
                    </td>
                </tr>

                {{-- Row 11: Status Persetujuan KS (Jika ada fitur approval) --}}
                @if($hasPersetujuan && $statusPersetujuan)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Persetujuan Kepala Sekolah
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black border {{ $approvalColor }}">
                                {{ $statusPersetujuan }}
                            </span>
                            @if($tanggalPersetujuan)
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    Disetujui pada: {{ $tanggalPersetujuan }}
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif

                {{-- Row 12: Catatan Disposisi Kepala Sekolah (Jika ada) --}}
                @if(!empty($catatanKepsek))
                <tr class="bg-amber-50/40 dark:bg-amber-950/20">
                    <td class="px-5 py-3.5 font-bold text-amber-800 dark:text-amber-300 bg-amber-100/50 dark:bg-amber-900/30 text-xs uppercase tracking-wider align-top">
                        Arahan / Catatan KS
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-semibold">
                        {{ $catatanKepsek }}
                    </td>
                </tr>
                @endif

                {{-- Row 13: Tujuan Program --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider align-top">
                        Tujuan Program
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                        @if(strip_tags($tujuan) !== $tujuan)
                            {!! $tujuan !!}
                        @else
                            {{ $tujuan }}
                        @endif
                    </td>
                </tr>

                {{-- Row 14: Teknis / Langkah Pelaksanaan --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider align-top">
                        Teknis Pelaksanaan
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                        @if(strip_tags($teknis) !== $teknis)
                            <div class="prose dark:prose-invert max-w-none text-sm [&>ul]:list-disc [&>ul]:ml-4 [&>ol]:list-decimal [&>ol]:ml-4">
                                {!! $teknis !!}
                            </div>
                        @else
                            {{ $teknis }}
                        @endif
                    </td>
                </tr>

                {{-- Row 15: Dokumen Terkait / Link --}}
                @if(!empty($dokumenTerkait))
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Dokumen Pendukung
                    </td>
                    <td class="px-5 py-3.5">
                        <a href="{{ $dokumenTerkait }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 hover:text-primary-700 font-bold text-xs border border-primary-200 dark:border-primary-800 shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>Buka Dokumen / Link Pendukung &rarr;</span>
                        </a>
                    </td>
                </tr>
                @endif

                {{-- Row 16: Disusun Oleh --}}
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                        Penyusun & Tanggal
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-400 font-medium">
                        Disusun oleh <span class="font-bold text-gray-900 dark:text-white">{{ $pembuat }}</span> pada {{ $tanggalDibuat }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
