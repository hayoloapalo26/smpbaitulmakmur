<x-filament-panels::page>
    <div x-data="{ search: '', filterDivisi: 'Semua', filterStatus: 'Semua' }" class="space-y-6">

        <!-- Progress Keterlaksanaan & Statistik Ringkas -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-2 bg-primary-100 dark:bg-primary-950/60 text-primary-600 rounded-xl">
                            <x-heroicon-o-presentation-chart-line class="w-5 h-5" />
                        </span>
                        Progres Keterlaksanaan Program Sekolah
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Total akumulasi seluruh program kerja lintas divisi tahun ajaran aktif
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">{{ $persenSelesai }}%</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Terlaksana ({{ $rekapStatus['Selesai'] }} dari {{ $totalProgram }} program)</span>
                </div>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3.5 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-green-500 h-3.5 rounded-full transition-all duration-500" style="width: {{ $persenSelesai }}%"></div>
            </div>
        </div>

        <!-- Statistik Status Program (Kartu Interaktif Dapat Diklik) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Rencana / Belum Mulai -->
            <div @click="filterStatus = (filterStatus === 'Belum' ? 'Semua' : 'Belum'); document.getElementById('tabel-monitoring')?.scrollIntoView({ behavior: 'smooth' })"
                 :class="filterStatus === 'Belum' ? 'ring-2 ring-red-500 shadow-md bg-red-50/60 dark:bg-red-950/30 border-red-300 dark:border-red-800' : 'hover:border-red-300 hover:shadow-md border-gray-200 dark:border-gray-700'"
                 class="p-5 bg-white dark:bg-gray-800 rounded-2xl border transition-all cursor-pointer group flex flex-col justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-100 rounded-xl dark:bg-red-900/40 text-red-600 dark:text-red-400 group-hover:scale-105 transition-transform">
                        <x-heroicon-o-clock class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rencana / Belum Mulai</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $rekapStatus['Belum Dimulai'] }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <span class="font-semibold text-red-600 dark:text-red-400" x-text="filterStatus === 'Belum' ? '✓ Sedang Difilter' : '👉 Klik untuk filter'"></span>
                    <x-heroicon-m-arrow-right class="w-4 h-4 text-red-500 group-hover:translate-x-1 transition-transform" />
                </div>
            </div>

            <!-- 2. Sedang Berjalan -->
            <div @click="filterStatus = (filterStatus === 'Jalan' ? 'Semua' : 'Jalan'); document.getElementById('tabel-monitoring')?.scrollIntoView({ behavior: 'smooth' })"
                 :class="filterStatus === 'Jalan' ? 'ring-2 ring-yellow-500 shadow-md bg-yellow-50/60 dark:bg-yellow-950/30 border-yellow-300 dark:border-yellow-800' : 'hover:border-yellow-300 hover:shadow-md border-gray-200 dark:border-gray-700'"
                 class="p-5 bg-white dark:bg-gray-800 rounded-2xl border transition-all cursor-pointer group flex flex-col justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 rounded-xl dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 group-hover:scale-105 transition-transform">
                        <x-heroicon-o-arrow-path class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sedang Berjalan</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $rekapStatus['Sedang Berjalan'] }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <span class="font-semibold text-yellow-600 dark:text-yellow-400" x-text="filterStatus === 'Jalan' ? '✓ Sedang Difilter' : '👉 Klik untuk filter'"></span>
                    <x-heroicon-m-arrow-right class="w-4 h-4 text-yellow-500 group-hover:translate-x-1 transition-transform" />
                </div>
            </div>

            <!-- 3. Selesai / Terlaksana -->
            <div @click="filterStatus = (filterStatus === 'Selesai' ? 'Semua' : 'Selesai'); document.getElementById('tabel-monitoring')?.scrollIntoView({ behavior: 'smooth' })"
                 :class="filterStatus === 'Selesai' ? 'ring-2 ring-emerald-500 shadow-md bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800' : 'hover:border-emerald-300 hover:shadow-md border-gray-200 dark:border-gray-700'"
                 class="p-5 bg-white dark:bg-gray-800 rounded-2xl border transition-all cursor-pointer group flex flex-col justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-100 rounded-xl dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                        <x-heroicon-o-check-circle class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Selesai / Terlaksana</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $rekapStatus['Selesai'] }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="filterStatus === 'Selesai' ? '✓ Sedang Difilter' : '👉 Klik untuk filter'"></span>
                    <x-heroicon-m-arrow-right class="w-4 h-4 text-emerald-500 group-hover:translate-x-1 transition-transform" />
                </div>
            </div>
        </div>

        <!-- Sebaran Program Per Divisi -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <span>Sebaran Program Kerja per Divisi (Total: {{ $totalProgram }})</span>
                    <button type="button" 
                            x-show="filterDivisi !== 'Semua' || filterStatus !== 'Semua' || search !== ''"
                            @click="filterDivisi = 'Semua'; filterStatus = 'Semua'; search = ''"
                            class="text-xs font-semibold text-primary-600 hover:underline">
                        🔄 Reset Semua Filter
                    </button>
                </div>
            </x-slot>
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3 mt-2">
                @foreach($rekapDivisi as $divisi => $jumlah)
                <button type="button" 
                        @click="filterDivisi = (filterDivisi === '{{ $divisi }}' ? 'Semua' : '{{ $divisi }}')"
                        :class="filterDivisi === '{{ $divisi }}' ? 'bg-primary-50 border-primary-500 text-primary-700 dark:bg-primary-950/40 dark:border-primary-600 dark:text-primary-300 ring-2 ring-primary-500/20' : 'bg-gray-50/70 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800 border-gray-200 dark:border-gray-700'"
                        class="p-3 border rounded-xl text-center transition">
                    <h4 class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1 truncate">{{ $divisi }}</h4>
                    <p class="text-lg font-black text-primary-600 dark:text-primary-400">{{ $jumlah }}</p>
                </button>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Tabel Monitoring Semua Program Kerja -->
        <div id="tabel-monitoring">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-table-cells class="w-5 h-5 text-primary-500" />
                            Monitoring Realisasi Seluruh Program Kerja Sekolah
                        </span>
                        <div class="text-xs text-gray-500 font-normal">
                            Menampilkan data program terpadu dengan link akses dan dokumentasi Drive
                        </div>
                    </div>
                </x-slot>

                <!-- Filter Controls -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Cari Program Kerja:</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="search" 
                                placeholder="Ketik nama program..." 
                                class="w-full pl-9 text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Filter Divisi:</label>
                        <select 
                            x-model="filterDivisi" 
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="Semua">Semua Divisi ({{ $totalProgram }})</option>
                            @foreach(array_keys($rekapDivisi) as $div)
                            <option value="{{ $div }}">{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Filter Status Program:</label>
                        <select 
                            x-model="filterStatus" 
                            class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="Semua">Semua Status</option>
                            <option value="Belum">📝 Rencana / Belum Mulai ({{ $rekapStatus['Belum Dimulai'] }})</option>
                            <option value="Jalan">🔄 Sedang Berjalan ({{ $rekapStatus['Sedang Berjalan'] }})</option>
                            <option value="Selesai">✅ Selesai / Terlaksana ({{ $rekapStatus['Selesai'] }})</option>
                        </select>
                    </div>
                </div>
                
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100/80 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 w-12 text-center">No</th>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 w-44">Divisi</th>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Nama Program Kerja & Link Drive Dokumentasi</th>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 w-48">Kategori / Tipe</th>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 w-40">Waktu</th>
                                <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 w-32 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($daftarProgram as $index => $prog)
                            @php
                                $status = strtolower($prog->status);
                                $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300 border border-red-200 dark:border-red-800';
                                $statusCategory = 'Belum';
                                if (str_contains($status, 'jalan') || str_contains($status, 'proses')) {
                                    $badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800';
                                    $statusCategory = 'Jalan';
                                } elseif (str_contains($status, 'selesai') || str_contains($status, 'terlaksana')) {
                                    $badgeColor = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
                                    $statusCategory = 'Selesai';
                                }
                            @endphp
                            <tr 
                                class="bg-white dark:bg-gray-900 hover:bg-gray-50/80 dark:hover:bg-gray-800/60 transition"
                                x-show="(
                                    (search === '' || '{{ strtolower(addslashes($prog->nama)) }}'.includes(search.toLowerCase())) &&
                                    (filterDivisi === 'Semua' || '{{ strtolower(addslashes($prog->divisi)) }}'.includes(filterDivisi.toLowerCase())) &&
                                    (filterStatus === 'Semua' || '{{ $statusCategory }}' === filterStatus)
                                )"
                            >
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs text-center">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                                        {{ $prog->divisi }}
                                    </span>
                                </td>
                                
                                {{-- Kolom Nama Program dengan Link Seluruh Program & Di Bawahnya Link Drive --}}
                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        {{-- 1. LINK PROGRAM KERJA --}}
                                        <div>
                                            @if(!empty($prog->url) && $prog->url !== '#')
                                                <a href="{{ $prog->url }}" target="_blank" 
                                                   class="font-bold text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400 hover:underline inline-flex items-center gap-1.5 transition text-sm">
                                                    <span>{{ $prog->nama }}</span>
                                                    <x-heroicon-m-arrow-top-right-on-square class="w-3.5 h-3.5 text-primary-500 inline-block flex-shrink-0" />
                                                </a>
                                            @else
                                                <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $prog->nama }}</span>
                                            @endif
                                        </div>

                                        {{-- 2. DIBUATKAN LINK DRIVE UNTUK DOKUMENTASI DAN DOKUMEN PENDUKUNG DI BAWAHNYA --}}
                                        @if(!empty($prog->link_drive))
                                            <div class="flex items-center gap-2 flex-wrap pt-0.5">
                                                <a href="{{ $prog->link_drive }}" target="_blank" rel="noopener noreferrer" 
                                                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-700 transition shadow-xs group"
                                                   title="Buka Google Drive Dokumentasi & Dokumen Pendukung">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" viewBox="0 0 87.3 78" fill="currentColor">
                                                        <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8H0c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                                        <path d="M43.65 25 29.9 1.2c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44A9.06 9.06 0 0 0 0 53h27.5z" fill="#00ac47"/>
                                                        <path d="M73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5H59.8l5.85 10.15z" fill="#ea4335"/>
                                                        <path d="M43.65 25 57.4 1.2C56.05.4 54.5 0 52.9 0H34.4c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                                                        <path d="m59.8 53-16.15-28H16.15L32.3 53z" fill="#2684fc"/>
                                                        <path d="M73.4 26.5 60.1 3.5c-.8-1.4-1.95-2.5-3.3-3.3L43.65 25 59.8 53h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                                                    </svg>
                                                    <span>Link Drive Dokumentasi & Dokumen Pendukung</span>
                                                    <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3 text-emerald-500 group-hover:translate-x-0.5 transition-transform" />
                                                </a>

                                                <button type="button" 
                                                        wire:click="openDriveModal('{{ addslashes($prog->model_type) }}', {{ $prog->id }}, '{{ addslashes($prog->nama) }}', '{{ addslashes($prog->link_drive) }}')"
                                                        class="text-[11px] text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300 underline font-medium transition"
                                                        title="Ubah URL Google Drive">
                                                    Ubah Link
                                                </button>
                                            </div>
                                        @else
                                            <div class="pt-0.5">
                                                <button type="button" 
                                                        wire:click="openDriveModal('{{ addslashes($prog->model_type) }}', {{ $prog->id }}, '{{ addslashes($prog->nama) }}', '')"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300 border border-dashed border-gray-300 dark:border-gray-700 hover:border-primary-400 rounded-lg transition bg-gray-50/50 dark:bg-gray-800/40">
                                                    <x-heroicon-m-plus class="w-3.5 h-3.5 text-primary-500" />
                                                    <span>Tautkan Link Drive Dokumentasi</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $prog->kategori }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $prog->tipe }}</div>
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs font-medium">
                                    {{ $prog->waktu }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeColor }}">
                                        {{ ucfirst($prog->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-gray-400" />
                                        <p class="font-medium">Belum ada program kerja yang terdata di sistem.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

    </div>

    <!-- MODAL POPUP INPUT / EDIT LINK GOOGLE DRIVE -->
    @if($showDriveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeDriveModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-primary-600 to-emerald-600 px-6 py-4 text-white flex items-center justify-between">
                        <h3 class="text-base font-bold flex items-center gap-2">
                            <x-heroicon-o-folder-arrow-down class="w-5 h-5 text-white" />
                            Tautkan Link Google Drive Dokumentasi
                        </h3>
                        <button type="button" wire:click="closeDriveModal" class="text-white/80 hover:text-white transition">
                            <x-heroicon-m-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                Program Kerja
                            </label>
                            <p class="text-sm font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                {{ $modalProgramName }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Tautan URL Google Drive (Folder / LPJ / Foto Kegiatan / Dokumen Pendukung)
                            </label>
                            <div class="relative">
                                <input type="url" 
                                       wire:model="modalDriveUrl"
                                       placeholder="https://drive.google.com/drive/folders/... atau https://docs.google.com/..." 
                                       class="w-full text-xs rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-10">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <x-heroicon-o-link class="w-4 h-4 text-gray-400" />
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                                💡 Masukkan link Google Drive untuk folder dokumentasi foto, LPJ kegiatan, atau dokumen pendukung. Pastikan hak akses link Google Drive disetel ke <em>"Siapa saja yang memiliki link"</em> (Anyone with link).
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-col sm:flex-row justify-end gap-2 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="closeDriveModal" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                            Batal
                        </button>
                        <button type="button" wire:click="saveDriveModal"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-sm transition flex items-center justify-center gap-1.5">
                            <x-heroicon-m-check class="w-4 h-4" />
                            <span>Simpan Link Drive</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
