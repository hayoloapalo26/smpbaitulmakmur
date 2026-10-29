@php
    $record = $record ?? (isset($getRecord) && is_callable($getRecord) ? $getRecord() : null);
    if (!$record) return;

    $student = $record->student;
    $classroom = $student?->classroom;
    $waliKelas = $classroom?->waliKelas;
    $case = $record->studentCase;
    $teacher = $record->teacher;

    $ortu = $student?->nama_ayah ?: ($student?->nama_ibu ?: '-');
    $status = $record->status ?? 'menunggu';
    $statusBadge = match($status) {
        'hadir' => [
            'bg' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
            'icon' => '✅',
            'label' => 'Telah Hadir Memenuhi Panggilan',
        ],
        'tidak_hadir' => [
            'bg' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-300 dark:border-rose-700',
            'icon' => '❌',
            'label' => 'Mangkir / Tidak Hadir',
        ],
        default => [
            'bg' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300 dark:border-amber-700',
            'icon' => '⏳',
            'label' => 'Menunggu Hadir / Terjadwal',
        ],
    };

    $tglPemanggilan = $record->tanggal_pemanggilan 
        ? \Carbon\Carbon::parse($record->tanggal_pemanggilan)->translatedFormat('d F Y') 
        : '-';

    $tglPertemuan = $record->tanggal_pertemuan 
        ? \Carbon\Carbon::parse($record->tanggal_pertemuan)->translatedFormat('l, d F Y') 
        : '-';

    $isKepsek = auth()->user()?->hasRole('kepala_sekolah');
@endphp

