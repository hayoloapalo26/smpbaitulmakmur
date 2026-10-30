@php
    $record = $record ?? (isset($getRecord) ? $getRecord() : (isset($entry) ? $entry->getRecord() : null));
    if ($record) {
        $record->loadMissing(['classroom', 'institution', 'user', 'studentAchievements', 'alumniTracer']);
    }
    $isIkhwan = ($record?->jenis_kelamin === 'L');
@endphp

<div class="space-y-5 text-sm text-gray-800 dark:text-gray-100">
    <!-- 1. HEADER PROFILE / QUICK SUMMARY CARD -->
    <div class="p-5 rounded-2xl bg-gradient-to-r {{ $isIkhwan ? 'from-blue-600 via-indigo-600 to-blue-700' : 'from-pink-600 via-rose-600 to-purple-700' }} text-white shadow-md relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl font-black text-white shadow-inner border border-white/30 shrink-0">
                    {{ strtoupper(substr($record?->name ?? 'S', 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-black tracking-tight text-white">{{ $record?->name ?? '-' }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white/20 border border-white/30 text-white backdrop-blur-sm">
                            {{ $isIkhwan ? '👦 Laki-laki' : '👧 Perempuan' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-white/90 mt-1">
                        <span class="font-mono bg-black/20 px-2 py-0.5 rounded border border-white/20">NIS: {{ $record?->nis ?: '-' }}</span>
                        @if($record?->nisn)
                            <span class="font-mono bg-black/20 px-2 py-0.5 rounded border border-white/20">NISN: {{ $record->nisn }}</span>
                        @endif
                        <span>•</span>
                        <span class="font-semibold">Kelas {{ $record?->classroom?->name ?? 'Belum Ditentukan' }}</span>
                        @if($record?->institution)
                            <span>•</span>
                            <span>{{ $record->institution->name }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Badges -->
            <div class="flex flex-wrap md:flex-col items-start md:items-end gap-2 text-xs">
                @if($record?->status_siswa === 'alumni' || $record?->alumniTracer()->exists())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 text-amber-950 font-black shadow-sm">
                        <span>🎓 Alumni (Lulus {{ $record->tahun_lulus ?: $record->alumniTracer?->tahun_lulus }})</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-400 text-emerald-950 font-black shadow-sm">
                        <span>✅ Siswa Aktif</span>
                    </span>
                @endif

                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-lg bg-white/20 text-white text-[11px] font-semibold border border-white/20">
                        Skor Karakter: <strong>{{ $record?->character_score ?? 80 }}</strong>
                    </span>
                    <span class="px-2.5 py-0.5 rounded-lg {{ $record?->is_administrasi_lunas ? 'bg-emerald-500/80' : 'bg-rose-500/80' }} text-white text-[11px] font-semibold border border-white/20">
                        {{ $record?->is_administrasi_lunas ? 'Administrasi Lunas' : 'Administrasi Belum Lunas' }}
                    </span>
                </div>
            </div>
        </div>
        <!-- Decorative subtle background shape -->
        <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- 2. TABEL RINGKASAN DATA UTAMA & IDENTITAS SISWA -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-m-identification class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-200">1. Data Utama & Identitas Diri Siswa</h4>
            </div>
            <span class="text-[11px] text-gray-500 font-medium">Biodata Lengkap</span>
        </div>
        <table class="w-full text-xs text-left">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Nomor Induk Siswa (NIS)</td>
                    <td class="w-1/4 py-2.5 px-4 font-mono font-bold text-gray-900 dark:text-gray-100">{{ $record?->nis ?: '-' }}</td>
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">NISN (Nasional)</td>
                    <td class="w-1/4 py-2.5 px-4 font-mono font-bold text-gray-900 dark:text-gray-100">{{ $record?->nisn ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Nama Lengkap Siswa</td>
                    <td class="py-2.5 px-4 font-bold text-gray-900 dark:text-gray-100">{{ $record?->name ?: '-' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">NIK Siswa (KTP/KK)</td>
                    <td class="py-2.5 px-4 font-mono text-gray-800 dark:text-gray-200">{{ $record?->nik ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Jenis Kelamin</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        {{ $isIkhwan ? 'Laki-laki (Ikhwan)' : ($record?->jenis_kelamin === 'P' ? 'Perempuan (Akhwat)' : '-') }}
                    </td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Tempat, Tanggal Lahir</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        {{ $record?->tempat_lahir ? $record->tempat_lahir . ', ' : '' }}
                        {{ $record?->tanggal_lahir ? \Carbon\Carbon::parse($record->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                        @if($record?->tanggal_lahir)
                            <span class="text-gray-400 text-[11px]">({{ \Carbon\Carbon::parse($record->tanggal_lahir)->age }} tahun)</span>
                        @endif
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Kelas / Rombel</td>
                    <td class="py-2.5 px-4 font-semibold text-blue-600 dark:text-blue-400">{{ $record?->classroom?->name ?? 'Belum Ditentukan' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Status Siswa</td>
                    <td class="py-2.5 px-4">
                        @if($record?->status_siswa === 'alumni' || $record?->alumniTracer()->exists())
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                🎓 Alumni
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                ✅ Siswa Aktif
                            </span>
                        @endif
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. Telepon / WA Siswa</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        @if($record?->no_telepon_siswa)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->no_telepon_siswa)) }}" target="_blank" class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline">
                                <span>📱 {{ $record->no_telepon_siswa }}</span>
                                <span class="text-[10px] text-gray-400">(Hubungi WA)</span>
                            </a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Unit / Lembaga</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-800 dark:text-gray-200">{{ $record?->institution?->name ?? '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Alamat Tempat Tinggal</td>
                    <td colspan="3" class="py-2.5 px-4 text-gray-800 dark:text-gray-200 font-medium">
                        {{ $record?->alamat_siswa ?: 'Belum diisi' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 3. TABEL RINGKASAN DATA AKADEMIK & RIWAYAT PENDIDIKAN -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-m-academic-cap class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-200">2. Data Riwayat Akademik & Kelulusan</h4>
            </div>
            <span class="text-[11px] text-gray-500 font-medium">Dokumen Sekolah</span>
        </div>
        <table class="w-full text-xs text-left">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Asal Sekolah Sebelumnya</td>
                    <td class="w-1/4 py-2.5 px-4 font-bold text-gray-800 dark:text-gray-200">{{ $record?->asal_sekolah ?: '-' }}</td>
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Tahun Lulus Asal</td>
                    <td class="w-1/4 py-2.5 px-4 text-gray-800 dark:text-gray-200">{{ $record?->tahun_lulus ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. Ijazah</td>
                    <td class="py-2.5 px-4 font-mono text-gray-800 dark:text-gray-200">{{ $record?->no_ijazah ?: '-' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. SKHU</td>
                    <td class="py-2.5 px-4 font-mono text-gray-800 dark:text-gray-200">{{ $record?->no_skhu ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. Peserta UN</td>
                    <td class="py-2.5 px-4 font-mono text-gray-800 dark:text-gray-200">{{ $record?->no_peserta_un ?: '-' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Skor Karakter Siswa</td>
                    <td class="py-2.5 px-4 font-black text-amber-600 dark:text-amber-400">{{ $record?->character_score ?? 80 }} Poin</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 4. TABEL INTEGRASI MESIN PRESENSI & AKUN LOGIN -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-m-cpu-chip class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-200">3. Integrasi Mesin Absensi & Akun Login Siswa</h4>
            </div>
            <span class="text-[11px] text-gray-500 font-medium">Fingerspot & RFID</span>
        </div>
        <table class="w-full text-xs text-left">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Fingerspot PIN / ID Mesin</td>
                    <td class="w-1/4 py-2.5 px-4 font-mono font-bold text-emerald-700 dark:text-emerald-400">
                        {{ $record?->fingerspot_pin ?? $record?->hikvision_employee_no ?? '-' }}
                    </td>
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. Kartu RFID / NFC</td>
                    <td class="w-1/4 py-2.5 px-4 font-mono text-gray-800 dark:text-gray-200">
                        {{ $record?->nfc_card_no ?: '-' }}
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Status Akun Login Siswa</td>
                    <td class="py-2.5 px-4">
                        @if($record?->user)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                                Akun Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                                <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-rose-600" />
                                Belum Memiliki Akun
                            </span>
                        @endif
                    </td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Username & Email Login</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        @if($record?->user)
                            <span class="font-mono font-semibold">{{ $record->user->username }}</span>
                            <span class="text-gray-400">({{ $record->user->email }})</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 5. TABEL DATA ORANG TUA / WALI & KELUARGA -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-m-user-group class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-200">4. Data Orang Tua / Wali & Susunan Keluarga</h4>
            </div>
            <span class="text-[11px] text-gray-500 font-medium">Kontak Keluarga</span>
        </div>
        <table class="w-full text-xs text-left">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">No. Telepon / WA Orang Tua</td>
                    <td class="w-1/4 py-2.5 px-4 text-gray-800 dark:text-gray-200 font-bold">
                        @if($record?->no_telepon_orang_tua)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->no_telepon_orang_tua)) }}" target="_blank" class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline">
                                <span>📱 {{ $record->no_telepon_orang_tua }}</span>
                                <span class="text-[10px] text-gray-400">(Hubungi WA)</span>
                            </a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="w-1/4 py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Posisi Anak dalam Keluarga</td>
                    <td class="w-1/4 py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        {{ $record?->anak_ke ? 'Anak ke-' . $record->anak_ke : '-' }}
                        @if($record?->jumlah_saudara)
                            <span class="text-gray-500">(dari {{ $record->jumlah_saudara }} bersaudara)</span>
                        @endif
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Nama Ayah Kandung</td>
                    <td class="py-2.5 px-4 font-bold text-gray-800 dark:text-gray-200">{{ $record?->nama_ayah ?: '-' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Nama Ibu Kandung</td>
                    <td class="py-2.5 px-4 font-bold text-gray-800 dark:text-gray-200">{{ $record?->nama_ibu ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Tempat, Tgl Lahir Ayah</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        {{ $record?->tempat_lahir_ayah ? $record->tempat_lahir_ayah . ', ' : '' }}
                        {{ $record?->tanggal_lahir_ayah ? \Carbon\Carbon::parse($record->tanggal_lahir_ayah)->translatedFormat('d F Y') : '-' }}
                    </td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Tempat, Tgl Lahir Ibu</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">
                        {{ $record?->tempat_lahir_ibu ? $record->tempat_lahir_ibu . ', ' : '' }}
                        {{ $record?->tanggal_lahir_ibu ? \Carbon\Carbon::parse($record->tanggal_lahir_ibu)->translatedFormat('d F Y') : '-' }}
                    </td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Pekerjaan Ayah</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">{{ $record?->pekerjaan_ayah ?: '-' }}</td>
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Pekerjaan Ibu</td>
                    <td class="py-2.5 px-4 text-gray-800 dark:text-gray-200">{{ $record?->pekerjaan_ibu ?: '-' }}</td>
                </tr>
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                    <td class="py-2.5 px-4 font-semibold text-gray-500 dark:text-gray-400 bg-gray-50/40 dark:bg-gray-700/20">Status Administrasi / Keuangan</td>
                    <td colspan="3" class="py-2.5 px-4">
                        @if($record?->is_administrasi_lunas)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 font-bold text-xs">
                                <x-heroicon-m-check-badge class="w-4 h-4 text-emerald-600" />
                                Lunas (Siswa & Orang Tua Dapat Mengunduh E-Rapor)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 font-bold text-xs">
                                <x-heroicon-m-clock class="w-4 h-4 text-rose-600" />
                                Belum Lunas (Akses Unduh E-Rapor Ditangguhkan Sementara)
                            </span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 6. TABEL RINGKASAN REKAM PRESTASI (JIKA ADA) -->
    @if($record?->studentAchievements && $record->studentAchievements->isNotEmpty())
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
            <div class="px-4 py-3 bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-trophy class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                    <h4 class="font-bold text-xs uppercase tracking-wider text-amber-900 dark:text-amber-200">5. Prestasi & Penghargaan Siswa</h4>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100 font-bold text-[10px]">
                    {{ $record->studentAchievements->count() }} Prestasi
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/60 font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="px-3 py-2 text-center w-10">No</th>
                            <th class="px-3 py-2">Nama Prestasi / Juara</th>
                            <th class="px-3 py-2 text-center">Kategori</th>
                            <th class="px-3 py-2 text-center">Tingkat</th>
                            <th class="px-3 py-2 text-center">Tanggal</th>
                            <th class="px-3 py-2 text-center">Reward</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($record->studentAchievements as $idx => $ach)
                            <tr class="hover:bg-amber-50/30 dark:hover:bg-gray-700/40">
                                <td class="px-3 py-2 text-center font-semibold text-gray-400">{{ $idx + 1 }}</td>
                                <td class="px-3 py-2 font-bold text-gray-800 dark:text-gray-100">{{ $ach->nama_prestasi }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ ucfirst($ach->kategori) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-gray-300">{{ $ach->tingkat }}</td>
                                <td class="px-3 py-2 text-center text-gray-500 font-mono">{{ \Carbon\Carbon::parse($ach->tanggal)->translatedFormat('d M Y') }}</td>
                                <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">{{ $ach->reward_detail ?: ($ach->jenis_reward === 'tanpa_reward' ? '-' : ucfirst($ach->jenis_reward)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
