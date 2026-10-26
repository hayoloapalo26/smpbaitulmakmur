<x-filament-panels::page>
    @php
        $supervision = $record;
        $supervision->load(['teacher', 'supervisor', 'institution']);
        $calc = $supervision->komponen_penilaian 
            ? \App\Models\TeacherSupervision::calculateScoresFromComponents($supervision->komponen_penilaian)
            : [
                'total_skor' => $supervision->total_skor ?? 0,
                'nilai_akhir' => $supervision->nilai_akhir ?? ($supervision->score ?? 0),
                'predikat' => $supervision->predikat ?? \App\Models\TeacherSupervision::determinePredicate($supervision->nilai_akhir ?? $supervision->score),
                'skor_perencanaan' => $supervision->skor_perencanaan ?? 0,
                'skor_pelaksanaan' => $supervision->skor_pelaksanaan ?? 0,
                'skor_evaluasi' => $supervision->skor_evaluasi ?? 0,
            ];
        $totalSkor = $calc['total_skor'];
        $na = $calc['nilai_akhir'];
        $predikat = $calc['predikat'];
        $komponen = $supervision->komponen_penilaian ?: [];
        $sumPerencanaan = $supervision->getClusterSummary('perencanaan');
        $sumPelaksanaan = $supervision->getClusterSummary('pelaksanaan');
        $sumEvaluasi = $supervision->getClusterSummary('evaluasi');
        $badgeColor = \App\Models\TeacherSupervision::getPredicateBadgeColor($na);
    @endphp

    <div class="space-y-6">
        <!-- Top Banner Identitas & Nilai Akhir -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 text-white shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md">
                            {{ $supervision->institution->name ?? 'SMP Islam Baitul Makmur' }}
                        </span>
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-black/20">
                            Program: {{ $supervision->unit_sekolah_program ?? 'Reguler' }}
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight">
                        {{ $supervision->teacher->name ?? 'Nama Guru' }}
                    </h1>
                    <p class="text-orange-100 text-sm mt-1">
                        Mata Pelajaran: <span class="font-bold text-white">{{ $supervision->mata_pelajaran ?? '-' }}</span> &bull; 
                        Kelas/Semester: <span class="font-bold text-white">{{ $supervision->kelas_semester ?? '-' }}</span> &bull; 
                        Tanggal: <span class="font-bold text-white">{{ $supervision->tanggal ? $supervision->tanggal->translatedFormat('d F Y') : '-' }}</span>
                    </p>
                    <p class="text-orange-100/90 text-xs mt-1">
                        Supervisor: <span class="font-semibold text-white">{{ $supervision->supervisor_name ?: ($supervision->supervisor->name ?? 'Tim Penjamin Mutu Yayasan') }}</span>
                    </p>
                </div>

                <!-- Score Highlights -->
                <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20">
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-orange-100">Total Skor</div>
                        <div class="text-3xl font-black text-white">{{ $totalSkor }}<span class="text-sm font-normal text-orange-200">/88</span></div>
                    </div>
                    <div class="h-10 w-px bg-white/30"></div>
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-orange-100">Nilai Akhir (NA)</div>
                        <div class="text-3xl font-black text-yellow-200">{{ number_format($na, 2) }}</div>
                    </div>
                    <div class="h-10 w-px bg-white/30"></div>
                    <div class="text-center px-3">
                        <div class="text-xs uppercase font-semibold text-orange-100">Predikat</div>
                        <div class="text-sm font-black px-3 py-1 mt-1 rounded-full bg-white text-orange-600 shadow-sm">
                            {{ $predikat }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Cards Klaster Ringkasan -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Klaster 1: Perencanaan -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Klaster A</span>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                        {{ $sumPerencanaan['percentage'] }}%
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-1">Perencanaan & Desain Pembelajaran</h3>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $sumPerencanaan['score'] }}</span>
                    <span class="text-xs text-gray-500">/ 20 Poin Maksimal</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full mt-2 overflow-hidden">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(100, $sumPerencanaan['percentage']) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Profil: <strong class="text-gray-700 dark:text-gray-300">{{ $sumPerencanaan['profile'] }}</strong>
                </p>
            </div>

            <!-- Klaster 2: Pelaksanaan -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Klaster B (B1–B5)</span>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        {{ $sumPelaksanaan['percentage'] }}%
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-1">Pelaksanaan Pembelajaran</h3>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $sumPelaksanaan['score'] }}</span>
                    <span class="text-xs text-gray-500">/ 60 Poin Maksimal</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full mt-2 overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $sumPelaksanaan['percentage']) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Profil: <strong class="text-gray-700 dark:text-gray-300">{{ $sumPelaksanaan['profile'] }}</strong>
                </p>
            </div>

            <!-- Klaster 3: Evaluasi -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Klaster B.6</span>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                        {{ $sumEvaluasi['percentage'] }}%
                    </span>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-1">Penutup Pembelajaran & Evaluasi</h3>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $sumEvaluasi['score'] }}</span>
                    <span class="text-xs text-gray-500">/ 8 Poin Maksimal</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full mt-2 overflow-hidden">
                    <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min(100, $sumEvaluasi['percentage']) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Profil: <strong class="text-gray-700 dark:text-gray-300">{{ $sumEvaluasi['profile'] }}</strong>
                </p>
            </div>
        </div>

        <!-- Tabel Rincian 22 Indikator -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/75 dark:bg-gray-800/75 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Rekapitulasi 22 Indikator Supervisi</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rincian skor capaian, bukti pengamatan kualitatif, dan rekomendasi khusus per klaster</p>
                </div>
                <a href="{{ route('supervisi.report-card.pdf', $supervision) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                    Unduh PDF
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4 w-16 text-center">Kode</th>
                            <th class="py-3 px-4 w-1/3">Komponen & Indikator</th>
                            <th class="py-3 px-4 w-1/3">Fokus / Deskripsi Pengamatan</th>
                            <th class="py-3 px-4 w-24 text-center">Skor (1-4)</th>
                            <th class="py-3 px-4">Catatan Kualitatif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- HEADER KLASTER A -->
                        <tr class="bg-blue-50/60 dark:bg-blue-950/30">
                            <td colspan="5" class="py-2.5 px-4 font-bold text-blue-900 dark:text-blue-300 text-xs tracking-wider uppercase">
                                A. Perencanaan & Desain Pembelajaran (Modul Ajar / RPP) &mdash; Skor: {{ $sumPerencanaan['score'] }}/20 ({{ $sumPerencanaan['percentage'] }}%)
                            </td>
                        </tr>
                        @php
                            $indicatorsA = [
                                1 => ['code' => 'A.1', 'title' => 'Analisis CP, TP, & KKTP', 'desc' => 'Keselarasan dan keterukuran TP/KKTP dengan Capaian Pembelajaran.'],
                                2 => ['code' => 'A.2', 'title' => 'Desain Pembelajaran Berdiferensiasi', 'desc' => 'Variasi konten, proses, dan produk sesuai kesiapan, minat, dan profil murid.'],
                                3 => ['code' => 'A.3', 'title' => 'Desain Pembelajaran Deep Learning', 'desc' => 'Pemantik bernalar kritis, alur runtut (Memahami > Mengaplikasi > Merefleksi), serta media digital interaktif.'],
                                4 => ['code' => 'A.4', 'title' => 'Integrasi Bahan Ajar & Karakter', 'desc' => 'Transformasi nilai moral, etika, dan pembiasaan karakter.'],
                                5 => ['code' => 'A.5', 'title' => 'Perencanaan Bahasa & Al-Qur\'an', 'desc' => 'Penggunaan Classroom Language (Bahasa Inggris) & pembiasaan Tahsin & Tahfidz (BMQ) terstruktur.'],
                            ];
                        @endphp
                        @foreach($indicatorsA as $idx => $ind)
                            @php
                                $skorInd = isset($komponen["i{$idx}"]['score']) ? (int)$komponen["i{$idx}"]['score'] : (isset($komponen[$idx]['score']) ? (int)$komponen[$idx]['score'] : null);
                                $catatanInd = isset($komponen["i{$idx}"]['catatan']) ? $komponen["i{$idx}"]['catatan'] : (isset($komponen[$idx]['catatan']) ? $komponen[$idx]['catatan'] : null);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                <td class="py-2.5 px-4 text-center font-bold text-xs text-gray-500">{{ $ind['code'] }}</td>
                                <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white">{{ $ind['title'] }}</td>
                                <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-300">{{ $ind['desc'] }}</td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs {{ $skorInd >= 3 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : ($skorInd == 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300') }}">
                                        {{ $skorInd ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-400">{{ $catatanInd ?: '-' }}</td>
                            </tr>
                        @endforeach
                        @if($supervision->rekomendasi_perencanaan)
                            <tr class="bg-blue-50/30 dark:bg-blue-950/20 text-xs">
                                <td colspan="5" class="py-2 px-4 text-blue-800 dark:text-blue-300">
                                    <strong>Rekomendasi Khusus Perencanaan:</strong> {{ $supervision->rekomendasi_perencanaan }}
                                </td>
                            </tr>
                        @endif

                        <!-- HEADER KLASTER B -->
                        <tr class="bg-emerald-50/60 dark:bg-emerald-950/30">
                            <td colspan="5" class="py-2.5 px-4 font-bold text-emerald-900 dark:text-emerald-300 text-xs tracking-wider uppercase">
                                B. Pelaksanaan Pembelajaran &mdash; Skor: {{ $sumPelaksanaan['score'] }}/60 ({{ $sumPelaksanaan['percentage'] }}%)
                            </td>
                        </tr>
                        @php
                            $structure = \App\Models\TeacherSupervision::getIndicatorsStructure();
                            $subclustersB = $structure['pelaksanaan']['subclusters'];
                        @endphp
                        @foreach($subclustersB as $subCode => $subData)
                            <tr class="bg-amber-50/40 dark:bg-amber-950/20">
                                <td colspan="5" class="py-2 px-4 font-bold text-amber-900 dark:text-amber-300 text-xs">
                                    {{ $subCode }}. {{ $subData['name'] }}
                                </td>
                            </tr>
                            @foreach($subData['indicators'] as $num => $ind)
                                @php
                                    $skorInd = isset($komponen["i{$num}"]['score']) ? (int)$komponen["i{$num}"]['score'] : (isset($komponen[$num]['score']) ? (int)$komponen[$num]['score'] : null);
                                    $catatanInd = isset($komponen["i{$num}"]['catatan']) ? $komponen["i{$num}"]['catatan'] : (isset($komponen[$num]['catatan']) ? $komponen[$num]['catatan'] : null);
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                    <td class="py-2.5 px-4 text-center font-bold text-xs text-gray-500">{{ $ind['code'] }}</td>
                                    <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white">{{ $ind['title'] }}</td>
                                    <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-300">{{ $ind['description'] }}</td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs {{ $skorInd >= 3 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : ($skorInd == 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300') }}">
                                            {{ $skorInd ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-400">{{ $catatanInd ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if($supervision->rekomendasi_pelaksanaan)
                            <tr class="bg-emerald-50/30 dark:bg-emerald-950/20 text-xs">
                                <td colspan="5" class="py-2 px-4 text-emerald-800 dark:text-emerald-300">
                                    <strong>Rekomendasi Khusus Pelaksanaan:</strong> {{ $supervision->rekomendasi_pelaksanaan }}
                                </td>
                            </tr>
                        @endif

                        <!-- HEADER KLASTER B.6 -->
                        <tr class="bg-purple-50/60 dark:bg-purple-950/30">
                            <td colspan="5" class="py-2.5 px-4 font-bold text-purple-900 dark:text-purple-300 text-xs tracking-wider uppercase">
                                B.6 Penutup Pembelajaran / Evaluasi &mdash; Skor: {{ $sumEvaluasi['score'] }}/8 ({{ $sumEvaluasi['percentage'] }}%)
                            </td>
                        </tr>
                        @php
                            $indicatorsC = [
                                21 => ['code' => 'Indikator 21', 'title' => 'Asesmen Formatif & Umpan Balik', 'desc' => 'Pengecekan pemahaman berkelanjutan (real-time feedback).'],
                                22 => ['code' => 'Indikator 22', 'title' => 'Analisis Ketuntasan & Tindak Lanjut', 'desc' => 'Pengukuran ketercapaian, pengayaan, atau program intervensi/remedial.'],
                            ];
                        @endphp
                        @foreach($indicatorsC as $idx => $ind)
                            @php
                                $skorInd = isset($komponen["i{$idx}"]['score']) ? (int)$komponen["i{$idx}"]['score'] : (isset($komponen[$idx]['score']) ? (int)$komponen[$idx]['score'] : null);
                                $catatanInd = isset($komponen["i{$idx}"]['catatan']) ? $komponen["i{$idx}"]['catatan'] : (isset($komponen[$idx]['catatan']) ? $komponen[$idx]['catatan'] : null);
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                <td class="py-2.5 px-4 text-center font-bold text-xs text-gray-500">{{ $ind['code'] }}</td>
                                <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white">{{ $ind['title'] }}</td>
                                <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-300">{{ $ind['desc'] }}</td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs {{ $skorInd >= 3 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : ($skorInd == 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300') }}">
                                        {{ $skorInd ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-xs text-gray-600 dark:text-gray-400">{{ $catatanInd ?: '-' }}</td>
                            </tr>
                        @endforeach
                        @if($supervision->rekomendasi_evaluasi)
                            <tr class="bg-purple-50/30 dark:bg-purple-950/20 text-xs">
                                <td colspan="5" class="py-2 px-4 text-purple-800 dark:text-purple-300">
                                    <strong>Rekomendasi Khusus Evaluasi:</strong> {{ $supervision->rekomendasi_evaluasi }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Evaluasi Kualitatif & Rencana Tindak Lanjut -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2 text-emerald-800 dark:text-emerald-300 font-bold text-sm">
                    <x-heroicon-m-sparkles class="w-5 h-5 text-emerald-600" />
                    Praktik Baik (Best Practice)
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                    {{ $supervision->praktik_baik ?: ($supervision->hasil ?: 'Menunjukkan komitmen mengajar yang baik sesuai prinsip pembelajaran LPI Baitul Makmur.') }}
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2 text-amber-800 dark:text-amber-300 font-bold text-sm">
                    <x-heroicon-m-wrench-screwdriver class="w-5 h-5 text-amber-600" />
                    Area Fokus Perbaikan
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                    {{ $supervision->area_fokus_perbaikan ?: 'Penguatan pembelajaran berdiferensiasi dan konsistensi penggunaan Classroom Language.' }}
                </p>
            </div>

            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-2 text-blue-800 dark:text-blue-300 font-bold text-sm">
                    <x-heroicon-m-arrow-path-rounded-square class="w-5 h-5 text-blue-600" />
                    Rencana Coaching / Action Plan
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                    {{ $supervision->rencana_coaching ?: 'Pendampingan supervisi klinis lanjutan bersama koordinator kurikulum yayasan.' }}
                </p>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <a href="{{ url('/yayasan/permintaan-supervisis') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-orange-600 transition">
                <x-heroicon-m-arrow-left class="w-4 h-4" />
                Kembali ke Daftar Supervisi
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('supervisi.report-card.print', $supervision) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 rounded-lg shadow-sm transition">
                    <x-heroicon-m-printer class="w-4 h-4" />
                    Cetak Lembar Laporan
                </a>
                <a href="{{ route('supervisi.report-card.pdf', $supervision) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-sm transition">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                    Download PDF
                </a>
                <a href="{{ url('/yayasan/permintaan-supervisis/' . $supervision->id . '/edit') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                    Edit Nilai & Catatan
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
