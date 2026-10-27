<x-filament-panels::page>
<div class="space-y-4 sm:space-y-6" wire:poll.30s="loadAttendance">

    {{-- ===== HEADER ALERT ===== --}}
    <div class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 p-3.5 sm:p-4 flex gap-3 items-start">
        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M3 12a9 9 0 1118 0A9 9 0 013 12z"/>
        </svg>
        <div>
            <p class="font-bold text-xs sm:text-sm text-amber-900 dark:text-amber-200">Mode Input Presensi Kolektif</p>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">Gunakan halaman ini untuk memasukkan data izin/sakit/alpa/halangan siswa secara manual atau saat alat scanner bermasalah.</p>
        </div>
    </div>

    {{-- ===== FILTER BAR ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 items-end">

            {{-- Tanggal --}}
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">📅 Tanggal</label>
                <input type="date" wire:model.live="selectedDate"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-base sm:text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
            </div>

            {{-- Tipe Absen --}}
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">⏰ Tipe Absen</label>
                <select wire:model.live="tipeAbsen"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-base sm:text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                    <option value="masuk">🏫 Absen Masuk (Pagi)</option>
                    <option value="sholat_dhuhur">🕌 Sholat Dhuhur</option>
                    <option value="sholat_ashar">🌅 Sholat Ashar</option>
                    <option value="pulang">🏠 Absen Pulang</option>
                </select>
            </div>

            {{-- Kelas --}}
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">🏫 Kelas</label>
                <select wire:model.live="selectedClassroomId"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-base sm:text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tombol Semua Hadir --}}
            <div>
                <button wire:click="tandaiSemuaHadir" type="button"
                    class="w-full bg-emerald-100 hover:bg-emerald-200 active:scale-95 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-bold text-xs sm:text-sm py-2.5 px-4 rounded-xl border border-emerald-300 dark:border-emerald-700 transition-all shadow-2xs">
                    ✅ Tandai Semua Hadir
                </button>
            </div>
        </div>
    </div>

    {{-- ===== DAFTAR SISWA (DUAL VIEW: CARDS UNTUK MOBILE & TABEL UNTUK DESKTOP) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

        {{-- Table Header --}}
        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                    Daftar Presensi Kelas:
                    <span class="text-primary-600 dark:text-primary-400" wire:loading.class="opacity-50">
                        {{ $classrooms->firstWhere('id', $selectedClassroomId)?->name ?? '—' }}
                    </span>
                </h3>
                <p class="text-xs text-gray-500">{{ count($students) }} siswa terdaftar</p>
            </div>
            {{-- Status Legend --}}
            <div class="hidden lg:flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span> Hadir</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Izin</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> Sakit</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> Alpa</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-pink-400 inline-block"></span> Halangan</span>
            </div>
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading wire:target="selectedClassroomId,tipeAbsen,selectedDate" class="px-4 sm:px-6 py-3 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 text-xs sm:text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            Memuat data presensi siswa...
        </div>

        {{-- 1. MOBILE TOUCH CARDS VIEW (< md) --}}
        <div class="block md:hidden divide-y divide-gray-100 dark:divide-gray-700/60 p-2 space-y-2">
            @forelse($students as $i => $student)
                @php
                    $currentStatus = $attendanceData[$student->id] ?? null;
                    $isPerempuan = $student->jenis_kelamin === 'P';
                    $isSholat = in_array($tipeAbsen, ['sholat_dhuhur', 'sholat_ashar']);
                @endphp
                <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-800/50 space-y-2.5
                    {{ $currentStatus === 'alpa'     ? 'ring-1 ring-red-400/50 bg-red-50/40 dark:bg-red-950/20'    : '' }}
                    {{ $currentStatus === 'halangan' ? 'ring-1 ring-pink-400/50 bg-pink-50/40 dark:bg-pink-950/20'  : '' }}
                    {{ $currentStatus === 'hadir'    ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : '' }}
                ">
                    {{-- Student Info Header --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-black {{ $isPerempuan ? 'bg-pink-500' : 'bg-blue-500' }}">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-sm text-gray-900 dark:text-white leading-tight">{{ $student->name }}</p>
                                <p class="text-[11px] text-gray-400">NIS: {{ $student->nis }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isPerempuan ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $isPerempuan ? '♀ P' : '♂ L' }}
                        </span>
                    </div>

                    {{-- Touch-Friendly Segmented Status Buttons (Thumb optimized) --}}
                    <div class="grid {{ ($isPerempuan && $isSholat) ? 'grid-cols-5' : 'grid-cols-4' }} gap-1 pt-1">
                        {{-- HADIR --}}
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="attendanceData.{{ $student->id }}" value="hadir" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 px-1 text-[11px] font-bold rounded-lg border-2 text-center transition-all select-none
                                peer-checked:bg-emerald-600 peer-checked:border-emerald-600 peer-checked:text-white
                                border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400 active:scale-95">
                                Hadir
                            </span>
                        </label>

                        {{-- IZIN --}}
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="attendanceData.{{ $student->id }}" value="izin" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 px-1 text-[11px] font-bold rounded-lg border-2 text-center transition-all select-none
                                peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white
                                border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-blue-400 active:scale-95">
                                Izin
                            </span>
                        </label>

                        {{-- SAKIT --}}
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="attendanceData.{{ $student->id }}" value="sakit" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 px-1 text-[11px] font-bold rounded-lg border-2 text-center transition-all select-none
                                peer-checked:bg-amber-600 peer-checked:border-amber-600 peer-checked:text-white
                                border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-amber-400 active:scale-95">
                                Sakit
                            </span>
                        </label>

                        {{-- ALPA --}}
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="attendanceData.{{ $student->id }}" value="alpa" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 px-1 text-[11px] font-bold rounded-lg border-2 text-center transition-all select-none
                                peer-checked:bg-rose-600 peer-checked:border-rose-600 peer-checked:text-white
                                border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-rose-400 active:scale-95">
                                Alpa
                            </span>
                        </label>

                        {{-- HALANGAN --}}
                        @if($isPerempuan && $isSholat)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="attendanceData.{{ $student->id }}" value="halangan" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 px-1 text-[11px] font-bold rounded-lg border-2 text-center transition-all select-none
                                peer-checked:bg-pink-500 peer-checked:border-pink-500 peer-checked:text-white
                                border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-pink-400 active:scale-95">
                                Udzur
                            </span>
                        </label>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-gray-400">
                    <p class="font-medium">Belum ada siswa di kelas ini</p>
                </div>
            @endforelse
        </div>

        {{-- 2. DESKTOP TABLE VIEW (>= md) --}}
        <div class="hidden md:block overflow-x-auto">
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
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-green-400 hover:text-green-600">
                                            ✅ Hadir
                                        </span>
                                    </label>

                                    {{-- IZIN --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="izin" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-blue-500 peer-checked:border-blue-500 peer-checked:text-white
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-blue-400 hover:text-blue-600">
                                            📋 Izin
                                        </span>
                                    </label>

                                    {{-- SAKIT --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="sakit" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-amber-500 peer-checked:border-amber-500 peer-checked:text-white
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-amber-400 hover:text-amber-600">
                                            🤒 Sakit
                                        </span>
                                    </label>

                                    {{-- ALPA --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="alpa" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-red-500 peer-checked:border-red-500 peer-checked:text-white
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600">
                                            ❌ Alpa
                                        </span>
                                    </label>

                                    {{-- HALANGAN --}}
                                    @if($isPerempuan && $isSholat)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="attendanceData.{{ $student->id }}"
                                            value="halangan" class="sr-only peer">
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border-2 transition-all select-none
                                            peer-checked:bg-pink-400 peer-checked:border-pink-400 peer-checked:text-white
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-pink-400 hover:text-pink-600">
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
                                <p class="font-medium">Belum ada siswa di kelas ini</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Save Button (Responsive for Phone / Tablet / PC) --}}
        <div class="px-4 sm:px-6 py-3.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex flex-col sm:flex-row justify-between items-center gap-3">
            <p class="text-xs sm:text-sm text-gray-500 order-2 sm:order-1 text-center sm:text-left">
                Pilihan yang belum diisi tidak akan diubah di database.
            </p>
            <button wire:click="simpan" wire:loading.attr="disabled"
                class="w-full sm:w-auto order-1 sm:order-2 inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-500 active:scale-95 disabled:opacity-50 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-md">
                <svg wire:loading wire:target="simpan" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <svg wire:loading.remove wire:target="simpan" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Simpan Presensi Kelas</span>
            </button>
        </div>
    </div>

    {{-- ===== INFO SISTEM ===== --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 p-3.5 sm:p-4 text-xs text-blue-800 dark:text-blue-300">
        <p class="font-bold mb-1">ℹ️ Informasi Integrasi Presensi</p>
        <p>Data yang tersimpan di sini akan otomatis terhubung ke Dasbor Guru Piket, Laporan Presensi Harian, dan notifikasi ke orang tua.</p>
    </div>

</div>
</x-filament-panels::page>
