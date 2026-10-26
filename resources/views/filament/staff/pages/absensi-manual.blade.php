<x-filament-panels::page>
<div class="space-y-6" wire:poll.30s="loadAttendance">

    {{-- ===== HEADER ALERT ===== --}}
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 flex gap-3 items-start">
        <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M3 12a9 9 0 1118 0A9 9 0 013 12z"/>
        </svg>
        <div>
            <p class="font-bold text-amber-900">Mode Input Absensi Manual</p>
            <p class="text-sm text-amber-700">Gunakan halaman ini jika alat Hikvision sedang mati/bermasalah, atau untuk memasukkan data izin/sakit/alpa/halangan siswi secara manual.</p>
        </div>
    </div>

    {{-- ===== FILTER BAR ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

            {{-- Tanggal --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">📅 Tanggal</label>
                <input type="date" wire:model.live="selectedDate"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500">
            </div>

            {{-- Tipe Absen --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">⏰ Tipe Absen</label>
                <select wire:model.live="tipeAbsen"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="masuk">🏫 Absen Masuk (Pagi)</option>
                    <option value="sholat_dhuhur">🕌 Sholat Dhuhur</option>
                    <option value="sholat_ashar">🌅 Sholat Ashar</option>
                    <option value="pulang">🏠 Absen Pulang</option>
                </select>
            </div>

            {{-- Kelas --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">🏫 Kelas</label>
                <select wire:model.live="selectedClassroomId"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tombol Semua Hadir --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">&nbsp;</label>
                <button wire:click="tandaiSemuaHadir" type="button"
                    class="w-full bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-semibold text-sm py-2 px-4 rounded-lg border border-emerald-200 dark:border-emerald-700 transition-colors">
                    ✅ Tandai Semua Hadir
                </button>
            </div>
        </div>
    </div>

    {{-- ===== TABEL ABSENSI ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

        {{-- Table Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Daftar Presensi —
                    <span wire:loading.class="opacity-50">{{ $classrooms->firstWhere('id', $selectedClassroomId)?->name ?? '—' }}</span>
                </h3>
                <p class="text-sm text-gray-500">{{ count($students) }} siswa terdaftar</p>
            </div>
            {{-- Status Legend --}}
            <div class="hidden md:flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span> Hadir</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-400 inline-block"></span> Izin</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Sakit</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Alpa</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-pink-400 inline-block"></span> Halangan</span>
            </div>
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading wire:target="selectedClassroomId,tipeAbsen,selectedDate" class="px-6 py-3 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            Memuat data siswa...
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider w-8">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Nama Siswa</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider w-8">L/P</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($students as $i => $student)
                        @php
                            $currentStatus = $attendanceData[$student->id] ?? null;
                            $isPerempuan = $student->jenis_kelamin === 'P';
                            $isSholat = in_array($tipeAbsen, ['sholat_dhuhur', 'sholat_ashar']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors
                            {{ $currentStatus === 'alpa'     ? 'bg-red-50 dark:bg-red-900/10'    : '' }}
                            {{ $currentStatus === 'halangan' ? 'bg-pink-50 dark:bg-pink-900/10'  : '' }}
                        ">
                            <td class="px-6 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold
                                        {{ $isPerempuan ? 'bg-pink-400' : 'bg-blue-400' }}">
                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $student->name }}</p>
                                        <p class="text-xs text-gray-400">NIS: {{ $student->nis }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                    {{ $isPerempuan ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $isPerempuan ? '♀' : '♂' }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-2 justify-center">

                                    {{-- HADIR --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="hadir" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-green-500 peer-checked:border-green-500 peer-checked:text-white
                                            border-gray-200 text-gray-600 hover:border-green-400 hover:text-green-600">
                                            ✅ Hadir
                                        </span>
                                    </label>

                                    {{-- IZIN --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="izin" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-blue-500 peer-checked:border-blue-500 peer-checked:text-white
                                            border-gray-200 text-gray-600 hover:border-blue-400 hover:text-blue-600">
                                            📋 Izin
                                        </span>
                                    </label>

                                    {{-- SAKIT --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="sakit" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-amber-500 peer-checked:border-amber-500 peer-checked:text-white
                                            border-gray-200 text-gray-600 hover:border-amber-400 hover:text-amber-600">
                                            🤒 Sakit
                                        </span>
                                    </label>

                                    {{-- ALPA --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="alpa" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-red-500 peer-checked:border-red-500 peer-checked:text-white
                                            border-gray-200 text-gray-600 hover:border-red-400 hover:text-red-600">
                                            ❌ Alpa
                                        </span>
                                    </label>

                                    {{-- HALANGAN (hanya siswi perempuan, saat waktu sholat) --}}
                                    @if($isPerempuan && $isSholat)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="halangan" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-pink-400 peer-checked:border-pink-400 peer-checked:text-white
                                            border-gray-200 text-gray-600 hover:border-pink-400 hover:text-pink-600">
                                            🌸 Halangan
                                        </span>
                                    </label>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="font-medium">Belum ada siswa di kelas ini</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Save Button --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex justify-between items-center">
            <p class="text-sm text-gray-500">
                Data yang belum dipilih tidak akan disimpan.
            </p>
            <button wire:click="simpan" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white font-bold py-2.5 px-6 rounded-xl transition-colors shadow-sm">
                <svg wire:loading wire:target="simpan" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <svg wire:loading.remove wire:target="simpan" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Absensi
            </button>
        </div>
    </div>

    {{-- ===== INFO HIKVISION STATUS ===== --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
        <p class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">ℹ️ Informasi Sistem Absensi</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-blue-800 dark:text-blue-400">
            <div class="flex items-start gap-2">
                <span class="font-bold">🔐 Hikvision DS-K1T343MFX</span>
                <span>— Absensi utama via fingerprint/NFC/wajah (otomatis masuk ke sistem)</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-bold">✏️ Halaman ini</span>
                <span>— Input manual saat alat mati/bermasalah, atau untuk izin/sakit/alpa/halangan</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-bold">🌸 Halangan siswi</span>
                <span>— Hanya muncul saat tipe Sholat Dhuhur/Ashar dan hanya untuk siswi (♀)</span>
            </div>
        </div>
    </div>

</div>
</x-filament-panels::page>
