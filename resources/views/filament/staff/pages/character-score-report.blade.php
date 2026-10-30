<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header & Info Banner -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rekapitulasi Skor Karakter Siswa</h2>
                    @if($isRestrictedWaliKelas)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            👨‍🏫 Binaan Kelas Anda
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            👁️ Pemantauan Seluruh Kelas (Waka Kesiswaan & Kepsek)
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Rekapitulasi akumulasi poin karakter yang tercatat otomatis dari jurnal mengajar guru mapel, pantauan guru piket, dan pembiasaan orang tua.
                </p>
            </div>

            <!-- Ringkasan Statistik Kelas -->
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <div class="px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                    <span class="text-gray-500">Total Siswa:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200 ml-1">{{ count($report) }}</span>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                    <span class="text-amber-700 dark:text-amber-300">Rata-rata:</span>
                    <span class="font-bold text-amber-800 dark:text-amber-200 ml-1">{{ $avgScore }}</span>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800">
                    <span class="text-emerald-700 dark:text-emerald-300">Sangat Baik (A):</span>
                    <span class="font-bold text-emerald-800 dark:text-emerald-200 ml-1">{{ $countSangatBaik }}</span>
                </div>
                @if($countPerluBina > 0)
                    <div class="px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800">
                        <span class="text-rose-700 dark:text-rose-300">Perlu Bina:</span>
                        <span class="font-bold text-rose-800 dark:text-rose-200 ml-1">{{ $countPerluBina }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Filter Kelas -->
        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <label class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                    Pilihan Kelas:
                </label>

                @if($isRestrictedWaliKelas)
                    @if($availableClasses->count() > 1)
                        <select wire:model.live="selectedClassroomId" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm font-semibold focus:border-amber-500 focus:ring-amber-500 min-w-[200px]">
                            @foreach($availableClasses as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }} (Tingkat {{ $cls->tingkat }})</option>
                            @endforeach
                        </select>
                    @elseif($availableClasses->count() === 1)
                        <span class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold text-sm rounded-lg border border-gray-200 dark:border-gray-600">
                            {{ $availableClasses->first()->name }} (Tingkat {{ $availableClasses->first()->tingkat }})
                        </span>
                    @else
                        <span class="text-xs text-rose-600 font-medium">
                            ⚠️ Belum ada kelas yang ditetapkan untuk akun Anda sebagai wali kelas.
                        </span>
                    @endif
                @else
                    {{-- Waka Kesiswaan & Kepala Sekolah dapat memilih semua kelas --}}
                    <select wire:model.live="selectedClassroomId" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 min-w-[240px]">
                        @foreach($availableClasses as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }} (Tingkat {{ $cls->tingkat }})</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Legend Sumber Poin -->
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="text-gray-400">Sumber Poin:</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                    📖 Jurnal Mapel
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300">
                    👮 Guru Piket
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300">
                    👨‍👩‍👧 Orang Tua
                </span>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Karakter -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            @if(count($report) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-600 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 w-32">NIS</th>
                                <th class="px-6 py-3 min-w-[220px]">Nama Siswa</th>
                                <th class="px-4 py-3 text-center bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300">Jurnal Mapel</th>
                                <th class="px-4 py-3 text-center bg-blue-50/50 dark:bg-blue-950/20 text-blue-800 dark:text-blue-300">Guru Piket</th>
                                <th class="px-4 py-3 text-center bg-purple-50/50 dark:bg-purple-950/20 text-purple-800 dark:text-purple-300">Orang Tua</th>
                                <th class="px-4 py-3 text-center font-extrabold bg-amber-50/60 dark:bg-amber-950/30">Total Skor</th>
                                <th class="px-4 py-3 text-center">Predikat</th>
                                <th class="px-4 py-3 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($report as $index => $row)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $row['nis'] }}</td>
                                    <td class="px-6 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $row['name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold bg-emerald-50/30 dark:bg-emerald-950/10 {{ $row['guru_mapel'] < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                        {{ $row['guru_mapel'] > 0 ? '+'.$row['guru_mapel'] : $row['guru_mapel'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold bg-blue-50/30 dark:bg-blue-950/10 {{ $row['guru_piket'] < 0 ? 'text-red-500' : 'text-blue-600' }}">
                                        {{ $row['guru_piket'] > 0 ? '+'.$row['guru_piket'] : $row['guru_piket'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold bg-purple-50/30 dark:bg-purple-950/10 {{ $row['orang_tua'] < 0 ? 'text-red-500' : 'text-purple-600' }}">
                                        {{ $row['orang_tua'] > 0 ? '+'.$row['orang_tua'] : $row['orang_tua'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-black bg-amber-50/40 dark:bg-amber-950/20 text-gray-900 dark:text-white">
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-extrabold {{ $row['total'] >= 80 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200' : ($row['total'] >= 65 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200') }}">
                                            {{ $row['total'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-md {{ $row['predikat']['color'] === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200' : ($row['predikat']['color'] === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200' : ($row['predikat']['color'] === 'warning' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200')) }}">
                                            {{ $row['predikat']['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            type="button"
                                            wire:click="openDetailModal({{ $row['id'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition-colors shadow-sm"
                                            title="Lihat Rincian Riwayat Log">
                                            <span>📜 Rincian ({{ $row['logs_count'] }})</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500">
                    <div class="text-4xl mb-3">📊</div>
                    <p class="font-medium">Belum ada siswa pada kelas ini.</p>
                </div>
            @endif
        </div>

        <!-- MODAL RINCIAN LOG PENILAIAN KARAKTER -->
        @if($selectedStudentForDetail)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/40">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                                Riwayat Log Karakter: {{ $selectedStudentForDetail->name }}
                            </h3>
                            <p class="text-xs text-gray-500 font-mono">NIS: {{ $selectedStudentForDetail->nis }} | Skor Akumulasi: <span class="font-bold text-amber-600">{{ $selectedStudentForDetail->character_score }}</span></p>
                        </div>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-4 flex-1">
                        @if($selectedStudentForDetail->characterScoreLogs->isNotEmpty())
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($selectedStudentForDetail->characterScoreLogs as $log)
                                    <div class="py-3 flex items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center space-x-2 flex-wrap">
                                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ in_array($log->source, ['guru_kelas', 'guru_mapel']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : ($log->source === 'guru_piket' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300') }}">
                                                    {{ match($log->source) {
                                                        'guru_kelas', 'guru_mapel' => '👨‍🏫 Guru Mapel (Jurnal)',
                                                        'guru_piket'               => '👮 Guru Piket',
                                                        'orang_tua'                => '👨‍👩‍👧 Orang Tua',
                                                        default                    => $log->source
                                                    } }}
                                                </span>
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $log->category }}</span>
                                                <span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($log->tanggal)->format('d M Y') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $log->description }}</p>
                                        </div>
                                        <div class="text-right pl-4 whitespace-nowrap">
                                            <span class="font-bold text-sm {{ $log->point_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                                {{ $log->point_change >= 0 ? '+'.$log->point_change : $log->point_change }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-400 text-sm">
                                Belum ada riwayat catatan log untuk siswa ini.
                            </div>
                        @endif
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end bg-gray-50/50 dark:bg-gray-700/20">
                        <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
