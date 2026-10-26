<div class="space-y-6 text-sm">
    @if(auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
        <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2 text-xs">
                <span>🎓</span>
                <span class="font-bold">Fitur Kepala Sekolah: Ketercapaian Profil Lulusan Siswa Ini</span>
            </div>
            <a href="{{ \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl(['student_id' => $record->id, 'class_id' => $record->classroom_id]) }}" class="px-3 py-1 bg-white text-amber-700 font-bold text-xs rounded-lg shadow-sm hover:bg-amber-50 transition-all">
                Buka Analisis 3 Aspek →
            </a>
        </div>
    @endif

    <!-- Header Ringkasan Siswa -->
    <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-amber-200/60 dark:border-gray-600 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-full bg-amber-500 text-white font-extrabold flex items-center justify-center text-xl shadow-md">
                {{ strtoupper(substr($record->name, 0, 1)) }}
            </div>
            <div>
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $record->name }}</h3>
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span class="font-mono bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600">NIS: {{ $record->nis }}</span>
                    <span>•</span>
                    <span class="font-semibold text-amber-700 dark:text-amber-400">Kelas: {{ $record->classroom->name ?? '-' }}</span>
                    <span>•</span>
                    <span>{{ $record->jenis_kelamin === 'L' ? 'Laki-laki (Ikhwan)' : 'Perempuan (Akhwat)' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-4 border-t md:border-t-0 md:border-l pt-3 md:pt-0 md:pl-6 border-amber-200 dark:border-gray-600">
            <div class="text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Skor Karakter</div>
                <div class="text-xl font-black text-amber-600 dark:text-amber-400">{{ $record->character_score ?? 100 }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Prestasi</div>
                <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $record->studentAchievements->count() }}</div>
            </div>
        </div>
    </div>

    <!-- SEKSI 1: REKAM JEJAK PRESTASI SISWA (BERSUMBER DARI WAKA KESISWAAN) -->
    <div class="border border-emerald-200 dark:border-emerald-800 rounded-xl overflow-hidden shadow-sm">
        <div class="p-3.5 bg-emerald-50/80 dark:bg-emerald-950/40 border-b border-emerald-200 dark:border-emerald-800 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="text-lg">🏆</span>
                <h4 class="font-bold text-emerald-900 dark:text-emerald-200 text-sm">Rekam Jejak Prestasi Siswa (Inputan Waka Kesiswaan)</h4>
            </div>
            <span class="text-xs px-2 py-0.5 bg-emerald-200 text-emerald-900 font-bold rounded-full">
                {{ $record->studentAchievements->count() }} Prestasi Tercatat
            </span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800">
            @if($record->studentAchievements->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 uppercase font-bold text-gray-600 dark:text-gray-300 border-b">
                            <tr>
                                <th class="px-3 py-2 text-center w-8">No</th>
                                <th class="px-3 py-2">Nama Prestasi / Kejuaraan</th>
                                <th class="px-3 py-2 text-center">Kategori</th>
                                <th class="px-3 py-2 text-center">Tingkat</th>
                                <th class="px-3 py-2 text-center">Tanggal</th>
                                <th class="px-3 py-2 text-center">Bukti / Sertifikat</th>
                                <th class="px-3 py-2 text-center">Diinput Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($record->studentAchievements as $idx => $ach)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-3 py-2 text-center text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $ach->nama_prestasi }}</div>
                                        @if($ach->keterangan)
                                            <div class="text-[11px] text-gray-500 mt-0.5">{{ $ach->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @php
                                            $badgeClass = match($ach->kategori) {
                                                'akademik' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                                'religius' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                default => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                            };
                                            $labelKategori = match($ach->kategori) {
                                                'akademik' => 'Akademik',
                                                'religius' => 'Religius',
                                                default => 'Non-Akademik',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $badgeClass }}">
                                            {{ $labelKategori }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $ach->tingkat }}</td>
                                    <td class="px-3 py-2 text-center text-gray-500 font-mono">{{ \Carbon\Carbon::parse($ach->tanggal)->format('d M Y') }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($ach->sertifikat)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($ach->sertifikat) }}" target="_blank" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 hover:bg-emerald-200">
                                                <span>📄</span>
                                                <span>Lihat</span>
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-[10px]">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center text-gray-500 text-[11px]">{{ $ach->inputBy?->name ?? 'Waka Kesiswaan' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-gray-400 text-xs">
                    <div class="text-2xl mb-1">🏅</div>
                    Belum ada catatan prestasi yang diinputkan oleh Waka Kesiswaan untuk siswa ini.
                </div>
            @endif
        </div>
    </div>

    <!-- SEKSI 2: BIODATA & DATA KELUARGA -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <h4 class="font-bold text-gray-800 dark:text-gray-200 text-xs flex items-center space-x-2">
                <span>📋</span>
                <span>Biodata Lengkap & Kontak Orang Tua</span>
            </h4>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">Tempat, Tanggal Lahir</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $record->tempat_lahir ?? '-' }}, {{ $record->tanggal_lahir ? \Carbon\Carbon::parse($record->tanggal_lahir)->format('d M Y') : '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">Nama Ayah</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $record->nama_ayah ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">Nama Ibu</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $record->nama_ibu ?? '-' }}</span>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">No. HP Orang Tua</span>
                    <span class="font-bold font-mono text-emerald-600">{{ $record->no_telepon_orang_tua ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">Alamat Rumah</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200 truncate max-w-[200px]" title="{{ $record->alamat }}">{{ $record->alamat ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                    <span class="text-gray-500">Status Administrasi</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $record->administrasi_status ?? 'Lengkap' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SEKSI 3: RIWAYAT KASUS & REKAP KEHADIRAN (DUA KOLOM) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Rekap Kehadiran -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden p-3 bg-white dark:bg-gray-800">
            <h5 class="font-bold text-xs text-gray-700 dark:text-gray-300 mb-2 flex items-center space-x-1">
                <span>📅</span>
                <span>Ringkasan Kehadiran</span>
            </h5>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-lg">
                    <div class="font-black text-base">{{ $record->dailyAttendances->where('status', 'hadir')->count() }}</div>
                    <div class="text-[10px]">Hadir</div>
                </div>
                <div class="p-2 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 rounded-lg">
                    <div class="font-black text-base">{{ $record->dailyAttendances->where('status', 'sakit')->count() }}</div>
                    <div class="text-[10px]">Sakit</div>
                </div>
                <div class="p-2 bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 rounded-lg">
                    <div class="font-black text-base">{{ $record->dailyAttendances->where('status', 'izin')->count() }}</div>
                    <div class="text-[10px]">Izin</div>
                </div>
                <div class="p-2 bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 rounded-lg">
                    <div class="font-black text-base">{{ $record->dailyAttendances->where('status', 'alpa')->count() }}</div>
                    <div class="text-[10px]">Alpha</div>
                </div>
            </div>
        </div>

        <!-- Riwayat Kasus Siswa -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden p-3 bg-white dark:bg-gray-800">
            <h5 class="font-bold text-xs text-gray-700 dark:text-gray-300 mb-2 flex items-center space-x-1">
                <span>⚠️</span>
                <span>Catatan Pelanggaran & Kasus ({{ $record->studentCases->count() }})</span>
            </h5>
            @if($record->studentCases->isNotEmpty())
                <div class="space-y-1.5 max-h-28 overflow-y-auto pr-1 text-xs">
                    @foreach($record->studentCases as $sc)
                        <div class="p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded flex justify-between items-center text-[11px]">
                            <span class="font-medium text-gray-800 dark:text-gray-200 truncate max-w-[140px]">{{ $sc->nomor_kasus }}: {{ $sc->deskripsi }}</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $sc->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ ucfirst($sc->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-4 text-center text-gray-400 text-xs">
                    ✅ Tidak ada riwayat pelanggaran kasus.
                </div>
            @endif
        </div>
    </div>
</div>
