<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. SELECTION CARD: LEMBAGA -> KELAS -> TAHUN AJARAN -> NOMINAL --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                <div class="p-2 bg-orange-100 dark:bg-orange-950 text-orange-600 dark:text-orange-400 rounded-lg">
                    <x-heroicon-o-funnel class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Filter Pemilihan Lembaga & Kelas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih Lembaga terlebih dahulu, kemudian tentukan Kelas dan Bulan pembayaran SPP.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- 1. Lembaga --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-m-building-library class="w-4 h-4 text-orange-500" />
                            1. Lembaga / Unit Sekolah <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <select wire:model.live="selectedInstitutionId" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500 shadow-sm transition">
                        @foreach ($this->institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Kelas --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-m-academic-cap class="w-4 h-4 text-orange-500" />
                            2. Kelas / Rombel <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <select wire:model.live="selectedClassroomId" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500 shadow-sm transition">
                        @forelse ($this->classrooms as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }} (Tingkat {{ $cls->tingkat }})</option>
                        @empty
                            <option value="">-- Tidak ada kelas terdaftar --</option>
                        @endforelse
                    </select>
                </div>

                {{-- 3. Tahun Ajaran --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-m-calendar-days class="w-4 h-4 text-orange-500" />
                            3. Tahun Ajaran
                        </span>
                    </label>
                    <select wire:model.live="selectedTahunAjaran" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500 shadow-sm transition">
                        <option value="2026/2027">2026/2027 (Tahun Aktif)</option>
                        <option value="2025/2026">2025/2026</option>
                        <option value="2027/2028">2027/2028</option>
                    </select>
                </div>

                {{-- 4. Nominal Standar --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-m-currency-dollar class="w-4 h-4 text-orange-500" />
                            Nominal SPP Default (Rp)
                        </span>
                    </label>
                    <input type="number" wire:model.live.debounce.500ms="defaultNominal" step="5000" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500 shadow-sm transition" placeholder="200000" />
                </div>
            </div>

            {{-- 12 BULAN SELECTOR TABS --}}
            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-m-clock class="w-4 h-4 text-orange-500" />
                        Pilih Bulan Tagihan:
                    </span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\StudentSpp::BULAN_LIST as $bulanKey => $bulanLabel)
                        <button 
                            type="button" 
                            wire:click="$set('selectedBulan', '{{ $bulanKey }}')"
                            class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-all shadow-sm border {{ $selectedBulan === $bulanKey ? 'bg-orange-600 text-white border-orange-600 shadow-orange-200 dark:shadow-none' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                            {{ $bulanLabel }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2. SUMMARY KPI STATS CARDS --}}
        @php
            $stats = $this->stats;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Siswa --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 rounded-xl">
                    <x-heroicon-o-user-group class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa di Kelas</p>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }} <span class="text-xs font-normal text-gray-500">siswa</span></h4>
                </div>
            </div>

            {{-- Sudah Lunas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-emerald-200 dark:border-emerald-800/40 shadow-sm flex items-center gap-4 bg-emerald-50/30 dark:bg-emerald-950/20">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <x-heroicon-o-check-circle class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Sudah Bayar (Lunas)</p>
                    <h4 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['lunas'] }} <span class="text-xs font-normal text-gray-500">siswa</span></h4>
                </div>
            </div>

            {{-- Belum Lunas --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-rose-200 dark:border-rose-800/40 shadow-sm flex items-center gap-4 bg-rose-50/30 dark:bg-rose-950/20">
                <div class="p-3 bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 rounded-xl">
                    <x-heroicon-o-exclamation-circle class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-rose-700 dark:text-rose-300">Belum Bayar</p>
                    <h4 class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $stats['belum_lunas'] }} <span class="text-xs font-normal text-gray-500">siswa</span></h4>
                </div>
            </div>

            {{-- Persentase & Uang Terkumpul --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tingkat Pelunasan</p>
                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400">{{ $stats['persentase'] }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden mb-2">
                    <div class="bg-orange-500 h-2 rounded-full transition-all duration-500" style="width: {{ $stats['persentase'] }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Terkumpul: <strong class="text-gray-900 dark:text-white font-semibold">Rp {{ number_format($stats['total_nominal'], 0, ',', '.') }}</strong>
                </p>
            </div>
        </div>

        {{-- 3. TOOLBAR: SEARCH, STATUS FILTER & BULK ACTIONS --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            
            {{-- Search & Status Filter Tabs --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari nama atau NIS siswa..." 
                        class="pl-9 pr-3 py-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500 w-full"
                    />
                </div>

                {{-- Status Filter --}}
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 bg-gray-50 dark:bg-gray-900">
                    <button 
                        type="button" 
                        wire:click="$set('statusFilter', 'all')"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition {{ $statusFilter === 'all' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                        Semua ({{ $stats['total'] }})
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('statusFilter', 'belum_lunas')"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition {{ $statusFilter === 'belum_lunas' ? 'bg-rose-500 text-white shadow-sm' : 'text-rose-600 dark:text-rose-400 hover:text-rose-800' }}">
                        Belum Bayar ({{ $stats['belum_lunas'] }})
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('statusFilter', 'lunas')"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition {{ $statusFilter === 'lunas' ? 'bg-emerald-600 text-white shadow-sm' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-800' }}">
                        Lunas ({{ $stats['lunas'] }})
                    </button>
                </div>
            </div>

            {{-- Bulk Actions & Quick Action --}}
            <div class="flex flex-wrap items-center gap-2">
                @if (count($selectedStudents) > 0)
                    <span class="text-xs text-gray-600 dark:text-gray-300 font-medium mr-1">
                        {{ count($selectedStudents) }} dipilih:
                    </span>
                    <button 
                        type="button" 
                        wire:click="bulkMarkPaid"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Tandai Lunas
                    </button>
                    <button 
                        type="button" 
                        wire:click="bulkMarkUnpaid"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Tandai Belum Lunas
                    </button>
                @endif

                <button 
                    type="button" 
                    wire:click="markAllInClassPaid"
                    wire:confirm="Yakin ingin menandai SELURUH siswa di kelas ini sebagai LUNAS untuk bulan {{ $selectedBulan }}?"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-950/50 border border-orange-300 dark:border-orange-800 hover:bg-orange-100 transition shadow-sm">
                    <x-heroicon-m-check-badge class="w-4 h-4 text-orange-600" />
                    Tandai Semua Lunas 1 Kelas
                </button>

                <button 
                    type="button" 
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 transition">
                    <x-heroicon-m-printer class="w-4 h-4" />
                    Cetak Rekap
                </button>
            </div>
        </div>

        {{-- 4. DATA TABLE: DAFTAR SISWA & STATUS SPP --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th scope="col" class="p-4 w-10 text-center">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="selectAll" 
                                    class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500" 
                                />
                            </th>
                            <th scope="col" class="py-3.5 px-3 w-12 text-center">No</th>
                            <th scope="col" class="py-3.5 px-3">Siswa</th>
                            <th scope="col" class="py-3.5 px-3">NIS / NISN</th>
                            <th scope="col" class="py-3.5 px-3">Status SPP ({{ $selectedBulan }})</th>
                            <th scope="col" class="py-3.5 px-3">Tanggal Bayar</th>
                            <th scope="col" class="py-3.5 px-3">Nominal & Metode</th>
                            <th scope="col" class="py-3.5 px-3">Kwitansi / Catatan</th>
                            <th scope="col" class="py-3.5 px-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php $students = $this->students; @endphp
                        @forelse ($students as $index => $student)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition {{ $student->is_lunas ? 'bg-emerald-50/20 dark:bg-emerald-950/10' : '' }}">
                                {{-- Checkbox --}}
                                <td class="p-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="selectedStudents" 
                                        value="{{ $student->id }}" 
                                        class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500" 
                                    />
                                </td>

                                {{-- No --}}
                                <td class="py-3.5 px-3 text-center text-xs text-gray-500">{{ $loop->iteration }}</td>

                                {{-- Siswa --}}
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $student->is_lunas ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ strtoupper(substr($student->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $student->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">JK: {{ $student->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- NIS / NISN --}}
                                <td class="py-3.5 px-3 text-xs">
                                    <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-800 dark:text-gray-200">
                                        {{ $student->nis ?? '-' }}
                                    </span>
                                    @if ($student->nisn)
                                        <span class="block text-[10px] text-gray-400 font-mono mt-0.5">{{ $student->nisn }}</span>
                                    @endif
                                </td>

                                {{-- Status SPP --}}
                                <td class="py-3.5 px-3">
                                    @if ($student->is_lunas)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            LUNAS
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            BELUM BAYAR
                                        </span>
                                    @endif
                                </td>

                                {{-- Tanggal Bayar --}}
                                <td class="py-3.5 px-3 text-xs">
                                    @if ($student->is_lunas)
                                        <span class="text-gray-900 dark:text-white font-medium">
                                            {{ $student->spp_tanggal_bayar ?? '-' }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>

                                {{-- Nominal & Metode --}}
                                <td class="py-3.5 px-3 text-xs">
                                    @if ($student->is_lunas)
                                        <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($student->spp_nominal, 0, ',', '.') }}</p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $student->spp_metode }}</p>
                                    @else
                                        <span class="text-gray-400 italic">Rp {{ number_format($defaultNominal, 0, ',', '.') }}</span>
                                    @endif
                                </td>

                                {{-- Kwitansi & Catatan --}}
                                <td class="py-3.5 px-3 text-xs max-w-[180px] truncate">
                                    @if ($student->is_lunas)
                                        @if ($student->spp_kwitansi && $student->spp_kwitansi !== '-')
                                            <span class="font-mono text-[11px] text-blue-600 dark:text-blue-400">{{ $student->spp_kwitansi }}</span>
                                        @endif
                                        @if ($student->spp_catatan)
                                            <p class="text-[11px] text-gray-500 truncate" title="{{ $student->spp_catatan }}">{{ $student->spp_catatan }}</p>
                                        @endif
                                        @if ((!$student->spp_kwitansi || $student->spp_kwitansi === '-') && !$student->spp_catatan)
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Aksi Cepat --}}
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if ($student->is_lunas)
                                            <button 
                                                type="button" 
                                                wire:click="togglePaymentStatus({{ $student->id }})" 
                                                wire:confirm="Yakin ingin membatalkan status LUNAS siswa {{ $student->name }}?"
                                                title="Batalkan status lunas"
                                                class="px-2 py-1 text-xs font-medium rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800">
                                                Batal Lunas
                                            </button>
                                        @else
                                            <button 
                                                type="button" 
                                                wire:click="togglePaymentStatus({{ $student->id }})" 
                                                title="Tandai langsung sebagai lunas hari ini"
                                                class="px-2.5 py-1 text-xs font-semibold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">
                                                Tandai Lunas
                                            </button>
                                        @endif

                                        <button 
                                            type="button" 
                                            wire:click="openEditPaymentModal({{ $student->id }})" 
                                            title="Ubah detail pembayaran (tanggal, nominal, bukti, dll)"
                                            class="p-1 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-gray-700 rounded-lg transition">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <x-heroicon-o-users class="w-10 h-10 text-gray-400" />
                                        <p class="text-sm font-medium">Tidak ada data siswa ditemukan untuk kelas ini.</p>
                                        <p class="text-xs text-gray-400">Pastikan lembaga dan kelas telah memiliki data siswa yang diinputkan dari lembaga masing-masing.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 5. MODAL EDIT DETAIL PEMBAYARAN --}}
        @if ($showEditModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-orange-100 text-orange-600 rounded-lg">
                                    <x-heroicon-o-currency-dollar class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Form Pembayaran SPP</h3>
                                    <p class="text-xs text-gray-500">{{ $editStudentName }} (NIS: {{ $editStudentNis }})</p>
                                </div>
                            </div>
                            <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                                <x-heroicon-m-x-mark class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="space-y-4 mt-4">
                            {{-- Info Periode --}}
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-xs flex justify-between">
                                <div>
                                    <span class="text-gray-500">Tahun Ajaran:</span>
                                    <strong class="text-gray-900 dark:text-white ml-1">{{ $selectedTahunAjaran }}</strong>
                                </div>
                                <div>
                                    <span class="text-gray-500">Bulan:</span>
                                    <strong class="text-orange-600 dark:text-orange-400 font-bold ml-1">{{ $selectedBulan }}</strong>
                                </div>
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Pembayaran</label>
                                <select wire:model="editStatus" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500">
                                    <option value="lunas">Lunas</option>
                                    <option value="belum_lunas">Belum Lunas</option>
                                </select>
                            </div>

                            {{-- Tanggal Bayar --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Bayar</label>
                                <input type="date" wire:model="editTanggalBayar" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500" />
                            </div>

                            {{-- Nominal --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nominal (Rp)</label>
                                <input type="number" wire:model="editNominal" step="1000" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500" />
                            </div>

                            {{-- Metode Pembayaran --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                                <select wire:model="editMetode" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500">
                                    @foreach (\App\Models\StudentSpp::METODE_PEMBAYARAN as $metodeKey => $metodeVal)
                                        <option value="{{ $metodeKey }}">{{ $metodeVal }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- No Kwitansi --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nomor Kwitansi / Referensi</label>
                                <input type="text" wire:model="editKwitansi" placeholder="Contoh: SPP/202610/001" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500" />
                            </div>

                            {{-- Catatan --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea wire:model="editCatatan" rows="2" placeholder="Catatan pembayaran..." class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-xs font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 transition">
                                Batal
                            </button>
                            <button type="button" wire:click="savePaymentModal" class="px-4 py-2 text-xs font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 transition shadow-sm">
                                Simpan Data SPP
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
