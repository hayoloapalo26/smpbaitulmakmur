@php
    $statePath = $getStatePath();
    $charStatePath = $charStatePath ?? str_replace('student_attendances', 'catatan_karakter', $statePath);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($students->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center bg-gray-50/50 dark:bg-gray-800/40">
            <x-heroicon-o-user-group class="w-10 h-10 text-gray-400 mx-auto mb-2" />
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ $classroomId ? 'Belum ada data siswa terdaftar di kelas ini.' : 'Pilih Jadwal Mengajar Terlebih Dahulu' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                {{ $classroomId ? 'Silakan pastikan data rombongan belajar (rombel) telah diisi untuk kelas ini.' : 'Daftar presensi siswa per mapel dengan 4 pilihan (Hadir, Sakit, Izin, Alpha) dan tombol input Karakter Baik / Karakter Buruk per siswa akan otomatis dimuat setelah Jadwal Mengajar dipilih di atas.' }}
            </p>
        </div>
    @else
        <div
            wire:key="{{ (isset($this) && method_exists($this, 'getId') ? $this->getId() : $field->getId()) }}.{{ $statePath }}.class-{{ $classroomId ?? 'empty' }}"
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                characters: $wire.{{ $applyStateBindingModifiers("\$entangle('{$charStatePath}')") }},
                search: '',
                activeCharInput: null,
                students: @js($students->map(fn($s) => [
                    'id' => (string) $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis ?? '-',
                    'gender' => $s->jenis_kelamin,
                ])),
                presetBaik: [
                    'Sangat aktif & antusias dalam pembelajaran',
                    'Berani maju mempresentasikan tugas di depan kelas',
                    'Membantu dan membimbing temannya yang kesulitan',
                    'Sangat disiplin, tertib, dan fokus sepanjang pelajaran',
                    'Menunjukkan sikap sopan santun dan akhlak terpuji',
                    'Jujur dan bertanggung jawab menyelesaikan tugas mandiri'
                ],
                presetKurangBaik: [
                    'Ramai dan membuat kegaduhan saat guru menjelaskan',
                    'Mengganggu dan usil kepada teman saat pembelajaran',
                    'Tidur / mengantuk di kelas saat jam pelajaran berlangsung',
                    'Tidak fokus / bermain benda lain saat jam pelajaran',
                    'Tidak membawa perlengkapan belajar / tugas belum selesai',
                    'Meninggalkan kelas tanpa izin guru pengajar'
                ],
                init() {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        this.state = {};
                    }
                    let updated = { ...this.state };
                    let changed = false;
                    this.students.forEach(s => {
                        if (!updated[s.id]) {
                            updated[s.id] = 'hadir';
                            changed = true;
                        }
                    });
                    if (changed) {
                        this.state = updated;
                    }

                    if (!this.characters || !Array.isArray(this.characters)) {
                        this.characters = [];
                    }
                },
                setStatus(id, val) {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        this.state = {};
                    }
                    this.state = {
                        ...this.state,
                        [id]: val,
                    };
                },
                getStatus(id) {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        return 'hadir';
                    }
                    return this.state[id] || 'hadir';
                },
                setAll(val) {
                    let updated = {};
                    this.students.forEach(s => {
                        updated[s.id] = val;
                    });
                    this.state = updated;
                },
                countStatus(val) {
                    if (!this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                        return val === 'hadir' ? this.students.length : 0;
                    }
                    let count = 0;
                    this.students.forEach(s => {
                        let cur = this.state[s.id] || 'hadir';
                        if (cur === val) {
                            count++;
                        }
                    });
                    return count;
                },
                getStudentCharacters(studentId) {
                    if (!this.characters || !Array.isArray(this.characters)) return [];
                    return this.characters.filter(c => String(c.student_id) === String(studentId));
                },
                openCharInput(studentId, type) {
                    if (this.activeCharInput && this.activeCharInput.studentId === String(studentId) && this.activeCharInput.type === type) {
                        this.activeCharInput = null;
                        return;
                    }
                    this.activeCharInput = {
                        studentId: String(studentId),
                        type: type,
                        kategori: type === 'baik' ? 'Keaktifan & Partisipasi' : 'Kedisiplinan & Ketertiban',
                        catatan: '',
                    };
                },
                cancelCharInput() {
                    this.activeCharInput = null;
                },
                saveCharInput() {
                    if (!this.activeCharInput) return;
                    let note = (this.activeCharInput.catatan || '').trim();
                    let kat = (this.activeCharInput.kategori || '').trim();
                    if (!kat) {
                        kat = this.activeCharInput.type === 'baik' ? 'Keaktifan & Partisipasi' : 'Kedisiplinan & Ketertiban';
                    }
                    if (!note) {
                        note = kat;
                    }

                    if (!this.characters || !Array.isArray(this.characters)) {
                        this.characters = [];
                    }

                    this.characters = [
                        ...this.characters,
                        {
                            uid: Date.now() + Math.random(),
                            type: this.activeCharInput.type,
                            student_id: String(this.activeCharInput.studentId),
                            kategori: kat,
                            catatan: note,
                        }
                    ];

                    this.activeCharInput = null;
                },
                removeCharacter(item) {
                    if (!this.characters || !Array.isArray(this.characters)) return;
                    this.characters = this.characters.filter(c => {
                        if (item.uid && c.uid) return c.uid !== item.uid;
                        return !(String(c.student_id) === String(item.student_id) && c.catatan === item.catatan && c.type === item.type);
                    });
                },
                countCharacters(type) {
                    if (!this.characters || !Array.isArray(this.characters)) return 0;
                    return this.characters.filter(c => c.type === type).length;
                }
            }"
            class="space-y-3.5"
        >
            {{-- Toolbar: Ringkasan Rekap & Quick Actions --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 p-3.5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    {{-- Badges Count --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 shadow-xs">
                            <x-heroicon-m-users class="w-3.5 h-3.5 text-gray-400" />
                            Total: <span x-text="students.length"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Hadir: <span x-text="countStatus('hadir')"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Sakit: <span x-text="countStatus('sakit')"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-300 dark:border-blue-800">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Izin: <span x-text="countStatus('izin')"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Alpha: <span x-text="countStatus('alpa')"></span>
                        </span>

                        {{-- Ringkasan Karakter Siswa --}}
                        <template x-if="countCharacters('baik') > 0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                <span>⭐</span> Karakter Baik: <span x-text="countCharacters('baik')"></span>
                            </span>
                        </template>
                        <template x-if="countCharacters('kurang_baik') > 0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                <span>⚠️</span> Karakter Buruk: <span x-text="countCharacters('kurang_baik')"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Bulk Actions --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="setAll('hadir')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition-all active:scale-95"
                            title="Tandai semua siswa hadir"
                        >
                            <x-heroicon-m-check class="w-4 h-4" />
                            Semua Hadir
                        </button>
                        <button
                            type="button"
                            @click="setAll('alpa')"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-gray-700 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 transition-all active:scale-95"
                            title="Tandai semua siswa alpha"
                        >
                            Semua Alpha
                        </button>
                    </div>
                </div>

                {{-- Search Box --}}
                <div class="relative">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Ketik untuk mencari nama atau NIS siswa..."
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700/60 py-1.5 pl-8 pr-3 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500"
                    />
                    <x-heroicon-m-magnifying-glass class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" />
                </div>
            </div>

            {{-- Grid Siswa (2 Kolom di Desktop) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 max-h-[520px] overflow-y-auto pr-1">
                @foreach($students as $index => $student)
                    @php
                        $isFemale = $student->jenis_kelamin === 'P';
                    @endphp
                    <div
                        x-show="!search || '{{ strtolower(addslashes($student->name)) }}'.includes(search.toLowerCase()) || '{{ strtolower(addslashes($student->nis ?? '')) }}'.includes(search.toLowerCase())"
                        class="p-3 rounded-xl border transition-all bg-white dark:bg-gray-800/90 shadow-2xs space-y-2.5"
                        :class="{
                            'border-emerald-300 dark:border-emerald-700/70 bg-emerald-50/20 dark:bg-emerald-950/10': getStatus('{{ $student->id }}') === 'hadir',
                            'border-amber-300 dark:border-amber-700/70 bg-amber-50/25 dark:bg-amber-950/10': getStatus('{{ $student->id }}') === 'sakit',
                            'border-blue-300 dark:border-blue-700/70 bg-blue-50/25 dark:bg-blue-950/10': getStatus('{{ $student->id }}') === 'izin',
                            'border-rose-300 dark:border-rose-700/70 bg-rose-50/25 dark:bg-rose-950/10': getStatus('{{ $student->id }}') === 'alpa',
                        }"
                    >
                        {{-- BARIS UTAMA: Identitas Siswa & 4 Pilihan Presensi --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            {{-- Identitas Siswa --}}
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-[11px] font-bold text-gray-400 w-5 text-right flex-shrink-0">
                                    {{ $index + 1 }}.
                                </span>
                                <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-white text-[10px] font-bold shadow-xs {{ $isFemale ? 'bg-pink-400' : 'bg-blue-500' }}">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 truncate">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 text-xs truncate">
                                        {{ $student->name }}
                                    </p>
                                    <p class="text-[10px] text-gray-400">
                                        NIS: {{ $student->nis ?: '-' }} • {{ $isFemale ? 'Perempuan' : 'Laki-laki' }}
                                    </p>
                                </div>
                            </div>

                            {{-- 4 Pilihan Presensi: Hadir, Sakit, Izin, Alpha --}}
                            <div class="flex items-center gap-1 flex-shrink-0 justify-end">
                                {{-- Hadir --}}
                                <button
                                    type="button"
                                    @click="setStatus('{{ $student->id }}', 'hadir')"
                                    class="px-2 py-1 text-[11px] font-bold rounded-lg border transition-all flex items-center gap-0.5 select-none"
                                    :class="getStatus('{{ $student->id }}') === 'hadir'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                        : 'bg-white dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-emerald-400 hover:text-emerald-600'"
                                >
                                    <x-heroicon-m-check x-show="getStatus('{{ $student->id }}') === 'hadir'" class="w-3 h-3" />
                                    Hadir
                                </button>

                                {{-- Sakit --}}
                                <button
                                    type="button"
                                    @click="setStatus('{{ $student->id }}', 'sakit')"
                                    class="px-2 py-1 text-[11px] font-bold rounded-lg border transition-all flex items-center gap-0.5 select-none"
                                    :class="getStatus('{{ $student->id }}') === 'sakit'
                                        ? 'bg-amber-500 text-white border-amber-500 shadow-xs'
                                        : 'bg-white dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-amber-400 hover:text-amber-600'"
                                >
                                    <x-heroicon-m-check x-show="getStatus('{{ $student->id }}') === 'sakit'" class="w-3 h-3" />
                                    Sakit
                                </button>

                                {{-- Izin --}}
                                <button
                                    type="button"
                                    @click="setStatus('{{ $student->id }}', 'izin')"
                                    class="px-2 py-1 text-[11px] font-bold rounded-lg border transition-all flex items-center gap-0.5 select-none"
                                    :class="getStatus('{{ $student->id }}') === 'izin'
                                        ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                                        : 'bg-white dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-400 hover:text-blue-600'"
                                >
                                    <x-heroicon-m-check x-show="getStatus('{{ $student->id }}') === 'izin'" class="w-3 h-3" />
                                    Izin
                                </button>

                                {{-- Alpha --}}
                                <button
                                    type="button"
                                    @click="setStatus('{{ $student->id }}', 'alpa')"
                                    class="px-2 py-1 text-[11px] font-bold rounded-lg border transition-all flex items-center gap-0.5 select-none"
                                    :class="getStatus('{{ $student->id }}') === 'alpa'
                                        ? 'bg-rose-600 text-white border-rose-600 shadow-xs'
                                        : 'bg-white dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-rose-400 hover:text-rose-600'"
                                >
                                    <x-heroicon-m-check x-show="getStatus('{{ $student->id }}') === 'alpa'" class="w-3 h-3" />
                                    Alpha
                                </button>
                            </div>
                        </div>

                        {{-- DI BAWAHNYA DATA SISWA MASING-MASING: Input & Badge Karakter Baik & Karakter Buruk --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                            {{-- Catatan Karakter yang sudah dibuat untuk siswa ini --}}
                            <div class="flex flex-wrap gap-1.5" x-show="getStudentCharacters('{{ $student->id }}').length > 0">
                                <template x-for="item in getStudentCharacters('{{ $student->id }}')" :key="item.uid || (item.kategori + item.catatan)">
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-medium border shadow-2xs"
                                        :class="item.type === 'baik'
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
                                            : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'"
                                    >
                                        <span class="font-bold flex items-center gap-1 flex-shrink-0">
                                            <template x-if="item.type === 'baik'">
                                                <span>⭐ +1</span>
                                            </template>
                                            <template x-if="item.type !== 'baik'">
                                                <span>⚠️ -1</span>
                                            </template>
                                            <span x-text="item.kategori"></span>:
                                        </span>
                                        <span class="truncate max-w-[170px] sm:max-w-[220px]" x-text="item.catatan" :title="item.catatan"></span>
                                        <button
                                            type="button"
                                            @click.stop="removeCharacter(item)"
                                            class="ml-0.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 p-0.5 rounded transition-colors flex-shrink-0"
                                            title="Hapus catatan ini"
                                        >
                                            <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </template>
                            </div>

                            {{-- 2 Tombol Input di Bawah Data Siswa Masing-Masing --}}
                            <div class="flex items-center gap-1.5 flex-wrap" x-show="!activeCharInput || activeCharInput.studentId !== '{{ $student->id }}'">
                                <button
                                    type="button"
                                    @click="openCharInput('{{ $student->id }}', 'baik')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-emerald-300 dark:border-emerald-800/80 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 transition-all active:scale-95 shadow-2xs"
                                    title="Input apresiasi karakter baik (+1 Poin) untuk siswa ini"
                                >
                                    <x-heroicon-m-hand-thumb-up class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>+ Karakter Baik</span>
                                    <span class="text-[9px] px-1 py-0.2 rounded bg-emerald-200/80 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-100 font-bold">+1</span>
                                </button>

                                <button
                                    type="button"
                                    @click="openCharInput('{{ $student->id }}', 'kurang_baik')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-rose-300 dark:border-rose-800/80 bg-rose-50/70 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 transition-all active:scale-95 shadow-2xs"
                                    title="Input pembinaan karakter buruk (-1 Poin) untuk siswa ini"
                                >
                                    <x-heroicon-m-hand-thumb-down class="w-3.5 h-3.5 text-rose-600" />
                                    <span>+ Karakter Buruk</span>
                                    <span class="text-[9px] px-1 py-0.2 rounded bg-rose-200/80 dark:bg-rose-800 text-rose-800 dark:text-rose-100 font-bold">-1</span>
                                </button>
                            </div>

                            {{-- Panel Form Input Inline Khusus Siswa Ini --}}
                            <div
                                x-show="activeCharInput && activeCharInput.studentId === '{{ $student->id }}'"
                                x-transition
                                class="p-3 rounded-xl border text-xs space-y-2 mt-1"
                                :class="activeCharInput?.type === 'baik'
                                    ? 'border-emerald-300 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/40'
                                    : 'border-rose-300 dark:border-rose-800 bg-rose-50/60 dark:bg-rose-950/40'"
                            >
                                {{-- Header Form Panel --}}
                                <div class="flex items-center justify-between pb-1.5 border-b border-gray-200/70 dark:border-gray-700/70">
                                    <div class="flex items-center gap-1.5 font-bold" :class="activeCharInput?.type === 'baik' ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">
                                        <template x-if="activeCharInput?.type === 'baik'">
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-hand-thumb-up class="w-4 h-4 text-emerald-600" />
                                                Input Karakter Baik ({{ $student->name }}) &bull; +1 Poin
                                            </span>
                                        </template>
                                        <template x-if="activeCharInput?.type !== 'baik'">
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-hand-thumb-down class="w-4 h-4 text-rose-600" />
                                                Input Karakter Buruk ({{ $student->name }}) &bull; -1 Poin
                                            </span>
                                        </template>
                                    </div>
                                    <button
                                        type="button"
                                        @click="cancelCharInput()"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-0.5 rounded transition-colors"
                                        title="Batal / Tutup"
                                    >
                                        <x-heroicon-m-x-mark class="w-4 h-4" />
                                    </button>
                                </div>

                                {{-- Kategori Perilaku --}}
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-200 mb-0.5">
                                        Kategori Aspek Perilaku:
                                    </label>
                                    <select
                                        x-model="activeCharInput.kategori"
                                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 px-2 text-gray-900 dark:text-gray-100 font-semibold focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <template x-if="activeCharInput?.type === 'baik'">
                                            <optgroup label="Aspek Karakter Baik">
                                                <option value="Keaktifan & Partisipasi">Keaktifan & Partisipasi Belajar</option>
                                                <option value="Kerjasama & Tolong-Menolong">Kerjasama & Tolong-Menolong</option>
                                                <option value="Kedisiplinan & Ketertiban">Kedisiplinan & Ketertiban</option>
                                                <option value="Keberanian & Kepemimpinan">Keberanian Maju / Kepemimpinan</option>
                                                <option value="Tanggung Jawab">Tanggung Jawab & Kejujuran</option>
                                                <option value="Santun & Berakhlak">Sopan Santun & Akhlakul Karimah</option>
                                                <option value="Lainnya">Lainnya</option>
                                            </optgroup>
                                        </template>
                                        <template x-if="activeCharInput?.type !== 'baik'">
                                            <optgroup label="Aspek Kurang Baik / Perlu Pembinaan">
                                                <option value="Kedisiplinan & Ketertiban">Kedisiplinan & Ketertiban</option>
                                                <option value="Ramai / Gaduh">Ramai & Membuat Kegaduhan</option>
                                                <option value="Mengganggu Teman">Mengganggu / Usil ke Teman</option>
                                                <option value="Tidur / Mengantuk di Kelas">Tidur / Mengantuk Saat Pelajaran</option>
                                                <option value="Tidak Fokus / Main Benda Lain">Tidak Fokus / Bermain Sendiri</option>
                                                <option value="Tidak Mengerjakan Tugas">Tidak Mengerjakan Tugas / PR</option>
                                                <option value="Lainnya">Lainnya</option>
                                            </optgroup>
                                        </template>
                                    </select>
                                </div>

                                {{-- Rekomendasi Cepat (Pill Buttons) --}}
                                <div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1">
                                        💡 Rekomendasi cepat (klik untuk mengisi otomatis):
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="rec in (activeCharInput?.type === 'baik' ? presetBaik : presetKurangBaik)" :key="rec">
                                            <button
                                                type="button"
                                                @click="activeCharInput.catatan = rec"
                                                class="text-[10px] px-2 py-0.5 rounded-md border text-left transition-all bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-primary-400"
                                                :class="activeCharInput?.catatan === rec
                                                    ? 'border-primary-500 text-primary-700 dark:text-primary-300 font-bold bg-primary-50 dark:bg-primary-950/40 shadow-2xs'
                                                    : 'border-gray-200 dark:border-gray-700'"
                                                x-text="rec"
                                            ></button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Textarea Catatan Uraian --}}
                                <div>
                                    <textarea
                                        x-model="activeCharInput.catatan"
                                        rows="2"
                                        placeholder="Tulis uraian catatan perilaku siswa atau pilih rekomendasi di atas..."
                                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 px-2.5 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500"
                                    ></textarea>
                                </div>

                                {{-- Tombol Aksi Simpan & Batal --}}
                                <div class="flex items-center justify-end gap-1.5 pt-1">
                                    <button
                                        type="button"
                                        @click="cancelCharInput()"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveCharInput()"
                                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg text-white shadow-xs transition-all active:scale-95"
                                        :class="activeCharInput?.type === 'baik' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500'"
                                    >
                                        <x-heroicon-m-check class="w-3.5 h-3.5" />
                                        Simpan Catatan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-dynamic-component>
