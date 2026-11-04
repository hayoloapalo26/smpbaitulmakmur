<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. HEADER & FILTER KELAS / PERIODE --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-2 bg-primary-100 dark:bg-primary-950/60 text-primary-600 rounded-xl">
                            <x-heroicon-o-document-chart-bar class="w-5 h-5" />
                        </span>
                        Buku Rapor & Leger Nilai Kelas
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Kalkulasi resmi nilai rapor mengacu pada standar kurikulum: Pengetahuan NA = (Nilai Harian + 2 × UTS) / 3 dan Konversi Skala 1 - 4.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($selectedClass)
                        <a href="{{ route('report.rapor.leger.pdf', ['classroom' => $selectedClass->id, 'academic_year_id' => $selectedAcademicYearId, 'semester' => $selectedSemester]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            <x-heroicon-o-printer class="w-4 h-4" />
                            Cetak Leger Nilai (PDF)
                        </a>
                    @endif

                    <a href="{{ url('/staff/input-nilai-mapel') }}" 
                       class="text-xs inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition font-medium">
                        <x-heroicon-o-pencil-square class="w-4 h-4 text-primary-500" />
                        Input Nilai Guru Mapel
                    </a>
                </div>
            </div>

            {{-- Filter Selector Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Pilih Kelas (Rombel)</label>
                    <select wire:model.live="selectedClassroomId"
                            class="w-full text-xs font-semibold rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm py-2">
                        @foreach($availableClasses as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Tingkat {{ $c->tingkat ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tahun Pelajaran</label>
                    <select wire:model.live="selectedAcademicYearId"
                            class="w-full text-xs font-semibold rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm py-2">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}">{{ $ay->name ?? $ay->year }} {{ $ay->is_active ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                    <select wire:model.live="selectedSemester"
                            class="w-full text-xs font-semibold rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm py-2">
                        <option value="Ganjil">Semester 1 (Ganjil)</option>
                        <option value="Genap">Semester 2 (Genap)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. TAB SWITCHER --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800">
            <button type="button" 
                    wire:click="setTab('rapor')"
                    class="flex items-center gap-2 px-5 py-2.5 text-xs font-bold border-b-2 transition {{ $activeTab === 'rapor' ? 'border-primary-600 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                <x-heroicon-o-academic-cap class="w-4 h-4" />
                <span>📑 Daftar Siswa & Buku Rapor</span>
                @if(!empty($legerData['rows']))
                    <span class="px-2 py-0.5 text-[10px] rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ count($legerData['rows']) }} Siswa
                    </span>
                @endif
            </button>

            <button type="button" 
                    wire:click="setTab('leger')"
                    class="flex items-center gap-2 px-5 py-2.5 text-xs font-bold border-b-2 transition {{ $activeTab === 'leger' ? 'border-primary-600 text-primary-600 dark:text-primary-400 bg-primary-50/50 dark:bg-primary-950/20' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                <x-heroicon-o-table-cells class="w-4 h-4" />
                <span>📊 Matriks Leger Nilai Rapor</span>
            </button>
        </div>

        {{-- 3. KONTEN TAB 1: DAFTAR SISWA & CETAK RAPOR --}}
        @if($activeTab === 'rapor')
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-4 bg-gray-50/60 dark:bg-gray-800/40 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Data Capaian Rapor Siswa Kelas: {{ $selectedClass?->name ?? '-' }}
                        </h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Wali Kelas: <strong>{{ $selectedClass?->waliKelas?->name ?? 'Belum ditentukan' }}</strong> | Tahun Pelajaran: {{ $legerData['academic_year']?->name ?? '-' }} ({{ $selectedSemester }})
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-100/80 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold text-[10px]">
                            <tr>
                                <th class="py-3 px-3 text-center w-12">No</th>
                                <th class="py-3 px-3 w-28">NIS / NISN</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-3 text-center">Total Nilai NA</th>
                                <th class="py-3 px-3 text-center">Rata-rata NA</th>
                                <th class="py-3 px-3 text-center">Peringkat</th>
                                <th class="py-3 px-3 text-center">Ketuntasan (KKM 75)</th>
                                <th class="py-3 px-3 text-center">Status Administrasi</th>
                                <th class="py-3 px-4 text-center w-48">Aksi Rapor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($legerData['rows'] ?? [] as $idx => $row)
                                @php
                                    $student = $row['student'];
                                    $isTuntas = $row['average_na'] >= 75;
                                @endphp
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/60 transition">
                                    <td class="py-3 px-3 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-3">
                                        <div class="font-mono font-semibold text-gray-900 dark:text-white">{{ $student->nis }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $student->nisn ?: '-' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $student->name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $student->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-gray-800 dark:text-gray-200">
                                        {{ number_format($row['total_na'], 2) }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold {{ $isTuntas ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ number_format($row['average_na'], 2) }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold text-xs {{ $row['class_rank'] === 1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300' : ($row['class_rank'] <= 3 ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                            Rank #{{ $row['class_rank'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $isTuntas ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' }}">
                                            {{ $isTuntas ? '✓ Tuntas' : '⚠️ Belum Tuntas' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if($student->is_administrasi_lunas)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                                                Lunas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800">
                                                Belum Lunas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button"
                                                    wire:click="openPreviewRapor({{ $student->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 dark:bg-primary-950/50 dark:hover:bg-primary-900/50 dark:text-primary-300 text-xs font-semibold transition border border-primary-200 dark:border-primary-800">
                                                <x-heroicon-o-eye class="w-3.5 h-3.5" />
                                                Lihat
                                            </button>
                                            
                                            <a href="{{ route('report.rapor.student.pdf', ['student' => $student->id, 'academic_year_id' => $selectedAcademicYearId, 'semester' => $selectedSemester]) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-sm">
                                                <x-heroicon-o-printer class="w-3.5 h-3.5" />
                                                Cetak PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-12 text-center text-gray-500">
                                        Tidak ada siswa pada rombel ini atau belum ada kelas yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 4. KONTEN TAB 2: MATRIKS LEGER NILAI LENGKAP --}}
        @if($activeTab === 'leger')
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-4 bg-gray-50/60 dark:bg-gray-800/40 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Format Leger Nilai Siswa (Sheet 2) - Kelas: {{ $selectedClass?->name ?? '-' }}
                        </h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Pengetahuan: RT-NH, UTS, NA = (RT-NH + 2*UTS)/3, Konversi (1-4), Predikat | Keterampilan: RT-NH, NA, Konversi, Predikat | Sikap | Total & Rank
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[650px] overflow-y-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-[10px] uppercase font-bold sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th rowspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-center bg-gray-200 dark:bg-gray-800">No</th>
                                <th rowspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-left min-w-40 bg-gray-200 dark:bg-gray-800">Nama Siswa</th>
                                <th rowspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-center w-20 bg-gray-200 dark:bg-gray-800">NIS</th>

                                {{-- Header Mapel Dinamis --}}
                                @foreach($legerData['subjects'] ?? [] as $sub)
                                    <th colspan="10" class="p-2 border border-gray-300 dark:border-gray-700 text-center bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200">
                                        {{ $sub->name }}
                                    </th>
                                @endforeach

                                <th colspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-center bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200">
                                    Kehadiran
                                </th>
                                <th rowspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-center bg-amber-50 dark:bg-amber-950/60 font-black text-amber-900 dark:text-amber-200">
                                    JML
                                </th>
                                <th rowspan="3" class="p-2 border border-gray-300 dark:border-gray-700 text-center bg-amber-100 dark:bg-amber-900 font-black text-amber-950 dark:text-amber-100">
                                    RANK
                                </th>
                            </tr>
                            <tr>
                                @foreach($legerData['subjects'] ?? [] as $sub)
                                    <th colspan="5" class="p-1 border border-gray-300 dark:border-gray-700 text-center bg-blue-50 dark:bg-blue-950/40 text-[9px] text-blue-900 dark:text-blue-200">
                                        Pengetahuan (KI-3)
                                    </th>
                                    <th colspan="4" class="p-1 border border-gray-300 dark:border-gray-700 text-center bg-green-50 dark:bg-green-950/40 text-[9px] text-green-900 dark:text-green-200">
                                        Keterampilan (KI-4)
                                    </th>
                                    <th rowspan="2" class="p-1 border border-gray-300 dark:border-gray-700 text-center bg-purple-50 dark:bg-purple-950/40 text-[9px] text-purple-900 dark:text-purple-200">
                                        Sikap
                                    </th>
                                @endforeach

                                <th rowspan="2" class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">S</th>
                                <th rowspan="2" class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">I</th>
                                <th rowspan="2" class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">A</th>
                            </tr>
                            <tr>
                                @foreach($legerData['subjects'] ?? [] as $sub)
                                    {{-- Pengetahuan --}}
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">RT-NH</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">UTS</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px] bg-blue-100/60 dark:bg-blue-900/60">NA</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">1-4</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">Pred</th>

                                    {{-- Keterampilan --}}
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">RT-NH</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px] bg-green-100/60 dark:bg-green-900/60">NA</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">1-4</th>
                                    <th class="p-1 border border-gray-300 dark:border-gray-700 text-center text-[9px]">Pred</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($legerData['rows'] ?? [] as $idx => $row)
                                @php
                                    $student = $row['student'];
                                    $subMap = collect($row['subjects'])->keyBy('subject.id');
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50">
                                    <td class="p-2 border border-gray-300 dark:border-gray-700 text-center font-bold">{{ $idx + 1 }}</td>
                                    <td class="p-2 border border-gray-300 dark:border-gray-700 font-bold whitespace-nowrap">{{ $student->name }}</td>
                                    <td class="p-2 border border-gray-300 dark:border-gray-700 text-center font-mono">{{ $student->nis }}</td>

                                    @foreach($legerData['subjects'] ?? [] as $sub)
                                        @php
                                            $sData = $subMap->get($sub->id);
                                            $p = $sData['pengetahuan'] ?? [];
                                            $k = $sData['keterampilan'] ?? [];
                                            $skp = $sData['sikap'] ?? [];
                                        @endphp
                                        {{-- Pengetahuan --}}
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $p['rt_nh'] !== null ? $p['rt_nh'] : '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $p['uts'] !== null ? $p['uts'] : '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-bold bg-blue-50/50 dark:bg-blue-950/20 text-blue-900 dark:text-blue-300">{{ $p['na'] !== null ? $p['na'] : '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-mono">{{ $p['konversi'] ?: '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-bold">{{ $p['predikat'] ?: '-' }}</td>

                                        {{-- Keterampilan --}}
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $k['rt_nh'] !== null ? $k['rt_nh'] : '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-bold bg-green-50/50 dark:bg-green-950/20 text-green-900 dark:text-green-300">{{ $k['na'] !== null ? $k['na'] : '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-mono">{{ $k['konversi'] ?: '-' }}</td>
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-bold">{{ $k['predikat'] ?: '-' }}</td>

                                        {{-- Sikap --}}
                                        <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-semibold text-purple-700 dark:text-purple-300">{{ $skp['predikat'] ?? 'B' }}</td>
                                    @endforeach

                                    {{-- Kehadiran --}}
                                    <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $row['attendance']['sakit'] }}</td>
                                    <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $row['attendance']['izin'] }}</td>
                                    <td class="p-1 border border-gray-300 dark:border-gray-700 text-center">{{ $row['attendance']['alpa'] }}</td>

                                    {{-- Total & Peringkat --}}
                                    <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-black bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200">
                                        {{ number_format($row['total_na'], 2) }}
                                    </td>
                                    <td class="p-1 border border-gray-300 dark:border-gray-700 text-center font-black bg-amber-100 dark:bg-amber-900/60 text-amber-950 dark:text-amber-100">
                                        {{ $row['class_rank'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="50" class="p-8 text-center text-gray-500">
                                        Belum ada data nilai pada rombel ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 5. MODAL PREVIEW BUKU RAPOR SISWA --}}
        @if($previewRaporData)
            @php
                $pData = $previewRaporData;
                $st = $pData['student'];
            @endphp
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-900 rounded-3xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="p-2.5 rounded-2xl bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                <x-heroicon-o-academic-cap class="w-6 h-6" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    Preview Buku Rapor: {{ $st->name }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    NIS: {{ $st->nis }} | Kelas: {{ $st->classroom?->name }} | Tahun: {{ $pData['academic_year']?->name }} ({{ $pData['semester'] }})
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('report.rapor.student.pdf', ['student' => $st->id, 'academic_year_id' => $selectedAcademicYearId, 'semester' => $selectedSemester]) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition">
                                <x-heroicon-o-printer class="w-4 h-4" />
                                Cetak PDF Resmi
                            </a>
                            <button type="button" 
                                    wire:click="closePreviewModal"
                                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    {{-- Data Identitas Rapor --}}
                    <div class="grid grid-cols-2 gap-4 text-xs bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-200 dark:border-gray-700">
                        <div class="space-y-1">
                            <div><span class="text-gray-400">Nama Sekolah:</span> <strong>{{ $st->institution?->name ?? 'SMP/SMK Islam Baitul Makmur Malang' }}</strong></div>
                            <div><span class="text-gray-400">Nama Siswa:</span> <strong>{{ $st->name }}</strong></div>
                            <div><span class="text-gray-400">NIS / NISN:</span> {{ $st->nis }} / {{ $st->nisn ?: '-' }}</div>
                        </div>
                        <div class="space-y-1">
                            <div><span class="text-gray-400">Kelas / Semester:</span> {{ $st->classroom?->name }} / {{ $pData['semester'] }}</div>
                            <div><span class="text-gray-400">Tahun Pelajaran:</span> {{ $pData['academic_year']?->name }}</div>
                            <div><span class="text-gray-400">Peringkat Kelas:</span> <strong class="text-primary-600">Rank #{{ $pData['class_rank'] }}</strong> dari {{ $pData['total_students'] }} Siswa</div>
                        </div>
                    </div>

                    {{-- Tabel Nilai Capaian Kompetensi --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">
                            I. Capaian Kompetensi Nilai Mata Pelajaran
                        </h4>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 dark:bg-gray-800 text-[10px] uppercase font-bold text-gray-700 dark:text-gray-300">
                                    <tr>
                                        <th rowspan="2" class="p-2 text-center w-10 border-r border-gray-200 dark:border-gray-700">No</th>
                                        <th rowspan="2" class="p-2 border-r border-gray-200 dark:border-gray-700">Mata Pelajaran</th>
                                        <th colspan="3" class="p-1 text-center border-b border-r border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-950/40">Pengetahuan (KI-3)</th>
                                        <th colspan="2" class="p-1 text-center border-b border-r border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-950/40">Keterampilan (KI-4)</th>
                                        <th colspan="2" class="p-1 text-center border-b border-gray-200 dark:border-gray-700 bg-purple-50 dark:bg-purple-950/40">Sikap</th>
                                    </tr>
                                    <tr>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700">UTS</th>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700 bg-blue-100/50">NA</th>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700">Pred</th>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700 bg-green-100/50">NA</th>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700">Pred</th>
                                        <th class="p-1 text-center text-[9px] border-r border-gray-200 dark:border-gray-700">Angka</th>
                                        <th class="p-1 text-center text-[9px]">Pred</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($pData['subjects'] as $s)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                            <td class="p-2 text-center text-gray-500 border-r border-gray-200 dark:border-gray-700">{{ $s['no'] }}</td>
                                            <td class="p-2 font-bold text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">{{ $s['name'] }}</td>
                                            
                                            {{-- Pengetahuan --}}
                                            <td class="p-1 text-center border-r border-gray-200 dark:border-gray-700">{{ $s['pengetahuan']['uts'] !== null ? $s['pengetahuan']['uts'] : '-' }}</td>
                                            <td class="p-1 text-center font-bold text-blue-700 dark:text-blue-300 bg-blue-50/30 border-r border-gray-200 dark:border-gray-700">{{ $s['pengetahuan']['na'] !== null ? $s['pengetahuan']['na'] : '-' }}</td>
                                            <td class="p-1 text-center font-bold border-r border-gray-200 dark:border-gray-700">{{ $s['pengetahuan']['predikat'] }}</td>

                                            {{-- Keterampilan --}}
                                            <td class="p-1 text-center font-bold text-green-700 dark:text-green-300 bg-green-50/30 border-r border-gray-200 dark:border-gray-700">{{ $s['keterampilan']['na'] !== null ? $s['keterampilan']['na'] : '-' }}</td>
                                            <td class="p-1 text-center font-bold border-r border-gray-200 dark:border-gray-700">{{ $s['keterampilan']['predikat'] }}</td>

                                            {{-- Sikap --}}
                                            <td class="p-1 text-center border-r border-gray-200 dark:border-gray-700">{{ $s['sikap']['score'] }}</td>
                                            <td class="p-1 text-center font-bold text-purple-700 dark:text-purple-300">{{ $s['sikap']['predikat'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tabel Ekstrakurikuler & Absensi --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Eskul --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">
                                II. Kegiatan Ekstrakurikuler
                            </h4>
                            <table class="w-full text-xs text-left">
                                <thead class="text-gray-400 border-b pb-1 text-[10px]">
                                    <tr>
                                        <th class="py-1">Kegiatan</th>
                                        <th class="py-1 text-center w-12">Nilai</th>
                                        <th class="py-1">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($pData['extracurriculars'] as $e)
                                        <tr>
                                            <td class="py-1.5 font-bold">{{ $e['name'] }}</td>
                                            <td class="py-1.5 text-center font-black text-primary-600">{{ $e['nilai'] }}</td>
                                            <td class="py-1.5 text-gray-500 text-[11px]">{{ $e['deskripsi'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="py-2 text-center text-gray-400">Tidak ada eskul</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Ketidakhadiran --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">
                                III. Ketidakhadiran (Presensi)
                            </h4>
                            <table class="w-full text-xs text-left">
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr>
                                        <td class="py-1.5">1. Sakit</td>
                                        <td class="py-1.5 text-right font-bold">{{ $pData['attendance']['sakit'] }} Hari</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5">2. Izin</td>
                                        <td class="py-1.5 text-right font-bold">{{ $pData['attendance']['izin'] }} Hari</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5">3. Tanpa Keterangan (Alpa)</td>
                                        <td class="py-1.5 text-right font-bold">{{ $pData['attendance']['alpa'] }} Hari</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Catatan Wali Kelas --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-200 dark:border-gray-700 text-xs">
                        <strong class="text-gray-800 dark:text-gray-200">Catatan Wali Kelas:</strong>
                        <p class="text-gray-600 dark:text-gray-300 mt-1 italic">
                            "{{ $pData['catatan_wali_kelas'] }}"
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" 
                                wire:click="closePreviewModal"
                                class="px-5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
