@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if(empty($program))
        <div class="rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-8 text-center bg-gray-50/60 dark:bg-gray-800/40">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                <x-heroicon-o-book-open class="w-7 h-7" />
            </div>
            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100">
                Pilih Kelompok / Kelas Al-Qur'an Terlebih Dahulu di Atas
            </h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                Setelah Kelompok Al-Qur'an dipilih (Tahfidz / Tahsin / Yanbu'a), seluruh santri binaan akan otomatis termuat. Anda dapat menginput setoran hafalan (Juz, Surat, Ayat, Predikat & Catatan) untuk seluruh santri sekaligus.
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
            wire:key="{{ (isset($this) && method_exists($this, 'getId') ? $this->getId() : $field->getId()) }}.{{ $statePath }}.ach-{{ $program }}"
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                search: '',
                defaultJuz: '{{ $defaultJuz ?? 30 }}',
                defaultSurah: '{{ $defaultSurah ?? 78 }}',
                defaultAyah: '{{ $defaultAyah ?? '1-10' }}',
                defaultType: '{{ $defaultType ?? 'ziyadah' }}',
                defaultJilid: '{{ $defaultJilidHalaman ?? '' }}',
                quickPredikat: 'Jayyid Jiddan',
                quickCatatan: 'Hafalan lancar dan makharijul huruf tepat.',
                presetCatatans: [
                    'Mutqin lancar sekali tanpa salah, tajwid sempurna.',
                    'Lancar baik, 1-2 kali peringatan/teguran.',
                    'Cukup lancar, perlu muraja\'ah mandiri.',
                    'Belum mutqin, dianjurkan mengulang setoran esok hari.'
                ],
                surahs: @js($surahs ?? []),
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
                            this.state[s.id] = {
                                active: true,
                                type: this.defaultType,
                                juz: this.defaultJuz,
                                surah: this.defaultSurah,
                                ayah: this.defaultAyah,
                                jilid_halaman: this.defaultJilid,
                                predikat: this.quickPredikat,
                                catatan: ''
                            };
                        } else {
                            if (typeof this.state[s.id] !== 'object') {
                                this.state[s.id] = { active: false };
                            }
                            if (this.state[s.id].active === undefined) this.state[s.id].active = true;
                            if (!this.state[s.id].type) this.state[s.id].type = this.defaultType;
                            if (!this.state[s.id].juz) this.state[s.id].juz = this.defaultJuz;
                            if (!this.state[s.id].surah) this.state[s.id].surah = this.defaultSurah;
                            if (!this.state[s.id].ayah) this.state[s.id].ayah = this.defaultAyah;
                            if (!this.state[s.id].predikat) this.state[s.id].predikat = this.quickPredikat;
                            if (this.state[s.id].catatan === undefined) this.state[s.id].catatan = '';
                        }
                    });
                },
                getItem(id) {
                    if (!this.state) this.state = {};
                    if (!this.state[id]) {
                        this.state[id] = {
                            active: true,
                            type: this.defaultType,
                            juz: this.defaultJuz,
                            surah: this.defaultSurah,
                            ayah: this.defaultAyah,
                            jilid_halaman: this.defaultJilid,
                            predikat: this.quickPredikat,
                            catatan: ''
                        };
                    }
                    return this.state[id];
                },
                updateItem(id, key, val) {
                    let cur = this.getItem(id);
                    this.state[id] = { ...cur, [key]: val };
                    this.state = { ...this.state };
                },
                toggleActive(id) {
                    let cur = this.getItem(id);
                    this.updateItem(id, 'active', !cur.active);
                },
                setAllActive(val) {
                    this.students.forEach(s => {
                        this.updateItem(s.id, 'active', val);
                    });
                },
                applyQuickPredikat() {
                    let p = this.quickPredikat;
                    if (!p) return;
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) {
                            this.updateItem(s.id, 'predikat', p);
                        }
                    });
                },
                applyQuickCatatan() {
                    let c = this.quickCatatan;
                    if (!c) return;
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) {
                            this.updateItem(s.id, 'catatan', c);
                        }
                    });
                },
                applyPreset(id, text) {
                    this.updateItem(id, 'catatan', text);
                },
                countActive() {
                    let count = 0;
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) count++;
                    });
                    return count;
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
                
                {{-- Baris 1: Status Ringkasan --}}
                <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                            <x-heroicon-m-users class="w-4 h-4 text-gray-400" />
                            Total: <strong x-text="students.length"></strong> Santri
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Menyetor Hari Ini: <strong x-text="countActive()"></strong> Santri
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
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                            />
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Alat Bantu Pengisian Cepat (Quick Fill) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    {{-- Quick Fill Predikat --}}
                    <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/70 dark:border-gray-700/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1">
                            <span>⭐</span> Predikat Bersama:
                        </span>
                        <select
                            x-model="quickPredikat"
                            class="flex-1 min-w-[130px] px-2.5 py-1 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <option value="Mumtaz">Mumtaz (Istimewa / 90-100)</option>
                            <option value="Jayyid Jiddan">Jayyid Jiddan (Sangat Baik / 80-89)</option>
                            <option value="Jayyid">Jayyid (Baik / 70-79)</option>
                            <option value="Maqbul">Maqbul (Perlu Pembinaan / &lt; 70)</option>
                        </select>
                        <button
                            type="button"
                            @click="applyQuickPredikat()"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition"
                            title="Terapkan predikat ini ke seluruh santri yang menyetor"
                        >
                            Terapkan
                        </button>
                    </div>

                    {{-- Quick Fill Catatan Guru --}}
                    <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200/70 dark:border-gray-700/60">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1">
                            <span>💬</span> Catatan Bersama:
                        </span>
                        <select
                            x-model="quickCatatan"
                            class="flex-1 min-w-[160px] px-2.5 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            <template x-for="p in presetCatatans" :key="p">
                                <option :value="p" x-text="p"></option>
                            </template>
                        </select>
                        <button
                            type="button"
                            @click="applyQuickCatatan()"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition"
                            title="Terapkan catatan ini ke seluruh santri yang menyetor"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            {{-- TABEL SANTRI FORMAT JURNAL MENGAJAR AL-QUR'AN --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 font-bold uppercase tracking-wider text-[11px]">
                                <th class="px-3.5 py-3 w-10 text-center">
                                    <input
                                        type="checkbox"
                                        checked
                                        @change="setAllActive($event.target.checked)"
                                        class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                        title="Pilih / Batalkan Semua"
                                    />
                                </th>
                                <th class="px-4 py-3 min-w-[190px]">Nama Lengkap Santri</th>
                                <th class="px-3 py-3 w-24 text-center">Juz</th>
                                <th class="px-3 py-3 min-w-[160px]">Surah</th>
                                <th class="px-3 py-3 w-32">Ayat / Jilid</th>
                                <th class="px-3 py-3 min-w-[140px] text-center">Predikat Kelulusan</th>
                                <th class="px-4 py-3 min-w-[280px]">Catatan Evaluasi Guru Al-Qur'an</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                            <template x-for="(student, index) in students" :key="student.id">
                                <tr
                                    x-show="matchesSearch(student)"
                                    class="transition-colors"
                                    :class="getItem(student.id).active ? 'hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20' : 'opacity-40 bg-gray-50/50 dark:bg-gray-800/30'"
                                >
                                    {{-- Checkbox Santri Menyetor --}}
                                    <td class="px-3.5 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="getItem(student.id).active"
                                            @change="toggleActive(student.id)"
                                            class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                            title="Centang jika santri menyetor hari ini"
                                        />
                                    </td>

                                    {{-- Identitas Santri --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                                                :class="student.gender === 'P' ? 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'"
                                                x-text="student.gender === 'P' ? '♀' : '♂'"
                                            ></div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white" x-text="student.name"></div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                                    NIS: <span x-text="student.nis"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Juz --}}
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            type="number"
                                            min="1"
                                            max="30"
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).juz"
                                            @input="updateItem(student.id, 'juz', $event.target.value)"
                                            class="w-14 text-center text-xs font-bold px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                        />
                                    </td>

                                    {{-- Surah --}}
                                    <td class="px-3 py-3">
                                        <select
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).surah"
                                            @change="updateItem(student.id, 'surah', $event.target.value)"
                                            class="w-full text-xs font-semibold px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                        >
                                            <template x-for="(name, num) in surahs" :key="num">
                                                <option :value="num" x-text="num + '. ' + name" :selected="String(num) === String(getItem(student.id).surah)"></option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- Ayat / Jilid --}}
                                    <td class="px-3 py-3">
                                        <input
                                            type="text"
                                            placeholder="Contoh: 1-15"
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).ayah"
                                            @input="updateItem(student.id, 'ayah', $event.target.value)"
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40 font-mono"
                                        />
                                    </td>

                                    {{-- Predikat Kelulusan --}}
                                    <td class="px-3 py-3 text-center">
                                        <select
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).predikat"
                                            @change="updateItem(student.id, 'predikat', $event.target.value)"
                                            class="w-full text-xs font-bold px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                            :class="{
                                                'text-emerald-700 dark:text-emerald-300': getItem(student.id).predikat === 'Mumtaz',
                                                'text-blue-700 dark:text-blue-300': getItem(student.id).predikat === 'Jayyid Jiddan',
                                                'text-amber-700 dark:text-amber-300': getItem(student.id).predikat === 'Jayyid',
                                                'text-rose-700 dark:text-rose-300': getItem(student.id).predikat === 'Maqbul'
                                            }"
                                        >
                                            <option value="Mumtaz">Mumtaz (Istimewa)</option>
                                            <option value="Jayyid Jiddan">Jayyid Jiddan (Sangat Baik)</option>
                                            <option value="Jayyid">Jayyid (Baik)</option>
                                            <option value="Maqbul">Maqbul (Perlu Pembinaan)</option>
                                        </select>
                                    </td>

                                    {{-- Catatan Evaluasi Guru --}}
                                    <td class="px-4 py-3">
                                        <div class="space-y-1.5">
                                            <input
                                                type="text"
                                                placeholder="Ketik catatan kelancaran & mutqin..."
                                                :disabled="!getItem(student.id).active"
                                                :value="getItem(student.id).catatan"
                                                @input="updateItem(student.id, 'catatan', $event.target.value)"
                                                class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                            />

                                            {{-- Pintasan Preset Catatan Cepat --}}
                                            <div class="flex flex-wrap items-center gap-1.5 text-[10px]" x-show="getItem(student.id).active">
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Mutqin lancar sekali tanpa salah, tajwid sempurna.')"
                                                    class="px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition"
                                                >
                                                    ⭐ Mutqin Lancar
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Lancar baik, 1-2 kali peringatan/teguran.')"
                                                    class="px-2 py-0.5 rounded-md bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition"
                                                >
                                                    ✅ Lancar Baik
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Belum mutqin, dianjurkan mengulang setoran esok hari.')"
                                                    class="px-2 py-0.5 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition"
                                                >
                                                    🔄 Perlu Muraja\'ah
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
