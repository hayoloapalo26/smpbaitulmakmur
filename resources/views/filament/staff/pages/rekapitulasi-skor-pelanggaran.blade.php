<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. RINGKASAN METRIK KEDISIPLINAN --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Kasus Pelanggaran -->
            <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-2xs dark:bg-gray-800 dark:border-gray-700 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Total Pelanggaran</p>
                        <p class="mt-1 text-2xl font-black {{ $stats['totalKasus'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $stats['totalKasus'] }} <span class="text-xs font-normal text-gray-500">Kasus</span>
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl {{ $stats['totalKasus'] > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                    Periode: <strong>{{ ucfirst($activeTab) }}</strong>
                </div>
            </div>

            <!-- Akumulasi Poin -->
            <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-2xs dark:bg-gray-800 dark:border-gray-700 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-amber-600 uppercase dark:text-amber-400">Akumulasi Poin</p>
                        <p class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">
                            +{{ $stats['totalPoin'] }} <span class="text-xs font-normal text-gray-500">Poin</span>
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                    Total poin pelanggaran
                </div>
            </div>

            <!-- Siswa Melanggar -->
            <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-2xs dark:bg-gray-800 dark:border-gray-700 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-purple-600 uppercase dark:text-purple-400">Siswa Melanggar</p>
                        <p class="mt-1 text-2xl font-black text-purple-600 dark:text-purple-400">
                            {{ $stats['siswaMelanggar'] }} <span class="text-xs font-normal text-gray-500">dari {{ $stats['totalSiswaKelas'] }} siswa</span>
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                        <x-filament::icon icon="heroicon-o-user-group" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                    Siswa perlu pembinaan adab
                </div>
            </div>

            <!-- Status Validasi Dokumen -->
            <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-2xs dark:bg-gray-800 dark:border-gray-700 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider text-emerald-600 uppercase dark:text-emerald-400">Validasi & ACC</p>
                        <p class="mt-1 text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-1.5">
                            @if($activeTab === 'harian' && $approval?->acc_piket)
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Tervalidasi</span>
                            @elseif($activeTab === 'mingguan' && $approval?->acc_wali_kelas)
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Tervalidasi</span>
                            @elseif($activeTab === 'bulanan' && $approval?->acc_wali_kelas && $approval?->acc_bk)
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Lengkap</span>
                            @elseif($activeTab === 'triwulan' && $approval?->acc_wali_kelas && $approval?->acc_bk && $approval?->acc_waka_kesiswaan)
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Lengkap</span>
                            @elseif($activeTab === 'semester' && $approval?->acc_wali_kelas && $approval?->acc_bk && $approval?->acc_waka_kesiswaan && $approval?->acc_kepala_sekolah)
                                <span class="text-emerald-600 dark:text-emerald-400">✓ Disahkan</span>
                            @else
                                <span class="text-amber-600 dark:text-amber-400">⏳ Belum Lengkap</span>
                            @endif
                        </p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-o-check-badge" class="w-5 h-5" />
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                    Kode: {{ $currentPeriodeKey }}
                </div>
            </div>
        </div>

        {{-- 2. TAB NAVIGASI 5 PERIODE (HORIZONTAL SCROLL RAPI & KONSISTEN) --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-2 overflow-x-auto pb-2 scrollbar-thin" aria-label="Tabs">
                <button wire:click="setTab('harian')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'harian' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>📋</span>
                    <span>1. Jurnal Harian (Piket)</span>
                </button>
                <button wire:click="setTab('mingguan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'mingguan' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>📅</span>
                    <span>2. Rekap Mingguan</span>
                </button>
                <button wire:click="setTab('bulanan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'bulanan' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>📆</span>
                    <span>3. Rekap Bulanan</span>
                </button>
                <button wire:click="setTab('triwulan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'triwulan' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>📊</span>
                    <span>4. Rekap Triwulan (3 Bulan)</span>
                </button>
                <button wire:click="setTab('semester')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'semester' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}">
                    <span>🎓</span>
                    <span>5. Rekap 1 Semester</span>
                </button>
            </nav>
        </div>

        {{-- 3. BAR FILTER INTERAKTIF & TOMBOL CATAT --}}
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl shadow-2xs border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Pilihan Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Rombel / Kelas</label>
                    @if($isRestrictedWaliKelas && $availableClasses->count() === 1)
                        <span class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-xs rounded-xl border border-gray-200 dark:border-gray-600">
                            👨‍🏫 {{ $availableClasses->first()->name }}
                        </span>
                    @else
                        <select wire:model.live="selectedClassroomId"
                                class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-emerald-500 focus:ring-emerald-500 min-w-[170px] py-2">
                            @if(!$isRestrictedWaliKelas)
                                <option value="">Semua Kelas</option>
                            @endif
                            @foreach($availableClasses as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Filter Khusus Tab HARIAN -->
                @if($activeTab === 'harian')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Kejadian</label>
                        <input type="date" wire:model.live="selectedTanggal"
                               class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-emerald-500 focus:ring-emerald-500 py-2">
                    </div>
                @endif

                <!-- Filter Khusus Tab MINGGUAN -->
                @if($activeTab === 'mingguan')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Bulan</label>
                        <select wire:model.live="selectedBulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Minggu Ke</label>
                        <select wire:model.live="selectedMinggu" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            <option value="1">Minggu 1 (Tgl 1 - 7)</option>
                            <option value="2">Minggu 2 (Tgl 8 - 14)</option>
                            <option value="3">Minggu 3 (Tgl 15 - 21)</option>
                            <option value="4">Minggu 4 (Tgl 22 - 28)</option>
                            <option value="5">Minggu 5 (Tgl 29 - Akhir)</option>
                        </select>
                    </div>
                @endif

                <!-- Filter Khusus Tab BULANAN -->
                @if($activeTab === 'bulanan')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Bulan</label>
                        <select wire:model.live="selectedBulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Filter Khusus Tab TRIWULAN -->
                @if($activeTab === 'triwulan')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Periode Triwulan</label>
                        <select wire:model.live="selectedTriwulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold min-w-[210px] py-2">
                            <option value="1">Triwulan 1 (Juli - September)</option>
                            <option value="2">Triwulan 2 (Oktober - Desember)</option>
                            <option value="3">Triwulan 3 (Januari - Maret)</option>
                            <option value="4">Triwulan 4 (April - Juni)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Filter Khusus Tab SEMESTER -->
                @if($activeTab === 'semester')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun Ajaran</label>
                        <select wire:model.live="selectedTahunAjaran" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold py-2">
                            <option value="2025/2026">2025/2026</option>
                            <option value="2026/2027">2026/2027</option>
                            <option value="2027/2028">2027/2028</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Semester</label>
                        <select wire:model.live="selectedSemester" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold min-w-[190px] py-2">
                            <option value="ganjil">Semester Ganjil (Juli - Des)</option>
                            <option value="genap">Semester Genap (Jan - Jun)</option>
                        </select>
                    </div>
                @endif
            </div>

            <!-- Tombol Tambah Catatan Guru Piket (Khusus Tab Harian) -->
            @if($activeTab === 'harian')
                <div class="pt-1">
                    <button wire:click="openCreateViolationModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow-sm active:scale-98 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Catat Pelanggaran Siswa</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- 4. KARTU PENGESAHAN & STATUS ACC BERJENJANG --}}
        <div class="p-4 bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-gray-800 dark:to-gray-800/80 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-3 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200/60 dark:border-gray-700 pb-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm">✍️</span>
                    <span class="text-xs font-extrabold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                        Lembar Validasi & ACC Resmi Dokumen
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $currentPeriodeKey }}
                    </span>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    SOP Sekolah: klik tombol untuk menyetujui (ACC) atau membatalkan persetujuan.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. GURU PIKET (Untuk Tab Harian) -->
                @if($activeTab === 'harian')
                    <div class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-3 {{ $approval?->acc_piket ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-white border-gray-200 dark:bg-gray-800/60 dark:border-gray-700' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Guru Piket</div>
                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                {{ $approval?->piketUser?->name ?? 'Belum Ditandatangani' }}
                            </div>
                            <div class="text-[10px] {{ $approval?->acc_piket ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600' }}">
                                {{ $approval?->acc_piket ? '✓ Di-ACC: ' . $approval->acc_piket_at?->translatedFormat('d M, H:i') : '⏳ Menunggu ACC Petugas' }}
                            </div>
                        </div>
                        <button wire:click="toggleAcc('piket')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ $approval?->acc_piket ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $approval?->acc_piket ? 'Batal ACC' : 'ACC Sekarang' }}
                        </button>
                    </div>
                @endif

                <!-- 2. WALI KELAS (Untuk Tab Mingguan, Bulanan, Triwulan, Semester) -->
                @if(in_array($activeTab, ['mingguan', 'bulanan', 'triwulan', 'semester']))
                    <div class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-3 {{ $approval?->acc_wali_kelas ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-white border-gray-200 dark:bg-gray-800/60 dark:border-gray-700' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Wali Kelas</div>
                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                {{ $approval?->waliKelasUser?->name ?? ($selectedClass?->waliKelas?->name ?? 'Wali Kelas') }}
                            </div>
                            <div class="text-[10px] {{ $approval?->acc_wali_kelas ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600' }}">
                                {{ $approval?->acc_wali_kelas ? '✓ Di-ACC: ' . $approval->acc_wali_kelas_at?->translatedFormat('d M, H:i') : '⏳ Menunggu ACC' }}
                            </div>
                        </div>
                        <button wire:click="toggleAcc('wali_kelas')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ $approval?->acc_wali_kelas ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $approval?->acc_wali_kelas ? 'Batal ACC' : 'ACC' }}
                        </button>
                    </div>
                @endif

                <!-- 3. GURU BK (Untuk Tab Bulanan, Triwulan, Semester) -->
                @if(in_array($activeTab, ['bulanan', 'triwulan', 'semester']))
                    <div class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-3 {{ $approval?->acc_bk ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-white border-gray-200 dark:bg-gray-800/60 dark:border-gray-700' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Guru BK</div>
                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                {{ $approval?->bkUser?->name ?? 'Guru BK' }}
                            </div>
                            <div class="text-[10px] {{ $approval?->acc_bk ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600' }}">
                                {{ $approval?->acc_bk ? '✓ Di-ACC: ' . $approval->acc_bk_at?->translatedFormat('d M, H:i') : '⏳ Menunggu ACC' }}
                            </div>
                        </div>
                        <button wire:click="toggleAcc('bk')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ $approval?->acc_bk ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $approval?->acc_bk ? 'Batal ACC' : 'ACC' }}
                        </button>
                    </div>
                @endif

                <!-- 4. WAKA KESISWAAN (Untuk Tab Triwulan, Semester) -->
                @if(in_array($activeTab, ['triwulan', 'semester']))
                    <div class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-3 {{ $approval?->acc_waka_kesiswaan ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-white border-gray-200 dark:bg-gray-800/60 dark:border-gray-700' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Waka Kesiswaan</div>
                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                {{ $approval?->kesiswaanUser?->name ?? 'Waka Kesiswaan' }}
                            </div>
                            <div class="text-[10px] {{ $approval?->acc_waka_kesiswaan ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600' }}">
                                {{ $approval?->acc_waka_kesiswaan ? '✓ Di-ACC: ' . $approval->acc_waka_kesiswaan_at?->translatedFormat('d M') : '⏳ Menunggu ACC' }}
                            </div>
                        </div>
                        <button wire:click="toggleAcc('waka_kesiswaan')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ $approval?->acc_waka_kesiswaan ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $approval?->acc_waka_kesiswaan ? 'Batal ACC' : 'ACC' }}
                        </button>
                    </div>
                @endif

                <!-- 5. KEPALA SEKOLAH (Khusus Tab Semester) -->
                @if($activeTab === 'semester')
                    <div class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-3 {{ $approval?->acc_kepala_sekolah ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-white border-gray-200 dark:bg-gray-800/60 dark:border-gray-700' }}">
                        <div class="space-y-1 min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kepala Sekolah</div>
                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                {{ $approval?->kepsekUser?->name ?? 'Kepala Sekolah' }}
                            </div>
                            <div class="text-[10px] {{ $approval?->acc_kepala_sekolah ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600' }}">
                                {{ $approval?->acc_kepala_sekolah ? '✓ Di-ACC: ' . $approval->acc_kepala_sekolah_at?->translatedFormat('d M') : '⏳ Menunggu ACC' }}
                            </div>
                        </div>
                        <button wire:click="toggleAcc('kepala_sekolah')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ $approval?->acc_kepala_sekolah ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $approval?->acc_kepala_sekolah ? 'Batal ACC' : 'ACC' }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- 5. TABEL DATA RESMI DENGAN LEBAR KOLOM PRESISI & INDEPENDEN --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xs border border-gray-200 dark:border-gray-700 overflow-hidden">
            
            <!-- 1. TABEL HARIAN (GURU PIKET) -->
            @if($activeTab === 'harian')
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="w-14 py-3.5 px-4 text-center whitespace-nowrap">No</th>
                                <th class="w-24 py-3.5 px-4 text-center whitespace-nowrap">Waktu</th>
                                <th class="min-w-[200px] py-3.5 px-6">Nama Murid</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Kelas</th>
                                <th class="min-w-[280px] py-3.5 px-6">Bentuk / Deskripsi Pelanggaran</th>
                                <th class="w-24 py-3.5 px-4 text-center whitespace-nowrap">Poin</th>
                                <th class="min-w-[180px] py-3.5 px-5">Nama Guru Pencatat</th>
                                <th class="min-w-[240px] py-3.5 px-6">Tindak Lanjut Langsung</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700">{{ $row->jam }}</span>
                                    </td>
                                    <td class="py-3.5 px-6">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $row->student->name ?? '-' }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">NIS: {{ $row->student->nis ?? '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-300 font-bold text-xs">
                                            {{ $row->classroom->name ?? ($row->student->classroom->name ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                                        {{ $row->bentuk_pelanggaran }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 font-black text-xs">
                                            +{{ $row->poin }} Poin
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-gray-700 dark:text-gray-300 font-medium">{{ $row->teacher->name ?? '-' }}</td>
                                    <td class="py-3.5 px-6 text-gray-600 dark:text-gray-300 leading-relaxed">{{ $row->tindak_lanjut_langsung ?: '-' }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button wire:click="openCreateViolationModal({{ $row->id }})" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs" title="Edit">
                                                Edit
                                            </button>
                                            <button wire:confirm="Yakin ingin menghapus catatan pelanggaran ini?" wire:click="deleteViolation({{ $row->id }})" class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs" title="Hapus">
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-14 text-center">
                                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 mb-3 text-2xl font-bold">
                                            ✓
                                        </div>
                                        <p class="font-bold text-gray-800 dark:text-gray-200 text-base">Tidak Ada Catatan Pelanggaran Siswa</p>
                                        <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">Seluruh siswa tertib dan tidak ada pelanggaran yang dicatat pada tanggal {{ \Carbon\Carbon::parse($selectedTanggal)->translatedFormat('d F Y') }}.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 2. TABEL MINGGUAN -->
            @elseif($activeTab === 'mingguan')
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="w-14 py-3.5 px-4 text-center whitespace-nowrap">No</th>
                                <th class="min-w-[200px] py-3.5 px-6">Nama Murid</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Kelas</th>
                                <th class="min-w-[260px] py-3.5 px-6">Jenis Pelanggaran yang Dilakukan</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Akumulasi Poin</th>
                                <th class="min-w-[240px] py-3.5 px-6">Catatan Perkembangan (Wali Kelas)</th>
                                <th class="min-w-[240px] py-3.5 px-6">Tindak Lanjut Minggu Ini (Guru Piket)</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-6">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $row['student']->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="py-3.5 px-6">
                                        @if(!empty($row['jenis_pelanggaran']) && $row['jenis_pelanggaran'] !== '-')
                                            <span class="text-rose-700 dark:text-rose-400 font-medium leading-relaxed">{{ $row['jenis_pelanggaran'] }}</span>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400 italic text-xs">Nihil (Tertib)</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        @if($row['total_poin'] > 0)
                                            <span class="inline-flex px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-black text-xs">+{{ $row['total_poin'] }} Poin</span>
                                        @else
                                            <span class="inline-flex px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs">0 Poin</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-700 dark:text-gray-300 leading-relaxed">{{ $row['catatan_wali_kelas'] }}</td>
                                    <td class="py-3.5 px-6 text-gray-600 dark:text-gray-300 leading-relaxed">{{ $row['tindak_lanjut_piket'] }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'mingguan', '{{ $currentPeriodeKey }}')"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl font-bold border border-amber-200 transition-all text-xs">
                                            ✏️ Catat Wali
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center text-gray-400">Tidak ada data siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 3. TABEL BULANAN -->
            @elseif($activeTab === 'bulanan')
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="w-14 py-3.5 px-4 text-center whitespace-nowrap">No</th>
                                <th class="min-w-[200px] py-3.5 px-6">Nama Murid</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Kelas</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Poin Bulan Lalu</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Poin Bulan Ini</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Total Akumulasi</th>
                                <th class="min-w-[220px] py-3.5 px-6">Kategori / Status Sanksi (BK)</th>
                                <th class="min-w-[240px] py-3.5 px-6">Tindak Lanjut / Agenda Pembinaan (BK)</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-6">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $row['student']->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="py-3.5 px-4 text-center font-bold text-gray-600 whitespace-nowrap">{{ $row['poin_bulan_lalu'] }}</td>
                                    <td class="py-3.5 px-4 text-center font-bold whitespace-nowrap {{ $row['poin_bulan_ini'] > 0 ? 'text-rose-600' : 'text-gray-600' }}">{{ $row['poin_bulan_ini'] }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 rounded-full font-black text-xs {{ $row['total_akumulasi'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                            {{ $row['total_akumulasi'] }} Poin
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 font-medium">{{ $row['status_sanksi_bk'] }}</td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 leading-relaxed">{{ $row['tindak_lanjut_bk'] }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'bulanan', '{{ $currentPeriodeKey }}')"
                                                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded-xl font-bold border border-indigo-200 transition-all text-xs">
                                            🛡️ Input BK
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-gray-400">Tidak ada data siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 4. TABEL TRIWULAN (3 BULAN) -->
            @elseif($activeTab === 'triwulan')
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="w-14 py-3.5 px-4 text-center whitespace-nowrap">No</th>
                                <th class="min-w-[200px] py-3.5 px-6">Nama Siswa</th>
                                <th class="w-24 py-3.5 px-4 text-center whitespace-nowrap">Kelas</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Poin {{ $bulanHeaders[0] ?? 'Bln 1' }}</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Poin {{ $bulanHeaders[1] ?? 'Bln 2' }}</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Poin {{ $bulanHeaders[2] ?? 'Bln 3' }}</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Total 3 Bulan</th>
                                <th class="min-w-[220px] py-3.5 px-6">Status Peringatan / Sanksi (Kesiswaan)</th>
                                <th class="min-w-[240px] py-3.5 px-6">Tindak Lanjut & Pembinaan (Kesiswaan)</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-6">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $row['student']->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="py-3.5 px-4 text-center font-semibold whitespace-nowrap">{{ $row['poin_bulan_1'] }}</td>
                                    <td class="py-3.5 px-4 text-center font-semibold whitespace-nowrap">{{ $row['poin_bulan_2'] }}</td>
                                    <td class="py-3.5 px-4 text-center font-semibold whitespace-nowrap">{{ $row['poin_bulan_3'] }}</td>
                                    <td class="py-3.5 px-4 text-center font-bold whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-black {{ $row['total_3_bulan'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                            {{ $row['total_3_bulan'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 font-medium">{{ $row['status_sanksi_kesiswaan'] }}</td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 leading-relaxed">{{ $row['tindak_lanjut_kesiswaan'] }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'triwulan', '{{ $currentPeriodeKey }}')"
                                                class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-800 rounded-xl font-bold border border-purple-200 transition-all text-xs">
                                            ⚡ Kesiswaan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-12 text-center text-gray-400">Tidak ada data siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 5. TABEL SEMESTER (6 BULAN) -->
            @elseif($activeTab === 'semester')
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="w-14 py-3.5 px-4 text-center whitespace-nowrap">No</th>
                                <th class="min-w-[200px] py-3.5 px-6">Nama Siswa</th>
                                <th class="w-24 py-3.5 px-3 text-center whitespace-nowrap">Kelas</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[0] ?? 'B1' }}</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[1] ?? 'B2' }}</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[2] ?? 'B3' }}</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[3] ?? 'B4' }}</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[4] ?? 'B5' }}</th>
                                <th class="w-20 py-3.5 px-2 text-center whitespace-nowrap">{{ $bulanHeaders[5] ?? 'B6' }}</th>
                                <th class="w-24 py-3.5 px-3 text-center whitespace-nowrap">Total Poin</th>
                                <th class="w-32 py-3.5 px-4 text-center whitespace-nowrap">Predikat Sikap</th>
                                <th class="min-w-[280px] py-3.5 px-6">Catatan Kepribadian Rapor (Wali Kelas)</th>
                                <th class="w-28 py-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-6">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $row['student']->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m1'] }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m2'] }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m3'] }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m4'] }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m5'] }}</td>
                                    <td class="py-3.5 px-2 text-center text-gray-600 whitespace-nowrap">{{ $row['poin_m6'] }}</td>
                                    <td class="py-3.5 px-3 text-center font-extrabold whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs {{ $row['total_semester'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                            {{ $row['total_semester'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        @php $pred = $row['predikat']; @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-black shadow-2xs {{ $pred['predikat'] === 'A' || $pred['predikat'] === 'A-' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : ($pred['predikat'] === 'B+' || $pred['predikat'] === 'B' || $pred['predikat'] === 'B-' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : ($pred['predikat'] === 'C+' || $pred['predikat'] === 'C' || $pred['predikat'] === 'C-' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300')) }}">
                                            {{ $pred['predikat'] }} ({{ $pred['label'] }})
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-gray-800 dark:text-gray-200 leading-relaxed">{{ $row['catatan_wali_kelas'] }}</td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'semester', '{{ $currentPeriodeKey }}')"
                                                class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl font-bold border border-emerald-200 transition-all text-xs">
                                            📝 Rapor
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="p-12 text-center text-gray-400">Tidak ada data siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Banner Rubrik Indikator Nilai Sikap -->
                <div class="p-5 bg-slate-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 text-xs">
                    <div class="font-bold text-gray-800 dark:text-gray-200 mb-2 flex items-center gap-1.5">
                        <span>📌</span>
                        <span class="text-sm">Indikator Penentuan Predikat Nilai Sikap Berdasarkan Poin Pelanggaran:</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-[11px]">
                        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 rounded-xl text-emerald-800 dark:text-emerald-300">
                            <strong>A</strong> (0 - 5 Poin)<br><span class="text-[10px] text-emerald-600">Sangat Disiplin</span>
                        </div>
                        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 rounded-xl text-emerald-800 dark:text-emerald-300">
                            <strong>A-</strong> (6 - 15 Poin)<br><span class="text-[10px] text-emerald-600">Baik Sekali</span>
                        </div>
                        <div class="p-2.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 rounded-xl text-blue-800 dark:text-blue-300">
                            <strong>B+</strong> (16 - 30 Poin)<br><span class="text-[10px] text-blue-600">Baik Plus</span>
                        </div>
                        <div class="p-2.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 rounded-xl text-blue-800 dark:text-blue-300">
                            <strong>B</strong> (31 - 45 Poin)<br><span class="text-[10px] text-blue-600">Baik</span>
                        </div>
                        <div class="p-2.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 rounded-xl text-blue-800 dark:text-blue-300">
                            <strong>B-</strong> (46 - 60 Poin)<br><span class="text-[10px] text-blue-600">Cukup Baik</span>
                        </div>
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 rounded-xl text-amber-800 dark:text-amber-300">
                            <strong>C+</strong> (61 - 85 Poin)<br><span class="text-[10px] text-amber-600">Cukup Plus</span>
                        </div>
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 rounded-xl text-amber-800 dark:text-amber-300">
                            <strong>C</strong> (86 - 105 Poin)<br><span class="text-[10px] text-amber-600">Cukup</span>
                        </div>
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 rounded-xl text-amber-800 dark:text-amber-300">
                            <strong>C-</strong> (106 - 120 Poin)<br><span class="text-[10px] text-amber-600">Kurang Cukup</span>
                        </div>
                        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 rounded-xl text-rose-800 dark:text-rose-300">
                            <strong>D</strong> (121 - 140 Poin)<br><span class="text-[10px] text-rose-600">Perlu Perhatian</span>
                        </div>
                        <div class="p-2.5 bg-rose-100 dark:bg-rose-950/50 border border-rose-300 rounded-xl text-rose-900 dark:text-rose-200 font-bold">
                            <strong>E</strong> (141 - 150+ Poin)<br><span class="text-[10px] text-rose-700">Pembinaan Khusus</span>
                        </div>
                    </div>
                </div>
            @endif

        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- MODAL 1: INPUT/EDIT JURNAL PELANGGARAN HARIAN (GURU PIKET) --}}
    {{-- ========================================================= --}}
    @if($showViolationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100 dark:border-gray-700">
                <!-- Modal Header -->
                <div class="p-5 bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-gray-800 dark:to-gray-800/90 border-b border-gray-200/80 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 flex items-center justify-center text-lg font-bold shadow-2xs">
                            📋
                        </span>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">
                                {{ $editingViolationId ? 'Perbarui' : 'Pencatatan' }} Pelanggaran Siswa (Guru Piket)
                            </h3>
                            <p class="text-xs text-gray-500">Mencatat pelanggaran harian siswa untuk pembinaan disiplin & nilai sikap</p>
                        </div>
                    </div>
                    <button wire:click="closeViolationModal" class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-xs sm:text-sm">
                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Nama Murid *</label>
                        <select wire:model="formStudentId" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            <option value="">-- Pilih Nama Murid --</option>
                            @foreach($studentOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formStudentId') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Tanggal Kejadian *</label>
                            <input type="date" wire:model="formTanggal" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                            @error('formTanggal') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Waktu / Jam Kejadian *</label>
                            <input type="text" wire:model="formJam" placeholder="Contoh: 07:30" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 font-mono py-2.5">
                            @error('formJam') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Bentuk / Deskripsi Pelanggaran *</label>
                        <textarea wire:model="formBentukPelanggaran" rows="3" placeholder="Jelaskan secara spesifik pelanggaran yang dilakukan (Contoh: Datang terlambat lebih dari 15 menit tanpa surat izin dari gerbang)" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                        @error('formBentukPelanggaran') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-bold text-gray-700 dark:text-gray-300 block">Poin Pelanggaran *</label>
                            <span class="text-xs text-gray-400">Pilih Preset Cepat:</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mb-2.5">
                            <button type="button" wire:click="setPresetPoin(5)" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $formPoin == 5 ? 'bg-amber-100 text-amber-900 border-amber-300 shadow-2xs' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">+5 Poin (Ringan)</button>
                            <button type="button" wire:click="setPresetPoin(10)" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $formPoin == 10 ? 'bg-amber-100 text-amber-900 border-amber-300 shadow-2xs' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">+10 Poin (Sedang)</button>
                            <button type="button" wire:click="setPresetPoin(15)" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $formPoin == 15 ? 'bg-amber-100 text-amber-900 border-amber-300 shadow-2xs' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">+15 Poin (Bermasalah)</button>
                            <button type="button" wire:click="setPresetPoin(25)" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $formPoin == 25 ? 'bg-rose-100 text-rose-900 border-rose-300 shadow-2xs' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">+25 Poin (Berat)</button>
                        </div>
                        <input type="number" wire:model="formPoin" min="1" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm font-bold font-mono focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                        @error('formPoin') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Nama Guru Pencatat</label>
                        <input type="text" value="{{ $currentUser?->name }}" disabled class="w-full rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 text-xs sm:text-sm py-2.5">
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Tindak Lanjut Langsung</label>
                        <textarea wire:model="formTindakLanjut" rows="2" placeholder="Tindakan yang langsung diberikan (Contoh: Diberi teguran lisan, membaca istighfar 33x, dan dicatat di buku piket)" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-5 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-200/80 dark:border-gray-700 flex items-center justify-end gap-3">
                    <button wire:click="closeViolationModal" type="button" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-gray-600 hover:bg-gray-200/70 border border-gray-300 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-all">
                        Batal
                    </button>
                    <button wire:click="saveViolation" type="button" class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm transition-all">
                        Simpan Catatan
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================================================================== --}}
    {{-- MODAL 2: INPUT CATATAN EVALUASI & TINDAK LANJUT STAKEHOLDER PER SISWA --}}
    {{-- ===================================================================== --}}
    @if($showRecordModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100 dark:border-gray-700">
                <!-- Modal Header -->
                <div class="p-5 bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-gray-800 dark:to-gray-800/90 border-b border-gray-200/80 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📝</span>
                            <span>Form Evaluasi & Tindak Lanjut Siswa</span>
                        </h3>
                        <p class="text-xs text-amber-600 font-bold mt-0.5">{{ $recordStudentName }} <span class="text-gray-400 font-normal">({{ $recordKey }})</span></p>
                    </div>
                    <button wire:click="closeRecordModal" class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-xs sm:text-sm">
                    @if($recordType === 'mingguan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Catatan Perkembangan (Diisi Wali Kelas)</label>
                            <textarea wire:model="fieldCatatanWaliKelas" rows="4" placeholder="Tuliskan catatan perkembangan kedisiplinan dan sikap murid minggu ini..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                        </div>
                    @elseif($recordType === 'bulanan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Kategori / Status Sanksi (Diisi BK)</label>
                            <input type="text" wire:model="fieldStatusSanksiBk" placeholder="Contoh: Peringatan Lisan / Konseling Pribadi Tahap 1" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Tindak Lanjut / Agenda Pembinaan (Diisi BK)</label>
                            <textarea wire:model="fieldTindakLanjutBk" rows="4" placeholder="Contoh: Dijadwalkan bimbingan konseling pribadi pada hari Selasa..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                        </div>
                    @elseif($recordType === 'triwulan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Status Peringatan / Sanksi (Diisi Waka Kesiswaan)</label>
                            <input type="text" wire:model="fieldStatusSanksiKesiswaan" placeholder="Contoh: SP-1 / Pembinaan Karakter Kesiswaan" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2.5">
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Tindak Lanjut & Proses Pembinaan (Diisi Waka Kesiswaan)</label>
                            <textarea wire:model="fieldTindakLanjutKesiswaan" rows="4" placeholder="Contoh: Koordinasi bersama orang tua dan penugasan bakti sosial sekolah..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                        </div>
                    @elseif($recordType === 'semester')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1.5">Catatan Kepribadian Rapor (Diinput oleh Wali Kelas)</label>
                            <textarea wire:model="fieldCatatanWaliKelas" rows="5" placeholder="Tuliskan narasi kepribadian dan sikap spiritual & sosial untuk dimasukkan ke dalam rapor semester siswa..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 leading-relaxed p-3"></textarea>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-5 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-200/80 dark:border-gray-700 flex items-center justify-end gap-3">
                    <button wire:click="closeRecordModal" type="button" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-gray-600 hover:bg-gray-200/70 border border-gray-300 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-all">
                        Batal
                    </button>
                    <button wire:click="saveRecord" type="button" class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm transition-all">
                        Simpan Evaluasi
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
