<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Banner Header & Tombol Cetak PDF -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="p-2 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 font-bold text-lg">
                        🛡️
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rekapitulasi Skor Poin Pelanggaran Siswa</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Fitur terpadu pencatatan pelanggaran harian oleh Guru Piket, rekapitulasi berkala (Mingguan, Bulanan, Triwulan, 1 Semester), ACC bertingkat, dan cetak PDF resmi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Cetak PDF -->
            <div class="flex items-center gap-2">
                <a href="{{ $pdfUrl }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Unduh PDF Resmi ({{ ucfirst($activeTab) }})
                </a>
            </div>
        </div>

        <!-- 5 Tabs Navigasi Periode Terpadu -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-2 overflow-x-auto pb-1" aria-label="Tabs">
                <button wire:click="setTab('harian')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'harian' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span>📋</span>
                    <span>1. Jurnal Harian (Piket)</span>
                </button>
                <button wire:click="setTab('mingguan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'mingguan' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span>📅</span>
                    <span>2. Rekap Mingguan</span>
                </button>
                <button wire:click="setTab('bulanan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'bulanan' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span>📆</span>
                    <span>3. Rekap Bulanan</span>
                </button>
                <button wire:click="setTab('triwulan')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'triwulan' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span>📊</span>
                    <span>4. Rekap Triwulan (3 Bulan)</span>
                </button>
                <button wire:click="setTab('semester')"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $activeTab === 'semester' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span>🎓</span>
                    <span>5. Rekap 1 Semester</span>
                </button>
            </nav>
        </div>

        <!-- Filter Bar Interaktif Sesuai Tab -->
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- Pilihan Kelas -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kelas:</label>
                    @if($isRestrictedWaliKelas && $availableClasses->count() === 1)
                        <span class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-xs rounded-lg border border-gray-200 dark:border-gray-600">
                            {{ $availableClasses->first()->name }}
                        </span>
                    @else
                        <select wire:model.live="selectedClassroomId"
                                class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-rose-500 focus:ring-rose-500">
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
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal:</label>
                        <input type="date" wire:model.live="selectedTanggal"
                               class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-rose-500 focus:ring-rose-500">
                    </div>
                @endif

                <!-- Filter Khusus Tab MINGGUAN -->
                @if($activeTab === 'mingguan')
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tahun:</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Bulan:</label>
                        <select wire:model.live="selectedBulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Minggu Ke:</label>
                        <select wire:model.live="selectedMinggu" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
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
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Bulan:</label>
                        <select wire:model.live="selectedBulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tahun:</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Filter Khusus Tab TRIWULAN -->
                @if($activeTab === 'triwulan')
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Periode Triwulan:</label>
                        <select wire:model.live="selectedTriwulan" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            <option value="1">Triwulan 1 (Juli - September)</option>
                            <option value="2">Triwulan 2 (Oktober - Desember)</option>
                            <option value="3">Triwulan 3 (Januari - Maret)</option>
                            <option value="4">Triwulan 4 (April - Juni)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tahun:</label>
                        <select wire:model.live="selectedTahun" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            @foreach([2025, 2026, 2027] as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Filter Khusus Tab SEMESTER -->
                @if($activeTab === 'semester')
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tahun Ajaran:</label>
                        <select wire:model.live="selectedTahunAjaran" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            <option value="2025/2026">2025/2026</option>
                            <option value="2026/2027">2026/2027</option>
                            <option value="2027/2028">2027/2028</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Semester:</label>
                        <select wire:model.live="selectedSemester" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold">
                            <option value="ganjil">Semester Ganjil (Juli - Desember)</option>
                            <option value="genap">Semester Genap (Januari - Juni)</option>
                        </select>
                    </div>
                @endif
            </div>

            <!-- Tombol Tambah Catatan Pelanggaran Khusus Guru Piket (Di Tab Harian) -->
            @if($activeTab === 'harian')
                <div>
                    <button wire:click="openCreateViolationModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all">
                        <span>➕</span>
                        <span>Catat Pelanggaran Siswa</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Panel Status & Kontrol ACC Berjenjang -->
        <div class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        ✍️ Status Verifikasi & ACC Dokumen:
                    </span>
                    <span class="text-xs text-gray-500 font-mono">({{ $currentPeriodeKey }})</span>
                </div>
                <div class="text-[11px] text-gray-400">
                    Klik tombol untuk menyetujui (ACC) atau membatalkan persetujuan sesuai kewenangan akun Anda.
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- TAB HARIAN: ACC GURU PIKET -->
                @if($activeTab === 'harian')
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('piket')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_piket ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            <span>{{ $approval?->acc_piket ? '✅' : '⏳' }}</span>
                            <span>ACC Guru Piket</span>
                        </button>
                        @if($approval?->acc_piket)
                            <span class="text-[11px] text-emerald-600 font-medium">
                                Disetujui oleh: <strong>{{ $approval->piketUser?->name ?? 'Guru Piket' }}</strong> ({{ $approval->acc_piket_at?->translatedFormat('d/m H:i') }})
                            </span>
                        @else
                            <span class="text-[11px] text-amber-600">Menunggu ACC Petugas Piket</span>
                        @endif
                    </div>
                @endif

                <!-- TAB MINGGUAN: ACC WALI KELAS -->
                @if($activeTab === 'mingguan')
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('wali_kelas')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_wali_kelas ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            <span>{{ $approval?->acc_wali_kelas ? '✅' : '⏳' }}</span>
                            <span>ACC Wali Kelas</span>
                        </button>
                        @if($approval?->acc_wali_kelas)
                            <span class="text-[11px] text-emerald-600 font-medium">
                                Disetujui: <strong>{{ $approval->waliKelasUser?->name ?? 'Wali Kelas' }}</strong> ({{ $approval->acc_wali_kelas_at?->translatedFormat('d/m H:i') }})
                            </span>
                        @else
                            <span class="text-[11px] text-amber-600">Menunggu ACC Wali Kelas</span>
                        @endif
                    </div>
                @endif

                <!-- TAB BULANAN: ACC BK & WALI KELAS -->
                @if($activeTab === 'bulanan')
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('wali_kelas')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_wali_kelas ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_wali_kelas ? '✅' : '⏳' }}</span>
                            <span>ACC Wali Kelas</span>
                        </button>
                        @if($approval?->acc_wali_kelas)
                            <span class="text-[11px] text-emerald-600">({{ $approval->waliKelasUser?->name ?? 'Wali Kelas' }})</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('bk')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_bk ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_bk ? '✅' : '⏳' }}</span>
                            <span>ACC Guru BK</span>
                        </button>
                        @if($approval?->acc_bk)
                            <span class="text-[11px] text-emerald-600">({{ $approval->bkUser?->name ?? 'Guru BK' }})</span>
                        @endif
                    </div>
                @endif

                <!-- TAB TRIWULAN: ACC BK, WALI KELAS, & WAKA KESISWAAN -->
                @if($activeTab === 'triwulan')
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('wali_kelas')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_wali_kelas ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_wali_kelas ? '✅' : '⏳' }}</span>
                            <span>1. Wali Kelas</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('bk')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_bk ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_bk ? '✅' : '⏳' }}</span>
                            <span>2. Guru BK</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('waka_kesiswaan')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_waka_kesiswaan ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_waka_kesiswaan ? '✅' : '⏳' }}</span>
                            <span>3. Waka Kesiswaan</span>
                        </button>
                    </div>
                @endif

                <!-- TAB SEMESTER: ACC WALI KELAS, BK, WAKA KESISWAAN, & KEPALA SEKOLAH -->
                @if($activeTab === 'semester')
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('wali_kelas')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_wali_kelas ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_wali_kelas ? '✅' : '⏳' }}</span>
                            <span>1. Wali Kelas</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('bk')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_bk ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_bk ? '✅' : '⏳' }}</span>
                            <span>2. Guru BK</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('waka_kesiswaan')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_waka_kesiswaan ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_waka_kesiswaan ? '✅' : '⏳' }}</span>
                            <span>3. Waka Kesiswaan</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="toggleAcc('kepala_sekolah')"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $approval?->acc_kepala_sekolah ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-700 border-gray-300' }}">
                            <span>{{ $approval?->acc_kepala_sekolah ? '✅' : '⏳' }}</span>
                            <span>4. Kepala Sekolah</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- ==================== KONTEN TABEL DATA ==================== -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            
            <!-- 1. TABEL HARIAN -->
            @if($activeTab === 'harian')
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3 text-center w-24">Waktu (Jam)</th>
                                <th class="p-3">Nama Murid</th>
                                <th class="p-3 text-center">Kelas</th>
                                <th class="p-3">Bentuk / Deskripsi Pelanggaran</th>
                                <th class="p-3 text-center">Poin</th>
                                <th class="p-3">Nama Guru Pencatat</th>
                                <th class="p-3">Tindak Lanjut Langsung</th>
                                <th class="p-3 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-gray-700 dark:text-gray-300">{{ $row->jam }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $row->student->name ?? '-' }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold">
                                            {{ $row->classroom->name ?? ($row->student->classroom->name ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row->bentuk_pelanggaran }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 font-bold">
                                            +{{ $row->poin }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $row->teacher->name ?? '-' }}</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $row->tindak_lanjut_langsung ?: '-' }}</td>
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <button wire:click="openCreateViolationModal({{ $row->id }})" class="text-blue-600 hover:text-blue-800 font-semibold mr-2">Edit</button>
                                        <button wire:confirm="Hapus catatan pelanggaran ini?" wire:click="deleteViolation({{ $row->id }})" class="text-rose-600 hover:text-rose-800 font-semibold">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-400">
                                        Belum ada catatan pelanggaran siswa pada tanggal ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 2. TABEL MINGGUAN -->
            @elseif($activeTab === 'mingguan')
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3">Nama Murid</th>
                                <th class="p-3 text-center">Kelas</th>
                                <th class="p-3">Jenis Pelanggaran yang Dilakukan</th>
                                <th class="p-3 text-center">Akumulasi Poin</th>
                                <th class="p-3">Catatan Perkembangan (Wali Kelas)</th>
                                <th class="p-3">Tindak Lanjut Minggu Ini (Guru Piket)</th>
                                <th class="p-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $row['student']->name }}</div>
                                        <div class="text-[10px] text-gray-400">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">
                                        @if(!empty($row['jenis_pelanggaran']) && $row['jenis_pelanggaran'] !== '-')
                                            <span class="text-rose-700 dark:text-rose-400 font-medium">{{ $row['jenis_pelanggaran'] }}</span>
                                        @else
                                            <span class="text-emerald-600 italic">Nihil (Tidak Ada)</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($row['total_poin'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">+{{ $row['total_poin'] }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">0</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-gray-700 dark:text-gray-300">{{ $row['catatan_wali_kelas'] }}</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $row['tindak_lanjut_piket'] }}</td>
                                    <td class="p-3 text-center">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'mingguan', '{{ $currentPeriodeKey }}')"
                                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg font-bold border border-amber-200 transition-all">
                                            ✏️ Catat Wali
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 3. TABEL BULANAN -->
            @elseif($activeTab === 'bulanan')
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3">Nama Murid</th>
                                <th class="p-3 text-center">Kelas</th>
                                <th class="p-3 text-center">Poin Bulan Lalu</th>
                                <th class="p-3 text-center">Poin Bulan Ini</th>
                                <th class="p-3 text-center">Total Akumulasi</th>
                                <th class="p-3">Kategori / Status Sanksi (BK)</th>
                                <th class="p-3">Tindak Lanjut / Agenda Pembinaan (BK)</th>
                                <th class="p-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $row['student']->name }}</div>
                                        <div class="text-[10px] text-gray-400">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="p-3 text-center font-bold text-gray-600">{{ $row['poin_bulan_lalu'] }}</td>
                                    <td class="p-3 text-center font-bold {{ $row['poin_bulan_ini'] > 0 ? 'text-rose-600' : 'text-gray-600' }}">{{ $row['poin_bulan_ini'] }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full {{ $row['total_akumulasi'] > 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }} font-bold">
                                            {{ $row['total_akumulasi'] }} Poin
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row['status_sanksi_bk'] }}</td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row['tindak_lanjut_bk'] }}</td>
                                    <td class="p-3 text-center">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'bulanan', '{{ $currentPeriodeKey }}')"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 rounded-lg font-bold border border-indigo-200 transition-all">
                                            🛡️ Input BK
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 4. TABEL TRIWULAN (3 BULAN) -->
            @elseif($activeTab === 'triwulan')
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="p-3 text-center w-12">No</th>
                                <th class="p-3">Nama Siswa</th>
                                <th class="p-3 text-center">Kelas</th>
                                <th class="p-3 text-center">Poin {{ $bulanHeaders[0] ?? 'Bln 1' }}</th>
                                <th class="p-3 text-center">Poin {{ $bulanHeaders[1] ?? 'Bln 2' }}</th>
                                <th class="p-3 text-center">Poin {{ $bulanHeaders[2] ?? 'Bln 3' }}</th>
                                <th class="p-3 text-center">Total 3 Bulan</th>
                                <th class="p-3">Status Peringatan / Sanksi (Waka Kesiswaan)</th>
                                <th class="p-3">Tindak Lanjut & Pembinaan (Waka Kesiswaan)</th>
                                <th class="p-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $row['student']->name }}</div>
                                        <div class="text-[10px] text-gray-400">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="p-3 text-center font-semibold">{{ $row['poin_bulan_1'] }}</td>
                                    <td class="p-3 text-center font-semibold">{{ $row['poin_bulan_2'] }}</td>
                                    <td class="p-3 text-center font-semibold">{{ $row['poin_bulan_3'] }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full {{ $row['total_3_bulan'] > 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }} font-bold">
                                            {{ $row['total_3_bulan'] }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row['status_sanksi_kesiswaan'] }}</td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row['tindak_lanjut_kesiswaan'] }}</td>
                                    <td class="p-3 text-center">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'triwulan', '{{ $currentPeriodeKey }}')"
                                                class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-800 rounded-lg font-bold border border-purple-200 transition-all">
                                            ⚡ Kesiswaan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-8 text-center text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            <!-- 5. TABEL SEMESTER (6 BULAN) -->
            @elseif($activeTab === 'semester')
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="p-3 text-center w-10">No</th>
                                <th class="p-3">Nama Siswa</th>
                                <th class="p-3 text-center">Kelas</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[0] ?? 'Bln 1' }}</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[1] ?? 'Bln 2' }}</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[2] ?? 'Bln 3' }}</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[3] ?? 'Bln 4' }}</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[4] ?? 'Bln 5' }}</th>
                                <th class="p-3 text-center">{{ $bulanHeaders[5] ?? 'Bln 6' }}</th>
                                <th class="p-3 text-center">Total Poin</th>
                                <th class="p-3 text-center">Predikat Sikap</th>
                                <th class="p-3">Catatan Kepribadian Rapor (Wali Kelas)</th>
                                <th class="p-3 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($tableData as $idx => $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                                    <td class="p-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $row['student']->name }}</div>
                                        <div class="text-[10px] text-gray-400">NIS: {{ $row['student']->nis ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $row['student']->classroom->name ?? '-' }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m1'] }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m2'] }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m3'] }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m4'] }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m5'] }}</td>
                                    <td class="p-3 text-center">{{ $row['poin_m6'] }}</td>
                                    <td class="p-3 text-center font-bold">
                                        <span class="px-2 py-0.5 rounded-full {{ $row['total_semester'] > 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $row['total_semester'] }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        @php $pred = $row['predikat']; @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold shadow-xs {{ $pred['predikat'] === 'A' || $pred['predikat'] === 'A-' ? 'bg-emerald-100 text-emerald-800' : ($pred['predikat'] === 'B+' || $pred['predikat'] === 'B' || $pred['predikat'] === 'B-' ? 'bg-blue-100 text-blue-800' : ($pred['predikat'] === 'C+' || $pred['predikat'] === 'C' || $pred['predikat'] === 'C-' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')) }}">
                                            {{ $pred['predikat'] }} ({{ $pred['label'] }})
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-800 dark:text-gray-200">{{ $row['catatan_wali_kelas'] }}</td>
                                    <td class="p-3 text-center">
                                        <button wire:click="openRecordModal({{ $row['student']->id }}, 'semester', '{{ $currentPeriodeKey }}')"
                                                class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg font-bold border border-emerald-200 transition-all">
                                            📝 Rapor
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="p-8 text-center text-gray-400">Tidak ada data siswa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Banner Rubrik Indikator Nilai Sikap -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 text-xs">
                    <div class="font-bold text-gray-800 dark:text-gray-200 mb-1">
                        📌 Indikator Penentuan Predikat Nilai Sikap Berdasarkan Akumulasi Poin Pelanggaran:
                    </div>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold"><strong>A</strong> : 0 - 5 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold"><strong>A-</strong> : 6 - 15 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-semibold"><strong>B+</strong> : 16 - 30 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-semibold"><strong>B</strong> : 31 - 45 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-semibold"><strong>B-</strong> : 46 - 60 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold"><strong>C+</strong> : 61 - 85 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold"><strong>C</strong> : 86 - 105 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold"><strong>C-</strong> : 106 - 120 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-semibold"><strong>D</strong> : 121 - 140 Poin</span>
                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-300 font-bold"><strong>E</strong> : 141 - 150+ Poin</span>
                    </div>
                </div>
            @endif

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL 1: INPUT/EDIT JURNAL PELANGGARAN HARIAN (GURU PIKET) -->
    <!-- ========================================================= -->
    @if($showViolationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>{{ $editingViolationId ? '✏️ Edit' : '➕ Input' }}</span>
                        <span>Catatan Pelanggaran Harian (Piket)</span>
                    </h3>
                    <button wire:click="closeViolationModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Nama Murid *</label>
                        <select wire:model="formStudentId" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                            <option value="">-- Pilih Murid --</option>
                            @foreach($studentOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('formStudentId') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Tanggal *</label>
                            <input type="date" wire:model="formTanggal" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                            @error('formTanggal') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Waktu (Jam) *</label>
                            <input type="text" wire:model="formJam" placeholder="07:30" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                            @error('formJam') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Bentuk / Deskripsi Pelanggaran *</label>
                        <textarea wire:model="formBentukPelanggaran" rows="2" placeholder="Contoh: Datang terlambat lebih dari 15 menit tanpa surat izin" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        @error('formBentukPelanggaran') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Poin Pelanggaran *</label>
                            <input type="number" wire:model="formPoin" min="1" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                            @error('formPoin') <span class="text-rose-600 text-[11px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Guru Pencatat</label>
                            <input type="text" value="{{ $currentUser?->name }}" disabled class="w-full rounded-xl bg-gray-100 dark:bg-gray-600 text-gray-500 border-gray-300 dark:border-gray-600 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Tindak Lanjut Langsung</label>
                        <textarea wire:model="formTindakLanjut" rows="2" placeholder="Contoh: Diberi teguran lisan, membaca istighfar, dan dicatat di buku piket" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t pt-3 dark:border-gray-700">
                    <button wire:click="closeViolationModal" type="button" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <button wire:click="saveViolation" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm">
                        Simpan Catatan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ===================================================================== -->
    <!-- MODAL 2: INPUT CATATAN EVALUASI & TINDAK LANJUT STAKEHOLDER PER SISWA -->
    <!-- ===================================================================== -->
    @if($showRecordModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Form Evaluasi & Tindak Lanjut Siswa
                        </h3>
                        <p class="text-xs text-rose-600 font-semibold">{{ $recordStudentName }} (Periode: {{ $recordKey }})</p>
                    </div>
                    <button wire:click="closeRecordModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    @if($recordType === 'mingguan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Catatan Perkembangan (Diisi Wali Kelas)</label>
                            <textarea wire:model="fieldCatatanWaliKelas" rows="3" placeholder="Tuliskan catatan perkembangan kedisiplinan dan sikap murid minggu ini..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    @elseif($recordType === 'bulanan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Kategori / Status Sanksi (Diisi BK)</label>
                            <input type="text" wire:model="fieldStatusSanksiBk" placeholder="Contoh: Peringatan Lisan / Konseling Tahap 1" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Tindak Lanjut / Agenda Pembinaan (Diisi BK)</label>
                            <textarea wire:model="fieldTindakLanjutBk" rows="3" placeholder="Contoh: Dijadwalkan bimbingan konseling pribadi pada hari Selasa..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    @elseif($recordType === 'triwulan')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Status Peringatan / Sanksi (Diisi Waka Kesiswaan)</label>
                            <input type="text" wire:model="fieldStatusSanksiKesiswaan" placeholder="Contoh: SP-1 / Pembinaan Karakter Tingkat Kesiswaan" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs">
                        </div>
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Tindak Lanjut & Proses Pembinaan (Diisi Waka Kesiswaan)</label>
                            <textarea wire:model="fieldTindakLanjutKesiswaan" rows="3" placeholder="Contoh: Koordinasi bersama orang tua dan penugasan bakti sosial sekolah..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    @elseif($recordType === 'semester')
                        <div>
                            <label class="font-bold text-gray-700 dark:text-gray-300 block mb-1">Catatan Kepribadian Rapor (Diinput oleh Wali Kelas)</label>
                            <textarea wire:model="fieldCatatanWaliKelas" rows="4" placeholder="Tuliskan deskripsi narasi kepribadian dan sikap spiritual & sosial untuk dimasukkan ke dalam rapor semester..." class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs"></textarea>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 border-t pt-3 dark:border-gray-700">
                    <button wire:click="closeRecordModal" type="button" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <button wire:click="saveRecord" type="button" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm">
                        Simpan Evaluasi
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