<div class="w-full space-y-4 my-1 text-sm">
    @if($isKepsek)
        <div class="p-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2 text-xs">
                <span class="text-base">👔</span>
                <div>
                    <span class="font-bold">Mode Tinjauan Kepala Sekolah</span>
                    <span class="block text-[11px] text-blue-100">Menampilkan rekapitulasi data pemanggilan orang tua secara ringkas (hanya lihat / non-editable).</span>
                </div>
            </div>
            <span class="px-2.5 py-1 bg-white/20 backdrop-blur-sm text-white font-mono text-[11px] font-bold rounded-lg border border-white/30">
                Read-Only
            </span>
        </div>
    @endif

    {{-- KARTU TABEL RINGKASAN UTAMA --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        {{-- Header Ringkasan --}}
        <div class="bg-gradient-to-r from-gray-50 to-slate-100 dark:from-gray-800 dark:to-gray-800/90 p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-primary-100 dark:bg-primary-950/70 text-primary-700 dark:text-primary-300 flex items-center justify-center shrink-0 border border-primary-200 dark:border-primary-800 shadow-sm text-xl">
                    📨
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black text-gray-900 dark:text-white leading-tight">
                            Surat Panggilan Orang Tua / Wali Murid
                        </h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span class="font-mono bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300">
                            No. Surat: {{ $record->nomor_surat ?? '-' }}
                        </span>
                        <span>•</span>
                        <span>Tgl Surat: <strong>{{ $tglPemanggilan }}</strong></span>
                    </div>
                </div>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black tracking-wide border shadow-sm {{ $statusBadge['bg'] }}">
                    <span>{{ $statusBadge['icon'] }}</span>
                    <span>{{ $statusBadge['label'] }}</span>
                </span>
            </div>
        </div>

        {{-- Tabel Ringkasan Detail --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm text-left border-collapse">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    {{-- 1. IDENTITAS SISWA --}}
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40">
                        <td colspan="2" class="px-4 py-2 font-black text-slate-700 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <span>👤</span>
                            <span>A. Data Identitas Siswa</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="w-48 sm:w-56 px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Nama Lengkap Siswa
                        </td>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                            {{ $student?->name ?? '-' }}
                            @if($student?->nis)
                                <span class="ml-2 font-mono text-xs font-normal text-gray-500">(NIS: {{ $student->nis }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Kelas & Wali Kelas
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                Kelas {{ $classroom?->name ?? '-' }}
                            </span>
                            <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">
                                Wali Kelas: <strong>{{ $waliKelas?->name ?? '-' }}</strong>
                            </span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Skor Karakter Terkini
                        </td>
                        <td class="px-4 py-3 font-mono font-bold text-amber-600 dark:text-amber-400">
                            {{ $student?->character_score ?? 100 }} Poin
                        </td>
                    </tr>

                    {{-- 2. DATA WALI MURID --}}
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40">
                        <td colspan="2" class="px-4 py-2 font-black text-slate-700 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <span>👨‍👩‍👧</span>
                            <span>B. Data Orang Tua / Wali Murid</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Nama Orang Tua / Wali
                        </td>
                        <td class="px-4 py-3 font-bold text-gray-800 dark:text-gray-200">
                            {{ $ortu }}
                            @if($student?->nama_ayah && $student?->nama_ibu)
                                <span class="block text-xs font-normal text-gray-500 mt-0.5">Ayah: {{ $student->nama_ayah }} | Ibu: {{ $student->nama_ibu }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Nomor Kontak / WhatsApp
                        </td>
                        <td class="px-4 py-3">
                            @if($student?->no_telepon_orang_tua)
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $student->no_telepon_orang_tua }}</span>
                                    @php
                                        $phoneClean = preg_replace('/[^0-9]/', '', $student->no_telepon_orang_tua);
                                    @endphp
                                    <a href="https://wa.me/{{ $phoneClean }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>💬 Hubungi WA</span>
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-400 italic">Belum terdata</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Alamat Tinggal
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $student?->alamat ?: '-' }}
                        </td>
                    </tr>

                    {{-- 3. JADWAL & TEMPAT PERTEMUAN --}}
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40">
                        <td colspan="2" class="px-4 py-2 font-black text-slate-700 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <span>📅</span>
                            <span>C. Jadwal & Tempat Pertemuan</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Hari & Tanggal Pertemuan
                        </td>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                            {{ $tglPertemuan }}
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Tempat Menghadap
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">
                            🏛️ {{ $record->tempat_pertemuan ?: 'Ruang BK / Ruang Wali Kelas' }}
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Pihak yang Mengundang
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $teacher?->name ?? 'Wali Kelas / Guru BK' }}
                        </td>
                    </tr>

                    {{-- 4. URAIAN MASALAH & KASUS TERKAIT --}}
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40">
                        <td colspan="2" class="px-4 py-2 font-black text-slate-700 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <span>⚠️</span>
                            <span>D. Uraian Masalah & Kasus Siswa</span>
                        </td>
                    </tr>
                    @if($case)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Kasus Terhubung
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-mono font-bold text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/40 px-2 py-0.5 rounded border border-primary-200 dark:border-primary-800 text-xs">
                                    {{ $case->nomor_kasus }}
                                </span>
                                @if($case->kategori_kasus)
                                    @php
                                        $katText = is_array($case->kategori_kasus) ? implode(', ', $case->kategori_kasus) : $case->kategori_kasus;
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        {{ $katText }}
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $case->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    Status Kasus: {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 italic">
                                {{ $case->deskripsi }}
                            </div>
                        </td>
                    </tr>
                    @endif
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Alasan Surat Pemanggilan
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white leading-relaxed">
                            {{ $record->kasus }}
                        </td>
                    </tr>

                    {{-- 5. NOTULA & HASIL KESEPAKATAN --}}
                    <tr class="bg-slate-50/70 dark:bg-slate-800/40">
                        <td colspan="2" class="px-4 py-2 font-black text-slate-700 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <span>📝</span>
                            <span>E. Notula Hasil Pertemuan & Kesepakatan</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Hasil Musyawarah / Notula
                        </td>
                        <td class="px-4 py-3">
                            @if(!empty($record->hasil_pertemuan))
                                <div class="p-3 bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-900 dark:text-emerald-200 leading-relaxed font-medium">
                                    {{ $record->hasil_pertemuan }}
                                </div>
                            @else
                                <div class="text-gray-400 italic text-xs flex items-center gap-1.5">
                                    <span>⏳</span>
                                    <span>Belum ada notula hasil pertemuan (orang tua belum hadir atau proses musyawarah belum berlangsung).</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-800/20 text-xs uppercase tracking-wider">
                            Dokumen / Berita Acara
                        </td>
                        <td class="px-4 py-3">
                            @if(!empty($record->surat_file))
                                @php
                                    $fileUrl = str_starts_with($record->surat_file, 'http')
                                        ? $record->surat_file
                                        : asset('storage/' . $record->surat_file);
                                @endphp
                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 border border-primary-200 dark:border-primary-800 hover:bg-primary-100 transition-all shadow-sm">
                                    <span>🔗</span>
                                    <span>Buka Dokumen / Surat di Google Drive ↗</span>
                                </a>
                            @else
                                <span class="text-gray-400 italic text-xs">Tidak ada lampiran dokumen / tautan Google Drive.</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
