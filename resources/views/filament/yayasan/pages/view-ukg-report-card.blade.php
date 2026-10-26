<x-filament-panels::page>
    @php
        $ukg = $record;
        $ukg->load(['teacher', 'assessor', 'institution']);
        $calc = $ukg->komponen_penilaian 
            ? \App\Models\TeacherCompetencyTest::calculateScoresAndPredicate($ukg->komponen_penilaian)
            : [
                'total_skor' => $ukg->total_skor ?? 0,
                'nilai_akhir' => $ukg->nilai_akhir ?? ($ukg->score ?? 0),
                'predikat' => $ukg->predikat ?? \App\Models\TeacherCompetencyTest::determinePredicate($ukg->nilai_akhir ?? $ukg->score),
            ];
        $totalSkor = $calc['total_skor'];
        $na = $calc['nilai_akhir'];
        $predikat = $calc['predikat'];
        $clusters = \App\Models\TeacherCompetencyTest::getClustersStructure();
        $komponen = $ukg->komponen_penilaian ?: [];
        $badgeColor = \App\Models\TeacherCompetencyTest::getPredicateBadgeColor($na);
    @endphp

    <div class="space-y-6">
        <!-- Top Banner Identitas Guru & Nilai PKG -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-600 via-orange-500 to-amber-700 p-6 text-white shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md">
                            {{ $ukg->institution->name ?? 'LPI Baitul Makmur' }}
                        </span>
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-black/20">
                            {{ $ukg->status_kepegawaian ?? 'GTY' }}
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight">
                        {{ $ukg->teacher->name ?? 'Nama Guru' }}
                    </h1>
                    <div class="text-amber-100 text-xs md:text-sm mt-1 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
                        <div>NIY: <span class="font-bold text-white">{{ $ukg->niy ?? '-' }}</span> &bull; Mapel: <span class="font-bold text-white">{{ $ukg->mata_pelajaran ?? '-' }}</span></div>
                        <div>Lama Mengajar: <span class="font-bold text-white">{{ $ukg->lama_mengajar ?? '-' }}</span> &bull; Tgl Masuk: <span class="font-bold text-white">{{ $ukg->tanggal_masuk ?? '-' }}</span></div>
                        <div>TTL: <span class="font-bold text-white">{{ $ukg->tempat_lahir ?? '-' }}, {{ $ukg->tanggal_lahir ? $ukg->tanggal_lahir->translatedFormat('d M Y') : '-' }}</span></div>
                        <div>Asesor: <span class="font-bold text-white">{{ $ukg->assessor_name ?: ($ukg->assessor->name ?? 'Tim Penjamin Mutu') }}</span></div>
                    </div>
                </div>

                <!-- Score Highlights -->
                <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-amber-100">Total Skor (70 Indikator)</div>
                        <div class="text-3xl font-black text-white">{{ $totalSkor }}<span class="text-sm font-normal text-amber-200">/420</span></div>
                    </div>
                    <div class="h-10 w-px bg-white/30"></div>
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-amber-100">Nilai Akhir (NA)</div>
                        <div class="text-3xl font-black text-yellow-200">{{ number_format($na, 2) }}</div>
                    </div>
                    <div class="h-10 w-px bg-white/30"></div>
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-amber-100">Status Kelulusan</div>
                        <div class="text-xs font-black px-3 py-1 mt-1 rounded-full bg-white text-orange-600 shadow-sm">
                            {{ $predikat }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8 Cards Grid Klaster Ringkasan -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($clusters as $cKey => $cInfo)
                @php
                    $summary = $ukg->getClusterSummary($cKey);
                @endphp
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-orange-600 dark:text-orange-400">
                            Klaster {{ $cInfo['code'] }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            {{ number_format($summary['percentage'], 1) }}%
                        </span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-xs line-clamp-1" title="{{ $cInfo['name'] }}">
                        {{ $cInfo['name'] }}
                    </h3>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-xl font-black text-gray-900 dark:text-white">{{ $summary['score'] }}</span>
                        <span class="text-xs text-gray-500">/ {{ $summary['max'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ min(100, $summary['percentage']) }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 truncate">
                        {{ $summary['profile'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <!-- Tabel Rekapitulasi Akhir 8 Klaster Komponen -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/75 dark:bg-gray-800/75 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Tabel Rekapitulasi Penilaian Kinerja Guru (PKG - 8 Klaster Komponen)</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rangkuman skor perolehan, nilai terstandardisasi, profil capaian, dan rekomendasi perbaikan</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('ukg.report-card.print', $ukg) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 transition">
                        <x-heroicon-m-printer class="w-4 h-4" />
                        Print
                    </a>
                    <a href="{{ route('ukg.report-card.pdf', $ukg) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition">
                        <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                        Unduh Raport PKG PDF
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">Kode</th>
                            <th class="py-3 px-4 w-1/4">Klaster Komponen</th>
                            <th class="py-3 px-4 w-28 text-center">Skor / Max</th>
                            <th class="py-3 px-4 w-24 text-center">Nilai (100)</th>
                            <th class="py-3 px-4 w-36 text-center">Kategori</th>
                            <th class="py-3 px-4 w-40">Profil Capaian</th>
                            <th class="py-3 px-4">Rekomendasi Perbaikan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($clusters as $cKey => $cInfo)
                            @php
                                $summary = $ukg->getClusterSummary($cKey);
                                $cPredikat = \App\Models\TeacherCompetencyTest::determinePredicate($summary['percentage']);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                <td class="py-3 px-4 text-center font-bold text-xs text-orange-600 dark:text-orange-400">{{ $cInfo['code'] }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $cInfo['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $cInfo['count'] }} Indikator (Skala 1-6)</div>
                                </td>
                                <td class="py-3 px-4 text-center font-extrabold text-gray-900 dark:text-white">
                                    {{ $summary['score'] }} <span class="text-xs text-gray-400 font-normal">/ {{ $summary['max'] }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        {{ number_format($summary['percentage'], 2) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $cPredikat }}
                                </td>
                                <td class="py-3 px-4 text-xs font-medium text-gray-600 dark:text-gray-300">
                                    {{ $summary['profile'] }}
                                </td>
                                <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ $summary['rekomendasi'] ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-amber-50/80 dark:bg-amber-950/40 font-bold text-xs">
                            <td colspan="2" class="py-3 px-4 text-right uppercase tracking-wider text-amber-900 dark:text-amber-200">
                                TOTAL & RATA-RATA AKUMULASI PKG:
                            </td>
                            <td class="py-3 px-4 text-center text-amber-900 dark:text-amber-200 font-black">
                                {{ $totalSkor }} / 420
                            </td>
                            <td class="py-3 px-4 text-center text-emerald-700 dark:text-emerald-300 font-black text-sm">
                                {{ number_format($na, 2) }}
                            </td>
                            <td class="py-3 px-4 text-center text-amber-900 dark:text-amber-200">
                                {{ $predikat }}
                            </td>
                            <td colspan="2" class="py-3 px-4 text-amber-900 dark:text-amber-200">
                                Status: <strong>{{ $na >= 76 ? 'MEMENUHI STANDAR KINERJA GURU (PKG)' : 'PERLU PEMBINAAN & PENDAMPINGAN' }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if($ukg->catatan_umum)
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300 flex items-center gap-2 mb-2">
                <x-heroicon-m-chat-bubble-bottom-center-text class="w-5 h-5 text-amber-600" />
                Catatan Umum & Rekomendasi Khusus dari Asesor Yayasan
            </h3>
            <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                {{ $ukg->catatan_umum }}
            </p>
        </div>
        @endif

        <!-- Rincian 70 Indikator (Collapsible Section) -->
        <details class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden group">
            <summary class="cursor-pointer bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex items-center justify-between text-sm font-bold text-gray-900 dark:text-white select-none">
                <span>Rincian Lengkap Penilaian 70 Indikator PKG</span>
                <span class="text-xs text-orange-600 font-semibold group-open:rotate-180 transition-transform">▼ Klik untuk melihat detail</span>
            </summary>
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold">
                            <th class="py-2 px-3 w-10 text-center">No</th>
                            <th class="py-2 px-3 w-1/3">Indikator Penilaian</th>
                            <th class="py-2 px-3 w-1/3">Deskripsi / Fokus Pengamatan</th>
                            <th class="py-2 px-3 w-20 text-center">Teknik</th>
                            <th class="py-2 px-3 w-20 text-center">Skor (1-6)</th>
                            <th class="py-2 px-3">Bukti / Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($clusters as $cKey => $cInfo)
                            <tr class="bg-orange-50/60 dark:bg-orange-950/30 font-bold text-orange-900 dark:text-orange-300">
                                <td colspan="6" class="py-2 px-3">
                                    {{ $cInfo['code'] }}. {{ strtoupper($cInfo['name']) }} (Indikator {{ $cInfo['start_idx'] }} s.d. {{ $cInfo['end_idx'] }}) &mdash; Skor: {{ $ukg->{'skor_' . $cKey} ?? 0 }}/{{ $cInfo['max_score'] }}
                                </td>
                            </tr>
                            @foreach($cInfo['indicators'] as $iNum => $iData)
                                @php
                                    $skorInd = isset($komponen["i{$iNum}"]['score']) ? $komponen["i{$iNum}"]['score'] : (isset($komponen[$iNum]['score']) ? $komponen[$iNum]['score'] : null);
                                    $teknikInd = isset($komponen["i{$iNum}"]['teknik']) ? $komponen["i{$iNum}"]['teknik'] : (isset($komponen[$iNum]['teknik']) ? $komponen[$iNum]['teknik'] : $iData['teknik']);
                                    $catatanInd = isset($komponen["i{$iNum}"]['catatan']) ? $komponen["i{$iNum}"]['catatan'] : (isset($komponen[$iNum]['catatan']) ? $komponen[$iNum]['catatan'] : null);
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                    <td class="py-2 px-3 text-center font-bold text-gray-500">{{ $iNum }}</td>
                                    <td class="py-2 px-3 font-semibold text-gray-900 dark:text-white">{{ $iData['title'] }}</td>
                                    <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ $iData['desc'] }}</td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ $teknikInd }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full font-bold text-xs {{ $skorInd >= 5 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : ($skorInd >= 3 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300') }}">
                                            {{ $skorInd ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-gray-500">{{ $catatanInd ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        <!-- Bottom Actions -->
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <a href="{{ url('/yayasan/permintaan-ukgs') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-orange-600 transition">
                <x-heroicon-m-arrow-left class="w-4 h-4" />
                Kembali ke Daftar PKG
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('ukg.report-card.print', $ukg) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 rounded-lg shadow-sm transition">
                    <x-heroicon-m-printer class="w-4 h-4" />
                    Cetak Raport PKG
                </a>
                <a href="{{ route('ukg.report-card.pdf', $ukg) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                    Download PDF PKG
                </a>
                <a href="{{ url('/yayasan/permintaan-ukgs/' . $ukg->id . '/edit') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                    Edit Nilai & Instrumen
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
