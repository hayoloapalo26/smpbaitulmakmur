@php
    $record = $record 
        ?? (isset($entry) && method_exists($entry, 'getRecord') ? $entry->getRecord() : null)
        ?? (isset($getRecord) && is_callable($getRecord) ? $getRecord() : null)
        ?? ($this->record ?? null);

    if (!$record) {
        return;
    }

    $content = is_array($record->content) ? $record->content : [];
    $selectedTps = $record->selected_tps;
    $guru = $record->guru;
    $institution = $guru?->institution?->name ?? 'SMP ISLAM BAITUL MAKMUR';
    $reviewer = $record->reviewer;
    $status = $record->status ?? 'published';
@endphp

<div class="space-y-6 text-gray-800 dark:text-gray-100">

    {{-- 1. BANNER STATUS SUPERVISI KEPALA SEKOLAH --}}
    @if($status === 'approved')
        <div class="p-5 bg-gradient-to-r from-emerald-50 via-emerald-100/50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/30 border-2 border-emerald-500/60 rounded-2xl shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xl font-black shrink-0 shadow-xs">
                        ✓
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-extrabold text-emerald-900 dark:text-emerald-200 uppercase tracking-wide">
                                Dokumen RPPM Telah Disetujui (ACC)
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-600 text-white shadow-2xs">
                                Resmi & Terverifikasi
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1">
                            Disupervisi oleh <strong>{{ $reviewer?->name ?? 'Kepala Sekolah' }}</strong> pada {{ $record->reviewed_at ? $record->reviewed_at->translatedFormat('d F Y, H:i') : '-' }} WIB.
                        </p>
                        @if($record->catatan_kepala_sekolah)
                            <div class="mt-2.5 p-3 rounded-xl bg-white/80 dark:bg-gray-800/80 border border-emerald-200 dark:border-emerald-800 text-xs text-gray-800 dark:text-gray-200 leading-relaxed">
                                <span class="font-bold text-emerald-800 dark:text-emerald-300">Catatan Supervisi:</span>
                                <em>"{{ $record->catatan_kepala_sekolah }}"</em>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('academic.rppm.pdf', $record) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>Cetak PDF Resmi</span>
                    </a>
                </div>
            </div>
        </div>
    @elseif($status === 'need_revision')
        <div class="p-5 bg-gradient-to-r from-amber-50 via-rose-50/50 to-amber-50 dark:from-amber-950/40 dark:to-rose-950/30 border-2 border-amber-500/60 rounded-2xl shadow-xs">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl font-black shrink-0 shadow-xs">
                    ⚠️
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-extrabold text-amber-900 dark:text-amber-200 uppercase tracking-wide">
                            RPPM Memerlukan Perbaikan / Revisi
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500 text-white shadow-2xs">
                            Perlu Tindak Lanjut Guru
                        </span>
                    </div>
                    <p class="text-xs text-amber-800 dark:text-amber-300">
                        Catatan dari <strong>{{ $reviewer?->name ?? 'Kepala Sekolah' }}</strong> ({{ $record->reviewed_at ? $record->reviewed_at->translatedFormat('d F Y, H:i') : '-' }} WIB):
                    </p>
                    <div class="mt-2 p-3 rounded-xl bg-white/90 dark:bg-gray-800/90 border border-amber-200 dark:border-amber-800 text-xs text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                        "{{ $record->catatan_kepala_sekolah ?? 'Mohon perbaiki perangkat ajar sesuai arahan supervisi kurikulum.' }}"
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="p-4 bg-gradient-to-r from-slate-50 via-blue-50/40 to-slate-50 dark:from-gray-800 dark:to-blue-950/30 border border-blue-200 dark:border-blue-900/60 rounded-2xl shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-base font-bold shadow-2xs">
                        ⏳
                    </span>
                    <div>
                        <div class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wide">
                            Status Dokumen: Menunggu Peninjauan / Verifikasi Kepala Sekolah
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            RPPM telah disusun oleh guru pengampu dan siap disupervisi oleh Kepala Sekolah.
                        </p>
                    </div>
                </div>
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                    Gunakan tombol di pojok kanan atas untuk <strong class="text-emerald-600">ACC</strong> atau <strong class="text-amber-600">Minta Perbaikan</strong>.
                </div>
            </div>
        </div>
    @endif

    {{-- 2. KARTU DOKUMEN UTAMA (MODERN NARRATIVE PAPER VIEW) --}}
    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/90 dark:border-gray-700 shadow-sm overflow-hidden">

        <!-- KOP DOKUMEN ACADEMIC -->
        <div class="p-6 sm:p-8 bg-gradient-to-b from-slate-50/80 via-white to-white dark:from-gray-800 dark:to-gray-800/90 border-b border-gray-200/80 dark:border-gray-700">
            <div class="text-center space-y-1.5 max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 mb-1">
                    <span>🏛️</span>
                    <span>{{ strtoupper($institution) }}</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">
                    Rencana Pelaksanaan Pembelajaran Mingguan (RPPM)
                </h1>
                <p class="text-xs sm:text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                    Modul Ajar Kurikulum Merdeka • Pembelajaran Bermakna Berbasis Karakter
                </p>
                <div class="pt-2 flex items-center justify-center gap-2 flex-wrap text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $record->mata_pelajaran }}</span>
                    <span>•</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $record->kelas_fase }}</span>
                    <span>•</span>
                    <span>Topik: <strong>{{ $record->topik ?? 'Materi Pembelajaran' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-8 text-xs sm:text-sm leading-relaxed">

            {{-- SECTION I: IDENTITAS MODUL & PERANGKAT PEMBELAJARAN --}}
            <section class="space-y-3">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">I</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Identitas Modul & Informasi Umum
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Kolom Kiri -->
                    <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-gray-700/40 border border-gray-200/70 dark:border-gray-700 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Satuan Pendidikan</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $institution }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Guru Penyusun / Pengampu</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">
                                {{ $guru?->name ?? '-' }}
                                <span class="block text-[11px] text-gray-400 font-mono">NUPTK: {{ $guru?->nuptk ?? ($guru?->nip ?? '-') }}</span>
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Mata Pelajaran</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400 text-right">{{ $record->mata_pelajaran }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Fase / Kelas</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $record->kelas_fase }}</span>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-gray-700/40 border border-gray-200/70 dark:border-gray-700 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Topik / Bab Materi</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $record->topik ?? '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Alokasi Waktu</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $record->alokasi_waktu ?? '2 x 40 Menit' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Lintas Disiplin Ilmu</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $content['lintas_disiplin'] ?? 'Terintegrasi Antar Bidang Studi' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">Target Peserta Didik</span>
                            <span class="font-bold text-gray-900 dark:text-white text-right">{{ $content['identifikasi']['kebutuhan_khusus'] ?? 'Peserta Didik Reguler / Heterogen' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- SECTION II: KOMPONEN ACUAN CP, ATP & TUJUAN PEMBELAJARAN (TP) --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">II</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Capaian Pembelajaran (CP) & Alur Tujuan Pembelajaran (ATP)
                    </h2>
                </div>

                <!-- Box Capaian Pembelajaran -->
                <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-50/50 via-white to-slate-50/50 dark:from-gray-800 dark:to-gray-700/50 border border-emerald-200 dark:border-emerald-800 space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-600 text-white shadow-2xs">
                                Capaian Pembelajaran (CP)
                            </span>
                            @if($record->capaianPembelajaran?->kode_cp)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    {{ $record->capaianPembelajaran->kode_cp }}
                                </span>
                            @endif
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                Elemen: {{ $record->capaianPembelajaran?->elemen ?? 'Materi Inti' }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400">Tingkat: {{ $record->capaianPembelajaran?->tingkat ?? 'Fase D' }}</span>
                    </div>
                    <div class="text-gray-800 dark:text-gray-200 leading-relaxed pt-1 text-xs sm:text-sm italic pl-3 border-l-4 border-emerald-500">
                        "{{ $content['capaian_pembelajaran'] ?? $record->capaianPembelajaran?->deskripsi_cp ?? 'Peserta didik memahami konsep dasar dan mampu mengaplikasikan dalam penyelesaian masalah kontekstual.' }}"
                    </div>
                </div>

                <!-- Box Alur Tujuan Pembelajaran -->
                @if($record->alurTujuanPembelajaran)
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alur Tujuan Pembelajaran (ATP):</span>
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                {{ $record->alurTujuanPembelajaran->kode_atp ?? 'ATP' }}
                            </span>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">
                            {{ $record->alurTujuanPembelajaran->judul_atp ?? '-' }}
                        </p>
                        @if($record->alurTujuanPembelajaran->deskripsi_atp)
                            <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed mt-1">
                                {{ $record->alurTujuanPembelajaran->deskripsi_atp }}
                            </p>
                        @endif
                    </div>
                @endif

                <!-- DAFTAR TUJUAN PEMBELAJARAN (TP) DENGAN DESKRIPSI LENGKAP -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🎯</span>
                            <span>Tujuan Pembelajaran (TP) yang Dicapai Minggu Ini</span>
                        </h3>
                        <span class="text-xs text-gray-400">
                            {{ $selectedTps->count() }} Rumusan TP Terpilih
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50/90 dark:bg-gray-800 text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="w-20 py-3 px-4 text-center whitespace-nowrap">Kode</th>
                                    <th class="w-48 py-3 px-5 whitespace-nowrap">Pokok Bahasan / Materi</th>
                                    <th class="py-3 px-6">Deskripsi Rumusan Tujuan Pembelajaran (TP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                @forelse($selectedTps as $tp)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold">
                                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                {{ $tp->kode_tp }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-5 font-bold text-gray-900 dark:text-white">
                                            {{ $tp->materi ?? ($record->topik ?? '-') }}
                                        </td>
                                        <td class="py-3 px-6 text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                                            {{ $tp->deskripsi_tp }}
                                        </td>
                                    </tr>
                                @empty
                                    @if(!empty($content['tujuan_pembelajaran']) && is_array($content['tujuan_pembelajaran']))
                                        @foreach($content['tujuan_pembelajaran'] as $idx => $tpItem)
                                            <tr>
                                                <td class="py-3 px-4 text-center font-mono font-bold text-gray-400">TP-{{ $idx + 1 }}</td>
                                                <td class="py-3 px-5 font-bold text-gray-900 dark:text-white">{{ $record->topik ?? 'Materi Pembelajaran' }}</td>
                                                <td class="py-3 px-6 text-gray-800 dark:text-gray-200 leading-relaxed">
                                                    {{ is_array($tpItem) ? ($tpItem['rumusan'] ?? ($tpItem['tujuan'] ?? '-')) : $tpItem }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3" class="py-6 text-center text-gray-400 italic">
                                                Belum ada Tujuan Pembelajaran yang ditautkan ke RPPM ini.
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- SECTION III: PROFIL PELAJAR PANCASILA, MODEL & METODE PEDAGOGIS --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">III</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Dimensi Profil Pelajar & Pendekatan Pedagogis
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dimensi Karakter Pelajar Pancasila & Rahmatan Lil Alamin -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-gray-700/40 border border-gray-200/70 dark:border-gray-700 space-y-3">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                            <span>🌟</span>
                            <span>Dimensi Profil Pelajar Pancasila (P5)</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if(!empty($content['profil_lulusan']) && is_array($content['profil_lulusan']))
                                @foreach($content['profil_lulusan'] as $prof)
                                    @php
                                        $label = is_array($prof) ? ($prof['dimensi'] ?? ($prof['elemen'] ?? 'Karakter')) : $prof;
                                    @endphp
                                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800 shadow-2xs flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                        {{ $label }}
                                    </span>
                                @endforeach
                            @else
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-purple-800 dark:text-purple-200 border border-purple-200 shadow-2xs">✓ Beriman & Bertakwa kepada Tuhan YME</span>
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-purple-800 dark:text-purple-200 border border-purple-200 shadow-2xs">✓ Bernalar Kritis</span>
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-purple-800 dark:text-purple-200 border border-purple-200 shadow-2xs">✓ Mandiri</span>
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-purple-800 dark:text-purple-200 border border-purple-200 shadow-2xs">✓ Bergotong Royong</span>
                            @endif
                        </div>
                    </div>

                    <!-- Model, Metode & Praktik Digital -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-gray-700/40 border border-gray-200/70 dark:border-gray-700 space-y-2.5">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                            <span>💡</span>
                            <span>Model & Metode Pembelajaran</span>
                        </div>
                        <div class="space-y-1.5 text-xs sm:text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium">Model:</span>
                                <strong class="text-gray-900 dark:text-white ml-1">{{ $content['model_pembelajaran'] ?? ($content['praktik_pedagogis'] ?? 'Problem-Based Learning (PBL)') }}</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium">Metode:</span>
                                <span class="text-gray-800 dark:text-gray-200 ml-1">{{ $content['metode_pembelajaran'] ?? 'Eksplorasi Konsep, Diskusi Kelompok, Tanya Jawab & Unjuk Hasil' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium">Media & Digital:</span>
                                <span class="text-gray-800 dark:text-gray-200 ml-1">{{ $content['pemanfaatan_digital'] ?? 'Media Presentasi Interaktif, LKPD Kontekstual, dan Modul Ajar Digital' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- SECTION IV: DESAIN PEMBELAJARAN & PEMANTIK --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">IV</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Desain Pembelajaran, Pemahaman Bermakna & Pemantik
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Pemahaman Bermakna -->
                    <div class="p-5 rounded-2xl bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/60 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            <span>🧠</span>
                            <span>Pemahaman Bermakna</span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                            {{ $content['pemahaman_bermakna'] ?? 'Peserta didik menyadari bahwa konsep materi ini memiliki korelasi erat dalam memecahkan permasalahan nyata di kehidupan sehari-hari dan menumbuhkan rasa tanggung jawab sosial.' }}
                        </p>
                    </div>

                    <!-- Pertanyaan Pemantik -->
                    <div class="p-5 rounded-2xl bg-amber-50/40 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-800/60 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                            <span>❓</span>
                            <span>Pertanyaan Pemantik</span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                            {{ $content['pertanyaan_pemantik'] ?? 'Bagaimana konsep materi ini dapat membantu kita memahami peristiwa di sekitar kita? Apa dampak positif yang dapat kita berikan setelah menguasai materi ini?' }}
                        </p>
                    </div>
                </div>

                <!-- Sarana Prasarana & Lingkungan Belajar -->
                @if(!empty($content['sarana_prasarana']) || !empty($content['lingkungan_belajar']))
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-4">
                        @if(!empty($content['sarana_prasarana']))
                            <div class="flex-1 space-y-1">
                                <span class="text-xs font-bold text-gray-500 uppercase">Sarana & Prasarana:</span>
                                <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $content['sarana_prasarana'] }}</p>
                            </div>
                        @endif
                        @if(!empty($content['lingkungan_belajar']))
                            <div class="flex-1 space-y-1">
                                <span class="text-xs font-bold text-gray-500 uppercase">Lingkungan & Sumber Belajar:</span>
                                <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $content['lingkungan_belajar'] }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            {{-- SECTION V: LANGKAH-LANGKAH KEGIATAN PEMBELAJARAN (DIFERENSIASI) --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">V</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Langkah-Langkah Kegiatan Pembelajaran
                    </h2>
                </div>

                @if(!empty($content['pengalaman_belajar']) && is_array($content['pengalaman_belajar']))
                    <div class="space-y-3">
                        @foreach($content['pengalaman_belajar'] as $idx => $keg)
                            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                                            {{ $idx + 1 }}
                                        </span>
                                        <h3 class="font-extrabold text-gray-900 dark:text-white text-xs sm:text-sm">
                                            {{ $keg['tahap'] ?? 'Aktivitas Pembelajaran' }}
                                        </h3>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        ⏱️ {{ $keg['alokasi_waktu'] ?? '15 Menit' }}
                                    </span>
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-normal pl-8">
                                    {!! nl2br(e($keg['kegiatan'] ?? '-')) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Alur Default Naratif Standar Kurikulum Merdeka -->
                    <div class="space-y-3">
                        <!-- 1. Pendahuluan -->
                        <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-gray-700/30 border border-gray-200/80 dark:border-gray-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                                    Kegiatan Pendahuluan
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-white dark:bg-gray-800 border border-gray-200">⏱️ 15 Menit</span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed pl-8">
                                Guru membuka kelas dengan salam hangat, memimpin doa bersama, memeriksa presensi kehadiran murid, melakukan apersepsi mengaitkan materi pertemuan sebelumnya, serta memotivasi murid dan menyampaikan rumusan Tujuan Pembelajaran yang ingin dicapai hari ini.
                            </p>
                        </div>

                        <!-- 2. Kegiatan Inti -->
                        <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-gray-700/30 border border-gray-200/80 dark:border-gray-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                                    Kegiatan Inti (Eksplorasi Konsep & Kolaborasi)
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-white dark:bg-gray-800 border border-gray-200">⏱️ 55 Menit</span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed pl-8">
                                Murid mengamati materi stimulus kontekstual, berdiskusi aktif dalam kelompok heterogen, melakukan penyelidikan terbimbing, merumuskan solusi, mempresentasikan hasil karya di depan kelas, serta saling memberikan tanggapan dengan bimbingan dan umpan balik penguatan dari guru.
                            </p>
                        </div>

                        <!-- 3. Kegiatan Penutup -->
                        <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-gray-700/30 border border-gray-200/80 dark:border-gray-700 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                                    Kegiatan Penutup & Refleksi
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-white dark:bg-gray-800 border border-gray-200">⏱️ 10 Menit</span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed pl-8">
                                Guru bersama murid menyimpulkan intisari pembelajaran hari ini, melaksanakan refleksi pemahaman adab dan keilmuan, menyampaikan rencana materi pertemuan pekan berikutnya, dan menutup kegiatan pembelajaran dengan doa bersama dan rasa syukur.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Rincian Diferensiasi Pembelajaran (Jika Ada) -->
                @if(!empty($content['diferensiasi']))
                    <div class="pt-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-500 mb-2">
                            Penerapan Diferensiasi Pembelajaran:
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="p-3.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-1">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Diferensiasi Konten</span>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $content['diferensiasi']['konten'] ?? 'Penyajian materi melalui teks, infografis, dan video.' }}</p>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-1">
                                <span class="text-xs font-bold text-blue-700 dark:text-blue-400">Diferensiasi Proses</span>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $content['diferensiasi']['proses'] ?? 'Bimbingan berjenjang (scaffolding) sesuai kesiapan murid.' }}</p>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-1">
                                <span class="text-xs font-bold text-purple-700 dark:text-purple-400">Diferensiasi Produk</span>
                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ $content['diferensiasi']['produk'] ?? 'Kebebasan memilih media presentasi laporan/karya.' }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            {{-- SECTION VI: ASESMEN & KRITERIA KETERCAPAIAN TUJUAN (KKTP) --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">VI</span>
                    <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Rencana Asesmen & Rubrik Ketercapaian (KKTP)
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 space-y-1">
                        <span class="text-xs font-extrabold text-blue-700 dark:text-blue-300">1. Asesmen Diagnostik (Awal)</span>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Pertanyaan pemantik lisan di awal pembelajaran untuk memetakan kesiapan awal dan minat belajar murid.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 space-y-1">
                        <span class="text-xs font-extrabold text-emerald-700 dark:text-emerald-300">2. Asesmen Formatif (Proses)</span>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Observasi keaktifan diskusi kelompok, lembar kerja siswa (LKPD), serta unjuk pemahaman lisan/tertulis.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 space-y-1">
                        <span class="text-xs font-extrabold text-purple-700 dark:text-purple-300">3. Asesmen Sumatif (Akhir)</span>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Tes tertulis pemahaman konsep materi dan penilaian proyek/performa di akhir bab atau lingkup materi.</p>
                    </div>
                </div>

                <!-- MATRIKS RUBRIK KKTP -->
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📊</span>
                            <span>Matriks Rubrik Penilaian Ketercapaian (KKTP)</span>
                        </h3>
                        <span class="text-[11px] text-gray-400">Skala 4 Kategori Capaian</span>
                    </div>

                    @if(!empty($content['kktp']) && is_array($content['kktp']))
                        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-gray-50/90 dark:bg-gray-800 text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 text-center">
                                    <tr>
                                        <th class="py-3 px-4 text-left w-1/4">Kriteria Penilaian</th>
                                        <th class="py-3 px-3 text-rose-700 dark:text-rose-400 w-[18%]">Perlu Bimbingan<br><span class="text-[10px] text-gray-400">(&lt; 70)</span></th>
                                        <th class="py-3 px-3 text-amber-700 dark:text-amber-400 w-[18%]">Cukup<br><span class="text-[10px] text-gray-400">(70 - 79)</span></th>
                                        <th class="py-3 px-3 text-blue-700 dark:text-blue-400 w-[18%]">Baik<br><span class="text-[10px] text-gray-400">(80 - 89)</span></th>
                                        <th class="py-3 px-3 text-emerald-700 dark:text-emerald-400 w-[21%]">Sangat Baik<br><span class="text-[10px] text-gray-400">(90 - 100)</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                    @foreach($content['kktp'] as $kktp)
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="py-3 px-4 font-bold text-gray-900 dark:text-white leading-relaxed">
                                                {{ $kktp['kriteria'] ?? '-' }}
                                            </td>
                                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300 leading-relaxed text-center">
                                                {{ $kktp['bimbingan'] ?? '-' }}
                                            </td>
                                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300 leading-relaxed text-center">
                                                {{ $kktp['cukup'] ?? '-' }}
                                            </td>
                                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300 leading-relaxed text-center">
                                                {{ $kktp['baik'] ?? '-' }}
                                            </td>
                                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300 leading-relaxed text-center font-medium">
                                                {{ $kktp['sangat_baik'] ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300">
                            <strong>Standar Kriteria Ketuntasan:</strong>
                            <ul class="list-disc pl-5 mt-1 space-y-1">
                                <li><strong>Perlu Bimbingan (&lt; 70):</strong> Belum mencapai ketuntasan minimum, memerlukan remedial dan pendampingan individu.</li>
                                <li><strong>Cukup (70 - 79):</strong> Mencapai ketuntasan minimum dengan bimbingan umum guru.</li>
                                <li><strong>Baik (80 - 89):</strong> Mencapai ketuntasan dan mampu menerapkan konsep secara mandiri.</li>
                                <li><strong>Sangat Baik (90 - 100):</strong> Melampaui ketuntasan target dan mampu membimbing teman sebaya (tutor sebaya).</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            {{-- SECTION VII: LAMPIRAN & REFERENSI (JIKA ADA) --}}
            @if(!empty($content['lampiran']))
                <section class="space-y-3">
                    <div class="flex items-center gap-2.5 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                        <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shadow-2xs">VII</span>
                        <h2 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                            Lampiran, Bahan Ajar & Glosarium
                        </h2>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700 text-xs sm:text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                        {!! $content['lampiran'] !!}
                    </div>
                </section>
            @endif

            {{-- SECTION VIII: LEMBAR PENGESAHAN DOKUMEN RESMI --}}
            <section class="pt-6 border-t-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="text-center mb-6">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Lembar Pengesahan Modul Ajar</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-center text-xs sm:text-sm">
                    <!-- Kiri: Kepala Sekolah -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-gray-700/30 border border-gray-200/80 dark:border-gray-700 space-y-4">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Mengetahui & Menyetujui,</p>
                            <p class="font-extrabold text-gray-900 dark:text-white text-sm">Kepala Sekolah</p>
                        </div>
                        <div class="py-4">
                            @if($status === 'approved')
                                <div class="inline-flex flex-col items-center justify-center p-3 rounded-2xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-2xs">
                                    <span class="text-2xl font-black">✓ ACC</span>
                                    <span class="text-[10px] font-mono font-bold mt-1">TERVALIDASI RESMI</span>
                                    <span class="text-[9px] text-gray-400">{{ $record->reviewed_at ? $record->reviewed_at->format('d/m/Y') : '-' }}</span>
                                </div>
                            @else
                                <div class="h-16 flex items-center justify-center text-xs text-gray-400 italic">
                                    (Menunggu Pengesahan Digital)
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white underline text-sm">
                                {{ $reviewer?->name ?? (\App\Models\User::role('kepala_sekolah')->first()?->name ?? 'Kepala SMP Islam Baitul Makmur') }}
                            </p>
                            <p class="text-xs text-gray-500 font-mono mt-0.5">NIP: 19780512 200501 1 003</p>
                        </div>
                    </div>

                    <!-- Kanan: Guru Pengampu -->
                    <div class="p-5 rounded-2xl bg-slate-50/70 dark:bg-gray-700/30 border border-gray-200/80 dark:border-gray-700 space-y-4">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Jawa Timur, {{ $record->created_at ? $record->created_at->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</p>
                            <p class="font-extrabold text-gray-900 dark:text-white text-sm">Guru Mata Pelajaran</p>
                        </div>
                        <div class="py-4">
                            <div class="inline-flex flex-col items-center justify-center p-3 rounded-2xl bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-2xs">
                                <span class="text-xl">✍️</span>
                                <span class="text-[10px] font-mono font-bold mt-1">PENYUSUN MODUL</span>
                                <span class="text-[9px] text-gray-400">{{ $record->created_at ? $record->created_at->format('d/m/Y') : '-' }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white underline text-sm">
                                {{ $guru?->name ?? 'Guru Pengampu' }}
                            </p>
                            <p class="text-xs text-gray-500 font-mono mt-0.5">NUPTK: {{ $guru?->nuptk ?? ($guru?->nip ?? '-') }}</p>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>
