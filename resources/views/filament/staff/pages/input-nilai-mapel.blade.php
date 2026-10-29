<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. PEMILIHAN RUMAH MATA PELAJARAN (TABS MAPEL) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-2 bg-primary-100 dark:bg-primary-950/60 text-primary-600 rounded-xl">
                            <x-heroicon-o-book-open class="w-5 h-5" />
                        </span>
                        Pilih Rumah Mata Pelajaran
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Setiap mata pelajaran memiliki ruang input terisolasi. Pilih mapel di bawah untuk mengelola nilainya secara spesifik.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ url('/staff/grades') }}" 
                       class="text-xs inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 hover:bg-primary-100 dark:bg-primary-950/60 dark:hover:bg-primary-900/60 text-primary-700 dark:text-primary-300 rounded-lg transition font-semibold border border-primary-200 dark:border-primary-800 shadow-sm">
                        <x-heroicon-o-academic-cap class="w-4 h-4 text-primary-600" />
                        📋 Input Nilai Lengkap (TP, Remedial & Pengayaan)
                    </a>

                    @if(auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']))
                        <a href="{{ route('filament.staff.resources.teacher-subject-classes.index') }}" 
                           class="text-xs inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg transition font-medium">
                            <x-heroicon-o-link class="w-4 h-4 text-primary-500" />
                            Kelola Routing Guru Mapel
                        </a>
                    @endif
                </div>
            </div>

            {{-- Grid Tombol / Tab Mata Pelajaran --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                @forelse($subjects as $subj)
                    @php
                        $isActive = $selectedSubjectId === $subj->id;
                    @endphp
                    <button type="button" 
                            wire:click="selectSubject({{ $subj->id }})"
                            class="relative flex flex-col items-start p-3 rounded-xl border text-left transition-all duration-150 {{ $isActive 
                                ? 'bg-primary-50 dark:bg-primary-950/40 border-primary-500 shadow-sm ring-2 ring-primary-500/20' 
                                : 'bg-gray-50/70 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800 border-gray-200 dark:border-gray-700/60' }}">
                        
                        <div class="flex items-center justify-between w-full mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded {{ $isActive ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                                {{ $subj->kode ?? 'MPL' }}
                            </span>
                            @if($isActive)
                                <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                            @endif
                        </div>

                        <span class="text-sm font-semibold truncate w-full {{ $isActive ? 'text-primary-700 dark:text-primary-300' : 'text-gray-800 dark:text-gray-200' }}">
                            {{ $subj->name }}
                        </span>
                    </button>
                @empty
                    <div class="col-span-full p-4 text-center text-sm text-gray-500 bg-gray-50 rounded-xl">
                        Belum ada mata pelajaran yang ditugaskan ke akun Anda. Hubungi Waka Kurikulum.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 2. FILTER KELAS, PERIODE & BANNER MAPEL AKTIF --}}
        @if($selectedSubject)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                
                {{-- Kartu Info Rumah Mapel --}}
                <div class="lg:col-span-2 bg-gradient-to-br from-primary-500/10 via-white to-gray-50 dark:from-primary-950/30 dark:via-gray-900 dark:to-gray-900 border border-primary-200/80 dark:border-primary-800/40 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-primary-600 text-white uppercase tracking-wider">
                                Rumah Mapel Aktif
                            </span>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Kode: <strong class="text-gray-800 dark:text-gray-200">{{ $selectedSubject->kode }}</strong>
                            </span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                            {{ $selectedSubject->name }}
                        </h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span>
                                👨‍🏫 Guru Pengampu: 
                                <strong class="text-gray-900 dark:text-gray-200">{{ $assignedTeacher?->name ?? (auth()->user()->name . ' (Anda)') }}</strong>
                            </span>
                            <span>
                                🏫 Rombel: 
                                <strong class="text-gray-900 dark:text-gray-200">{{ $selectedClassroom?->name ?? 'Belum Dipilih' }}</strong>
                            </span>
                            <span>
                                📅 Periode: 
                                <strong class="text-gray-900 dark:text-gray-200">Semester {{ $selectedSemester }} {{ $academicYears->firstWhere('id', $selectedAcademicYearId)?->name ?? '' }}</strong>
                            </span>
                        </p>
                    </div>

                    {{-- Ringkasan Statistik Siswa --}}
                    <div class="grid grid-cols-4 gap-2.5 mt-5 pt-4 border-t border-gray-200/60 dark:border-gray-800">
                        <div class="bg-white/80 dark:bg-gray-800/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/40 text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Total Siswa</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $totalStudents }}</div>
                        </div>
                        <div class="bg-white/80 dark:bg-gray-800/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/40 text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Nilai Lengkap</div>
                            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $completeCount }}</div>
                        </div>
                        <div class="bg-white/80 dark:bg-gray-800/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/40 text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Rata-rata Kelas</div>
                            <div class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $averageScore > 0 ? $averageScore : '-' }}</div>
                        </div>
                        <div class="bg-white/80 dark:bg-gray-800/60 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700/40 text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Tuntas (≥70)</div>
                            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $tuntasPercent }}%</div>
                        </div>
                    </div>
                </div>

                {{-- Kontrol Filter Kelas & Periode --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <x-heroicon-o-adjustments-horizontal class="w-4 h-4 text-primary-500" />
                        Pilih Kelas & Periode Penilaian
                    </h3>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Pilih Kelas / Rombel
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($classrooms as $cls)
                                <button type="button" 
                                        wire:click="selectClassroom({{ $cls->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ $selectedClassroomId === $cls->id 
                                            ? 'bg-primary-600 text-white shadow-sm' 
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                                    {{ $cls->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Filter Kelas Al-Qur'an
                            </label>
                            <select wire:model.live="selectedQuranClass" 
                                    class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- Semua Siswa Rombel --</option>
                                <option value="tahsin">📖 Hanya Kelas Tahsin</option>
                                <option value="yanbua">📖 Hanya Kelas Yanbu'a</option>
                                <option value="tahfidz_dasar">📖 Hanya Kelas Tahfidz Dasar</option>
                                <option value="tahfidz_lanjutan">📖 Hanya Kelas Tahfidz Lanjutan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Semester
                            </label>
                            <select wire:model.live="selectedSemester" 
                                    class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="Ganjil">Semester Ganjil</option>
                                <option value="Genap">Semester Genap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Tahun Ajaran
                            </label>
                            <select wire:model.live="selectedAcademicYearId" 
                                    class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 3. TABEL SPREADSHEET INPUT NILAI SISWA PER MAPEL --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                
                {{-- Header Aksi Tabel --}}
                <div class="p-4 bg-gray-50/70 dark:bg-gray-800/40 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="relative w-64">
                            <input type="text" 
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="Cari nama atau NIS siswa..." 
                                   class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-2 text-gray-400" />
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Menampilkan <strong>{{ $students->count() }}</strong> siswa
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                                onclick="window.print()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 text-gray-700 dark:text-gray-200 shadow-sm transition">
                            <x-heroicon-o-printer class="w-4 h-4 text-gray-500" />
                            Cetak Lembar Nilai
                        </button>

                        <button type="button" 
                                wire:click="saveScores"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-sm transition disabled:opacity-50">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                            <span wire:loading.remove wire:target="saveScores">Simpan Semua Nilai ({{ $selectedSubject->kode }})</span>
                            <span wire:loading wire:target="saveScores">Menyimpan Nilai...</span>
                        </button>
                    </div>
                </div>

                {{-- Tabel Data --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-100/75 dark:bg-gray-800/80 text-[11px] font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-3 px-3 w-12 text-center">No</th>
                                <th class="py-3 px-3 w-32">NIS / NISN</th>
                                <th class="py-3 px-4 min-w-[200px]">Nama Siswa</th>
                                <th class="py-3 px-3 w-36 text-center">
                                    <div class="flex flex-col items-center">
                                        <span>Pengetahuan (P)</span>
                                        <span class="text-[9px] font-normal lowercase text-gray-400">tugas/harian (30%)</span>
                                    </div>
                                </th>
                                <th class="py-3 px-3 w-36 text-center">
                                    <div class="flex flex-col items-center">
                                        <span>Keterampilan (K)</span>
                                        <span class="text-[9px] font-normal lowercase text-gray-400">praktik/proyek (30%)</span>
                                    </div>
                                </th>
                                <th class="py-3 px-3 w-36 text-center">
                                    <div class="flex flex-col items-center">
                                        <span>Ujian (UTS/UAS)</span>
                                        <span class="text-[9px] font-normal lowercase text-gray-400">sumatif (40%)</span>
                                    </div>
                                </th>
                                <th class="py-3 px-3 w-28 text-center">
                                    <span>Nilai Akhir</span>
                                </th>
                                <th class="py-3 px-4 w-44 text-center">
                                    <span>Predikat & Status</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($students as $index => $student)
                                @php
                                    $row = $scores[$student->id] ?? ['pengetahuan' => '', 'keterampilan' => '', 'ujian' => ''];
                                    $finalScore = $this->calculateFinalScore($row['pengetahuan'] ?? '', $row['keterampilan'] ?? '', $row['ujian'] ?? '');
                                    $pred = $this->getPredicate($finalScore);
                                @endphp
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-2.5 px-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3 text-xs font-mono text-gray-600 dark:text-gray-400">
                                        {{ $student->nis ?? ($student->nisn ?? '-') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-gray-900 dark:text-white">
                                        <div class="font-semibold text-sm">{{ $student->name }}</div>
                                        <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                            @if ($student->kelas_quran)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                    📖 Kelas Al-Qur'an: {{ $student->kelas_quran_label }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                    ⚠️ Belum Masuk Kelas Al-Qur'an
                                                </span>
                                            @endif
                                            @if ($student->guruQuran)
                                                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                                    (Guru: {{ $student->guruQuran->name }})
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    {{-- Input Nilai Pengetahuan --}}
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="number" 
                                               min="0" 
                                               max="100" 
                                               step="0.5"
                                               wire:model.live.debounce.400ms="scores.{{ $student->id }}.pengetahuan"
                                               placeholder="0 - 100"
                                               class="w-24 px-2 py-1 text-center font-bold text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm">
                                    </td>

                                    {{-- Input Nilai Keterampilan --}}
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="number" 
                                               min="0" 
                                               max="100" 
                                               step="0.5"
                                               wire:model.live.debounce.400ms="scores.{{ $student->id }}.keterampilan"
                                               placeholder="0 - 100"
                                               class="w-24 px-2 py-1 text-center font-bold text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm">
                                    </td>

                                    {{-- Input Nilai Ujian --}}
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="number" 
                                               min="0" 
                                               max="100" 
                                               step="0.5"
                                               wire:model.live.debounce.400ms="scores.{{ $student->id }}.ujian"
                                               placeholder="0 - 100"
                                               class="w-24 px-2 py-1 text-center font-bold text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm">
                                    </td>

                                    {{-- Nilai Akhir (Kalkulasi Otomatis) --}}
                                    <td class="py-2.5 px-3 text-center">
                                        @if($finalScore !== null)
                                            <span class="inline-block px-2.5 py-1 text-xs font-black rounded-lg {{ $finalScore >= 75 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' }}">
                                                {{ $finalScore }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>

                                    {{-- Predikat & Ketercapaian --}}
                                    <td class="py-2.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $pred['color'] }}">
                                            {{ $pred['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-heroicon-o-user-group class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                            <span>Tidak ada siswa pada kelas ini atau belum ada rombel yang dipilih.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer Simpan & Shortcut Info --}}
                <div class="p-4 bg-gray-50/80 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Bobot Nilai Akhir: 30% Pengetahuan + 30% Keterampilan + 40% Ujian. KKM Kelulusan = 75.</span>
                    </div>

                    <button type="button" 
                            wire:click="saveScores"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-6 py-2 text-sm font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-md transition disabled:opacity-50">
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        <span wire:loading.remove wire:target="saveScores">Simpan Perubahan Nilai {{ $selectedSubject->name }}</span>
                        <span wire:loading wire:target="saveScores">Menyimpan Nilai...</span>
                    </button>
                </div>

            </div>

        @else
            <div class="p-12 text-center bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl">
                <x-heroicon-o-book-open class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">Silakan Pilih Mata Pelajaran Terlebih Dahulu</h3>
                <p class="text-xs text-gray-500 mt-1">Pilih salah satu mata pelajaran di bagian atas untuk membuka ruang input nilai.</p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
