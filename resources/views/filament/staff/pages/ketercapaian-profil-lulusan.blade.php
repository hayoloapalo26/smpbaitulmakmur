<x-filament-panels::page>
    <div class="space-y-6">
        <!-- HEADER & FILTER KEPALA SEKOLAH -->
        <div class="p-6 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-100/60 dark:from-gray-800 dark:via-gray-800 dark:to-gray-700/80 rounded-2xl border border-amber-200/80 dark:border-gray-700 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/10 dark:bg-amber-400/10 text-amber-700 dark:text-amber-300 text-xs font-bold uppercase tracking-wider border border-amber-300/40">
                        <span>🛡️</span>
                        <span>Portal Evaluasi Kepala Sekolah</span>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                        Ketercapaian Profil Lulusan Siswa
                    </h2>
                    <p class="text-xs text-gray-600 dark:text-gray-300 max-w-3xl">
                        Penilaian komprehensif profil lulusan dinilai dari <strong>3 Aspek Utama</strong>: 
                        <span class="text-amber-700 dark:text-amber-300 font-semibold">(1) Nilai Akademik per Mapel Akumulasi Harian Guru</span>, 
                        <span class="text-emerald-700 dark:text-emerald-300 font-semibold">(2) Nilai & Capaian Tahfidz/Tahsin</span>, dan 
                        <span class="text-purple-700 dark:text-purple-300 font-semibold">(3) Inputan Prestasi Lomba Waka Kesiswaan</span>.
                    </p>
                </div>

                <!-- SELECTOR TAHUN AJARAN & SEMESTER -->
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun Ajaran</label>
                        <select wire:model.live="selectedAcademicYearId" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-amber-500 focus:ring-amber-500">
                            @foreach($academicYears as $y)
                                <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_active ? '(Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Semester</label>
                        <select wire:model.live="selectedSemester" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-amber-500 focus:ring-amber-500">
                            <option value="Ganjil">Semester Ganjil</option>
                            <option value="Genap">Semester Genap</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TAB / BAR KELAS -->
            <div class="mt-6 pt-5 border-t border-amber-200/60 dark:border-gray-700">
                <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                    <span>🏫</span>
                    <span>Pilih Kelas untuk Dipantau Kepala Sekolah:</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @forelse($availableClasses as $cls)
                        <button 
                            type="button"
                            wire:click="selectClass({{ $cls->id }})"
                            class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center space-x-2 border shadow-sm
                            {{ $selectedClassroomId === $cls->id 
                                ? 'bg-amber-500 text-white border-amber-600 ring-2 ring-amber-400/40 shadow-amber-500/20' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-amber-50 dark:hover:bg-gray-700' }}">
                            <span class="text-sm">📍</span>
                            <span>{{ $cls->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $selectedClassroomId === $cls->id ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                Tingkat {{ $cls->tingkat }}
                            </span>
                        </button>
                    @empty
                        <div class="text-xs text-gray-500 italic">Belum ada kelas yang terdaftar.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- VIEW 1: DAFTAR SISWA KELAS (KETIKA BELUM MEMILIH DETAIL SISWA)            --}}
        {{-- ========================================================================= --}}
        @if(!$selectedStudentDetail)
            <div class="space-y-6">
                <!-- RINGKASAN STATISTIK KELAS TERPILIH -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Rata-rata Akademik Kelas -->
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center text-2xl font-bold">
                            📚
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rata-rata Akademik</div>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">
                                {{ $classStats->avg_academic }} <span class="text-xs text-gray-400 font-normal">/ 100</span>
                            </div>
                            <div class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">Akumulasi Mapel Harian</div>
                        </div>
                    </div>

                    <!-- Capaian Tahfidz Kelas -->
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center text-2xl font-bold">
                            📖
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Setoran Tahfidz</div>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">
                                {{ $classStats->total_ziyadah }} <span class="text-xs text-gray-400 font-normal">Ziyadah</span>
                            </div>
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Hafalan Baru Tercatat</div>
                        </div>
                    </div>

                    <!-- Prestasi Siswa Kelas (Waka Kesiswaan) -->
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 flex items-center justify-center text-2xl font-bold">
                            🏆
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prestasi Terverifikasi</div>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">
                                {{ $classStats->total_achievements }} <span class="text-xs text-gray-400 font-normal">Juara</span>
                            </div>
                            <div class="text-[11px] text-purple-600 dark:text-purple-400 font-medium">Input Waka Kesiswaan</div>
                        </div>
                    </div>

                    <!-- Ketercapaian Profil Lulusan Kelas -->
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center text-2xl font-bold">
                            🌟
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kelulusan Profil Siswa</div>
                            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">
                                {{ $classStats->pass_rate }}%
                            </div>
                            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">{{ $classStats->total_students }} Siswa di Kelas</div>
                        </div>
                    </div>
                </div>

                <!-- FILTER SEARCH & STATUS SISWA -->
                <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1 relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchStudent"
                            placeholder="🔍 Cari nama siswa atau NIS..."
                            class="w-full pl-4 pr-10 py-2.5 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-700/60 text-xs focus:border-amber-500 focus:ring-amber-500 text-gray-800 dark:text-gray-200"
                        />
                    </div>

                    <div class="flex items-center space-x-2">
                        <label class="text-xs font-semibold text-gray-500">Filter Status:</label>
                        <select wire:model.live="filterStatus" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-700/60 text-xs font-medium focus:border-amber-500 focus:ring-amber-500">
                            <option value="all">Semua Ketercapaian</option>
                            <option value="sangat_memenuhi">Sangat Memenuhi (Predikat A)</option>
                            <option value="memenuhi">Memenuhi (Predikat B)</option>
                            <option value="perlu_peningkatan">Perlu Peningkatan (Predikat C)</option>
                        </select>
                    </div>
                </div>

                <!-- DAFTAR SISWA KELAS -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 bg-gray-50/70 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">📋</span>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                                Rekap Ketercapaian Profil Lulusan Siswa: {{ $selectedClass?->name ?? 'Semua Kelas' }}
                            </h3>
                        </div>
                        <span class="text-xs font-medium text-gray-500">
                            Menampilkan {{ count($studentsData) }} Siswa • Klik baris atau tombol untuk melihat detail 3 aspek
                        </span>
                    </div>

                    @if(count($studentsData) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-12">No</th>
                                        <th class="px-4 py-3">Nama Siswa & Identitas</th>
                                        <th class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>📚</span>
                                                <span>Aspek 1: Akademik Mapel</span>
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>📖</span>
                                                <span>Aspek 2: Tahfidz / Tahsin</span>
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center space-x-1">
                                                <span>🏆</span>
                                                <span>Aspek 3: Prestasi Waka</span>
                                            </div>
                                        </th>
                                        <th class="px-4 py-3 text-center">Skor Profil Lulusan</th>
                                        <th class="px-4 py-3 text-center">Status Kelulusan</th>
                                        <th class="px-4 py-3 text-center w-36">Aksi Detail</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($studentsData as $idx => $st)
                                        <tr class="hover:bg-amber-50/40 dark:hover:bg-gray-700/40 transition-colors cursor-pointer group" wire:click="selectStudent({{ $st->id }})">
                                            <td class="px-4 py-3.5 text-center font-mono text-gray-400">{{ $idx + 1 }}</td>
                                            
                                            <!-- IDENTITAS SISWA -->
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 to-orange-400 text-white font-black flex items-center justify-center text-sm shadow-sm group-hover:scale-105 transition-transform">
                                                        {{ strtoupper(substr($st->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-gray-900 dark:text-white text-xs group-hover:text-amber-600 transition-colors">
                                                            {{ $st->name }}
                                                        </div>
                                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center space-x-1.5 mt-0.5">
                                                            <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">NIS: {{ $st->nis }}</span>
                                                            <span>•</span>
                                                            <span>{{ $st->jenis_kelamin === 'L' ? 'Ikhwan (L)' : 'Akhwat (P)' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- ASPEK 1: NILAI AKADEMIK -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="inline-block p-2 rounded-xl bg-blue-50/80 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                                                    <div class="font-black text-sm text-blue-700 dark:text-blue-300">
                                                        {{ $st->academic_average }}
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                        {{ $st->graded_subjects_count }} Mapel Terakumulasi
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- ASPEK 2: TAHFIDZ & TAHSIN -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="inline-block p-2 rounded-xl bg-emerald-50/80 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800">
                                                    <div class="font-black text-xs text-emerald-700 dark:text-emerald-300">
                                                        {{ $st->ziyadah_count }}x Ziyadah
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                        Juz {{ $st->last_juz }} ({{ $st->last_surah_name }})
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- ASPEK 3: PRESTASI WAKA KESISWAAN -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="inline-block p-2 rounded-xl bg-purple-50/80 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800">
                                                    <div class="font-black text-xs text-purple-700 dark:text-purple-300">
                                                        {{ $st->total_achievements }} Prestasi
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                        {{ $st->akademik_achievements }} Akad / {{ $st->non_akademik_achievements }} Non-Akad
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- SKOR KOMPOSIT -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="font-extrabold text-sm text-gray-900 dark:text-white">
                                                    {{ $st->composite_score }}
                                                </div>
                                                <div class="text-[10px] text-gray-400">Predikat {{ $st->predikat }}</div>
                                            </td>

                                            <!-- STATUS KELULUSAN PROFIL -->
                                            <td class="px-4 py-3.5 text-center">
                                                @if($st->status_key === 'sangat_memenuhi')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200">
                                                        ✨ Sangat Memenuhi
                                                    </span>
                                                @elseif($st->status_key === 'memenuhi')
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200">
                                                        ✓ Memenuhi Profil
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200">
                                                        ⚠️ Perlu Peningkatan
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- TOMBOL DETAIL -->
                                            <td class="px-4 py-3.5 text-center" onclick="event.stopPropagation();">
                                                <button 
                                                    type="button" 
                                                    wire:click="selectStudent({{ $st->id }})"
                                                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all hover:scale-105 active:scale-95">
                                                    <span>Lihat Detail</span>
                                                    <span>→</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-12 text-center text-gray-400">
                            <div class="text-4xl mb-2">🔍</div>
                            <div class="font-bold text-sm text-gray-600 dark:text-gray-300">Tidak ada data siswa ditemukan.</div>
                            <p class="text-xs text-gray-400 mt-1">Coba pilih kelas lain atau ubah kata kunci pencarian Anda.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- VIEW 2: DETAIL PENILAIAN 3 ASPEK SISWA                                    --}}
        {{-- ========================================================================= --}}
        @if($selectedStudentDetail)
            @php $detail = $selectedStudentDetail; @endphp
            <div class="space-y-6">
                <!-- TOP ACTION BAR: KEMBALI & CETAK -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm print:hidden">
                    <button 
                        type="button"
                        wire:click="backToClassList"
                        class="inline-flex items-center space-x-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                        <span class="text-base">←</span>
                        <span>Kembali ke Daftar Siswa (Kelas {{ $detail->student->classroom->name ?? '-' }})</span>
                    </button>

                    <div class="flex items-center space-x-2">
                        <button 
                            type="button"
                            onclick="window.print()"
                            class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold bg-gray-900 text-white dark:bg-white dark:text-gray-900 hover:bg-gray-800 transition-all shadow-sm">
                            <span>🖨️</span>
                            <span>Cetak Portofolio Profil Lulusan</span>
                        </button>
                    </div>
                </div>

                <!-- BANNER HEADER SISWA TERPILIH -->
                <div class="p-6 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white rounded-2xl shadow-lg relative overflow-hidden">
                    <!-- Background decorative pattern -->
                    <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex items-start sm:items-center space-x-5">
                            <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm border-2 border-white/40 flex items-center justify-center text-3xl font-black text-white shadow-md">
                                {{ strtoupper(substr($detail->student->name, 0, 1)) }}
                            </div>
                            <div class="space-y-1">
                                <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-[11px] font-bold tracking-wide">
                                    <span>Kelas {{ $detail->student->classroom->name ?? '-' }}</span>
                                    <span>•</span>
                                    <span>{{ $detail->student->jenis_kelamin === 'L' ? 'Laki-laki (Ikhwan)' : 'Perempuan (Akhwat)' }}</span>
                                </div>
                                <h2 class="text-2xl font-black tracking-tight text-white">
                                    {{ $detail->student->name }}
                                </h2>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-white/90">
                                    <span class="font-mono bg-black/20 px-2 py-0.5 rounded">NIS: {{ $detail->student->nis }}</span>
                                    @if($detail->student->nisn)
                                        <span class="font-mono bg-black/20 px-2 py-0.5 rounded">NISN: {{ $detail->student->nisn }}</span>
                                    @endif
                                    <span>Wali Kelas: <strong>{{ $detail->student->classroom->waliKelas->name ?? 'Belum Ditentukan' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- KARTU GRAND SCORE KEPALA SEKOLAH -->
                        <div class="flex items-center space-x-4 bg-white/15 backdrop-blur-md p-4 rounded-2xl border border-white/30 self-stretch sm:self-auto justify-between sm:justify-start">
                            <div class="text-right">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-white/80">Skor Profil Lulusan</div>
                                <div class="text-3xl font-black text-white tracking-tight">
                                    {{ $detail->composite_score }} <span class="text-sm font-normal text-white/70">/ 100</span>
                                </div>
                                <div class="text-[11px] font-extrabold text-amber-200">
                                    {{ $detail->predikat_akhir }}
                                </div>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-white text-amber-600 font-black flex items-center justify-center text-xl shadow-md">
                                🌟
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-NAVIGASI TAB 3 ASPEK -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 space-x-3 overflow-x-auto print:hidden">
                    <button 
                        wire:click="setAspectTab('overview')"
                        class="py-3 px-4 font-bold text-xs border-b-2 transition-all flex items-center space-x-2 whitespace-nowrap
                        {{ $activeAspectTab === 'overview' 
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50/50 dark:bg-gray-800/60 rounded-t-xl' 
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span>⭐</span>
                        <span>Ringkasan 3 Aspek</span>
                    </button>

                    <button 
                        wire:click="setAspectTab('akademik')"
                        class="py-3 px-4 font-bold text-xs border-b-2 transition-all flex items-center space-x-2 whitespace-nowrap
                        {{ $activeAspectTab === 'akademik' 
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-gray-800/60 rounded-t-xl' 
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span>📚</span>
                        <span>Aspek 1: Nilai Akademik Mapel (Harian Guru)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ count($detail->subjects) }} Mapel</span>
                    </button>

                    <button 
                        wire:click="setAspectTab('tahfidz')"
                        class="py-3 px-4 font-bold text-xs border-b-2 transition-all flex items-center space-x-2 whitespace-nowrap
                        {{ $activeAspectTab === 'tahfidz' 
                            ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-gray-800/60 rounded-t-xl' 
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span>📖</span>
                        <span>Aspek 2: Nilai Tahfidz & Tahsin</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">{{ $detail->tahfidz->total_ziyadah }} Ziyadah</span>
                    </button>

                    <button 
                        wire:click="setAspectTab('prestasi')"
                        class="py-3 px-4 font-bold text-xs border-b-2 transition-all flex items-center space-x-2 whitespace-nowrap
                        {{ $activeAspectTab === 'prestasi' 
                            ? 'border-purple-500 text-purple-600 dark:text-purple-400 bg-purple-50/50 dark:bg-gray-800/60 rounded-t-xl' 
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span>🏆</span>
                        <span>Aspek 3: Inputan Prestasi (Waka Kesiswaan)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">{{ $detail->prestasi->total_count }} Prestasi</span>
                    </button>
                </div>

                {{-- ================================================================= --}}
                {{-- TAB: OVERVIEW / RINGKASAN 3 ASPEK                                 --}}
                {{-- ================================================================= --}}
                @if($activeAspectTab === 'overview')
                    <div class="space-y-6">
                        <!-- STATUS KELULUSAN CARD -->
                        <div class="p-5 rounded-2xl border {{ $detail->composite_score >= 75 ? 'bg-emerald-50/80 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800' : 'bg-amber-50/80 border-amber-200 dark:bg-amber-950/30 dark:border-amber-800' }} flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">{{ $detail->composite_score >= 85 ? '🌟' : ($detail->composite_score >= 75 ? '✅' : '⚠️') }}</span>
                                    <h3 class="font-extrabold text-sm {{ $detail->composite_score >= 75 ? 'text-emerald-950 dark:text-emerald-200' : 'text-amber-950 dark:text-amber-200' }}">
                                        Hasil Evaluasi Kepala Sekolah: {{ $detail->status_label }}
                                    </h3>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $detail->status_subtext }}
                                </p>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl font-mono font-bold text-xs {{ $detail->status_badge_class }}">
                                Predikat: {{ $detail->predikat_akhir }}
                            </div>
                        </div>

                        <!-- 3 PILAR PENILAIAN CARDS BERDAMPINGAN -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- ASPEK 1: AKADEMIK CARD -->
                            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-blue-200 dark:border-blue-900 shadow-sm flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-2xl">📚</span>
                                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">1. Nilai Akademik Siswa</h4>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Bobot 40%</span>
                                    </div>

                                    <div class="p-3 bg-blue-50/70 dark:bg-blue-900/20 rounded-xl space-y-1 text-center">
                                        <div class="text-[11px] text-gray-500 font-semibold uppercase">Rata-rata Kumulatif Mapel</div>
                                        <div class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ $detail->overall_academic_avg }}</div>
                                        <div class="text-[10px] text-gray-400">Ketuntasan KKM: {{ $detail->academic_completion_rate }}% ({{ $detail->total_tuntas }}/{{ $detail->total_subjects }} Mapel)</div>
                                    </div>

                                    <div class="space-y-1.5 text-xs">
                                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Mata Pelajaran Utama:</div>
                                        @foreach(collect($detail->subjects)->take(4) as $sub)
                                            <div class="flex justify-between items-center py-1 border-b border-gray-100 dark:border-gray-700">
                                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $sub['name'] }}</span>
                                                <span class="font-bold font-mono {{ $sub['is_tuntas'] ? 'text-blue-600' : 'text-rose-500' }}">
                                                    {{ $sub['final_score'] ?? '-' }} ({{ $sub['predikat'] }})
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="setAspectTab('akademik')" 
                                    class="mt-4 w-full py-2 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 transition-colors text-center">
                                    Lihat Semua Nilai Mapel Harian →
                                </button>
                            </div>

                            <!-- ASPEK 2: TAHFIDZ & TAHSIN CARD -->
                            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-emerald-200 dark:border-emerald-900 shadow-sm flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-2xl">📖</span>
                                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">2. Capaian Tahfidz & Tahsin</h4>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Bobot 35%</span>
                                    </div>

                                    <div class="p-3 bg-emerald-50/70 dark:bg-emerald-900/20 rounded-xl space-y-1 text-center">
                                        <div class="text-[11px] text-gray-500 font-semibold uppercase">Capaian Ziyadah & Mutu</div>
                                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $detail->tahfidz->total_ziyadah }} <span class="text-xs font-normal">Setoran</span></div>
                                        <div class="text-[10px] text-emerald-700 dark:text-emerald-300 font-semibold">{{ $detail->tahfidz->mutu_predikat }}</div>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Setoran Terakhir:</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-200">Juz {{ $detail->tahfidz->last_juz }} ({{ $detail->tahfidz->last_surah }})</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Tanggal Setor:</span>
                                            <span class="font-mono text-gray-700 dark:text-gray-300">{{ $detail->tahfidz->last_date }}</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Target Profil 3 Juz:</span>
                                            <span class="font-bold text-emerald-600">{{ $detail->tahfidz->progress_pct }}% Tercapai</span>
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="setAspectTab('tahfidz')" 
                                    class="mt-4 w-full py-2 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 transition-colors text-center">
                                    Lihat Riwayat Lengkap Tahfidz →
                                </button>
                            </div>

                            <!-- ASPEK 3: PRESTASI WAKA KESISWAAN CARD -->
                            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-purple-200 dark:border-purple-900 shadow-sm flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-2xl">🏆</span>
                                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">3. Prestasi Waka Kesiswaan</h4>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Bobot 25%</span>
                                    </div>

                                    <div class="p-3 bg-purple-50/70 dark:bg-purple-900/20 rounded-xl space-y-1 text-center">
                                        <div class="text-[11px] text-gray-500 font-semibold uppercase">Total Kejuaraan Siswa</div>
                                        <div class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ $detail->prestasi->total_count }} <span class="text-xs font-normal">Prestasi</span></div>
                                        <div class="text-[10px] text-purple-700 dark:text-purple-300 font-semibold">Tingkat Tertinggi: {{ $detail->prestasi->tingkat_tertinggi }}</div>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Lomba Akademik:</span>
                                            <span class="font-bold text-blue-600">{{ $detail->prestasi->akademik_count }} Kejuaraan</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Lomba Non-Akademik:</span>
                                            <span class="font-bold text-purple-600">{{ $detail->prestasi->non_akademik_count }} Kejuaraan</span>
                                        </div>
                                        <div class="flex justify-between border-b pb-1 border-gray-100 dark:border-gray-700">
                                            <span class="text-gray-500">Verifikator Data:</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Waka Kesiswaan</span>
                                        </div>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="setAspectTab('prestasi')" 
                                    class="mt-4 w-full py-2 rounded-xl text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 transition-colors text-center">
                                    Lihat Detail Piagam & Lomba →
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================================================================= --}}
                {{-- TAB: ASPEK 1 - NILAI AKADEMIK PER MAPEL (UPDATE HARIAN GURU)      --}}
                {{-- ================================================================= --}}
                @if($activeAspectTab === 'akademik')
                    <div class="space-y-6">
                        <!-- HEADER KETERANGAN ASPEK AKADEMIK -->
                        <div class="p-4 bg-blue-50/80 dark:bg-blue-950/30 rounded-2xl border border-blue-200 dark:border-blue-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">📚</span>
                                    <h3 class="font-bold text-sm text-blue-900 dark:text-blue-200">
                                        Aspek 1: Akumulasi Nilai Akademik per Mata Pelajaran
                                    </h3>
                                </div>
                                <p class="text-xs text-blue-800/80 dark:text-blue-300">
                                    Setiap mata pelajaran diupdate secara akumulatif setiap hari oleh guru pengampu masing-masing (Bahasa Inggris sendiri, Bahasa Indonesia sendiri, Informatika sendiri, dsb).
                                </p>
                            </div>
                            <div class="flex items-center space-x-4 border-t md:border-t-0 md:border-l pt-2 md:pt-0 md:pl-4 border-blue-200 dark:border-blue-800 text-center">
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Rata-rata Kumulatif</div>
                                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $detail->overall_academic_avg }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-gray-500 uppercase font-semibold">Ketuntasan KKM (75)</div>
                                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $detail->academic_completion_rate }}%</div>
                                </div>
                            </div>
                        </div>

                        <!-- GRID SETIAP MATA PELAJARAN (MAPEL SENDIRI-SENDIRI) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($detail->subjects as $mapel)
                                <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4 hover:border-blue-300 transition-all">
                                    <!-- Mapel Header -->
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $mapel['kode'] }}
                                                </span>
                                                <h4 class="font-extrabold text-sm text-gray-900 dark:text-white">
                                                    {{ $mapel['name'] }}
                                                </h4>
                                            </div>
                                            <div class="text-[11px] text-gray-500 flex items-center space-x-1">
                                                <span>👨‍🏫 Guru Pengampu:</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $mapel['teacher_name'] }}</span>
                                            </div>
                                        </div>

                                        <!-- Badge Nilai Akhir Mapel -->
                                        <div class="text-right">
                                            <div class="text-xl font-black {{ $mapel['is_tuntas'] ? 'text-blue-600 dark:text-blue-400' : 'text-rose-500' }}">
                                                {{ $mapel['final_score'] ?? '-' }}
                                            </div>
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ $mapel['is_tuntas'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                {{ $mapel['is_tuntas'] ? 'Tuntas (' . $mapel['predikat'] . ')' : 'Belum Tuntas' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Indikator Rata-rata Harian & Ujian -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 text-center">
                                            <div class="text-[10px] text-gray-500 font-semibold uppercase">Rata-rata Harian / Tugas</div>
                                            <div class="text-sm font-extrabold text-gray-800 dark:text-gray-200 mt-0.5">
                                                {{ $mapel['avg_harian'] ?? '-' }}
                                            </div>
                                            <div class="text-[9px] text-gray-400">Akumulasi {{ $mapel['update_count'] }}x Entri Guru</div>
                                        </div>

                                        <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/40 text-center">
                                            <div class="text-[10px] text-gray-500 font-semibold uppercase">Nilai Ujian (UTS / UAS)</div>
                                            <div class="text-sm font-extrabold text-gray-800 dark:text-gray-200 mt-0.5">
                                                {{ $mapel['score_ujian'] ?? '-' }}
                                            </div>
                                            <div class="text-[9px] text-gray-400">Bobot Ujian 40%</div>
                                        </div>
                                    </div>

                                    <!-- RIWAYAT LOG UPDATE HARIAN GURU MAPEL -->
                                    <div class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider flex items-center justify-between">
                                            <span>📅 Riwayat Update Harian Guru Mapel:</span>
                                            <span class="text-[10px] text-blue-600 font-normal">{{ count($mapel['daily_updates']) }} Update</span>
                                        </div>

                                        @if(count($mapel['daily_updates']) > 0)
                                            <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                                @foreach($mapel['daily_updates'] as $log)
                                                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50/80 dark:bg-gray-700/30 text-xs">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-gray-400 text-[10px] font-mono">{{ $log['date'] }}</span>
                                                            <span class="font-medium text-gray-700 dark:text-gray-300 text-[11px]">{{ $log['type_label'] }}</span>
                                                        </div>
                                                        <span class="font-bold font-mono px-2 py-0.5 rounded text-[11px] {{ $log['score'] >= 75 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                            {{ $log['score'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-xs text-gray-400 italic py-2 text-center">
                                                Belum ada riwayat update nilai harian untuk mapel ini.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ================================================================= --}}
                {{-- TAB: ASPEK 2 - NILAI TAHFIDZ & TAHSIN                             --}}
                {{-- ================================================================= --}}
                @if($activeAspectTab === 'tahfidz')
                    <div class="space-y-6">
                        <!-- HEADER STAT TAHFIDZ -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-emerald-100 dark:border-emerald-900 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Setoran Ziyadah (Hafalan Baru)</div>
                                <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    {{ $detail->tahfidz->total_ziyadah }} <span class="text-xs font-normal">Kali</span>
                                </div>
                                <div class="text-[11px] text-gray-400">Total entri hafalan baru</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-emerald-100 dark:border-emerald-900 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Setoran Muraja'ah</div>
                                <div class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">
                                    {{ $detail->tahfidz->total_murajaah }} <span class="text-xs font-normal">Kali</span>
                                </div>
                                <div class="text-[11px] text-gray-400">Pengulangan hafalan mutqin</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-emerald-100 dark:border-emerald-900 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Capaian Terakhir</div>
                                <div class="text-xl font-black text-gray-900 dark:text-white mt-1">
                                    Juz {{ $detail->tahfidz->last_juz }}
                                </div>
                                <div class="text-[11px] text-emerald-600 font-medium">{{ $detail->tahfidz->last_surah }} (Ayat {{ $detail->tahfidz->last_ayah }})</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-emerald-100 dark:border-emerald-900 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Skala Mutu Bacaan</div>
                                <div class="text-3xl font-black text-purple-600 dark:text-purple-400 mt-1">
                                    {{ $detail->tahfidz->overall_quality }}
                                </div>
                                <div class="text-[11px] text-purple-600 font-bold">{{ $detail->tahfidz->mutu_predikat }}</div>
                            </div>
                        </div>

                        <!-- PROGRESS TARGET PROFIL LULUSAN TAHFIDZ -->
                        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <div class="space-y-0.5">
                                    <h4 class="font-extrabold text-sm text-gray-900 dark:text-white flex items-center space-x-1.5">
                                        <span>🎯</span>
                                        <span>Target Profil Lulusan Tahfidz: Minimal {{ $detail->tahfidz->target_juz }} Juz Mutqin (Juz 30, 29, 28)</span>
                                    </h4>
                                    <p class="text-[11px] text-gray-500">Standar kompetensi kelulusan Al-Qur'an Yayasan Baitul Makmur.</p>
                                </div>
                                <span class="font-mono font-bold text-sm text-emerald-600">{{ $detail->tahfidz->progress_pct }}% Tercapai</span>
                            </div>

                            <div class="w-full bg-gray-100 dark:bg-gray-700 h-3.5 rounded-full overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: {{ $detail->tahfidz->progress_pct }}%"></div>
                            </div>

                            <div class="flex justify-between items-center text-[11px] text-gray-500">
                                <span>Capaian: <strong>{{ $detail->tahfidz->completed_juz_count }} dari {{ $detail->tahfidz->target_juz }} Juz</strong> telah disetorkan</span>
                                <span>Pembimbing Al-Qur'an: <strong>{{ $detail->tahfidz->last_teacher }}</strong></span>
                            </div>
                        </div>

                        <!-- RIWAYAT LENGKAP SETORAN TAHFIDZ -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-4 bg-gray-50/70 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <h4 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                                    <span>📜</span>
                                    <span>Log Riwayat Setoran Al-Qur'an Siswa</span>
                                </h4>
                                <span class="text-xs text-gray-500">{{ count($detail->tahfidz->logs) }} Catatan Setoran</span>
                            </div>

                            @if(count($detail->tahfidz->logs) > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b">
                                            <tr>
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3 text-center w-28">Tanggal</th>
                                                <th class="px-4 py-3 text-center w-28">Jenis Setoran</th>
                                                <th class="px-4 py-3 text-center">Juz</th>
                                                <th class="px-4 py-3">Nama Surat & Ayat</th>
                                                <th class="px-4 py-3">Catatan Guru Al-Qur'an</th>
                                                <th class="px-4 py-3">Guru Pembimbing</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach($detail->tahfidz->logs as $idx => $tlog)
                                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                                    <td class="px-4 py-3 text-center text-gray-400">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-3 text-center font-mono text-gray-500">{{ $tlog['tanggal'] }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ str_contains(strtolower($tlog['type']), 'ziyadah') ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                                            {{ $tlog['type'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-extrabold text-gray-800 dark:text-gray-200">Juz {{ $tlog['juz'] }}</td>
                                                    <td class="px-4 py-3">
                                                        <div class="font-bold text-gray-900 dark:text-white">{{ $tlog['surah_name'] }}</div>
                                                        <div class="text-[10px] text-gray-400">Ayat {{ $tlog['ayah'] }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 italic">{{ $tlog['catatan'] }}</td>
                                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $tlog['teacher_name'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="py-8 text-center text-gray-400 text-xs">
                                    Belum ada catatan setoran tahfidz untuk siswa ini.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- ================================================================= --}}
                {{-- TAB: ASPEK 3 - PRESTASI SISWA (INPUTAN WAKA KESISWAAN)            --}}
                {{-- ================================================================= --}}
                @if($activeAspectTab === 'prestasi')
                    <div class="space-y-6">
                        <!-- NOTICE VERIFIKASI WAKA KESISWAAN -->
                        <div class="p-4 bg-purple-50/80 dark:bg-purple-950/30 rounded-2xl border border-purple-200 dark:border-purple-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">🏆</span>
                                    <h3 class="font-bold text-sm text-purple-900 dark:text-purple-200">
                                        Aspek 3: Inputan & Rekam Jejak Prestasi Siswa oleh Waka Kesiswaan
                                    </h3>
                                </div>
                                <p class="text-xs text-purple-800/80 dark:text-purple-300">
                                    Mencakup seluruh perolehan prestasi lomba akademik (OSN, mapel, robotik) maupun non-akademik (MHQ, seni, olahraga, pramuka) yang dihimpun dan diverifikasi oleh Waka Kesiswaan.
                                </p>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl bg-purple-200/80 text-purple-900 font-bold text-xs">
                                {{ $detail->prestasi->total_count }} Prestasi Tercatat
                            </div>
                        </div>

                        <!-- STAT CARDS PRESTASI -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Prestasi Akademik</div>
                                <div class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">
                                    {{ $detail->prestasi->akademik_count }}
                                </div>
                                <div class="text-[11px] text-gray-400">Sains, Bahasa, Matematika, IT</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Prestasi Religius</div>
                                <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    {{ $detail->prestasi->religius_count }}
                                </div>
                                <div class="text-[11px] text-gray-400">Tahfidz, Tahsin, MTQ/MHQ, Da'i</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Prestasi Non-Akademik</div>
                                <div class="text-3xl font-black text-purple-600 dark:text-purple-400 mt-1">
                                    {{ $detail->prestasi->non_akademik_count }}
                                </div>
                                <div class="text-[11px] text-gray-400">Olahraga, Seni, Pramuka, dll</div>
                            </div>

                            <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Tingkat Tertinggi</div>
                                <div class="text-xl font-black text-amber-600 dark:text-amber-400 mt-1">
                                    {{ $detail->prestasi->tingkat_tertinggi }}
                                </div>
                                <div class="text-[11px] text-gray-400">Pencapaian level kejuaraan</div>
                            </div>
                        </div>

                        <!-- TABEL DETAIL PRESTASI LOMBA -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-4 bg-gray-50/70 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <h4 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider flex items-center space-x-1.5">
                                    <span>🏅</span>
                                    <span>Daftar Piagam & Penghargaan Siswa</span>
                                </h4>
                                <span class="text-xs text-gray-500">Diverifikasi Waka Kesiswaan</span>
                            </div>

                            @if($detail->prestasi->list->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b">
                                            <tr>
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3">Nama Prestasi / Kejuaraan</th>
                                                <th class="px-4 py-3 text-center">Kategori</th>
                                                <th class="px-4 py-3 text-center">Tingkat</th>
                                                <th class="px-4 py-3 text-center">Tanggal</th>
                                                <th class="px-4 py-3">Penyelenggara / Keterangan</th>
                                                <th class="px-4 py-3 text-center">Bukti / Sertifikat</th>
                                                <th class="px-4 py-3 text-center">Diinput Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach($detail->prestasi->list as $idx => $ach)
                                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
                                                    <td class="px-4 py-3 text-center text-gray-400">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-3">
                                                        <div class="font-extrabold text-gray-900 dark:text-white text-xs flex items-center gap-1.5 flex-wrap">
                                                            <span>{{ $ach->nama_prestasi }}</span>
                                                            @if($ach->lingkup === 'kelas')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">Prestasi Kelas</span>
                                                            @endif
                                                            @if($ach->jenis_reward === 'reward_religi')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">🎁 Reward Religi</span>
                                                            @elseif($ach->jenis_reward === 'reward_akademik')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">🏆 Reward Akademik</span>
                                                            @endif
                                                        </div>
                                                        @if($ach->reward_detail)
                                                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">Reward: {{ $ach->reward_detail }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        @php
                                                            $badgeClass = match($ach->kategori) {
                                                                'akademik' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                                                'prestasi_kelas', 'religius' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                                default => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                                            };
                                                            $labelKat = match($ach->kategori) {
                                                                'akademik' => 'Akademik',
                                                                'prestasi_kelas' => 'Kelas (' . ($ach->sub_kategori_label ?? 'Umum') . ')',
                                                                'religius' => 'Religius',
                                                                default => 'Non-Akademik',
                                                            };
                                                        @endphp
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $badgeClass }}">
                                                            {{ $labelKat }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                                                        {{ $ach->tingkat }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-mono text-gray-500">
                                                        {{ \Carbon\Carbon::parse($ach->tanggal)->format('d M Y') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                        {{ $ach->keterangan ?: '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        @if($ach->sertifikat)
                                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($ach->sertifikat) }}" target="_blank" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200">
                                                                <span>📄</span>
                                                                <span>Lihat Piagam</span>
                                                            </a>
                                                        @else
                                                            <span class="text-gray-400 text-[10px] italic">Tidak ada file</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-center text-gray-500 font-medium">
                                                        {{ $ach->inputBy?->name ?? 'Waka Kesiswaan' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="py-10 text-center text-gray-400 text-xs">
                                    <div class="text-3xl mb-1">🏅</div>
                                    Belum ada catatan prestasi atau kejuaraan yang diinputkan oleh Waka Kesiswaan untuk siswa ini.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- LEMBAR CETAK PRINT-FRIENDLY (KHUSUS FORMAT PRINT / PDF) -->
            <div class="hidden print:block space-y-6 text-black p-4">
                <div class="text-center border-b-2 border-black pb-4 mb-4">
                    <h2 class="text-lg font-black uppercase tracking-wider">YAYASAN BAITUL MAKMUR</h2>
                    <h3 class="text-base font-bold uppercase">SMP ISLAM BAITUL MAKMUR</h3>
                    <p class="text-xs">LEMBAR PORTOFOLIO & KETERCAPAIAN PROFIL LULUSAN SISWA</p>
                    <p class="text-[10px]">Tahun Ajaran {{ $detail->academic_year?->name ?? '2026/2027' }} • Semester {{ $detail->semester }}</p>
                </div>

                <table class="w-full text-xs mb-4">
                    <tr>
                        <td class="w-28 font-bold">Nama Siswa</td>
                        <td class="w-4">:</td>
                        <td>{{ $detail->student->name }}</td>
                        <td class="w-28 font-bold">Kelas</td>
                        <td class="w-4">:</td>
                        <td>{{ $detail->student->classroom->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">NIS / NISN</td>
                        <td>:</td>
                        <td>{{ $detail->student->nis }} / {{ $detail->student->nisn ?? '-' }}</td>
                        <td class="font-bold">Wali Kelas</td>
                        <td>:</td>
                        <td>{{ $detail->student->classroom->waliKelas->name ?? '-' }}</td>
                    </tr>
                </table>

                <div class="border border-black p-3 rounded mb-4 text-center">
                    <div class="text-xs font-bold uppercase">STATUS KETERCAPAIAN PROFIL LULUSAN:</div>
                    <div class="text-base font-black">{{ $detail->status_label }}</div>
                    <div class="text-xs font-bold">Skor Komposit: {{ $detail->composite_score }} / 100 • Predikat: {{ $detail->predikat_akhir }}</div>
                </div>

                <!-- 1. NILAI AKADEMIK -->
                <div class="mb-4">
                    <div class="font-bold text-xs uppercase mb-1">1. Nilai Akademik per Mata Pelajaran (Akumulasi Harian Guru Mapel)</div>
                    <table class="w-full text-[10px] border-collapse border border-black text-left">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-black px-2 py-1 text-center w-8">No</th>
                                <th class="border border-black px-2 py-1">Mata Pelajaran</th>
                                <th class="border border-black px-2 py-1">Guru Pengampu</th>
                                <th class="border border-black px-2 py-1 text-center">Rata-rata Harian</th>
                                <th class="border border-black px-2 py-1 text-center">Ujian</th>
                                <th class="border border-black px-2 py-1 text-center">Nilai Akhir</th>
                                <th class="border border-black px-2 py-1 text-center">Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detail->subjects as $i => $s)
                                <tr>
                                    <td class="border border-black px-2 py-1 text-center">{{ $i + 1 }}</td>
                                    <td class="border border-black px-2 py-1 font-semibold">{{ $s['name'] }}</td>
                                    <td class="border border-black px-2 py-1">{{ $s['teacher_name'] }}</td>
                                    <td class="border border-black px-2 py-1 text-center">{{ $s['avg_harian'] ?? '-' }}</td>
                                    <td class="border border-black px-2 py-1 text-center">{{ $s['score_ujian'] ?? '-' }}</td>
                                    <td class="border border-black px-2 py-1 text-center font-bold">{{ $s['final_score'] ?? '-' }}</td>
                                    <td class="border border-black px-2 py-1 text-center">{{ $s['predikat'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 2. TAHFIDZ & TAHSIN -->
                <div class="mb-4">
                    <div class="font-bold text-xs uppercase mb-1">2. Nilai & Capaian Tahfidz / Tahsin Al-Qur'an</div>
                    <table class="w-full text-[10px] border-collapse border border-black">
                        <tr>
                            <td class="border border-black p-2 w-1/3">
                                <strong>Total Setoran Ziyadah:</strong> {{ $detail->tahfidz->total_ziyadah }} kali<br>
                                <strong>Total Setoran Muraja'ah:</strong> {{ $detail->tahfidz->total_murajaah }} kali
                            </td>
                            <td class="border border-black p-2 w-1/3">
                                <strong>Capaian Hafalan Terakhir:</strong><br>
                                Juz {{ $detail->tahfidz->last_juz }} ({{ $detail->tahfidz->last_surah }} Ayat {{ $detail->tahfidz->last_ayah }})
                            </td>
                            <td class="border border-black p-2 w-1/3">
                                <strong>Mutu Bacaan (Tahsin):</strong> {{ $detail->tahfidz->overall_quality }}<br>
                                <strong>Predikat Mutu:</strong> {{ $detail->tahfidz->mutu_predikat }}
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- 3. PRESTASI WAKA KESISWAAN -->
                <div class="mb-6">
                    <div class="font-bold text-xs uppercase mb-1">3. Rekam Jejak Prestasi Lomba (Verifikasi Waka Kesiswaan)</div>
                    <table class="w-full text-[10px] border-collapse border border-black text-left">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-black px-2 py-1 text-center w-8">No</th>
                                <th class="border border-black px-2 py-1">Nama Prestasi / Kejuaraan</th>
                                <th class="border border-black px-2 py-1 text-center">Kategori</th>
                                <th class="border border-black px-2 py-1 text-center">Tingkat</th>
                                <th class="border border-black px-2 py-1 text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detail->prestasi->list as $idx => $p)
                                <tr>
                                    <td class="border border-black px-2 py-1 text-center">{{ $idx + 1 }}</td>
                                    <td class="border border-black px-2 py-1 font-bold">{{ $p->nama_prestasi }}</td>
                                    <td class="border border-black px-2 py-1 text-center font-bold">
                                        {{ $p->kategori_label }}{{ $p->sub_kategori ? ' (' . ($p->sub_kategori_label ?? $p->sub_kategori) . ')' : '' }}
                                        @if($p->jenis_reward && $p->jenis_reward !== 'tanpa_reward')
                                            <div class="text-[9px] text-emerald-700 font-normal">[{{ $p->reward_label }}]</div>
                                        @endif
                                    </td>
                                    <td class="border border-black px-2 py-1 text-center">{{ $p->tingkat }}</td>
                                    <td class="border border-black px-2 py-1 text-center">{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border border-black px-2 py-2 text-center italic">Tidak ada catatan prestasi lomba.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- TANDA TANGAN KEPALA SEKOLAH -->
                <div class="flex justify-between items-end pt-8 text-xs">
                    <div class="text-center">
                        <p>Mengetahui,</p>
                        <p class="font-semibold">Waka Kesiswaan</p>
                        <div class="h-16"></div>
                        <p class="font-bold underline">Lya Ratnawati, M.Pd</p>
                    </div>

                    <div class="text-center">
                        <p>Surabaya, {{ now()->translatedFormat('d F Y') }}</p>
                        <p class="font-semibold">Kepala Sekolah SMP Islam Baitul Makmur</p>
                        <div class="h-16"></div>
                        <p class="font-bold underline">Drs. Syamsul Arifin, M.Ed</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
