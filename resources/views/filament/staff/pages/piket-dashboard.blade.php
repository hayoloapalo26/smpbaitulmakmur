<x-filament-panels::page>
    {{-- 1. HEADER BANNER PENUGASAN GURU PIKET --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r {{ $isCurrentUserPiket ? 'from-emerald-700 via-teal-700 to-emerald-800' : 'from-slate-800 via-blue-900 to-indigo-900' }} p-6 text-white shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold mb-2 {{ $isCurrentUserPiket ? 'bg-emerald-500/30 text-emerald-100 border border-emerald-400/30' : 'bg-white/10 text-white/90 border border-white/20' }}">
                    <span class="w-2 h-2 rounded-full {{ $isCurrentUserPiket ? 'bg-emerald-400 animate-ping' : 'bg-blue-400' }}"></span>
                    {{ $isCurrentUserPiket ? 'Anda Bertugas Sebagai Guru Piket Hari Ini' : 'Dasbor Monitoring Guru Piket' }}
                </div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Selamat Bertugas, {{ auth()->user()->name }}!
                </h1>
                <p class="mt-1 text-sm text-white/80">
                    Hari ini: <strong>{{ $hariIni }}, {{ now()->translatedFormat('d F Y') }}</strong> • Melayani ketertiban, presensi, kedisiplinan, dan koordinasi jam kosong sekolah.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <div class="bg-black/25 backdrop-blur-md rounded-xl p-3 border border-white/15">
                    <div class="text-[11px] uppercase tracking-wider text-white/70 font-medium">Petugas Piket Hari Ini</div>
                    <div class="font-bold text-sm text-white mt-0.5">
                        @if(!empty($petugasHariIni))
                            {{ implode(' • ', $petugasHariIni) }}
                        @else
                            <span class="text-amber-300 italic text-xs">Belum ada jadwal terdaftar</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. METRIK RINGKASAN PRESENSI HARI INI --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- Total Siswa --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa</span>
                <span class="p-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-o-user-group" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalSiswa }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">Terdaftar di lembaga</div>
        </div>

        {{-- Hadir --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 shadow-2xs dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Siswa Hadir</span>
                <span class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $siswaHadir }}</div>
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $persenKehadiran }}% kehadiran</div>
        </div>

        {{-- Terlambat --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-4 shadow-2xs dark:border-amber-900/50 dark:bg-amber-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">Terlambat</span>
                <span class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300">
                    <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $siswaTerlambat }}</div>
            <div class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">Pintu gerbang pagi</div>
        </div>

        {{-- Izin --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-4 shadow-2xs dark:border-blue-900/50 dark:bg-blue-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-blue-800 dark:text-blue-300">Izin</span>
                <span class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $siswaIzin }}</div>
            <div class="text-[11px] text-blue-600 dark:text-blue-400 mt-0.5">Surat / Dispensasi</div>
        </div>

        {{-- Sakit --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50/40 p-4 shadow-2xs dark:border-purple-900/50 dark:bg-purple-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-purple-800 dark:text-purple-300">Sakit</span>
                <span class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                    <x-filament::icon icon="heroicon-o-heart" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $siswaSakit }}</div>
            <div class="text-[11px] text-purple-600 dark:text-purple-400 mt-0.5">Dengan keterangan</div>
        </div>

        {{-- Alpa --}}
        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-4 shadow-2xs dark:border-rose-900/50 dark:bg-rose-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-rose-800 dark:text-rose-300">Alpa / Belum</span>
                <span class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-900/50 text-rose-700 dark:text-rose-300">
                    <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-rose-700 dark:text-rose-300">{{ $siswaAlpa }}</div>
            <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">Tanpa kabar</div>
        </div>
    </div>

    {{-- 3. MENU PINTAS OPERASIONAL GURU PIKET (QUICK ACTION BUTTONS) --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-bolt" class="w-5 h-5 text-amber-500" />
                <span>Aksi & Fitur Cepat Guru Piket</span>
            </div>
        </x-slot>
        <x-slot name="description">
            Akses langsung ke seluruh fitur operasional yang berhubungan dengan tugas harian guru piket.
        </x-slot>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 pt-2">
            {{-- 1. Scan QR --}}
            <a href="{{ route('filament.staff.pages.scanner-presensi') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-qr-code" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400">Scan QR Siswa</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Pintu Gerbang Pagi</span>
            </a>

            {{-- 2. Input Absensi Manual --}}
            <a href="{{ route('filament.staff.pages.absensi-manual') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Presensi Manual</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Kolektif Per Kelas</span>
            </a>

            {{-- 3. Izin Siswa --}}
            <a href="{{ route('filament.staff.resources.presensi-izin-siswas.index') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">Catat Izin Siswa</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Dispensasi & Pulang</span>
            </a>

            {{-- 4. Pelanggaran Atribut --}}
            <a href="{{ route('filament.staff.resources.attribute-violations.index') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-rose-500 hover:bg-rose-50/50 dark:hover:bg-rose-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-rose-600 dark:group-hover:text-rose-400">Pelanggaran Atribut</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Dasi, Sabuk, Topi</span>
            </a>

            {{-- 5. Penilaian Karakter --}}
            <a href="{{ route('filament.staff.pages.character-score-report') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-amber-500 hover:bg-amber-50/50 dark:hover:bg-amber-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-star" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">Poin Karakter</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Akhlak & Disiplin</span>
            </a>

            {{-- 6. Tukar Piket --}}
            <a href="{{ route('filament.staff.resources.duty-swap-requests.index') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Tukar Jadwal</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Pengajuan Swap Shift</span>
            </a>

            {{-- 7. Jadwal Piket 1 Semester --}}
            <a href="{{ route('filament.staff.resources.duty-schedules.index') }}" class="flex flex-col items-center justify-center p-3.5 text-center rounded-xl border border-gray-200 dark:border-gray-800 hover:border-teal-500 hover:bg-teal-50/50 dark:hover:bg-teal-950/20 transition group">
                <span class="p-2.5 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 group-hover:scale-110 transition">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-6 h-6" />
                </span>
                <span class="mt-2 text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-teal-600 dark:group-hover:text-teal-400">Jadwal Piket</span>
                <span class="text-[10px] text-gray-400 mt-0.5">Atur 1 Semester</span>
            </a>
        </div>
    </x-filament::section>

    {{-- 4. DUA KOLOM: MONITORING INVAL & SISWA BERMASALAH HARI INI --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- KOLOM KIRI: MONITORING KELAS KOSONG & GURU IZIN (INVAL PIKET) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5 text-indigo-500" />
                        <span>Monitoring Jam Kosong & Guru Izin (Inval)</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ count($guruIzinHariIni) > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' }}">
                        {{ count($guruIzinHariIni) }} Guru Izin
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">
                Guru piket bertanggung jawab mengkondisikan atau meng-inval kelas yang guru pengampunya berhalangan.
            </x-slot>

            @if(count($guruIzinHariIni) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold">
                            <tr>
                                <th class="pb-2">Guru Izin</th>
                                <th class="pb-2">Kelas & Mapel</th>
                                <th class="pb-2">Keterangan</th>
                                <th class="pb-2">Guru Inval / Pengganti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($guruIzinHariIni as $att)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2.5 font-bold text-gray-900 dark:text-white">
                                        {{ $att->teacher?->name ?? 'Guru' }}
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $att->classroom?->name ?? ($att->schedule?->classroom?->name ?? '-') }}
                                        </span>
                                        <div class="text-[11px] text-gray-400">
                                            {{ $att->subject?->name ?? ($att->schedule?->subject?->name ?? '-') }}
                                        </div>
                                    </td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'sakit' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-700' } }}">
                                            {{ $att->status }}
                                        </span>
                                        @if($att->keterangan)
                                            <div class="text-[11px] text-gray-500 mt-0.5 truncate max-w-[120px]" title="{{ $att->keterangan }}">{{ $att->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        @if($att->substituteTeacher)
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                                <x-filament::icon icon="heroicon-m-check-badge" class="w-3.5 h-3.5" />
                                                {{ $att->substituteTeacher->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 italic text-[11px]">
                                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-3.5 h-3.5" />
                                                Ditangani Guru Piket
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-10 h-10 mx-auto mb-2 text-emerald-500 opacity-80" />
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">Seluruh Guru Hadir Mengajar</p>
                    <p class="text-xs text-gray-400 mt-0.5">Tidak ada guru yang izin atau jam kosong hari ini.</p>
                </div>
            @endif
        </x-filament::section>

        {{-- KOLOM KANAN: SISWA TERLAMBAT / ALPA & HUBUNGI ORANG TUA --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-user-minus" class="w-5 h-5 text-rose-500" />
                        <span>Siswa Terlambat & Tidak Hadir Hari Ini</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ count($siswaBermasalahHariIni) }} Siswa
                    </span>
                </div>
            </x-slot>
            <x-slot name="description">
                Piket dapat memantau keterlambatan dan langsung menghubungi wali murid melalui WhatsApp.
            </x-slot>

            @if(count($siswaBermasalahHariIni) > 0)
                <div class="overflow-x-auto max-h-[350px]">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold sticky top-0 bg-white dark:bg-gray-900">
                            <tr>
                                <th class="pb-2">Siswa</th>
                                <th class="pb-2">Kelas</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($siswaBermasalahHariIni as $att)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2.5 font-bold text-gray-900 dark:text-white">
                                        {{ $att->student?->name ?? 'Siswa' }}
                                        @if($att->is_late)
                                            <span class="block text-[10px] font-normal text-amber-600">Masuk: {{ $att->waktu_kedatangan ? \Carbon\Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $att->student?->classroom?->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        @if($att->is_late && $att->status === 'hadir')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($att->status) { 'alpa' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', 'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', 'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', default => 'bg-gray-100 text-gray-800' } }}">
                                                {{ $att->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right">
                                        @if(!empty($att->student?->no_telepon_orang_tua))
                                            @php
                                                $cleanPhone = preg_replace('/[^0-9]/', '', $att->student->no_telepon_orang_tua);
                                                if (str_starts_with($cleanPhone, '0')) {
                                                    $cleanPhone = '62' . substr($cleanPhone, 1);
                                                }
                                                $waText = "Assalamu'alaikum Warahmatullahi Wabarakatuh, Bapak/Ibu wali dari " . $att->student->name . ".\nKami dari Guru Piket sekolah menginformasikan bahwa ananda hari ini tercatat " . ($att->is_late ? "TERLAMBAT masuk sekolah (Pukul {$att->waktu_kedatangan})" : strtoupper($att->status) . " di sekolah") . ".\nMohon perhatian dan konfirmasinya. Terima kasih.";
                                                $waUrl = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($waText);
                                            @endphp
                                            <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 text-[11px] font-medium transition shadow-2xs">
                                                <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="w-3.5 h-3.5" />
                                                <span>Kirim WA</span>
                                            </a>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">No HP (-)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-face-smile" class="w-10 h-10 mx-auto mb-2 text-emerald-500 opacity-80" />
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">Presensi Siswa Tertib</p>
                    <p class="text-xs text-gray-400 mt-0.5">Belum ada catatan siswa terlambat atau alpa hari ini.</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- 5. PELANGGARAN ATRIBUT HARI INI --}}
    @if(count($pelanggaranAtributHariIni) > 0)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="w-5 h-5 text-amber-500" />
                    <span>Catatan Pelanggaran Atribut & Kerapian Hari Ini</span>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-semibold">
                        <tr>
                            <th class="pb-2">Nama Siswa</th>
                            <th class="pb-2">Kelas</th>
                            <th class="pb-2">Atribut yang Dilanggar</th>
                            <th class="pb-2">Catatan Tambahan</th>
                            <th class="pb-2">Guru Piket Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($pelanggaranAtributHariIni as $v)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-2 font-bold text-gray-900 dark:text-white">{{ $v->student?->name ?? 'Siswa' }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $v->student?->classroom?->name ?? '-' }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        {{ is_array($v->attribute_type) ? implode(', ', $v->attribute_type) : $v->attribute_type }}
                                    </span>
                                </td>
                                <td class="py-2 text-gray-500">{{ $v->jenis_pelanggaran ?? '-' }}</td>
                                <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ $v->teacher?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
