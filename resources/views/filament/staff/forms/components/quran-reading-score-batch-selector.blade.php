@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if(empty($program))
        <div class="rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center bg-gray-50/60 dark:bg-gray-800/40">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center">
                <x-heroicon-o-star class="w-7 h-7" />
            </div>
            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100">
                Pilih Kelompok / Kelas Al-Qur'an Terlebih Dahulu di Atas
            </h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                Setelah Kelompok Al-Qur'an dipilih (Tahsin / Yanbu'a / Tahfidz), daftar seluruh santri di kelompok tersebut akan otomatis termuat di tabel ini. Anda dapat memasukkan skor kualitas bacaan dan catatan evaluasi per santri.
            </p>
        </div>
    @elseif($students->isEmpty())
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-6 text-center">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-500 mx-auto mb-2" />
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                Belum ada data santri yang ditempatkan di kelompok Al-Qur'an ini oleh Waka Kurikulum.
            </p>
        </div>
    @else
        <div
            wire:key="{{ (isset($this) && method_exists($this, 'getId') ? $this->getId() : $field->getId()) }}.{{ $statePath }}.quran-{{ $program }}"
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                search: '',
                quickScore: '',
                quickCatatan: 'Lancar dan makharijul huruf tepat.',
                presetCatatans: [
                    'Makharijul huruf sangat tepat, tartil dan fasih.',
                    'Lancar dan makharijul huruf tepat.',
                    'Cukup lancar, perlu penguatan kaidah tajwid & mad.',
                    'Perlu pembinaan intensif pada pelafalan huruf & waqaf.',
                    'Adab tilawah sangat baik, suara jelas dan tartil.'
                ],
                students: @js($students->map(fn($s) => [
                    'id' => (string) $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis ?? '-',
                    'gender' => $s->jenis_kelamin,
                ])),
                init() {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        this.state = {};
                    }
                    this.students.forEach(s => {
                        if (!this.state[s.id]) {
                            this.state[s.id] = { score: '', catatan: '' };
                        } else {
                            if (typeof this.state[s.id] !== 'object') {
                                this.state[s.id] = { score: String(this.state[s.id]), catatan: '' };
                            }
                            if (this.state[s.id].score === undefined) this.state[s.id].score = '';
                            if (this.state[s.id].catatan === undefined) this.state[s.id].catatan = '';
                        }
                    });
                },
                getScore(id) {
                    return (this.state && this.state[id]) ? (this.state[id].score ?? '') : '';
                },
                setScore(id, val) {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) this.state = {};
                    let current = (this.state[id] && typeof this.state[id] === 'object') ? this.state[id] : { score: '', catatan: '' };
                    this.state[id] = { ...current, score: val };
                    this.state = { ...this.state };
                },
                getCatatan(id) {
                    return (this.state && this.state[id]) ? (this.state[id].catatan ?? '') : '';
                },
                setCatatan(id, val) {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) this.state = {};
                    let current = (this.state[id] && typeof this.state[id] === 'object') ? this.state[id] : { score: '', catatan: '' };
                    this.state[id] = { ...current, catatan: val };
                    this.state = { ...this.state };
                },
                applyPreset(id, text) {
                    this.setCatatan(id, text);
                },
                applyQuickScore(onlyEmpty = true) {
                    let val = this.quickScore;
                    if (val === '' || val === null || isNaN(val)) return;
                    val = Math.max(0, Math.min(100, parseFloat(val)));
                    this.students.forEach(s => {
                        let cur = this.getScore(s.id);
                        if (!onlyEmpty || cur === '' || cur === null) {
                            this.setScore(s.id, String(val));
                        }
                    });
                },
                applyQuickCatatan(onlyEmpty = true) {
                    let text = this.quickCatatan;
                    if (!text) return;
                    this.students.forEach(s => {
                        let cur = this.getCatatan(s.id);
                        if (!onlyEmpty || cur === '' || cur === null) {
                            this.setCatatan(s.id, text);
                        }
                    });
                },
                getPredikat(score) {
                    if (score === '' || score === null || isNaN(score)) {
                        return { label: '-', badge: 'bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-200 dark:border-gray-700' };
                    }
                    let num = parseFloat(score);
                    if (num >= 100) {
                        return { label: '100 : Mumtaz (Istimewa)', badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-700' };
                    } else if (num >= 90) {
                        return { label: '90-99 : Sangat Baik', badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' };
                    } else if (num >= 80) {
                        return { label: '80-89 : Jayyid (Baik)', badge: 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-300 dark:border-blue-700' };
                    } else if (num >= 70) {
                        return { label: '70-79 : Cukup Baik', badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/70 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700' };
                    } else {
                        return { label: '< 70 : Maqbul (Perlu Pembinaan)', badge: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-700' };
                    }
                },
                countFilled() {
                    let count = 0;
                    this.students.forEach(s => {
                        let sc = this.getScore(s.id);
                        if (sc !== '' && sc !== null && !isNaN(sc)) count++;
                    });
                    return count;
                },
                countTuntas() {
                    let count = 0;
                    this.students.forEach(s => {
                        let sc = this.getScore(s.id);
                        if (sc !== '' && sc !== null && !isNaN(sc) && parseFloat(sc) >= 70) count++;
                    });
                    return count;
                },
                countRemedial() {
                    let count = 0;
                    this.students.forEach(s => {
                        let sc = this.getScore(s.id);
                        if (sc !== '' && sc !== null && !isNaN(sc) && parseFloat(sc) < 70) count++;
                    });
                    return count;
                },
                averageScore() {
                    let total = 0;
                    let count = 0;
                    this.students.forEach(s => {
                        let sc = this.getScore(s.id);
                        if (sc !== '' && sc !== null && !isNaN(sc)) {
                            total += parseFloat(sc);
                            count++;
                        }
                    });
                    return count > 0 ? (total / count).toFixed(1) : '0';
                },
                matchesSearch(student) {
                    if (!this.search) return true;
                    let q = this.search.toLowerCase();
                    return student.name.toLowerCase().includes(q) || (student.nis && student.nis.toLowerCase().includes(q));
                }
            }"
            class="space-y-4"
        >
            {{-- TOOLBAR: STATISTIK REALTIME & ALAT BANTU PENGISIAN CEPAT --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-900 p-4 shadow-xs space-y-3.5">
                
                {{-- Baris 1: Ringkasan Status Nilai Santri --}}
                <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                            <x-heroicon-m-users class="w-4 h-4 text-gray-400" />
                            Total: <strong x-text="students.length"></strong> Santri
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Terisi: <strong x-text="countFilled()"></strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            ✅ Tuntas (≥70): <strong x-text="countTuntas()"></strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            ⚠️ Perlu Pembinaan (&lt;70): <strong x-text="countRemedial()"></strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            ⭐ Rata-rata Skor: <strong x-text="averageScore()"></strong>
                        </span>
                    </div>

                    {{-- Search Santri --}}
                    <div class="w-full sm:w-64">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                            </div>
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Cari santri atau NIS..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                            />
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Alat Bantu Pengisian Cepat (Quick Fill) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    {{-- Quick Fill Skor --}}
                    <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/70 dark:border-gray-700/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1">
                            <span>⚡</span> Template Skor:
                        </span>
                        <input
                            type="number"
                            min="0"
                            max="100"
                            x-model="quickScore"
                            placeholder="Contoh: 85"
                            class="w-24 px-2.5 py-1 text-xs font-bold rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        />
                        <button
                            type="button"
                            @click="applyQuickScore(true)"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-600 hover:bg-amber-500 text-white shadow-xs transition"
                            title="Terapkan ke santri yang nilainya masih kosong"
                        >
                            Isi yang Kosong
                        </button>
                        <button
                            type="button"
                            @click="applyQuickScore(false)"
                            class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-800 dark:text-gray-200 transition"
                            title="Terapkan nilai ini ke semua santri"
                        >
                            Isi Semua
                        </button>
                    </div>

                    {{-- Quick Fill Catatan Guru --}}
                    <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/70 dark:border-gray-700/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1">
                            <span>💬</span> Template Catatan:
                        </span>
                        <select
                            x-model="quickCatatan"
                            class="flex-1 min-w-[180px] px-2.5 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <template x-for="p in presetCatatans" :key="p">
                                <option :value="p" x-text="p"></option>
                            </template>
                        </select>
                        <button
                            type="button"
                            @click="applyQuickCatatan(true)"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition"
                            title="Terapkan catatan ini ke santri yang belum ada catatan"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            {{-- TABEL SANTRI FORMAT JURNAL MENGAJAR --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-bold uppercase tracking-wider text-[11px]">
                                <th class="px-3.5 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3 min-w-[200px]">Nama Lengkap Santri</th>
                                <th class="px-4 py-3 w-48 text-center">Skor Kualitas (0-100)</th>
                                <th class="px-4 py-3 min-w-[340px]">Catatan Evaluasi Guru Al-Qur'an (Makhraj, Tajwid, Fashahah, Adab)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                            <template x-for="(student, index) in students" :key="student.id">
                                <tr
                                    x-show="matchesSearch(student)"
                                    class="hover:bg-amber-50/40 dark:hover:bg-amber-950/20 transition-colors"
                                >
                                    {{-- No --}}
                                    <td class="px-3.5 py-3 text-center font-bold text-gray-400" x-text="index + 1"></td>

                                    {{-- Identitas Santri --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                                :class="student.gender === 'P' ? 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'"
                                                x-text="student.gender === 'P' ? '♀' : '♂'"
                                            ></div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white" x-text="student.name"></div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1.5 mt-0.5">
                                                    <span>NIS: <strong x-text="student.nis"></strong></span>
                                                    <span>•</span>
                                                    <span x-text="student.gender === 'P' ? 'Santriwati (P)' : 'Santri (L)'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Skor Kualitas & Predikat Realtime --}}
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="1"
                                                placeholder="0 - 100"
                                                :value="getScore(student.id)"
                                                @input="setScore(student.id, $event.target.value)"
                                                class="w-28 text-center text-sm font-extrabold px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-amber-700 dark:text-amber-400 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition shadow-2xs"
                                            />
                                            <span
                                                class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md text-center w-full max-w-[150px] truncate"
                                                :class="getPredikat(getScore(student.id)).badge"
                                                x-text="getPredikat(getScore(student.id)).label"
                                            ></span>
                                        </div>
                                    </td>

                                    {{-- Catatan Evaluasi Guru Per Santri --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-1.5">
                                            <input
                                                type="text"
                                                placeholder="Ketik catatan evaluasi makhraj, tajwid, fashahah..."
                                                :value="getCatatan(student.id)"
                                                @input="setCatatan(student.id, $event.target.value)"
                                                class="w-full text-xs px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition"
                                            />

                                            {{-- Pintasan Preset Catatan Cepat --}}
                                            <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Makharijul huruf sangat tepat, tartil dan fasih.')"
                                                    class="px-2 py-0.5 rounded-md bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition"
                                                >
                                                    ⭐ Sangat Fasih & Tartil
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Lancar dan makharijul huruf tepat.')"
                                                    class="px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition"
                                                >
                                                    ✅ Lancar & Tajwid Tepat
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Cukup lancar, perlu penguatan kaidah tajwid & mad.')"
                                                    class="px-2 py-0.5 rounded-md bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition"
                                                >
                                                    🔄 Perlu Penguatan Tajwid
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Perlu pembinaan intensif pada pelafalan huruf & waqaf.')"
                                                    class="px-2 py-0.5 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition"
                                                >
                                                    📖 Belum Lancar / Pembinaan
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-dynamic-component>
