<x-filament-panels::page>
    @php
        $data = $this->monitoring_data;
        $rows = $data['rows'];
    @endphp

    <div class="space-y-6">
        <!-- TOP BANNER -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 text-white shadow-lg">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-xs font-semibold mb-2">
                        <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                        Monitoring Administrasi Pembelajaran
                    </div>
                    <h2 class="text-2xl font-black tracking-tight">Monitoring Jurnal Mengajar & Presensi Guru</h2>
                    <p class="text-orange-100 text-sm mt-1 max-w-2xl">
                        Pantau kepatuhan pengisian jurnal mengajar harian oleh seluruh guru mapel sebagai bukti otentik kehadiran mengajar di kelas.
                    </p>
                </div>
                <div class="text-right bg-white/10 backdrop-blur-sm px-4 py-3 rounded-xl border border-white/20">
                    <div class="text-xs uppercase tracking-wider text-orange-100 font-semibold">Tanggal Pantauan</div>
                    <div class="text-lg font-black text-white">{{ $data['date_formatted'] }}</div>
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Guru -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-950/50 flex items-center justify-center text-orange-600 dark:text-orange-400">
                    <x-heroicon-o-users class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Guru Mapel</div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $data['total_guru'] }} <span class="text-xs font-normal text-gray-500">Guru</span></div>
                </div>
            </div>

            <!-- Sudah Mengisi (Hadir) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-emerald-100 dark:border-emerald-900/40 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <x-heroicon-o-check-circle class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sudah Terisi (Hadir)</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $data['total_sudah'] }} <span class="text-xs font-normal text-gray-500">Guru</span></div>
                </div>
            </div>

            <!-- Belum Mengisi -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-amber-100 dark:border-amber-900/40 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <x-heroicon-o-clock class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Belum Mengisi Hari Ini</div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{{ $data['total_belum'] }} <span class="text-xs font-normal text-gray-500">Guru</span></div>
                </div>
            </div>

            <!-- Kepatuhan -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-blue-100 dark:border-blue-900/40 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <x-heroicon-o-chart-pie class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Tingkat Kepatuhan</div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-0.5">{{ $data['persentase'] }}%</div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Picker -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pilih Tanggal:</span>
                    <input 
                        type="date" 
                        wire:model.live="selectedDate" 
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs font-semibold focus:border-orange-500 focus:ring-orange-500 text-gray-800 dark:text-gray-100 py-1.5"
                    />
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 p-1 rounded-lg text-xs font-semibold">
                    <button 
                        wire:click="$set('statusFilter', 'all')" 
                        class="px-3 py-1 rounded-md transition-all {{ $statusFilter === 'all' ? 'bg-white dark:bg-gray-800 text-orange-600 shadow-sm font-bold' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">
                        Semua Guru
                    </button>
                    <button 
                        wire:click="$set('statusFilter', 'sudah')" 
                        class="px-3 py-1 rounded-md transition-all {{ $statusFilter === 'sudah' ? 'bg-white dark:bg-gray-800 text-emerald-600 shadow-sm font-bold' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">
                        Sudah Terisi ({{ $data['total_sudah'] }})
                    </button>
                    <button 
                        wire:click="$set('statusFilter', 'belum')" 
                        class="px-3 py-1 rounded-md transition-all {{ $statusFilter === 'belum' ? 'bg-white dark:bg-gray-800 text-amber-600 shadow-sm font-bold' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">
                        Belum Terisi ({{ $data['total_belum'] }})
                    </button>
                </div>
            </div>

            <!-- Search -->
            <div class="relative w-full md:w-64">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama guru..." 
                    class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:border-orange-500 focus:ring-orange-500"
                />
                <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            </div>
        </div>

        <!-- MAIN MONITORING TABLE -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/20">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-clipboard-document-check class="w-5 h-5 text-orange-500" />
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm">Status Input Jurnal Mengajar Hari Ini</h3>
                </div>
                <span class="text-xs text-gray-500">
                    Menampilkan <strong class="text-orange-600">{{ count($rows) }}</strong> guru mapel
                </span>
            </div>

            @if(count($rows) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3">Nama Guru Pengajar</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="px-4 py-3">Kelas / Rombel</th>
                                <th class="px-4 py-3 text-center">Status Hari Ini</th>
                                <th class="px-4 py-3 text-center">Total Riwayat</th>
                                <th class="px-4 py-3 text-center w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($rows as $index => $row)
                                <tr class="hover:bg-orange-50/30 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-4 py-3.5 text-center font-semibold text-gray-500">{{ $index + 1 }}</td>
                                    
                                    <!-- Guru -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-orange-500 to-amber-400 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                                {{ strtoupper(substr($row['teacher']->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white">{{ $row['teacher']->name }}</div>
                                                <div class="text-[11px] text-gray-500">{{ $row['teacher']->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Mapel -->
                                    <td class="px-4 py-3.5">
                                        <span class="inline-block px-2.5 py-1 rounded-md bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300 font-semibold border border-orange-200 dark:border-orange-800/50">
                                            {{ $row['subjects'] }}
                                        </span>
                                    </td>

                                    <!-- Kelas -->
                                    <td class="px-4 py-3.5">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">
                                            {{ $row['classes'] }}
                                        </span>
                                    </td>

                                    <!-- Status Hari Ini -->
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row['leave_info'])
                                            <div class="inline-flex flex-col items-center">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 font-bold border border-blue-300 dark:border-blue-700 text-xs shadow-sm">
                                                    <x-heroicon-m-calendar-days class="w-4 h-4 text-blue-600" />
                                                    Izin ACC ({{ $row['leave_info']->waktu_izin_label }})
                                                </span>
                                                <span class="text-[10px] text-blue-600 font-medium mt-0.5" title="{{ $row['leave_info']->alasan }}">
                                                    {{ \Illuminate\Support\Str::limit($row['leave_info']->alasan, 25) }}
                                                </span>
                                            </div>
                                        @elseif($row['is_filled'])
                                            <div class="inline-flex flex-col items-center">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold border border-emerald-300 dark:border-emerald-700 text-xs shadow-sm">
                                                    <x-heroicon-m-check-circle class="w-4 h-4 text-emerald-600" />
                                                    Sudah Terisi (Hadir)
                                                </span>
                                                <span class="text-[10px] text-emerald-600 font-medium mt-0.5">
                                                    {{ $row['journals_count'] }} jam pelajaran terisi
                                                </span>
                                            </div>
                                        @else
                                            <div class="inline-flex flex-col items-center">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-bold border border-amber-300 dark:border-amber-700 text-xs">
                                                    <x-heroicon-m-exclamation-circle class="w-4 h-4 text-amber-600" />
                                                    Belum Terisi
                                                </span>
                                                <span class="text-[10px] text-gray-400 mt-0.5">
                                                    Belum ada jurnal hari ini
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Total History -->
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full">
                                            {{ $row['total_history'] }} Jurnal
                                        </span>
                                    </td>

                                    <!-- Aksi: Detail / History -->
                                    <td class="px-4 py-3.5 text-center">
                                        <button 
                                            wire:click="openDetail({{ $row['teacher']->id }})" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs shadow-sm hover:shadow transition-all">
                                            <x-heroicon-m-clock class="w-4 h-4" />
                                            <span>Rekap History</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <x-heroicon-o-face-frown class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <h4 class="text-base font-bold text-gray-700 dark:text-gray-200">Tidak Ada Data Guru</h4>
                    <p class="text-xs text-gray-500 mt-1">Tidak ditemukan guru yang cocok dengan kata kunci atau filter pencarian.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- DETAIL MODAL: REKAP HISTORY PER HARINYA -->
    @if($showDetailModal)
        @php
            $historyData = $this->selected_teacher_journals;
            $teacher = $historyData['teacher'];
            $entries = $historyData['entries'];
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-orange-500 to-amber-500 text-white flex items-center justify-between">
                    <div>
                        <div class="inline-flex items-center gap-1.5 text-xs text-orange-100 uppercase tracking-wider font-semibold">
                            <x-heroicon-m-calendar-days class="w-4 h-4" />
                            Rekap History Jurnal Mengajar
                        </div>
                        <h3 class="text-xl font-black mt-1">{{ $teacher?->name }}</h3>
                        <p class="text-xs text-orange-100 mt-0.5">
                            Total Riwayat: <strong class="text-white">{{ count($entries) }} Jurnal Pembelajaran</strong> tercatat di sistem
                        </p>
                    </div>
                    <button 
                        wire:click="closeDetail" 
                        class="p-2 rounded-xl bg-white/20 hover:bg-white/30 text-white transition-all">
                        <x-heroicon-m-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    @if(count($entries) > 0)
                        <div class="space-y-4">
                            @foreach($entries as $item)
                                @php
                                    $j = $item['journal'];
                                    $attendance = $item['student_attendance'];
                                @endphp
                                <div class="bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-orange-300 transition-all">
                                    <!-- Header Row -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 mb-3 border-b border-gray-200 dark:border-gray-700 gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-md bg-orange-100 dark:bg-orange-950 text-orange-700 dark:text-orange-300 font-black text-xs">
                                                {{ \App\Models\Schedule::getJamLabelWithTime($j->jam_ke) }}
                                            </span>
                                            <span class="font-bold text-gray-800 dark:text-gray-100 text-sm">
                                                {{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('l, d F Y') }}
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-semibold">
                                                {{ $j->classroom?->name ?? 'Kelas' }}
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-semibold">
                                                {{ $j->subject?->name ?? 'Mapel' }}
                                            </span>
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-full border border-emerald-200">
                                            <x-heroicon-m-check-circle class="w-4 h-4" />
                                            Guru Hadir Mengajar
                                        </span>
                                    </div>

                                    <!-- Materi Pembelajaran & TP dari RPPM -->
                                    <div class="mb-3 p-3 rounded-lg bg-orange-50/70 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800/60">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <x-heroicon-m-bookmark class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                                <span class="font-bold text-gray-900 dark:text-white text-xs">Materi: {{ $j->materi ?: ($j->rencana_kegiatan_guru ?: '-') }}</span>
                                            </div>
                                            @if($j->rppm)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-900/60 px-2 py-0.5 rounded border border-orange-300 dark:border-orange-700">
                                                    <x-heroicon-m-document-text class="w-3.5 h-3.5" />
                                                    RPPM: {{ $j->rppm->topik }}
                                                </span>
                                            @endif
                                        </div>

                                        @php
                                            $tps = $j->selected_tps;
                                        @endphp
                                        @if($tps->isNotEmpty())
                                            <div class="mt-2 pt-2 border-t border-orange-200/60 dark:border-orange-800/40">
                                                <div class="text-[10px] font-bold uppercase tracking-wider text-orange-700 dark:text-orange-400 mb-1 flex items-center gap-1">
                                                    <span>🎯 Tujuan Pembelajaran (TP) yang Dicapai:</span>
                                                </div>
                                                <div class="space-y-1">
                                                    @foreach($tps as $tp)
                                                        <div class="flex items-start gap-1.5 text-[11px] text-gray-700 dark:text-gray-200">
                                                            <span class="font-bold text-orange-600 dark:text-orange-400 shrink-0">[{{ $tp->kode_tp }}]</span>
                                                            <span>{{ $tp->deskripsi_tp }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Content Details Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold text-gray-500 uppercase text-[10px] tracking-wider block">Rencana Kegiatan Guru</span>
                                                <div class="text-gray-800 dark:text-gray-200 font-medium mt-0.5 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                                    {{ $j->rencana_kegiatan_guru ?: '-' }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-500 uppercase text-[10px] tracking-wider block">Kegiatan Siswa</span>
                                                <div class="text-gray-800 dark:text-gray-200 font-medium mt-0.5 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                                    {{ $j->kegiatan_siswa ?: '-' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold text-gray-500 uppercase text-[10px] tracking-wider block">Refleksi Guru</span>
                                                <div class="text-gray-800 dark:text-gray-200 font-medium mt-0.5 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                                    {{ $j->refleksi_guru ?: '-' }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-500 uppercase text-[10px] tracking-wider block">Tindak Lanjut</span>
                                                <div class="text-gray-800 dark:text-gray-200 font-medium mt-0.5 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                                    {{ $j->tindak_lanjut ?: '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Presensi Siswa Summary -->
                                    @if($attendance['total'] > 0)
                                        <div class="mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between text-xs gap-2">
                                            <span class="font-bold text-gray-600 dark:text-gray-300">Presensi Siswa:</span>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">Hadir: {{ $attendance['hadir'] }}</span>
                                                <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">Izin: {{ $attendance['izin'] }}</span>
                                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Sakit: {{ $attendance['sakit'] }}</span>
                                                <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-semibold">Alpa: {{ $attendance['alpa'] }}</span>
                                                <span class="text-gray-400 font-medium">({{ $attendance['total'] }} Siswa)</span>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Catatan Karakter Siswa -->
                                    @if(!empty($j->catatan_karakter) && is_array($j->catatan_karakter) && count($j->catatan_karakter) > 0)
                                        <div class="mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                                            <span class="font-bold text-gray-700 dark:text-gray-300 text-xs block mb-1.5 flex items-center gap-1.5">
                                                <x-heroicon-m-academic-cap class="w-4 h-4 text-orange-500" />
                                                Catatan Karakter Siswa di Jam Ini:
                                            </span>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                @foreach($j->catatan_karakter as $char)
                                                    @php
                                                        $st = \App\Models\Student::find($char['student_id'] ?? null);
                                                        $isB = ($char['type'] ?? 'baik') === 'baik';
                                                    @endphp
                                                    <div class="p-2.5 rounded-lg border {{ $isB ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800' : 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800' }}">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <strong class="font-bold text-gray-900 dark:text-gray-100">{{ $st?->name ?? 'Siswa' }}</strong>
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $isB ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                                                                {{ $isB ? '⭐ Baik (+1)' : '⚠️ Kurang Baik (-1)' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">
                                                            <span class="text-gray-400">[{{ $char['kategori'] ?? '-' }}]</span> {{ $char['catatan'] ?? '-' }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <h4 class="text-base font-bold text-gray-700 dark:text-gray-200">Belum Ada Riwayat Jurnal</h4>
                            <p class="text-xs text-gray-500 mt-1">Guru ini belum pernah menginput jurnal pembelajaran di sistem.</p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button 
                        wire:click="closeDetail" 
                        class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-100 font-bold text-xs transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
