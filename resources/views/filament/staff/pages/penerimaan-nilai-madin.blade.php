<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. KARTU STATISTIK PENERIMAAN NILAI MADIN --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Guru Al-Qur'an -->
            <div class="p-5 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">Guru Al-Qur'an</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_guru'] }} <span class="text-xs font-normal text-gray-500">Ustadz/ah</span></p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <x-heroicon-o-users class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Tenaga pengajar Al-Qur'an & Diniyah aktif
                </div>
            </div>

            <!-- Menunggu Verifikasi -->
            <div class="p-5 bg-white border border-amber-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-amber-800/40">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-amber-600 uppercase dark:text-amber-400">Menunggu Diterima</p>
                        <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ $stats['total_pending'] }}
                            @if($stats['total_pending'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 ml-2 text-xs font-medium rounded-full bg-amber-100 text-amber-800 animate-pulse dark:bg-amber-900 dark:text-amber-200">Perlu Verifikasi</span>
                            @endif
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-xl text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <x-heroicon-o-clock class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Hafalan: <strong>{{ $stats['pending_capaian'] }}</strong> • Kualitas: <strong>{{ $stats['pending_kualitas'] }}</strong> • Mapel: <strong>{{ $stats['pending_nilai'] }}</strong>
                </div>
            </div>

            <!-- Sudah Diterima -->
            <div class="p-5 bg-white border border-emerald-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-emerald-800/40">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-emerald-600 uppercase dark:text-emerald-400">Sudah Diterima</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['total_accepted'] }} <span class="text-xs font-normal text-gray-500">Data</span></p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <x-heroicon-o-check-badge class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Nilai & setoran tervalidasi Koordinator Madin
                </div>
            </div>

            <!-- Total Inputan -->
            <div class="p-5 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">Total Akumulasi</p>
                        <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['total_pending'] + $stats['total_accepted'] }} <span class="text-xs font-normal text-gray-500">Record</span></p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                        <x-heroicon-o-book-open class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Dari seluruh rombel kelas dan guru Al-Qur'an
                </div>
            </div>
        </div>

        {{-- 2. BAR FILTER PENERIMAAN --}}
        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <!-- Filter Spesialisasi Guru Al-Qur'an -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Spesialisasi Qur'an</label>
                    <select wire:model.live="selectedSpecialization" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Semua Spesialisasi --</option>
                        <option value="yanbua">Yanbu'a</option>
                        <option value="tahsin">Tahsin</option>
                        <option value="tahfidz_dasar">Tahfidz Dasar</option>
                        <option value="tahfidz_lanjutan">Tahfidz Lanjutan</option>
                    </select>
                </div>

                <!-- Filter Guru Al-Qur'an -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Guru Al-Qur'an</label>
                    <select wire:model.live="selectedTeacherId" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Seluruh Guru Al-Qur'an --</option>
                        @foreach($quranTeachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }} [{{ $teacher->getQuranSpecializationLabel() ?? 'Al-Qur\'an' }}]</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Kelas</label>
                    <select wire:model.live="selectedClassroomId" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Seluruh Kelas --</option>
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}">Kelas {{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Semester -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Semester</label>
                    <select wire:model.live="selectedSemester" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </div>

                <!-- Filter Status Verifikasi -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Status Verifikasi</label>
                    <select wire:model.live="selectedStatus" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="Semua">Semua Status</option>
                        <option value="Menunggu Verifikasi">Menunggu Verifikasi (Pending)</option>
                        <option value="Diterima">Sudah Diterima (Disetujui)</option>
                        <option value="Perlu Perbaikan">Perlu Perbaikan (Revisi)</option>
                    </select>
                </div>

                <!-- Pencarian Siswa / Catatan -->
                <div>
                    <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Cari Siswa / Keterangan</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Ketik nama siswa..." class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                </div>
            </div>
        </div>

        {{-- 3. TABS NAVIGASI UTAMA --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-2 sm:space-x-4" aria-label="Tabs">
                <button
                    wire:click="setTab('monitoring_guru')"
                    class="px-4 py-2.5 text-sm font-semibold rounded-t-lg transition flex items-center space-x-2 {{ $activeTab === 'monitoring_guru' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-user-group class="w-4 h-4" />
                    <span>Monitoring Guru Al-Qur'an</span>
                </button>

                <button
                    wire:click="setTab('capaian_quran')"
                    class="px-4 py-2.5 text-sm font-semibold rounded-t-lg transition flex items-center space-x-2 {{ $activeTab === 'capaian_quran' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-book-open class="w-4 h-4" />
                    <span>Setoran Hafalan Al-Qur'an</span>
                    @if($stats['pending_capaian'] > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-amber-200 text-amber-900 font-bold ml-1">{{ $stats['pending_capaian'] }}</span>
                    @endif
                </button>

                <button
                    wire:click="setTab('kualitas_bacaan')"
                    class="px-4 py-2.5 text-sm font-semibold rounded-t-lg transition flex items-center space-x-2 {{ $activeTab === 'kualitas_bacaan' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-star class="w-4 h-4" />
                    <span>Skor Kualitas Bacaan</span>
                    @if($stats['pending_kualitas'] > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-amber-200 text-amber-900 font-bold ml-1">{{ $stats['pending_kualitas'] }}</span>
                    @endif
                </button>

                <button
                    wire:click="setTab('nilai_mapel')"
                    class="px-4 py-2.5 text-sm font-semibold rounded-t-lg transition flex items-center space-x-2 {{ $activeTab === 'nilai_mapel' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700' }}"
                >
                    <x-heroicon-o-academic-cap class="w-4 h-4" />
                    <span>Nilai Mapel Diniyah</span>
                    @if($stats['pending_nilai'] > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-amber-200 text-amber-900 font-bold ml-1">{{ $stats['pending_nilai'] }}</span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- 4. KONTEN TAB --}}

        {{-- TAB 1: MONITORING GURU AL-QUR'AN --}}
        @if($activeTab === 'monitoring_guru')
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Rekapitulasi Penyerahan Nilai per Guru Al-Qur'an</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pantau progres penginputan nilai dan terima setoran nilai dari masing-masing guru Al-Qur'an.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Nama Guru Al-Qur'an</th>
                                <th class="px-4 py-3">Spesialisasi Guru</th>
                                <th class="px-4 py-3">Rombel Kelas</th>
                                <th class="px-4 py-3 text-center">Setoran Hafalan</th>
                                <th class="px-4 py-3 text-center">Kualitas Bacaan</th>
                                <th class="px-4 py-3 text-center">Nilai Mapel</th>
                                <th class="px-4 py-3 text-center">Menunggu Verifikasi</th>
                                <th class="px-4 py-3 text-center">Tgl Input Terakhir</th>
                                <th class="px-4 py-3 text-center">Aksi Penerimaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($monitoringGuru as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs dark:bg-emerald-950 dark:text-emerald-300">
                                                {{ strtoupper(substr($item['teacher']->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <span>{{ $item['teacher']->name }}</span>
                                                <div class="text-xs text-gray-400 font-normal">{{ $item['teacher']->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $spec = $item['specialization'];
                                            $colorClass = match($spec) {
                                                'Yanbu\'a' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/60 dark:text-amber-200',
                                                'Tahsin' => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-900/60 dark:text-sky-200',
                                                'Tahfidz Dasar' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/60 dark:text-emerald-200',
                                                'Tahfidz Lanjutan' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/60 dark:text-purple-200',
                                                default => 'bg-teal-100 text-teal-800 border-teal-300 dark:bg-teal-900/60 dark:text-teal-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md border {{ $colorClass }}">
                                            {{ $spec }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                            {{ $item['classes'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                        {{ $item['total_capaian'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                        {{ $item['total_kualitas'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                        {{ $item['total_grades'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['pending_all'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                                ⏳ {{ $item['pending_all'] }} Menunggu
                                            </span>
                                        @elseif($item['total_all'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                                                ✓ Semua Diterima
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Belum Ada Input</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item['last_input'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['pending_all'] > 0)
                                            <button
                                                wire:click="terimaSemuaDariGuru({{ $item['teacher']->id }})"
                                                wire:confirm="Apakah Anda yakin menerima dan memverifikasi seluruh inputan nilai ({{ $item['pending_all'] }} data) dari {{ $item['teacher']->name }}?"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm transition"
                                            >
                                                <x-heroicon-m-check class="w-4 h-4 mr-1" />
                                                Terima Semua
                                            </button>
                                        @else
                                            <span class="text-xs text-emerald-600 font-semibold dark:text-emerald-400">✓ Tervalidasi</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500 italic">
                                        Tidak ada data guru Al-Qur'an pada unit ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 2: SETORAN HAFALAN AL-QUR'AN --}}
        @if($activeTab === 'capaian_quran')
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Daftar Setoran Hafalan Al-Qur'an (Ziyadah & Muraja'ah)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Terima dan verifikasi setoran hafalan harian santri/siswa yang diinput oleh guru Al-Qur'an.</p>
                    </div>
                    <div>
                        <button
                            wire:click="terimaSemuaPadaTabAktif"
                            wire:confirm="Apakah Anda yakin menerima semua setoran hafalan yang sedang tampil pada filter ini?"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm transition"
                        >
                            <x-heroicon-m-check-badge class="w-4 h-4 mr-1" />
                            Terima Semua Tampil
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Guru Penginput</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Hafalan (Juz / Surah / Ayat)</th>
                                <th class="px-4 py-3">Catatan Guru</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi Koordinator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($capaianData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <td class="px-4 py-3 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row->student?->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $row->student?->classroom?->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-emerald-700 font-medium dark:text-emerald-400">
                                        <div>{{ $row->teacher?->name ?? '-' }}</div>
                                        @if($row->teacher?->getQuranSpecializationLabel())
                                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 mt-0.5">
                                                {{ $row->teacher->getQuranSpecializationLabel() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $types = is_array($row->type) ? $row->type : [$row->type];
                                        @endphp
                                        @foreach($types as $t)
                                            <span class="px-2 py-0.5 text-xs rounded font-medium {{ $t === 'ziyadah' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($t) }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-gray-900 dark:text-white">Juz {{ $row->juz }}</span>,
                                        <span>{{ $surahs[$row->surah] ?? $row->surah }}</span>
                                        <span class="text-xs text-gray-500">Ayat {{ $row->ayah }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs italic text-gray-500 max-w-xs truncate">
                                        {{ $row->catatan ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row->status_verifikasi === 'Diterima')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                                                ✓ Diterima
                                            </span>
                                            @if($row->verifier)
                                                <div class="text-[10px] text-gray-400 mt-0.5">Oleh: {{ $row->verifier->name }}</div>
                                            @endif
                                        @elseif($row->status_verifikasi === 'Perlu Perbaikan')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200">
                                                Revisi
                                            </span>
                                            @if($row->catatan_koordinator)
                                                <div class="text-[10px] text-rose-600 italic mt-0.5">{{ $row->catatan_koordinator }}</div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($row->status_verifikasi !== 'Diterima')
                                            <button
                                                wire:click="terimaCapaian({{ $row->id }})"
                                                title="Terima Setoran"
                                                class="p-1.5 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition mr-1"
                                            >
                                                <x-heroicon-m-check class="w-4 h-4" />
                                            </button>
                                            <button
                                                wire:click="openFeedbackModal('capaian', {{ $row->id }})"
                                                title="Beri Catatan / Revisi"
                                                class="p-1.5 text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200 transition"
                                            >
                                                <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 italic">
                                        Tidak ada data setoran hafalan yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($capaianData && $capaianData->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $capaianData->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 3: SKOR KUALITAS BACAAN --}}
        @if($activeTab === 'kualitas_bacaan')
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Daftar Penilaian Kualitas Bacaan Al-Qur'an & Tajwid</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Verifikasi skor kualitas bacaan santri (Tahfidz/Tahsin) dari seluruh guru Al-Qur'an.</p>
                    </div>
                    <div>
                        <button
                            wire:click="terimaSemuaPadaTabAktif"
                            wire:confirm="Apakah Anda yakin menerima semua skor kualitas yang sedang tampil pada filter ini?"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm transition"
                        >
                            <x-heroicon-m-check-badge class="w-4 h-4 mr-1" />
                            Terima Semua Tampil
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Guru Penginput</th>
                                <th class="px-4 py-3">Program</th>
                                <th class="px-4 py-3 text-center">Skor (1-100)</th>
                                <th class="px-4 py-3 text-center">Predikat</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi Koordinator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($kualitasData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <td class="px-4 py-3 text-xs whitespace-nowrap text-gray-500">
                                        {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row->student?->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $row->student?->classroom?->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-emerald-700 font-medium dark:text-emerald-400">
                                        <div>{{ $row->teacher?->name ?? '-' }}</div>
                                        @if($row->teacher?->getQuranSpecializationLabel())
                                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 mt-0.5">
                                                {{ $row->teacher->getQuranSpecializationLabel() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 text-xs rounded-md font-semibold {{ $row->program === 'tahfidz' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800' }}">
                                            {{ ucfirst($row->program) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-base {{ $row->scale_score >= 90 ? 'text-emerald-600' : ($row->scale_score >= 75 ? 'text-blue-600' : 'text-amber-600') }}">
                                        {{ $row->scale_score }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row->scale_score >= 90)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-emerald-100 text-emerald-800">A (Mumtaz)</span>
                                        @elseif($row->scale_score >= 80)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-800">B (Jayyid Jiddan)</span>
                                        @elseif($row->scale_score >= 70)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-amber-100 text-amber-800">C (Jayyid)</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-rose-100 text-rose-800">D (Maqbul)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row->status_verifikasi === 'Diterima')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                                                ✓ Diterima
                                            </span>
                                        @elseif($row->status_verifikasi === 'Perlu Perbaikan')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                                                Revisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($row->status_verifikasi !== 'Diterima')
                                            <button
                                                wire:click="terimaKualitas({{ $row->id }})"
                                                title="Terima Skor"
                                                class="p-1.5 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition mr-1"
                                            >
                                                <x-heroicon-m-check class="w-4 h-4" />
                                            </button>
                                            <button
                                                wire:click="openFeedbackModal('kualitas', {{ $row->id }})"
                                                title="Beri Catatan / Revisi"
                                                class="p-1.5 text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200 transition"
                                            >
                                                <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 italic">
                                        Tidak ada data penilaian kualitas bacaan yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($kualitasData && $kualitasData->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $kualitasData->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 4: NILAI MAPEL DINIYAH & AL-QUR'AN --}}
        @if($activeTab === 'nilai_mapel')
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Daftar Nilai Akademik Mapel Diniyah / Al-Qur'an</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Nilai formatif, harian, keterampilan, STS, dan SAS yang diinputkan guru mata pelajaran Al-Qur'an/Diniyah.</p>
                    </div>
                    <div>
                        <button
                            wire:click="terimaSemuaPadaTabAktif"
                            wire:confirm="Apakah Anda yakin menerima semua nilai mapel yang sedang tampil pada filter ini?"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm transition"
                        >
                            <x-heroicon-m-check-badge class="w-4 h-4 mr-1" />
                            Terima Semua Tampil
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="px-4 py-3">Guru Penginput</th>
                                <th class="px-4 py-3">Tipe Nilai</th>
                                <th class="px-4 py-3 text-center">Nilai</th>
                                <th class="px-4 py-3">Catatan Guru</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi Koordinator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($gradesData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <td class="px-4 py-3 text-xs whitespace-nowrap text-gray-500">
                                        {{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $row->student?->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $row->classroom?->name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $row->subject?->name }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-emerald-700 font-medium dark:text-emerald-400">
                                        <div>{{ $row->teacher?->name ?? '-' }}</div>
                                        @if($row->teacher?->getQuranSpecializationLabel())
                                            <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 mt-0.5">
                                                {{ $row->teacher->getQuranSpecializationLabel() }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span class="px-2 py-0.5 rounded font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ strtoupper($row->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-base {{ $row->score >= 95 ? 'text-amber-500' : ($row->score >= 75 ? 'text-emerald-600' : 'text-rose-600') }}">
                                        {{ $row->score }}
                                        @if($row->score >= 95)
                                            <span class="text-xs" title="Pengayaan">⭐</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs italic text-gray-500 max-w-xs truncate">
                                        {{ $row->catatan_guru ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($row->status_verifikasi === 'Diterima')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                                                ✓ Diterima
                                            </span>
                                        @elseif($row->status_verifikasi === 'Perlu Perbaikan')
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                                                Revisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($row->status_verifikasi !== 'Diterima')
                                            <button
                                                wire:click="terimaNilai({{ $row->id }})"
                                                title="Terima Nilai"
                                                class="p-1.5 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition mr-1"
                                            >
                                                <x-heroicon-m-check class="w-4 h-4" />
                                            </button>
                                            <button
                                                wire:click="openFeedbackModal('nilai', {{ $row->id }})"
                                                title="Beri Catatan / Revisi"
                                                class="p-1.5 text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200 transition"
                                            >
                                                <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4" />
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500 italic">
                                        Tidak ada data nilai mapel yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($gradesData && $gradesData->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $gradesData->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- 5. MODAL FEEDBACK / CATATAN REVISI KOORDINATOR MADIN --}}
        @if($showFeedbackModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="w-full max-w-md p-6 bg-white rounded-2xl shadow-xl dark:bg-gray-800">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                            <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 mr-2 text-amber-600" />
                            Catatan Revisi Koordinator Madin
                        </h4>
                        <button wire:click="closeFeedbackModal" class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-m-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Pesan / Catatan untuk Guru Al-Qur'an:
                        </label>
                        <textarea
                            wire:model="catatanFeedback"
                            rows="4"
                            placeholder="Contoh: Mohon dicek kembali kelancaran bacaan pada ayat 10-15..."
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        ></textarea>
                        <p class="mt-1 text-[11px] text-gray-500">
                            Status data ini akan diubah menjadi <strong>"Perlu Perbaikan"</strong> dan guru yang bersangkutan dapat melihat catatan ini.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <button
                            type="button"
                            wire:click="closeFeedbackModal"
                            class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="simpanCatatanFeedback"
                            class="px-4 py-2 text-xs font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700 shadow-sm transition"
                        >
                            Kirim Catatan Revisi
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
