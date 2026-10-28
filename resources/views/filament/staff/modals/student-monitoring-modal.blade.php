<div class="space-y-6 text-sm">
    @if(auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
        <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2 text-xs">
                <span>🎓</span>
                <span class="font-bold">Fitur Kepala Sekolah: Ketercapaian Profil Lulusan Siswa Ini</span>
            </div>
            <a href="{{ rescue(fn () => \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl(['student_id' => $record->id, 'class_id' => $record->classroom_id]), '#') }}" class="px-3 py-1 bg-white text-amber-700 font-bold text-xs rounded-lg shadow-sm hover:bg-amber-50 transition-all">
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
                                            @php
                                                $certUrl = str_starts_with($ach->sertifikat, 'http')
                                                    ? $ach->sertifikat
                                                    : \Illuminate\Support\Facades\Storage::url($ach->sertifikat);
                                            @endphp
                                            <a href="{{ $certUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 hover:bg-emerald-200">
                                                <span>🔗</span>
                                                <span>{{ str_starts_with($ach->sertifikat, 'http') ? 'Buka Link Drive' : 'Lihat' }}</span>
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

    <!-- SEKSI 3: REKAP KEHADIRAN & STATUS DISIPLIN -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden p-3 bg-white dark:bg-gray-800">
        <h5 class="font-bold text-xs text-gray-700 dark:text-gray-300 mb-2 flex items-center space-x-1">
            <span>📅</span>
            <span>Ringkasan Kehadiran Harian</span>
        </h5>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                <div class="font-black text-lg">{{ $record->dailyAttendances->where('status', 'hadir')->count() }}</div>
                <div class="text-[10px] font-semibold">Hadir</div>
            </div>
            <div class="p-2.5 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 rounded-lg border border-amber-100 dark:border-amber-900/40">
                <div class="font-black text-lg">{{ $record->dailyAttendances->where('status', 'sakit')->count() }}</div>
                <div class="text-[10px] font-semibold">Sakit</div>
            </div>
            <div class="p-2.5 bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 rounded-lg border border-blue-100 dark:border-blue-900/40">
                <div class="font-black text-lg">{{ $record->dailyAttendances->where('status', 'izin')->count() }}</div>
                <div class="text-[10px] font-semibold">Izin</div>
            </div>
            <div class="p-2.5 bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 rounded-lg border border-rose-100 dark:border-rose-900/40">
                <div class="font-black text-lg">{{ $record->dailyAttendances->where('status', 'alpa')->count() }}</div>
                <div class="text-[10px] font-semibold">Alpha</div>
            </div>
        </div>
    </div>

    <!-- SEKSI 4: KASUS PELANGGARAN SISWA & RIWAYAT PEMANGGILAN ORANG TUA (2 KOLOM) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Kasus Pelanggaran Siswa -->
        <div class="border border-rose-200 dark:border-rose-900/50 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800 flex flex-col">
            <div class="p-3 bg-rose-50/80 dark:bg-rose-950/30 border-b border-rose-200 dark:border-rose-900/50 flex justify-between items-center">
                <div class="flex items-center space-x-1.5 font-bold text-xs text-rose-900 dark:text-rose-300">
                    <span>⚠️</span>
                    <span>Kasus Disiplin & Pelanggaran ({{ $record->studentCases->count() }})</span>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ $record->studentCases->where('status', '!=', 'selesai')->count() > 0 ? 'bg-rose-200 text-rose-900' : 'bg-gray-100 text-gray-700' }}">
                    {{ $record->studentCases->where('status', '!=', 'selesai')->count() }} Aktif
                </span>
            </div>
            <div class="p-3 divide-y divide-gray-100 dark:divide-gray-700 flex-1 overflow-y-auto max-h-56">
                @forelse($record->studentCases as $sc)
                    <div class="py-2 first:pt-0 last:pb-0 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-rose-700 dark:text-rose-400">{{ $sc->nomor_kasus }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $sc->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : ($sc->status === 'eskalasi_bk' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $sc->status)) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-800 dark:text-gray-200 font-medium">{{ $sc->deskripsi }}</p>
                        <div class="flex flex-wrap items-center justify-between gap-1 text-[10px] text-gray-500 pt-0.5">
                            <span>Tgl: {{ \Carbon\Carbon::parse($sc->tanggal)->format('d M Y') }} • Kat: {{ ucfirst($sc->kategori) }}</span>
                            @if($sc->parentSummons->isNotEmpty())
                                <span class="text-blue-600 font-semibold">📞 {{ $sc->parentSummons->count() }} Panggilan Ortu</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-400">
                        ✅ Tidak ada catatan kasus pelanggaran.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Riwayat Pemanggilan Orang Tua -->
        <div class="border border-blue-200 dark:border-blue-900/50 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800 flex flex-col">
            <div class="p-3 bg-blue-50/80 dark:bg-blue-950/30 border-b border-blue-200 dark:border-blue-900/50 flex justify-between items-center">
                <div class="flex items-center space-x-1.5 font-bold text-xs text-blue-900 dark:text-blue-300">
                    <span>📨</span>
                    <span>Riwayat Pemanggilan Orang Tua ({{ $record->parentSummons->count() }})</span>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-blue-100 text-blue-900">
                    {{ $record->parentSummons->where('status', 'hadir')->count() }} Hadir
                </span>
            </div>
            <div class="p-3 divide-y divide-gray-100 dark:divide-gray-700 flex-1 overflow-y-auto max-h-56">
                @forelse($record->parentSummons as $ps)
                    <div class="py-2 first:pt-0 last:pb-0 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-blue-700 dark:text-blue-400">{{ $ps->nomor_surat }}</span>
                            @php
                                $statusBadge = match($ps->status) {
                                    'hadir' => 'bg-emerald-100 text-emerald-800',
                                    'tidak_hadir' => 'bg-rose-100 text-rose-800',
                                    'dibatalkan' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusBadge }}">
                                {{ ucfirst(str_replace('_', ' ', $ps->status)) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-800 dark:text-gray-200"><span class="font-semibold">Keperluan:</span> {{ $ps->keperluan }}</p>
                        @if($ps->hasil_pertemuan)
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 bg-emerald-50/60 dark:bg-emerald-950/20 p-1 rounded font-medium">
                                🤝 Kesepakatan: {{ Str::limit($ps->hasil_pertemuan, 80) }}
                            </p>
                        @endif
                        <div class="flex items-center justify-between text-[10px] text-gray-500 pt-0.5">
                            <span>Jadwal: {{ \Carbon\Carbon::parse($ps->tanggal_hadir)->format('d M Y') }} ({{ $ps->jam_hadir ?? '-' }})</span>
                            @if($ps->surat_file || $ps->notula_file)
                                <div class="flex items-center space-x-1">
                                    @if($ps->surat_file)
                                        <a href="{{ str_starts_with($ps->surat_file, 'http') ? $ps->surat_file : \Illuminate\Support\Facades\Storage::url($ps->surat_file) }}" target="_blank" class="text-blue-600 hover:underline">Surat ↗</a>
                                    @endif
                                    @if($ps->notula_file)
                                        <a href="{{ str_starts_with($ps->notula_file, 'http') ? $ps->notula_file : \Illuminate\Support\Facades\Storage::url($ps->notula_file) }}" target="_blank" class="text-emerald-600 hover:underline">Notula ↗</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-400">
                        Tidak ada riwayat pemanggilan orang tua.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- SEKSI 5: PELANGGARAN ATRIBUT PIKET & MUTASI SKOR KARAKTER (2 KOLOM) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Pelanggaran Atribut (Guru Piket) -->
        <div class="border border-amber-200 dark:border-amber-900/50 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800 flex flex-col">
            <div class="p-3 bg-amber-50/80 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-900/50 flex justify-between items-center">
                <div class="flex items-center space-x-1.5 font-bold text-xs text-amber-900 dark:text-amber-300">
                    <span>🥼</span>
                    <span>Pelanggaran Atribut (Guru Piket) ({{ $record->attributeViolations->count() }})</span>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-100 text-amber-900">
                    {{ $record->attributeViolations->where('status', '!=', 'selesai')->count() }} Terbuka
                </span>
            </div>
            <div class="p-3 divide-y divide-gray-100 dark:divide-gray-700 flex-1 overflow-y-auto max-h-56">
                @forelse($record->attributeViolations as $av)
                    @php
                        $attrText = is_array($av->attribute_type)
                            ? implode(', ', $av->attribute_type)
                            : ($av->attribute_type ?? $av->kategori ?? 'Atribut');
                    @endphp
                    <div class="py-2 first:pt-0 last:pb-0 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-gray-800 dark:text-gray-200 uppercase tracking-wide text-[11px]">{{ $attrText }}</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                Dicatat
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $av->jenis_pelanggaran ?? $av->keterangan }}</p>
                        <div class="text-[10px] text-gray-400">
                            {{ \Carbon\Carbon::parse($av->tanggal)->format('d M Y') }} • Petugas: {{ $av->teacher?->name ?? $av->inputBy?->name ?? 'Guru Piket' }}
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-400">
                        ✅ Tidak ada catatan pelanggaran atribut.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Riwayat Poin Karakter -->
        <div class="border border-purple-200 dark:border-purple-900/50 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800 flex flex-col">
            <div class="p-3 bg-purple-50/80 dark:bg-purple-950/30 border-b border-purple-200 dark:border-purple-900/50 flex justify-between items-center">
                <div class="flex items-center space-x-1.5 font-bold text-xs text-purple-900 dark:text-purple-300">
                    <span>📈</span>
                    <span>Riwayat Poin Karakter (Skor Saat Ini: {{ $record->character_score ?? 100 }})</span>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-purple-100 text-purple-900">
                    Log Terbaru
                </span>
            </div>
            <div class="p-3 divide-y divide-gray-100 dark:divide-gray-700 flex-1 overflow-y-auto max-h-56">
                @php
                    $scoreLogs = $record->characterScoreLogs()->latest('tanggal')->take(8)->get();
                @endphp
                @forelse($scoreLogs as $cLog)
                    <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                        <div class="space-y-0.5">
                            <div class="font-medium text-gray-800 dark:text-gray-200 text-[11px]">{{ $cLog->description }}</div>
                            <div class="text-[10px] text-gray-400">
                                {{ \Carbon\Carbon::parse($cLog->tanggal)->format('d M Y') }} • Kat: {{ ucfirst($cLog->category) }} ({{ ucfirst(str_replace('_', ' ', $cLog->source ?? 'guru')) }})
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-xs {{ $cLog->point_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $cLog->point_change > 0 ? '+'.$cLog->point_change : $cLog->point_change }}
                            </span>
                            <div class="text-[10px] text-gray-400 font-mono">Poin</div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-gray-400">
                        Belum ada mutasi log poin karakter.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
