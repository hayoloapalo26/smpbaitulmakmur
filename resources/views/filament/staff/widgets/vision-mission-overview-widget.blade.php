<x-filament-widgets::widget>
    @if ($activeVisionMission)
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-600 via-amber-500 to-orange-500 p-6 text-white shadow-lg">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/20 backdrop-blur-md px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white">
                                <x-heroicon-m-check-badge class="w-4 h-4 text-white" />
                                Visi & Misi Resmi Berlaku
                            </span>
                            <span class="inline-flex items-center rounded-full bg-black/20 px-3 py-1 text-xs font-medium text-white">
                                Periode: {{ $activeVisionMission->periode }}
                            </span>
                        </div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-white md:text-3xl">
                            {{ $activeVisionMission->institution?->name ?? 'SMP Islam Baitul Makmur' }}
                        </h2>
                        @if ($activeVisionMission->slogan)
                            <p class="mt-1 text-sm font-medium text-orange-100 italic">
                                “{{ $activeVisionMission->slogan }}”
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            onclick="window.print()" 
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-orange-600 shadow-sm hover:bg-orange-50 transition-all active:scale-95"
                        >
                            <x-heroicon-m-printer class="w-4 h-4" />
                            Cetak Dokumen
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3 Kolom Fitur Utama: Visi, Misi, Tujuan -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- FITUR 1: VISI SEKOLAH -->
                <div class="flex flex-col rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-950 dark:text-orange-400">
                                <x-heroicon-o-light-bulb class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Visi Sekolah</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Arah & Cita-Cita Utama</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-orange-50 dark:bg-orange-950/50 px-2.5 py-0.5 text-xs font-semibold text-orange-600 dark:text-orange-400">
                            Utama
                        </span>
                    </div>

                    <div class="mt-5 flex-1 flex flex-col justify-between">
                        <div class="rounded-xl bg-orange-50/60 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 p-4">
                            <p class="text-base font-medium leading-relaxed text-gray-800 dark:text-gray-200 italic">
                                “{{ $activeVisionMission->visi }}”
                            </p>
                        </div>

                        @if ($activeVisionMission->indikator_visi)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                    Indikator Ketercapaian Visi:
                                </h4>
                                <div class="text-xs leading-relaxed text-gray-600 dark:text-gray-300 whitespace-pre-line">
                                    {{ $activeVisionMission->indikator_visi }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- FITUR 2: MISI SEKOLAH -->
                <div class="flex flex-col rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400">
                                <x-heroicon-o-rocket-launch class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Misi Sekolah</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Langkah Strategis</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-sky-50 dark:bg-sky-950/50 px-2.5 py-0.5 text-xs font-semibold text-sky-600 dark:text-sky-400">
                            {{ is_array($activeVisionMission->misi) ? count($activeVisionMission->misi) : 1 }} Poin
                        </span>
                    </div>

                    <div class="mt-5 flex-1">
                        @if (is_array($activeVisionMission->misi) && count($activeVisionMission->misi) > 0)
                            <ul class="space-y-3">
                                @foreach ($activeVisionMission->misi as $idx => $item)
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/60 text-xs font-bold text-sky-700 dark:text-sky-300">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="text-sm leading-snug text-gray-700 dark:text-gray-300">
                                            {{ is_array($item) ? ($item['poin'] ?? '-') : $item }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-gray-500 italic">Belum ada poin misi yang diisi.</p>
                        @endif
                    </div>
                </div>

                <!-- FITUR 3: TUJUAN SEKOLAH -->
                <div class="flex flex-col rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-trophy class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Tujuan Sekolah</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Target Mutu & Capaian</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ is_array($activeVisionMission->tujuan) ? count($activeVisionMission->tujuan) : 1 }} Target
                        </span>
                    </div>

                    <div class="mt-5 flex-1 space-y-3">
                        @if (is_array($activeVisionMission->tujuan) && count($activeVisionMission->tujuan) > 0)
                            <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                                @foreach ($activeVisionMission->tujuan as $idx => $obj)
                                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 p-3">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                                {{ is_array($obj) ? ($obj['kategori'] ?? 'Tujuan') : 'Tujuan' }}
                                            </span>
                                            @if (is_array($obj) && !empty($obj['indikator']))
                                                <span class="text-[10px] text-gray-500 bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-600">
                                                    {{ $obj['indikator'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-medium leading-relaxed text-gray-700 dark:text-gray-300">
                                            {{ is_array($obj) ? ($obj['poin'] ?? '-') : $obj }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">Belum ada poin tujuan yang diisi.</p>
                        @endif

                        @if ($activeVisionMission->sasaran_strategis)
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Sasaran Strategis:
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                    {{ $activeVisionMission->sasaran_strategis }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 p-8 text-center bg-gray-50/50 dark:bg-gray-900/40">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-950 dark:text-orange-400">
                <x-heroicon-o-flag class="w-8 h-8" />
            </div>
            <h3 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">
                Belum Ada Dokumen Visi & Misi Sekolah
            </h3>
            <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Dokumen Visi, Misi & Tujuan Sekolah belum dibuat. Klik tombol <strong>"Buat Visi & Misi Baru"</strong> di atas untuk mulai merumuskan dokumen strategis satuan pendidikan Anda.
            </p>
        </div>
    @endif
</x-filament-widgets::widget>
