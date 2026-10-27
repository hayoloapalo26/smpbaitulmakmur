<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter & Header -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Filter Kelas</label>
                    <select wire:model.live="selectedClassroomId" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm font-semibold focus:border-amber-500 focus:ring-amber-500 min-w-[200px]">
                        @foreach($availableClasses as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }} (Tingkat {{ $cls->tingkat }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-5 text-xs text-gray-500">
                    Total Siswa: <span class="font-bold text-gray-800 dark:text-gray-200">{{ count($report) }}</span>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button 
                    type="button"
                    wire:click="openAddModal"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-sm shadow-sm flex items-center space-x-2 transition-colors">
                    <span>⭐</span>
                    <span>Input Penilaian Guru Kelas</span>
                </button>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Karakter -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50 dark:bg-gray-700/30">
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-gray-100">Rekapitulasi Karakter Siswa</h3>
                    <p class="text-xs text-gray-500">Dihimpun dari penilaian guru kelas, guru piket, dan catatan checklist orang tua.</p>
                </div>
                <div class="flex flex-wrap gap-1.5 text-xs">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                        👨‍🏫 Guru Kelas
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        👮 Guru Piket
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                        👨‍👩‍👧 Orang Tua
                    </span>
                </div>
            </div>

            @if(count($report) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-600 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300 font-bold border-b dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 w-32">NIS</th>
                                <th class="px-6 py-3 min-w-[200px]">Nama Siswa</th>
                                <th class="px-4 py-3 text-center bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300">Guru Kelas</th>
                                <th class="px-4 py-3 text-center bg-blue-50/50 dark:bg-blue-950/20 text-blue-800 dark:text-blue-300">Guru Piket</th>
                                <th class="px-4 py-3 text-center bg-purple-50/50 dark:bg-purple-950/20 text-purple-800 dark:text-purple-300">Orang Tua</th>
                                <th class="px-4 py-3 text-center font-extrabold bg-amber-50/60 dark:bg-amber-950/30">Total Skor</th>
                                <th class="px-4 py-3 text-center">Predikat</th>
                                <th class="px-4 py-3 text-center w-28">Aksi</th>
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
                                    <td class="px-4 py-3 text-center font-bold bg-emerald-50/30 dark:bg-emerald-950/10 {{ $row['guru_kelas'] < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                        {{ $row['guru_kelas'] > 0 ? '+'.$row['guru_kelas'] : $row['guru_kelas'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold bg-blue-50/30 dark:bg-blue-950/10 {{ $row['guru_piket'] < 0 ? 'text-red-500' : 'text-blue-600' }}">
                                        {{ $row['guru_piket'] > 0 ? '+'.$row['guru_piket'] : $row['guru_piket'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold bg-purple-50/30 dark:bg-purple-950/10 {{ $row['orang_tua'] < 0 ? 'text-red-500' : 'text-purple-600' }}">
                                        {{ $row['orang_tua'] > 0 ? '+'.$row['orang_tua'] : $row['orang_tua'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-black bg-amber-50/40 dark:bg-amber-950/20 text-gray-900 dark:text-white">
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-extrabold {{ $row['total'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($row['total'] >= 65 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ $row['total'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-md {{ $row['predikat']['color'] === 'success' ? 'bg-emerald-100 text-emerald-800' : ($row['predikat']['color'] === 'info' ? 'bg-blue-100 text-blue-800' : ($row['predikat']['color'] === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')) }}">
                                            {{ $row['predikat']['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            <button 
                                                type="button"
                                                wire:click="openDetailModal({{ $row['id'] }})"
                                                class="px-2.5 py-1 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition-colors"
                                                title="Lihat Rincian Log">
                                                📜 Rincian ({{ $row['logs_count'] }})
                                            </button>
                                            <button 
                                                type="button"
                                                wire:click="openAddModal({{ $row['id'] }})"
                                                class="px-2 py-1 text-xs font-semibold rounded bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-900/50 dark:hover:bg-amber-900 dark:text-amber-200 transition-colors"
                                                title="Tambah Poin Karakter">
                                                + Poin
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500">
                    <div class="text-4xl mb-3">⭐</div>
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
                                Riwayat Penilaian Karakter: {{ $selectedStudentForDetail->name }}
                            </h3>
                            <p class="text-xs text-gray-500 font-mono">NIS: {{ $selectedStudentForDetail->nis }} | Skor Saat Ini: <span class="font-bold text-amber-600">{{ $selectedStudentForDetail->character_score }}</span></p>
                        </div>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-4 flex-1">
                        @if($selectedStudentForDetail->characterScoreLogs->isNotEmpty())
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($selectedStudentForDetail->characterScoreLogs as $log)
                                    <div class="py-3 flex items-start justify-between">
                                        <div class="space-y-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $log->source === 'guru_kelas' ? 'bg-emerald-100 text-emerald-800' : ($log->source === 'guru_piket' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                                    {{ match($log->source) {
                                                        'guru_kelas' => '👨‍🏫 Guru Kelas',
                                                        'guru_piket' => '👮 Guru Piket',
                                                        'orang_tua'  => '👨‍👩‍👧 Orang Tua',
                                                        default      => $log->source
                                                    } }}
                                                </span>
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $log->category }}</span>
                                                <span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($log->tanggal)->format('d M Y') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $log->description }}</p>
                                        </div>
                                        <div class="text-right pl-4">
                                            <span class="font-bold text-sm {{ $log->point_change >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
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

        <!-- MODAL TAMBAH PENILAIAN GURU KELAS -->
        @if($showAddModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/40">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">
                            Input Penilaian Karakter oleh Guru Kelas
                        </h3>
                        <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Pilih Siswa</label>
                            <select wire:model="newStudentId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">-- Pilih Siswa --</option>
                                @foreach($classStudents as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->nis }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Kategori Sikap / Karakter</label>
                            <select wire:model="newCategory" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="Kedisiplinan">Kedisiplinan & Kerapian</option>
                                <option value="Adab & Akhlak">Adab & Sopan Santun</option>
                                <option value="Tanggung Jawab">Tanggung Jawab & Tugas</option>
                                <option value="Kepedulian Sosial">Kepedulian & Gotong Royong</option>
                                <option value="Kejujuran">Kejujuran & Integritas</option>
                                <option value="Ibadah Harian">Keseriusan Ibadah di Kelas</option>
                                <option value="Pelanggaran Disiplin">Pelanggaran Disiplin Kelas</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Perubahan Poin (+ / -)</label>
                            <select wire:model="newPointChange" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500 font-bold">
                                <option value="10">🌟 Sangat Baik (+10 Poin)</option>
                                <option value="5">👍 Baik (+5 Poin)</option>
                                <option value="2">✨ Cukup Baik (+2 Poin)</option>
                                <option value="-5">⚠️ Pelanggaran Ringan (-5 Poin)</option>
                                <option value="-10">⛔ Pelanggaran Sedang (-10 Poin)</option>
                                <option value="-20">🚨 Pelanggaran Berat (-20 Poin)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Uraian / Deskripsi Tindakan Siswa</label>
                            <textarea 
                                wire:model="newDescription" 
                                rows="3" 
                                placeholder="Contoh: Menginisiasi kebersihan pojok baca kelas tanpa disuruh..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end space-x-2 bg-gray-50/50 dark:bg-gray-700/20">
                        <button wire:click="closeAddModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-semibold transition-colors">
                            Batal
                        </button>
                        <button wire:click="saveCharacterLog" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-bold shadow-sm transition-colors">
                            Simpan Penilaian
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
