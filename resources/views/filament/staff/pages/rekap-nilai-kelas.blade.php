<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Bar -->
        <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pilih Kelas</label>
                    <select wire:model.live="selectedClassroomId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @foreach($availableClasses as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Tingkat {{ $c->tingkat }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tahun Ajaran</label>
                    <select wire:model.live="selectedAcademicYearId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @foreach($academicYears as $y)
                            <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_active ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Semester</label>
                    <select wire:model.live="selectedSemester" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs (Touch Friendly & Horizontally Scrollable on Mobile) -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 space-x-2 sm:space-x-4 overflow-x-auto pb-1 select-none">
            <button 
                wire:click="setTab('mapel')" 
                class="py-3 px-4 font-semibold text-sm border-b-2 transition-colors flex items-center space-x-2 {{ $activeTab === 'mapel' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <span>📚</span>
                <span>Rekap Nilai Guru Mapel</span>
                <span class="ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-800 rounded-full dark:bg-amber-900/50 dark:text-amber-300">{{ count($mapelReport) }} Siswa</span>
            </button>

            <button 
                wire:click="setTab('tahfidz')" 
                class="py-3 px-4 font-semibold text-sm border-b-2 transition-colors flex items-center space-x-2 {{ $activeTab === 'tahfidz' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <span>📖</span>
                <span>Rekap Capaian Guru Tahfidz</span>
                <span class="ml-2 px-2 py-0.5 text-xs bg-emerald-100 text-emerald-800 rounded-full dark:bg-emerald-900/50 dark:text-emerald-300">{{ count($tahfidzReport) }} Siswa</span>
            </button>

            <button 
                wire:click="setTab('input_ujian')" 
                class="py-3 px-4 font-semibold text-sm border-b-2 transition-colors flex items-center space-x-2 {{ $activeTab === 'input_ujian' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <span>✍️</span>
                <span>Input Nilai Ujian Siswa</span>
                <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900/50 dark:text-blue-300">Wali Kelas Entry</span>
            </button>
        </div>

        <!-- TAB 1: REKAP NILAI GURU MAPEL -->
        @if($activeTab === 'mapel')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/30">
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100">Rekap Nilai Siswa per Mata Pelajaran</h3>
                        <p class="text-xs text-gray-500">Dihimpun dari penilaian harian guru mapel dan nilai ujian semester.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-md font-medium">
                        Kelas: {{ $selectedClass?->name ?? '-' }} ({{ $selectedSemester }})
                    </span>
                </div>

                @if(count($mapelReport) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="px-3 py-3 text-center w-12">Rank</th>
                                    <th class="px-3 py-3 w-28">NIS</th>
                                    <th class="px-4 py-3 min-w-[180px]">Nama Siswa</th>
                                    @foreach($subjects as $subject)
                                        <th class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700" title="{{ $subject->name }}">
                                            <div class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[100px]">{{ $subject->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-normal">Harian | Ujian</div>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-center border-l-2 border-gray-200 dark:border-gray-600 font-extrabold bg-amber-50/60 dark:bg-amber-950/20">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($mapelReport as $index => $row)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                        <td class="px-3 py-3 text-center font-bold {{ $index < 3 ? 'text-amber-600 font-black' : 'text-gray-500' }}">
                                            @if($index === 0) 🥇 1
                                            @elseif($index === 1) 🥈 2
                                            @elseif($index === 2) 🥉 3
                                            @else {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $row['student']->nis }}</td>
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $row['student']->name }}</td>
                                        @foreach($subjects as $subject)
                                            @php
                                                $sc = $row['scores'][$subject->id] ?? null;
                                            @endphp
                                            <td class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700">
                                                @if($sc && $sc['final'] !== null)
                                                    <div class="font-bold {{ $sc['final'] >= 75 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                        {{ $sc['final'] }}
                                                    </div>
                                                    <div class="text-[10px] text-gray-400">
                                                        {{ $sc['harian'] ?? '-' }} | {{ $sc['ujian'] ?? '-' }}
                                                    </div>
                                                @else
                                                    <span class="text-gray-300 dark:text-gray-600">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 text-center font-extrabold border-l-2 border-gray-200 dark:border-gray-600 bg-amber-50/40 dark:bg-amber-950/20 text-gray-900 dark:text-white">
                                            <span class="inline-block px-2.5 py-1 rounded-md text-xs font-black {{ $row['overall_average'] >= 75 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200' }}">
                                                {{ $row['overall_average'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500">
                        <div class="text-4xl mb-3">📋</div>
                        <p class="font-medium">Belum ada data nilai untuk kelas dan tahun ajaran ini.</p>
                        <p class="text-xs text-gray-400 mt-1">Gunakan tab <b>Input Nilai Ujian Siswa</b> untuk menginput nilai ujian langsung.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 2: REKAP NILAI & CAPAIAN TAHFIDZ -->
        @if($activeTab === 'tahfidz')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/30">
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100">Rekap Capaian & Mutu Al-Qur'an Siswa</h3>
                        <p class="text-xs text-gray-500">Dihimpun dari inputan setoran hafalan (Ziyadah & Murajaah) serta penilaian mutu bacaan guru Al-Qur'an.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md font-medium">
                        Kelas: {{ $selectedClass?->name ?? '-' }}
                    </span>
                </div>

                @if(count($tahfidzReport) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700/60 uppercase font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="px-3 py-3 text-center w-12">No</th>
                                    <th class="px-3 py-3 w-28">NIS</th>
                                    <th class="px-4 py-3 min-w-[160px]">Nama Siswa</th>
                                    <th class="px-3 py-3 text-center">Total Ziyadah</th>
                                    <th class="px-3 py-3 text-center">Total Murajaah</th>
                                    <th class="px-4 py-3 min-w-[150px]">Capaian Terakhir</th>
                                    <th class="px-3 py-3 text-center">Skor Mutu Bacaan</th>
                                    <th class="px-4 py-3 min-w-[140px]">Guru Pengampu</th>
                                    <th class="px-4 py-3 min-w-[200px]">Catatan Perkembangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($tahfidzReport as $idx => $tRow)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                        <td class="px-3 py-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $tRow['student']->nis }}</td>
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $tRow['student']->name }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                {{ $tRow['total_ziyadah'] }}x
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                                {{ $tRow['total_murajaah'] }}x
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($tRow['last_surah'] !== '-')
                                                <div class="font-bold text-gray-800 dark:text-gray-200">
                                                    Juz {{ $tRow['last_juz'] }}: {{ $tRow['last_surah'] }} (Ayat {{ $tRow['last_ayah'] }})
                                                </div>
                                                <div class="text-[10px] text-gray-400">{{ $tRow['last_date'] }}</div>
                                            @else
                                                <span class="text-gray-400 italic">Belum ada setoran</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if($tRow['quality_scale'] !== null)
                                                <span class="inline-block px-2.5 py-1 rounded-md text-xs font-bold {{ $tRow['quality_scale'] >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $tRow['quality_scale'] }} ({{ ucfirst($tRow['quality_program']) }})
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tRow['teacher_name'] }}</td>
                                        <td class="px-4 py-3 text-gray-500 text-xs italic">{{ $tRow['catatan'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500">
                        <div class="text-4xl mb-3">📖</div>
                        <p class="font-medium">Belum ada siswa di kelas ini.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 3: INPUT NILAI UJIAN SISWA -->
        @if($activeTab === 'input_ujian')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Input Nilai Ujian Siswa (Kolektif)</h3>
                        <p class="text-xs text-gray-500">Wali kelas dapat menginput atau memperbarui nilai ujian semester untuk seluruh siswa di kelas.</p>
                    </div>

                    <div class="w-full md:w-72">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Mata Pelajaran yang Diinput</label>
                        <select wire:model.live="inputSubjectId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-amber-500 focus:ring-amber-500">
                            @foreach($subjects as $sb)
                                <option value="{{ $sb->id }}">{{ $sb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(count($students) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 uppercase text-xs font-bold text-gray-600 dark:text-gray-300 border-b dark:border-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">No</th>
                                    <th class="px-4 py-3 w-32">NIS</th>
                                    <th class="px-6 py-3">Nama Siswa</th>
                                    <th class="px-6 py-3 w-56 text-center">Nilai Ujian (Skala 0 - 100)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($students as $idx => $st)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-center text-gray-500">{{ $idx + 1 }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $st->nis }}</td>
                                        <td class="px-6 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ $st->name }}</td>
                                        <td class="px-6 py-3 text-center">
                                            <input 
                                                type="number" 
                                                min="0" 
                                                max="100" 
                                                step="0.5"
                                                wire:model.defer="examScores.{{ $st->id }}"
                                                placeholder="0 - 100"
                                                class="w-32 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 font-bold focus:border-amber-500 focus:ring-amber-500"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end pt-4 border-t dark:border-gray-700">
                        <button 
                            type="button"
                            wire:click="saveExamScores"
                            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-sm flex items-center space-x-2 transition-colors">
                            <span>💾 Simpan Semua Nilai Ujian</span>
                        </button>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500">
                        Tidak ada siswa pada kelas ini.
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
