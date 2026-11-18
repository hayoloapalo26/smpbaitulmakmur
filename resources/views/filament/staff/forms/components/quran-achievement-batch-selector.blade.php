@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($students->isEmpty())
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-6 text-center">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-500 mx-auto mb-2" />
            <h5 class="text-xs font-bold text-amber-900 dark:text-amber-200">
                Belum Ada Santri yang Ditempatkan di Kelompok Al-Qur'an Ini
            </h5>
            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1 max-w-md mx-auto">
                Daftar santri akan otomatis termuat begitu Waka Kurikulum menempatkan santri ke kelompok Al-Qur'an Anda. Silakan hubungi Waka Kurikulum jika santri belum muncul.
            </p>
        </div>
    @else
        <div
            wire:key="{{ (isset($this) && method_exists($this, 'getId') ? $this->getId() : $field->getId()) }}.{{ $statePath }}.ach-{{ $program }}"
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                search: '',
                isTahsin: @js($isTahsin ?? false),
                jilidOptions: @js($jilidOptions ?? []),
                defaultScaleScore: '{{ $defaultScaleScore ?? 85 }}',
                defaultJuz: '{{ $defaultJuz ?? 30 }}',
                defaultSurah: '{{ $defaultSurah ?? 78 }}',
                defaultAyah: '{{ $defaultAyah ?? '1-10' }}',
                defaultJuzMurajaah: '{{ $defaultJuzMurajaah ?? '' }}',
                defaultSurahMurajaah: '{{ $defaultSurahMurajaah ?? '' }}',
                defaultAyahMurajaah: '{{ $defaultAyahMurajaah ?? '' }}',
                quickPredikat: 'Jayyid Jiddan',
                quickCatatan: 'Hafalan lancar dan makharijul huruf tepat.',
                activeStudentForMurajaah: null,
                searchSurahMurajaah: '',
                quickMurajaahPreset: '3_qul',
                presetCatatans: [
                    'Mutqin lancar sekali tanpa salah, tajwid sempurna.',
                    'Lancar baik, 1-2 kali peringatan/teguran.',
                    'Cukup lancar, perlu muraja\'ah mandiri.',
                    'Belum mutqin, dianjurkan mengulang setoran esok hari.',
                    'Tidak ada setoran Al-Qur\'an hari ini.'
                ],
                surahs: @js($surahs ?? []),
                students: @js($students->map(fn($s) => [
                    'id'                  => (string) $s->id,
                    'name'                => $s->name,
                    'nis'                 => $s->nis ?? '-',
                    'gender'              => $s->jenis_kelamin,
                    'has_previous'        => $s->progress_data['has_previous'] ?? false,
                    'last_progress_label' => $s->progress_data['last_label'] ?? null,
                    'next_juz'            => $s->progress_data['juz'] ?? ($defaultJuz ?? 30),
                    'next_surah'          => (string)($s->progress_data['surah'] ?? ($defaultSurah ?? '78')),
                    'next_ayah'           => (string)($s->progress_data['ayah'] ?? ($defaultAyah ?? '1-10')),
                    'next_juz_murajaah'   => $s->progress_data['juz_murajaah'] ?? ($defaultJuzMurajaah ?? ''),
                    'next_surah_murajaah' => (string)($s->progress_data['surah_murajaah'] ?? ($defaultSurahMurajaah ?? '')),
                    'next_ayah_murajaah'  => (string)($s->progress_data['ayah_murajaah'] ?? ($defaultAyahMurajaah ?? '')),
                    'scale_score'         => $s->progress_data['scale_score'] ?? ($defaultScaleScore ?? 85),
                    'predikat'            => $s->progress_data['predikat'] ?? 'Jayyid Jiddan',
                ])),
                init() {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        this.state = {};
                    }
                    this.students.forEach(s => {
                        if (!this.state[s.id]) {
                            this.state[s.id] = {
                                active: true,
                                juz: s.next_juz,
                                surah: s.next_surah,
                                ayah: s.next_ayah,
                                juz_murajaah: s.next_juz_murajaah,
                                surah_murajaah: s.next_surah_murajaah,
                                ayah_murajaah: s.next_ayah_murajaah,
                                scale_score: s.scale_score,
                                predikat: s.predikat,
                                catatan: ''
                            };
                        } else {
                            if (typeof this.state[s.id] !== 'object') {
                                this.state[s.id] = { active: false };
                            }
                            if (this.state[s.id].active === undefined) this.state[s.id].active = true;
                            if (!this.state[s.id].juz) this.state[s.id].juz = s.next_juz;
                            if (!this.state[s.id].surah) this.state[s.id].surah = s.next_surah;
                            if (!this.state[s.id].ayah) this.state[s.id].ayah = s.next_ayah;
                            if (this.state[s.id].juz_murajaah === undefined) this.state[s.id].juz_murajaah = s.next_juz_murajaah;
                            if (this.state[s.id].surah_murajaah === undefined) this.state[s.id].surah_murajaah = s.next_surah_murajaah;
                            if (this.state[s.id].ayah_murajaah === undefined) this.state[s.id].ayah_murajaah = s.next_ayah_murajaah;
                            if (this.state[s.id].scale_score === undefined) this.state[s.id].scale_score = s.scale_score;
                            if (!this.state[s.id].predikat) this.state[s.id].predikat = s.predikat;
                            if (this.state[s.id].catatan === undefined) this.state[s.id].catatan = '';
                        }
                    });
                },
                getItem(id) {
                    if (!this.state) this.state = {};
                    if (!this.state[id]) {
                        let s = this.students.find(st => st.id === id);
                        this.state[id] = {
                            active: true,
                            juz: s ? s.next_juz : this.defaultJuz,
                            surah: s ? s.next_surah : this.defaultSurah,
                            ayah: s ? s.next_ayah : this.defaultAyah,
                            juz_murajaah: s ? s.next_juz_murajaah : this.defaultJuzMurajaah,
                            surah_murajaah: s ? s.next_surah_murajaah : this.defaultSurahMurajaah,
                            ayah_murajaah: s ? s.next_ayah_murajaah : this.defaultAyahMurajaah,
                            scale_score: s ? s.scale_score : this.defaultScaleScore,
                            predikat: s ? s.predikat : this.quickPredikat,
                            catatan: ''
                        };
                    }
                    return this.state[id];
                },
                updateScore(id, score) {
                    this.updateItem(id, 'scale_score', score);
                    let num = parseFloat(score);
                    if (!isNaN(num)) {
                        if (num >= 90) this.updateItem(id, 'predikat', 'Mumtaz');
                        else if (num >= 80) this.updateItem(id, 'predikat', 'Jayyid Jiddan');
                        else if (num >= 70) this.updateItem(id, 'predikat', 'Jayyid');
                        else this.updateItem(id, 'predikat', 'Maqbul');
                    }
                },
                updateItem(id, key, val) {
                    let cur = this.getItem(id);
                    this.state[id] = { ...cur, [key]: val };
                    this.state = { ...this.state };
                },
                updateZiyadahSurah(id, val) {
                    this.updateItem(id, 'surah', val);
                    let s = this.students.find(st => st.id === id);
                    if (val === 'tidak_menyetor' || val === '-') {
                        this.updateItem(id, 'surah', 'tidak_menyetor');
                        this.updateItem(id, 'ayah', '-');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz', '-');
                        }
                    } else {
                        let curAyah = this.getItem(id).ayah;
                        if (!curAyah || curAyah === '-') {
                            this.updateItem(id, 'ayah', s ? s.next_ayah : (this.isTahsin ? 'Hal. 1-5' : '1-10'));
                        }
                        if (!this.isTahsin) {
                            let curJuz = this.getItem(id).juz;
                            if (!curJuz || curJuz === '-') {
                                this.updateItem(id, 'juz', s ? s.next_juz : 30);
                            }
                        }
                    }
                },
                toggleTidakMenyetorZiyadah(id) {
                    let cur = this.getItem(id);
                    if (cur.surah === 'tidak_menyetor' || cur.surah === '-') {
                        let s = this.students.find(st => st.id === id);
                        this.updateItem(id, 'surah', s ? s.next_surah : (this.isTahsin ? 'Jilid 1' : '78'));
                        this.updateItem(id, 'ayah', s ? s.next_ayah : (this.isTahsin ? 'Hal. 1-5' : '1-10'));
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz', s ? s.next_juz : 30);
                        }
                    } else {
                        this.updateItem(id, 'surah', 'tidak_menyetor');
                        this.updateItem(id, 'ayah', '-');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz', '-');
                        }
                    }
                },
                openMurajaahModal(id) {
                    this.activeStudentForMurajaah = id;
                    this.searchSurahMurajaah = '';
                },
                getActiveStudent() {
                    if (!this.activeStudentForMurajaah) return null;
                    return this.students.find(s => s.id === this.activeStudentForMurajaah);
                },
                parseSurahArray(val) {
                    if (!val) return [];
                    if (Array.isArray(val)) return val.map(String).filter(v => v !== '' && v !== 'tidak_menyetor' && v !== '-');
                    if (typeof val === 'string') {
                        if (val === 'tidak_menyetor' || val === '-') return [];
                        try {
                            let parsed = JSON.parse(val);
                            if (Array.isArray(parsed)) return parsed.map(String).filter(v => v !== '' && v !== 'tidak_menyetor' && v !== '-');
                        } catch(e) {}
                        if (val.includes(',')) {
                            return val.split(',').map(s => s.trim()).filter(s => s !== '' && s !== 'tidak_menyetor' && s !== '-');
                        }
                        return [val.trim()];
                    }
                    return [String(val)];
                },
                getSelectedMurajaahArray(id) {
                    let cur = this.getItem(id);
                    return this.parseSurahArray(cur.surah_murajaah);
                },
                isMurajaahSelected(id, key) {
                    let arr = this.getSelectedMurajaahArray(id);
                    return arr.includes(String(key));
                },
                toggleMurajaahChoice(id, key) {
                    let strKey = String(key);
                    let arr = this.getSelectedMurajaahArray(id);
                    let idx = arr.indexOf(strKey);
                    if (idx > -1) {
                        arr.splice(idx, 1);
                    } else {
                        arr.push(strKey);
                    }
                    this.setMurajaahChoices(id, arr);
                },
                setMurajaahChoices(id, arr) {
                    if (!arr || arr.length === 0) {
                        this.updateItem(id, 'surah_murajaah', '');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz_murajaah', '');
                        }
                        this.updateItem(id, 'ayah_murajaah', '');
                        return;
                    }
                    this.updateItem(id, 'surah_murajaah', arr.join(', '));
                    let curAyah = this.getItem(id).ayah_murajaah;
                    if (!curAyah || curAyah === '-') {
                        this.updateItem(id, 'ayah_murajaah', this.isTahsin ? 'Hal. 1-5' : '1-Akhir');
                    }
                    if (!this.isTahsin) {
                        let firstNum = parseInt(arr[0]);
                        let targetJuz = (firstNum >= 78) ? 30 : ((firstNum >= 67) ? 29 : ((firstNum >= 58) ? 28 : 30));
                        this.updateItem(id, 'juz_murajaah', targetJuz);
                    }
                },
                applyPresetMurajaah(id, preset) {
                    let choices = [];
                    if (this.isTahsin) {
                        if (preset === 'jilid_1') choices = ['Jilid 1'];
                        else if (preset === 'jilid_2') choices = ['Jilid 2'];
                        else if (preset === 'jilid_3') choices = ['Jilid 3'];
                        else if (preset === 'jilid_1_2') choices = ['Jilid 1', 'Jilid 2'];
                        else choices = ['Jilid 1'];
                    } else {
                        if (preset === '3_qul') choices = ['112', '113', '114'];
                        else if (preset === 'an_nas_kafirun') choices = ['109', '110', '111', '112', '113', '114'];
                        else if (preset === 'an_nas_fil') choices = ['105', '106', '107', '108', '109', '110', '111', '112', '113', '114'];
                        else if (preset === 'an_nas_duha') {
                            choices = [];
                            for (let i = 93; i <= 114; i++) choices.push(String(i));
                        } else if (preset === 'lail_naba') {
                            choices = [];
                            for (let i = 78; i <= 92; i++) choices.push(String(i));
                        } else if (preset === 'juz_30_full') {
                            choices = [];
                            for (let i = 78; i <= 114; i++) choices.push(String(i));
                        }
                    }
                    this.setMurajaahChoices(id, choices);
                },
                applyQuickMurajaahToRombel(preset) {
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) {
                            if (preset === 'tidak_menyetor') {
                                this.updateItem(s.id, 'surah_murajaah', 'tidak_menyetor');
                                this.updateItem(s.id, 'ayah_murajaah', '-');
                                if (!this.isTahsin) this.updateItem(s.id, 'juz_murajaah', '-');
                            } else {
                                this.applyPresetMurajaah(s.id, preset);
                            }
                        }
                    });
                },
                getMurajaahLabel(key) {
                    if (this.isTahsin) {
                        return this.jilidOptions[key] || key;
                    }
                    return this.surahs[key] ? (key + '. ' + this.surahs[key]) : ('Surat ' + key);
                },
                getFilteredMurajaahOptions() {
                    let q = (this.searchSurahMurajaah || '').toLowerCase().trim();
                    let result = [];
                    if (this.isTahsin) {
                        for (let [k, label] of Object.entries(this.jilidOptions)) {
                            if (!q || label.toLowerCase().includes(q) || k.toLowerCase().includes(q)) {
                                result.push({ key: k, label: label });
                            }
                        }
                    } else {
                        for (let [num, name] of Object.entries(this.surahs)) {
                            let full = num + '. ' + name;
                            if (!q || full.toLowerCase().includes(q) || name.toLowerCase().includes(q) || String(num) === q) {
                                result.push({ key: String(num), label: full, name: name, num: num });
                            }
                        }
                    }
                    return result;
                },
                updateMurajaahSurah(id, val) {
                    this.updateItem(id, 'surah_murajaah', val);
                    let s = this.students.find(st => st.id === id);
                    if (val === 'tidak_menyetor' || val === '-') {
                        this.updateItem(id, 'surah_murajaah', 'tidak_menyetor');
                        this.updateItem(id, 'ayah_murajaah', '-');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz_murajaah', '-');
                        }
                    } else if (!val) {
                        this.updateItem(id, 'ayah_murajaah', '');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz_murajaah', '');
                        }
                    } else {
                        let curAyah = this.getItem(id).ayah_murajaah;
                        if (!curAyah || curAyah === '-') {
                            this.updateItem(id, 'ayah_murajaah', s ? s.next_ayah_murajaah : (this.isTahsin ? 'Hal. 1-5' : '1-10'));
                        }
                        if (!this.isTahsin) {
                            let curJuz = this.getItem(id).juz_murajaah;
                            if (!curJuz || curJuz === '-') {
                                this.updateItem(id, 'juz_murajaah', s ? s.next_juz_murajaah : 30);
                            }
                        }
                    }
                },
                toggleTidakMenyetorMurajaah(id) {
                    let cur = this.getItem(id);
                    if (cur.surah_murajaah === 'tidak_menyetor' || cur.surah_murajaah === '-') {
                        let s = this.students.find(st => st.id === id);
                        let defaultNext = s ? s.next_surah_murajaah : '';
                        this.updateItem(id, 'surah_murajaah', defaultNext);
                        this.updateItem(id, 'ayah_murajaah', s ? s.next_ayah_murajaah : (defaultNext ? '1-Akhir' : ''));
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz_murajaah', s ? s.next_juz_murajaah : (defaultNext ? 30 : ''));
                        }
                    } else {
                        this.updateItem(id, 'surah_murajaah', 'tidak_menyetor');
                        this.updateItem(id, 'ayah_murajaah', '-');
                        if (!this.isTahsin) {
                            this.updateItem(id, 'juz_murajaah', '-');
                        }
                    }
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
                applyContinuations() {
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) {
                            this.updateItem(s.id, 'juz', s.next_juz);
                            this.updateItem(s.id, 'surah', s.next_surah);
                            this.updateItem(s.id, 'ayah', s.next_ayah);
                            this.updateItem(s.id, 'juz_murajaah', s.next_juz_murajaah);
                            this.updateItem(s.id, 'surah_murajaah', s.next_surah_murajaah);
                            this.updateItem(s.id, 'ayah_murajaah', s.next_ayah_murajaah);
                            if (s.scale_score) this.updateScore(s.id, s.scale_score);
                            if (s.predikat) this.updateItem(s.id, 'predikat', s.predikat);
                        }
                    });
                },
                applyQuickDefaults() {
                    this.students.forEach(s => {
                        if (this.getItem(s.id).active) {
                            this.updateItem(s.id, 'juz', this.defaultJuz);
                            this.updateItem(s.id, 'surah', this.defaultSurah);
                            this.updateItem(s.id, 'ayah', this.defaultAyah);
                            if (this.defaultJuzMurajaah) this.updateItem(s.id, 'juz_murajaah', this.defaultJuzMurajaah);
                            if (this.defaultSurahMurajaah) this.updateItem(s.id, 'surah_murajaah', this.defaultSurahMurajaah);
                            if (this.defaultAyahMurajaah) this.updateItem(s.id, 'ayah_murajaah', this.defaultAyahMurajaah);
                            this.updateScore(s.id, this.defaultScaleScore);
                        }
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
                
                {{-- Baris 1: Status Ringkasan & Pencarian --}}
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
                        <button
                            type="button"
                            @click="applyContinuations()"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition"
                            :title="isTahsin ? 'Otomatis muat kelanjutan jilid & halaman terakhir masing-masing santri' : 'Otomatis muat kelanjutan surat & ayat terakhir masing-masing santri'"
                        >
                            <span>🔄</span> Lanjutkan Setoran Terakhir
                        </button>
                        <button
                            type="button"
                            @click="applyQuickDefaults()"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 transition"
                            :title="isTahsin ? 'Terapkan pengaturan default jilid & halaman ke seluruh santri yang dicentang' : 'Terapkan pengaturan default (Ziyadah & Muraja\'ah) ke seluruh santri yang dicentang'"
                        >
                            <span>⚡</span> Terapkan Default ke Rombel
                        </button>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                    {{-- Quick Fill Muraja'ah Multiple Choice Rombel --}}
                    <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/60">
                        <span class="text-xs font-bold text-blue-900 dark:text-blue-200 flex items-center gap-1">
                            <span>🔁</span> Muraja'ah Rombel:
                        </span>
                        <select
                            x-model="quickMurajaahPreset"
                            class="flex-1 min-w-[150px] px-2.5 py-1 text-xs font-semibold rounded-lg border border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        >
                            @if(!empty($isTahsin))
                                <option value="jilid_1">📖 Jilid 1</option>
                                <option value="jilid_2">📖 Jilid 2</option>
                                <option value="jilid_3">📖 Jilid 3</option>
                                <option value="jilid_1_2">📖 Jilid 1 & 2 (Multiple Choice)</option>
                            @else
                                <option value="3_qul">⚡ 3 Qul (Al-Ikhlas, Al-Falaq, An-Nas)</option>
                                <option value="an_nas_kafirun">⚡ Al-Kafirun s.d. An-Nas (6 Surat)</option>
                                <option value="an_nas_fil">⚡ Al-Fil s.d. An-Nas (10 Surat)</option>
                                <option value="an_nas_duha">⚡ Ad-Duha s.d. An-Nas (22 Surat)</option>
                                <option value="lail_naba">⚡ Al-Lail s.d. An-Naba (15 Surat)</option>
                                <option value="juz_30_full">⚡ Juz 30 Lengkap (78 - 114)</option>
                            @endif
                            <option value="tidak_menyetor">❌ Hari Ini Tidak Muraja'ah</option>
                        </select>
                        <button
                            type="button"
                            @click="applyQuickMurajaahToRombel(quickMurajaahPreset)"
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white shadow-xs transition"
                            title="Terapkan paket muraja'ah pilihan ganda ini ke seluruh santri yang aktif"
                        >
                            Terapkan
                        </button>
                    </div>

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

            {{-- TABEL SANTRI FORMAT JURNAL MENGAJAR AL-QUR'AN (DILENGKAPI ZIYADAH & MURAJA'AH) --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            {{-- Header Tingkat 1: Pengelompokan Ziyadah, Muraja'ah, Nilai, dan Catatan --}}
                            <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-bold uppercase tracking-wider text-[11px]">
                                <th rowspan="2" class="px-3.5 py-3 w-10 text-center border-r border-gray-200 dark:border-gray-700">
                                    <input
                                        type="checkbox"
                                        checked
                                        @change="setAllActive($event.target.checked)"
                                        class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                        title="Pilih / Batalkan Semua"
                                    />
                                </th>
                                <th rowspan="2" class="px-4 py-3 min-w-[180px] border-r border-gray-200 dark:border-gray-700">
                                    Nama Lengkap Santri
                                </th>
                                
                                {{-- Grup Ziyadah / Capaian Tahsin --}}
                                @if(!empty($isTahsin))
                                    <th colspan="2" class="px-3 py-2 text-center bg-emerald-50/80 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 border-r border-gray-200 dark:border-gray-700">
                                        📖 Capaian Tahsin (Materi Jilid & Halaman Baru)
                                    </th>
                                    <th colspan="2" class="px-3 py-2 text-center bg-blue-50/80 dark:bg-blue-950/60 text-blue-900 dark:text-blue-200 border-r border-gray-200 dark:border-gray-700">
                                        🔁 Capaian Muraja'ah (Pilihan Ganda / Multiple Choice)
                                    </th>
                                @else
                                    <th colspan="3" class="px-3 py-2 text-center bg-emerald-50/80 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 border-r border-gray-200 dark:border-gray-700">
                                        📖 Capaian Ziyadah (Hafalan Baru)
                                    </th>
                                    <th colspan="3" class="px-3 py-2 text-center bg-blue-50/80 dark:bg-blue-950/60 text-blue-900 dark:text-blue-200 border-r border-gray-200 dark:border-gray-700">
                                        🔁 Capaian Muraja'ah (Pilihan Ganda / Multiple Choice)
                                    </th>
                                @endif

                                {{-- Grup Kualitas Bacaan --}}
                                <th colspan="2" class="px-3 py-2 text-center bg-amber-50/80 dark:bg-amber-950/60 text-amber-900 dark:text-amber-200 border-r border-gray-200 dark:border-gray-700">
                                    ⭐ Nilai Skala Kualitas Bacaan
                                </th>

                                <th rowspan="2" class="px-4 py-3 min-w-[260px]">
                                    Catatan Evaluasi Guru
                                </th>
                            </tr>

                            {{-- Header Tingkat 2: Sub-kolom Spesifik --}}
                            <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-semibold text-[10px]">
                                @if(!empty($isTahsin))
                                    {{-- Sub-kolom Tahsin (Jilid Berapa & Halaman Berapa) --}}
                                    <th class="px-2 py-2 min-w-[140px] bg-emerald-50/40 dark:bg-emerald-950/30">Jilid Berapa</th>
                                    <th class="px-2 py-2 w-28 text-center bg-emerald-50/40 dark:bg-emerald-950/30 border-r border-gray-200 dark:border-gray-700">Halaman Berapa</th>

                                    {{-- Sub-kolom Muraja'ah Tahsin --}}
                                    <th class="px-2 py-2 min-w-[180px] bg-blue-50/40 dark:bg-blue-950/30">Jilid (Multiple Choice)</th>
                                    <th class="px-2 py-2 w-28 text-center bg-blue-50/40 dark:bg-blue-950/30 border-r border-gray-200 dark:border-gray-700">Halaman Berapa</th>
                                @else
                                    {{-- Sub-kolom Ziyadah Tahfidz --}}
                                    <th class="px-2 py-2 w-20 text-center bg-emerald-50/40 dark:bg-emerald-950/30">Juz</th>
                                    <th class="px-2 py-2 min-w-[140px] bg-emerald-50/40 dark:bg-emerald-950/30">Surat</th>
                                    <th class="px-2 py-2 w-24 text-center bg-emerald-50/40 dark:bg-emerald-950/30 border-r border-gray-200 dark:border-gray-700">Ayat</th>

                                    {{-- Sub-kolom Muraja'ah Tahfidz --}}
                                    <th class="px-2 py-2 w-20 text-center bg-blue-50/40 dark:bg-blue-950/30">Juz</th>
                                    <th class="px-2 py-2 min-w-[190px] bg-blue-50/40 dark:bg-blue-950/30">Surat (Multiple Choice)</th>
                                    <th class="px-2 py-2 w-24 text-center bg-blue-50/40 dark:bg-blue-950/30 border-r border-gray-200 dark:border-gray-700">Ayat</th>
                                @endif

                                {{-- Sub-kolom Kualitas --}}
                                <th class="px-2 py-2 w-20 text-center bg-amber-50/40 dark:bg-amber-950/30">Skor (0-100)</th>
                                <th class="px-2 py-2 min-w-[130px] text-center bg-amber-50/40 dark:bg-amber-950/30 border-r border-gray-200 dark:border-gray-700">Predikat</th>
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
                                    <td class="px-3.5 py-3 text-center border-r border-gray-100 dark:border-gray-800">
                                        <input
                                            type="checkbox"
                                            :checked="getItem(student.id).active"
                                            @change="toggleActive(student.id)"
                                            class="rounded border-gray-300 text-emerald-600 shadow-xs focus:ring-emerald-500"
                                            title="Centang jika santri menyetor hari ini"
                                        />
                                    </td>

                                    {{-- Identitas Santri --}}
                                    <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5"
                                                :class="student.gender === 'P' ? 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'"
                                                x-text="student.gender === 'P' ? '♀' : '♂'"
                                            ></div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-gray-900 dark:text-white leading-tight" x-text="student.name"></div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                    NIS: <span x-text="student.nis"></span>
                                                </div>
                                                <template x-if="student.has_previous && student.last_progress_label">
                                                    <div class="mt-1 inline-flex items-center gap-1 text-[9px] font-medium text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800 max-w-full truncate">
                                                        <span>📌 Terakhir:</span>
                                                        <strong class="truncate" x-text="student.last_progress_label"></strong>
                                                    </div>
                                                </template>
                                                <template x-if="!student.has_previous">
                                                    <div class="mt-0.5 text-[9px] text-gray-400 dark:text-gray-500 italic">
                                                        (Belum ada riwayat)
                                                    </div>
                                                </template>
                                                <template x-if="getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-'">
                                                    <div class="mt-1 inline-flex items-center gap-1 text-[9px] font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded-md border border-rose-200 dark:border-rose-800">
                                                        <span>❌ Ziyadah: Tidak Setor</span>
                                                    </div>
                                                </template>
                                                <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-'">
                                                    <div class="mt-1 inline-flex items-center gap-1 text-[9px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded-md border border-amber-200 dark:border-amber-800">
                                                        <span>❌ Muraja'ah: Tidak Setor</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>

                                    @if(!empty($isTahsin))
                                        {{-- 1. Tahsin: Dropdown Jilid Berapa --}}
                                        <td class="px-2 py-3 bg-emerald-50/20 dark:bg-emerald-950/10">
                                            <div class="space-y-1">
                                                <select
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).surah"
                                                    @change="updateZiyadahSurah(student.id, $event.target.value)"
                                                    class="w-full text-xs font-semibold px-2 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                    :class="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? 'text-center font-bold text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60' : ''"
                                                >
                                                    <option value="tidak_menyetor" class="font-bold text-gray-700 dark:text-gray-300">- (Tidak Setor)</option>
                                                    <template x-for="(label, key) in jilidOptions" :key="'z-jilid-' + key">
                                                        <option :value="key" x-text="label" :selected="String(key) === String(getItem(student.id).surah)"></option>
                                                    </template>
                                                </select>
                                                <button
                                                    type="button"
                                                    x-show="getItem(student.id).active"
                                                    @click="toggleTidakMenyetorZiyadah(student.id)"
                                                    class="text-[9px] font-medium transition block text-center w-full"
                                                    :class="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? 'text-emerald-600 hover:text-emerald-700 font-bold' : 'text-gray-400 hover:text-rose-600'"
                                                    x-text="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? '🔄 Pulihkan Setoran' : '❌ Tidak Setor'"
                                                ></button>
                                            </div>
                                        </td>

                                        {{-- 1. Tahsin: Kolom Ketik Halaman Berapa --}}
                                        <td class="px-2 py-3 text-center bg-emerald-50/20 dark:bg-emerald-950/10 border-r border-gray-100 dark:border-gray-800">
                                            <template x-if="getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-'">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah !== 'tidak_menyetor' && getItem(student.id).surah !== '-'">
                                                <input
                                                    type="text"
                                                    placeholder="Hal. 1-5"
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).ayah"
                                                    @input="updateItem(student.id, 'ayah', $event.target.value)"
                                                    class="w-24 text-center text-xs font-semibold px-2 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                />
                                            </template>
                                        </td>

                                        {{-- 2. Muraja'ah Tahsin: Multiple Choice Jilid --}}
                                        <td class="px-2 py-3 bg-blue-50/20 dark:bg-blue-950/10">
                                            <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-'">
                                                <div class="space-y-1 text-center">
                                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-800">
                                                        ❌ Tidak Setor
                                                    </span>
                                                    <button
                                                        type="button"
                                                        x-show="getItem(student.id).active"
                                                        @click="toggleTidakMenyetorMurajaah(student.id)"
                                                        class="text-[9px] font-medium text-blue-600 hover:text-blue-700 block text-center w-full"
                                                    >
                                                        🔄 Pulihkan Setoran
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="getItem(student.id).surah_murajaah !== 'tidak_menyetor' && getItem(student.id).surah_murajaah !== '-'">
                                                <div class="space-y-1.5 min-w-[170px]">
                                                    {{-- Daftar Chip Jilid Terpilih --}}
                                                    <div class="flex flex-wrap items-center gap-1" x-show="getSelectedMurajaahArray(student.id).length > 0">
                                                        <template x-for="sKey in getSelectedMurajaahArray(student.id)" :key="'t-chip-' + student.id + '-' + sKey">
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                                                                <span x-text="getMurajaahLabel(sKey)"></span>
                                                                <button
                                                                    type="button"
                                                                    :disabled="!getItem(student.id).active"
                                                                    @click.stop="toggleMurajaahChoice(student.id, sKey)"
                                                                    class="text-blue-500 hover:text-rose-600 font-extrabold ml-0.5"
                                                                    title="Hapus jilid ini"
                                                                >×</button>
                                                            </span>
                                                        </template>
                                                    </div>

                                                    {{-- Tombol Buka Modal Multiple Choice --}}
                                                    <button
                                                        type="button"
                                                        :disabled="!getItem(student.id).active"
                                                        @click="openMurajaahModal(student.id)"
                                                        class="w-full text-left px-2 py-1.5 rounded-lg border text-xs font-semibold transition flex items-center justify-between"
                                                        :class="getSelectedMurajaahArray(student.id).length > 0 
                                                            ? 'border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/30' 
                                                            : 'border-dashed border-blue-300 dark:border-blue-700 hover:border-blue-500 bg-blue-50/40 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300'"
                                                    >
                                                        <span class="truncate" x-text="getSelectedMurajaahArray(student.id).length > 0 
                                                            ? '+ Ubah (' + getSelectedMurajaahArray(student.id).length + ' Jilid)' 
                                                            : '+ Multiple Choice Jilid...'"></span>
                                                        <span class="text-xs text-blue-500 shrink-0 ml-1">▾</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        x-show="getItem(student.id).active"
                                                        @click="toggleTidakMenyetorMurajaah(student.id)"
                                                        class="text-[9px] font-medium text-gray-400 hover:text-rose-600 transition block text-center w-full"
                                                    >
                                                        ❌ Tidak Setor
                                                    </button>
                                                </div>
                                            </template>
                                        </td>

                                        {{-- 2. Muraja'ah Tahsin: Kolom Ketik Halaman Berapa --}}
                                        <td class="px-2 py-3 text-center bg-blue-50/20 dark:bg-blue-950/10 border-r border-gray-100 dark:border-gray-800">
                                            <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-'">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah_murajaah !== 'tidak_menyetor' && getItem(student.id).surah_murajaah !== '-'">
                                                <input
                                                    type="text"
                                                    placeholder="Hal. 1-5"
                                                    :disabled="!getItem(student.id).active || !getItem(student.id).surah_murajaah"
                                                    :value="getItem(student.id).ayah_murajaah"
                                                    @input="updateItem(student.id, 'ayah_murajaah', $event.target.value)"
                                                    class="w-24 text-center text-xs font-semibold px-2 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                />
                                            </template>
                                        </td>
                                    @else
                                        {{-- 1. Ziyadah: Dropdown Juz --}}
                                        <td class="px-2 py-3 text-center bg-emerald-50/20 dark:bg-emerald-950/10">
                                            <template x-if="getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-'">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah !== 'tidak_menyetor' && getItem(student.id).surah !== '-'">
                                                <select
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).juz"
                                                    @change="updateItem(student.id, 'juz', $event.target.value)"
                                                    class="w-18 text-center text-xs font-bold px-1.5 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                >
                                                    <template x-for="j in 30" :key="'z-juz-' + j">
                                                        <option :value="j" x-text="'Juz ' + j" :selected="Number(j) === Number(getItem(student.id).juz)"></option>
                                                    </template>
                                                </select>
                                            </template>
                                        </td>

                                        {{-- 1. Ziyadah: Dropdown Surat --}}
                                        <td class="px-2 py-3 bg-emerald-50/20 dark:bg-emerald-950/10">
                                            <div class="space-y-1">
                                                <select
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).surah"
                                                    @change="updateZiyadahSurah(student.id, $event.target.value)"
                                                    class="w-full text-xs font-semibold px-2 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                    :class="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? 'text-center font-bold text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60' : ''"
                                                >
                                                    <option value="tidak_menyetor" class="font-bold text-gray-700 dark:text-gray-300">- (Tidak Setor)</option>
                                                    <template x-for="(name, num) in surahs" :key="'z-surah-' + num">
                                                        <option :value="num" x-text="num + '. ' + name" :selected="String(num) === String(getItem(student.id).surah)"></option>
                                                    </template>
                                                </select>
                                                <button
                                                    type="button"
                                                    x-show="getItem(student.id).active"
                                                    @click="toggleTidakMenyetorZiyadah(student.id)"
                                                    class="text-[9px] font-medium transition block text-center w-full"
                                                    :class="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? 'text-emerald-600 hover:text-emerald-700 font-bold' : 'text-gray-400 hover:text-rose-600'"
                                                    x-text="(getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-') ? '🔄 Pulihkan Setoran' : '❌ Tidak Setor'"
                                                ></button>
                                            </div>
                                        </td>

                                        {{-- 1. Ziyadah: Kolom Ketik Ayat --}}
                                        <td class="px-2 py-3 text-center bg-emerald-50/20 dark:bg-emerald-950/10 border-r border-gray-100 dark:border-gray-800">
                                            <template x-if="getItem(student.id).surah === 'tidak_menyetor' || getItem(student.id).surah === '-'">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah !== 'tidak_menyetor' && getItem(student.id).surah !== '-'">
                                                <input
                                                    type="text"
                                                    placeholder="1-10"
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).ayah"
                                                    @input="updateItem(student.id, 'ayah', $event.target.value)"
                                                    class="w-20 text-center text-xs font-mono px-2 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                />
                                            </template>
                                        </td>

                                        {{-- 2. Muraja'ah: Dropdown Juz --}}
                                        <td class="px-2 py-3 text-center bg-blue-50/20 dark:bg-blue-950/10">
                                            <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-' || !getItem(student.id).surah_murajaah">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah_murajaah !== 'tidak_menyetor' && getItem(student.id).surah_murajaah !== '-' && getItem(student.id).surah_murajaah">
                                                <select
                                                    :disabled="!getItem(student.id).active"
                                                    :value="getItem(student.id).juz_murajaah"
                                                    @change="updateItem(student.id, 'juz_murajaah', $event.target.value)"
                                                    class="w-18 text-center text-xs font-semibold px-1.5 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                >
                                                    <option value="">-</option>
                                                    <template x-for="j in 30" :key="'m-juz-' + j">
                                                        <option :value="j" x-text="'Juz ' + j" :selected="Number(j) === Number(getItem(student.id).juz_murajaah)"></option>
                                                    </template>
                                                </select>
                                            </template>
                                        </td>

                                        {{-- 2. Muraja'ah Tahfidz: Multiple Choice Surat --}}
                                        <td class="px-2 py-3 bg-blue-50/20 dark:bg-blue-950/10">
                                            <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-'">
                                                <div class="space-y-1 text-center">
                                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-800">
                                                        ❌ Tidak Setor
                                                    </span>
                                                    <button
                                                        type="button"
                                                        x-show="getItem(student.id).active"
                                                        @click="toggleTidakMenyetorMurajaah(student.id)"
                                                        class="text-[9px] font-medium text-blue-600 hover:text-blue-700 block text-center w-full"
                                                    >
                                                        🔄 Pulihkan Setoran
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="getItem(student.id).surah_murajaah !== 'tidak_menyetor' && getItem(student.id).surah_murajaah !== '-'">
                                                <div class="space-y-1.5 min-w-[190px]">
                                                    {{-- Daftar Chip Surat Terpilih --}}
                                                    <div class="flex flex-wrap items-center gap-1" x-show="getSelectedMurajaahArray(student.id).length > 0">
                                                        <template x-for="sKey in getSelectedMurajaahArray(student.id)" :key="'m-chip-' + student.id + '-' + sKey">
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                                                                <span x-text="getMurajaahLabel(sKey)"></span>
                                                                <button
                                                                    type="button"
                                                                    :disabled="!getItem(student.id).active"
                                                                    @click.stop="toggleMurajaahChoice(student.id, sKey)"
                                                                    class="text-blue-500 hover:text-rose-600 font-extrabold ml-0.5"
                                                                    title="Hapus surat ini"
                                                                >×</button>
                                                            </span>
                                                        </template>
                                                    </div>

                                                    {{-- Tombol Buka Modal Multiple Choice --}}
                                                    <button
                                                        type="button"
                                                        :disabled="!getItem(student.id).active"
                                                        @click="openMurajaahModal(student.id)"
                                                        class="w-full text-left px-2 py-1.5 rounded-lg border text-xs font-semibold transition flex items-center justify-between"
                                                        :class="getSelectedMurajaahArray(student.id).length > 0 
                                                            ? 'border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/30' 
                                                            : 'border-dashed border-blue-300 dark:border-blue-700 hover:border-blue-500 bg-blue-50/40 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300'"
                                                    >
                                                        <span class="truncate" x-text="getSelectedMurajaahArray(student.id).length > 0 
                                                            ? '+ Ubah (' + getSelectedMurajaahArray(student.id).length + ' Surat)' 
                                                            : '+ Multiple Choice Surat...'"></span>
                                                        <span class="text-xs text-blue-500 shrink-0 ml-1">▾</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        x-show="getItem(student.id).active"
                                                        @click="toggleTidakMenyetorMurajaah(student.id)"
                                                        class="text-[9px] font-medium text-gray-400 hover:text-rose-600 transition block text-center w-full"
                                                    >
                                                        ❌ Tidak Setor
                                                    </button>
                                                </div>
                                            </template>
                                        </td>

                                        {{-- 2. Muraja'ah: Kolom Ketik Ayat --}}
                                        <td class="px-2 py-3 text-center bg-blue-50/20 dark:bg-blue-950/10 border-r border-gray-100 dark:border-gray-800">
                                            <template x-if="getItem(student.id).surah_murajaah === 'tidak_menyetor' || getItem(student.id).surah_murajaah === '-'">
                                                <span class="inline-flex items-center justify-center w-full py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">-</span>
                                            </template>
                                            <template x-if="getItem(student.id).surah_murajaah !== 'tidak_menyetor' && getItem(student.id).surah_murajaah !== '-'">
                                                <input
                                                    type="text"
                                                    placeholder="1-Akhir"
                                                    :disabled="!getItem(student.id).active || !getItem(student.id).surah_murajaah"
                                                    :value="getItem(student.id).ayah_murajaah"
                                                    @input="updateItem(student.id, 'ayah_murajaah', $event.target.value)"
                                                    class="w-20 text-center text-xs font-mono px-2 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                                />
                                            </template>
                                        </td>
                                    @endif

                                    {{-- 3. Skor Nilai Skala (0-100) --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/20 dark:bg-amber-950/10">
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="85"
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).scale_score !== undefined ? getItem(student.id).scale_score : defaultScaleScore"
                                            @input="updateScore(student.id, $event.target.value)"
                                            class="w-16 text-center text-xs font-bold px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                        />
                                    </td>

                                    {{-- 3. Predikat Kelulusan --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/20 dark:bg-amber-950/10 border-r border-gray-100 dark:border-gray-800">
                                        <select
                                            :disabled="!getItem(student.id).active"
                                            :value="getItem(student.id).predikat"
                                            @change="updateItem(student.id, 'predikat', $event.target.value)"
                                            class="w-full text-xs font-bold px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800 disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                            :class="{
                                                'text-emerald-700 dark:text-emerald-300': getItem(student.id).predikat === 'Mumtaz',
                                                'text-blue-700 dark:text-blue-300': getItem(student.id).predikat === 'Jayyid Jiddan',
                                                'text-amber-700 dark:text-amber-300': getItem(student.id).predikat === 'Jayyid',
                                                'text-rose-700 dark:text-rose-300': getItem(student.id).predikat === 'Maqbul'
                                            }"
                                        >
                                            <option value="Mumtaz">Mumtaz</option>
                                            <option value="Jayyid Jiddan">Jayyid Jiddan</option>
                                            <option value="Jayyid">Jayyid</option>
                                            <option value="Maqbul">Maqbul</option>
                                        </select>
                                    </td>

                                    {{-- 4. Catatan Evaluasi Guru --}}
                                    <td class="px-3 py-3">
                                        <div class="space-y-1.5">
                                            <input
                                                type="text"
                                                placeholder="Ketik catatan evaluasi..."
                                                :disabled="!getItem(student.id).active"
                                                :value="getItem(student.id).catatan"
                                                @input="updateItem(student.id, 'catatan', $event.target.value)"
                                                class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition disabled:bg-gray-100 dark:disabled:bg-gray-800/40"
                                            />

                                            {{-- Pintasan Preset Catatan Cepat --}}
                                            <div class="flex flex-wrap items-center gap-1 text-[10px]" x-show="getItem(student.id).active">
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Mutqin lancar sekali tanpa salah, tajwid sempurna.')"
                                                    class="px-1.5 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition text-[9px]"
                                                >
                                                    ⭐ Mutqin
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Lancar baik, 1-2 kali peringatan/teguran.')"
                                                    class="px-1.5 py-0.5 rounded-md bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition text-[9px]"
                                                >
                                                    ✅ Lancar
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Belum mutqin, dianjurkan mengulang setoran esok hari.')"
                                                    class="px-1.5 py-0.5 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition text-[9px]"
                                                >
                                                    🔄 Muraja'ah
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="applyPreset(student.id, 'Tidak ada setoran Al-Qur\'an hari ini.')"
                                                    class="px-1.5 py-0.5 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 transition text-[9px]"
                                                >
                                                    ❌ Tidak Menyetor
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

            {{-- MODAL DIALOG MULTIPLE CHOICE MURAJA'AH --}}
            <div
                x-show="activeStudentForMurajaah !== null"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
                @keydown.escape.window="activeStudentForMurajaah = null"
            >
                <div
                    class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-2xl w-full p-5 space-y-4 max-h-[90vh] flex flex-col"
                    @click.outside="activeStudentForMurajaah = null"
                >
                    {{-- Header Modal --}}
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                <span>🔁</span> Pilihan Ganda (Multiple Choice) Capaian Muraja'ah
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Santri: <strong class="text-blue-600 dark:text-blue-400" x-text="getActiveStudent()?.name"></strong> • Centang satu atau beberapa surat/jilid yang dimuraja'ahkan hari ini.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="activeStudentForMurajaah = null"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800 transition"
                        >
                            ✕
                        </button>
                    </div>

                    {{-- Paket Pilihan Cepat (Quick Presets) --}}
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <span>⚡</span> Pilihan Cepat (Sekali Klik):
                        </span>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            @if(!empty($isTahsin))
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'jilid_1')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">Jilid 1</button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'jilid_2')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">Jilid 2</button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'jilid_3')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">Jilid 3</button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'jilid_1_2')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">Jilid 1 & 2</button>
                            @else
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, '3_qul')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">
                                    ⚡ 3 Qul (Ikhlas, Falaq, Nas)
                                </button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'an_nas_kafirun')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">
                                    ⚡ Kafirun s.d. Nas (6 Surat)
                                </button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'an_nas_fil')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">
                                    ⚡ Al-Fil s.d. Nas (10 Surat)
                                </button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'an_nas_duha')" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800 transition">
                                    ⚡ Ad-Duha s.d. Nas
                                </button>
                                <button type="button" @click="applyPresetMurajaah(activeStudentForMurajaah, 'juz_30_full')" class="px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-bold border border-purple-200 dark:border-purple-800 transition">
                                    ⚡ Juz 30 Lengkap
                                </button>
                            @endif
                            <button type="button" @click="setMurajaahChoices(activeStudentForMurajaah, [])" class="px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold border border-gray-300 dark:border-gray-600 transition">
                                🗑️ Kosongkan
                            </button>
                        </div>
                    </div>

                    {{-- Pencarian Surat --}}
                    <div>
                        <input
                            type="text"
                            x-model="searchSurahMurajaah"
                            placeholder="🔍 Ketik nama surat atau nomor... (misal: ikhlas, falaq, nas, yasin)"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 transition"
                        />
                    </div>

                    {{-- Grid Checklist Checkboxes (Multiple Choice) --}}
                    <div class="flex-1 overflow-y-auto p-1 max-h-[360px] border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/50">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1.5 p-2">
                            <template x-for="item in getFilteredMurajaahOptions()" :key="'chk-' + item.key">
                                <label
                                    class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl border cursor-pointer text-xs font-semibold transition select-none"
                                    :class="isMurajaahSelected(activeStudentForMurajaah, item.key)
                                        ? 'bg-blue-100/80 dark:bg-blue-950/80 border-blue-400 text-blue-900 dark:text-blue-100'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-blue-50/50'"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isMurajaahSelected(activeStudentForMurajaah, item.key)"
                                        @change="toggleMurajaahChoice(activeStudentForMurajaah, item.key)"
                                        class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 border-gray-300 cursor-pointer"
                                    />
                                    <span class="truncate" x-text="item.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Footer Modal --}}
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-800">
                        <div class="text-xs font-bold text-gray-600 dark:text-gray-400">
                            Terpilih: <strong class="text-blue-600 dark:text-blue-400" x-text="getSelectedMurajaahArray(activeStudentForMurajaah).length"></strong> Pilihan
                        </div>
                        <button
                            type="button"
                            @click="activeStudentForMurajaah = null"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-xs transition cursor-pointer"
                        >
                            ✓ Selesai & Simpan Pilihan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-dynamic-component>
