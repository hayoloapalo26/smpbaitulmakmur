<x-filament-panels::page>
<div class="space-y-4 sm:space-y-6" wire:poll.30s="loadAttendance">

    {{-- ===== HEADER ALERT: INTEGRASI FINGERPRINT & INPUT MANUAL PIKET ===== --}}
    <div class="rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/30 border border-amber-200 dark:border-amber-800/60 p-4 flex gap-3.5 items-start shadow-xs">
        <div class="p-2 rounded-xl bg-amber-500 text-white flex-shrink-0 mt-0.5 shadow-xs">
            <x-filament::icon icon="heroicon-o-bolt" class="w-5 h-5 sm:w-6 sm:h-6" />
        </div>
        <div class="space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="font-bold text-sm sm:text-base text-amber-950 dark:text-amber-100">
                    Presensi Siswa Terintegrasi (Mesin Fingerprint & Cadangan Manual Guru Piket)
                </p>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-200 text-amber-900 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
                    👮 Khusus Guru Piket
                </span>
            </div>
            <p class="text-xs sm:text-sm text-amber-900/80 dark:text-amber-300 leading-relaxed">
                Presensi siswa otomatis terdata ketika siswa menempelkan sidik jari pada <strong>alat fingerprint</strong>. Halaman ini disediakan khusus untuk <strong>Guru yang bertugas piket</strong> guna melakukan pendataan manual per kelas apabila terjadi <strong>pemadaman listrik (mati lampu)</strong> atau kendala mesin. Siswa yang sudah tap fingerprint ditandai dengan badge khusus dan datanya tidak akan terhapus.
            </p>
        </div>
    </div>

    {{-- ===== FILTER BAR & RINGKASAN INTEGRASI ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-5 space-y-4">
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
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">🏫 Pilihan Kelas</label>
                <select wire:model.live="selectedClassroomId"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-base sm:text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500 py-2 px-3">
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tombol Semua Hadir (Cadangan Mati Lampu Cepat) --}}
            <div>
                <button wire:click="tandaiSemuaHadir" type="button"
                    class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5">
                    <span>⚡</span>
                    <span>Tandai Semua Hadir</span>
                </button>
            </div>
        </div>

        {{-- Indikator Status Integrasi Presensi --}}
        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-gray-500 dark:text-gray-400 font-medium">Status Data Kelas:</span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-bold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <span>👆 Hadir Fingerprint:</span>
                    <span class="font-extrabold">{{ $countFingerprint }}</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-bold bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                    <span>✍️ Hadir Manual:</span>
                    <span class="font-extrabold">{{ $countManual }}</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                    <span>⏳ Belum Presensi:</span>
                    <span class="font-extrabold">{{ $countBelum }}</span>
                </span>
            </div>
            <div class="text-[11px] text-gray-400 italic">
                *Siswa yang sudah scan fingerprint otomatis terkunci hadir dan tidak tertimpa jam aslinya.
            </div>
        </div>
    </div>

    {{-- ===== DAFTAR SISWA (DUAL VIEW: MOBILE & DESKTOP) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

        {{-- Table Header --}}
        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-gray-50/50 dark:bg-gray-700/30">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                    Daftar Presensi Kelas:
                    <span class="text-primary-600 dark:text-primary-400" wire:loading.class="opacity-50">
                        {{ $classrooms->firstWhere('id', $selectedClassroomId)?->name ?? '—' }}
                    </span>
                </h3>
                <p class="text-xs text-gray-500">{{ count($students) }} siswa terdaftar di rombel ini</p>
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
            Memuat data presensi siswa dan status sidik jari...
        </div>

        {{-- 1. MOBILE TOUCH CARDS VIEW (< md) --}}
        <div class="block md:hidden divide-y divide-gray-100 dark:divide-gray-700/60 p-2 space-y-2">
            @forelse($students as $i => $student)
                @php
                    $currentStatus = $attendanceData[$student->id] ?? null;
                    $meta = $attendanceMeta[$student->id] ?? null;
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
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    <span class="text-[11px] text-gray-400">NIS: {{ $student->nis }}</span>
                                    @if($meta && $meta['metode'] === 'fingerprint')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            👆 Fingerprint {{ $meta['jam'] ? '('.$meta['jam'].')' : '' }}
                                        </span>
                                    @elseif($meta && $meta['metode'] === 'manual')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                            ✍️ Manual (Piket)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            ⏳ Belum
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isPerempuan ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $isPerempuan ? '♀ P' : '♂ L' }}
                        </span>
                    </div>

                    {{-- Touch-Friendly Segmented Status Buttons --}}
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
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status & Metode Presensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($students as $i => $student)
                        @php
                            $currentStatus = $attendanceData[$student->id] ?? null;
                            $meta = $attendanceMeta[$student->id] ?? null;
                            $isPerempuan = $student->jenis_kelamin === 'P';
                            $isSholat = in_array($tipeAbsen, ['sholat_dhuhur', 'sholat_ashar']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors
                            {{ $currentStatus === 'alpa'     ? 'bg-red-50 dark:bg-red-900/10'    : '' }}
                            {{ $currentStatus === 'halangan' ? 'bg-pink-50 dark:bg-pink-900/10'  : '' }}
                        ">
                            <td class="px-6 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold
                                        {{ $isPerempuan ? 'bg-pink-400' : 'bg-blue-400' }}">
                                        {{ strtoupper(substr($student->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $student->name }}</p>
                                            @if($meta && $meta['metode'] === 'fingerprint')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                                    <span>👆 Fingerprint</span>
                                                    @if($meta['jam'])
                                                        <span>({{ $meta['jam'] }})</span>
                                                    @endif
                                                    @if($meta['is_late'])
                                                        <span class="text-rose-600 font-bold">• Terlambat</span>
                                                    @endif
                                                </span>
                                            @elseif($meta && $meta['metode'] === 'manual')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                                    <span>✍️ Manual (Piket)</span>
                                                    @if($meta['jam'])
                                                        <span>({{ $meta['jam'] }})</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                    <span>⏳ Belum Presensi</span>
                                                </span>
                                            @endif
                                        </div>
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
                                            🏥 Sakit
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
                                            border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-pink-300 hover:text-pink-500">
                                            🌸 Udzur
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
                Data presensi manual tersimpan secara permanen dan terhubung langsung ke Dasbor Guru Piket.
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

    {{-- ===== INFO SISTEM INTEGRASI ===== --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 p-3.5 sm:p-4 text-xs text-blue-800 dark:text-blue-300">
        <p class="font-bold mb-1">ℹ️ Integrasi Fingerprint & Manual</p>
        <p>Setiap kehadiran yang diinput manual maupun dari mesin fingerprint disatukan ke dalam rekapitulasi kehadiran siswa harian. Guru piket dapat melihat langsung siswa mana yang sudah berhasil presensi melalui sidik jari dan siapa yang memerlukan input manual saat pemadaman listrik.</p>
    </div>

</div>
</x-filament-panels::page>
