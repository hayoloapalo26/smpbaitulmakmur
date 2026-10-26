@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($students->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center bg-gray-50/50 dark:bg-gray-800/40">
            <x-heroicon-o-academic-cap class="w-8 h-8 text-gray-400 mx-auto mb-1.5" />
            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                Pilih Jadwal Mengajar Terlebih Dahulu
            </p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                Daftar pilihan siswa untuk pencatatan karakter akan aktif otomatis setelah jadwal & kelas dipilih di atas.
            </p>
        </div>
    @else
        <div
            wire:key="{{ $this->getId() }}.{{ $statePath }}.char-class-{{ $classroomId ?? 'empty' }}"
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                students: @js($students->map(fn($s) => [
                    'id' => (string) $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis ?? '-',
                ])),
                presetBaik: [
                    'Sangat aktif & antusias dalam pembelajaran',
                    'Berani maju mempresentasikan tugas di depan kelas',
                    'Membantu dan membimbing temannya yang kesulitan',
                    'Sangat disiplin, tertib, dan fokus sepanjang jam pelajaran',
                    'Menunjukkan sikap sopan santun dan akhlak terpuji',
                    'Jujur dan bertanggung jawab menyelesaikan tugas mandiri'
                ],
                presetKurangBaik: [
                    'Ramai dan membuat kegaduhan saat guru menjelaskan',
                    'Mengganggu dan usil kepada teman saat pembelajaran',
                    'Tidur / mengantuk di kelas saat jam pelajaran berlangsung',
                    'Tidak fokus / bermain benda lain saat jam pelajaran',
                    'Tidak membawa perlengkapan belajar / tidak mengerjakan tugas',
                    'Meninggalkan kelas tanpa izin guru pengajar'
                ],
                init() {
                    if (!this.state || !Array.isArray(this.state)) {
                        this.state = [];
                    }
                },
                addEntry(type) {
                    if (!this.state || !Array.isArray(this.state)) {
                        this.state = [];
                    }
                    this.state = [
                        ...this.state,
                        {
                            uid: Date.now() + Math.random(),
                            type: type,
                            student_id: this.students.length > 0 ? this.students[0].id : '',
                            kategori: type === 'baik' ? 'Keaktifan & Partisipasi' : 'Kedisiplinan & Ketertiban',
                            catatan: '',
                        }
                    ];
                },
                removeEntry(idx) {
                    let updated = [...this.state];
                    updated.splice(idx, 1);
                    this.state = updated;
                }
            }"
            class="space-y-3.5"
        >
            {{-- Tombol Utama: Input Karakter Baik & Input Karakter Kurang Baik --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <button
                    type="button"
                    @click="addEntry('baik')"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition-all active:scale-95"
                >
                    <x-heroicon-m-hand-thumb-up class="w-4 h-4" />
                    <span>+ Input Karakter Baik Siswa</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-md bg-white/20 font-black">+1 Poin</span>
                </button>

                <button
                    type="button"
                    @click="addEntry('kurang_baik')"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-xs transition-all active:scale-95"
                >
                    <x-heroicon-m-hand-thumb-down class="w-4 h-4" />
                    <span>+ Input Karakter Kurang Baik Siswa</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-md bg-white/20 font-black">-1 Poin</span>
                </button>
            </div>

            {{-- Helper text jika belum ada catatan --}}
            <div x-show="!state || state.length === 0" class="text-center py-2 px-4 rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 text-xs text-gray-500 dark:text-gray-400">
                <span class="italic">Belum ada catatan karakter yang ditambahkan. Fitur ini bersifat opsional — jika semua siswa berkegiatan normal tanpa catatan khusus, Anda tidak perlu mengisi bagian ini.</span>
            </div>

            {{-- Daftar Kartu Input Karakter yang Ditambahkan --}}
            <div class="space-y-3" x-show="state && state.length > 0">
                <template x-for="(entry, idx) in state" :key="entry.uid || idx">
                    <div
                        class="p-4 rounded-xl border transition-all space-y-3 shadow-2xs"
                        :class="entry.type === 'baik'
                            ? 'border-emerald-300 dark:border-emerald-800/80 bg-emerald-50/30 dark:bg-emerald-950/20'
                            : 'border-rose-300 dark:border-rose-800/80 bg-rose-50/30 dark:bg-rose-950/20'"
                    >
                        {{-- Card Header: Type Badge & Tombol Hapus --}}
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200/70 dark:border-gray-700/70">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                                    :class="entry.type === 'baik' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
                                >
                                    <template x-if="entry.type === 'baik'">
                                        <x-heroicon-m-star class="w-3.5 h-3.5" />
                                    </template>
                                    <template x-if="entry.type !== 'baik'">
                                        <x-heroicon-m-exclamation-triangle class="w-3.5 h-3.5" />
                                    </template>
                                    <span x-text="entry.type === 'baik' ? 'Karakter Baik Siswa (+1 Poin)' : 'Karakter Kurang Baik Siswa (-1 Poin)'"></span>
                                </span>
                                <span class="text-xs font-semibold text-gray-400" x-text="'#' + (idx + 1)"></span>
                            </div>

                            <button
                                type="button"
                                @click="removeEntry(idx)"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-100 dark:hover:bg-rose-950/50 transition-colors"
                                title="Hapus catatan ini"
                            >
                                <x-heroicon-m-trash class="w-4 h-4" />
                                <span>Hapus</span>
                            </button>
                        </div>

                        {{-- Form Row 1: Pilih Siswa & Kategori --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Dropdown Siswa --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">
                                    Nama Siswa <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    x-model="entry.student_id"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 px-2.5 text-gray-900 dark:text-gray-100 font-semibold focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <template x-for="st in students" :key="st.id">
                                        <option :value="st.id" x-text="st.name + (st.nis && st.nis !== '-' ? ' (NIS: ' + st.nis + ')' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Kategori Perilaku --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1">
                                    Kategori Aspek Perilaku <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    x-model="entry.kategori"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 px-2.5 text-gray-900 dark:text-gray-100 font-semibold focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <template x-if="entry.type === 'baik'">
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
                                    <template x-if="entry.type !== 'baik'">
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
                        </div>

                        {{-- Form Row 2: Uraian Catatan Perilaku --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-200">
                                    Catatan Uraian Perilaku Siswa <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] text-gray-400">Klik rekomendasi atau ketik sendiri</span>
                            </div>

                            {{-- Rekomendasi cepat catatan --}}
                            <div class="flex flex-wrap gap-1 mb-1.5">
                                <template x-for="rec in (entry.type === 'baik' ? presetBaik : presetKurangBaik)" :key="rec">
                                    <button
                                        type="button"
                                        @click="entry.catatan = rec"
                                        class="text-[10px] px-2 py-0.5 rounded-md border text-left transition-all bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-primary-400"
                                        :class="entry.catatan === rec ? 'border-primary-500 text-primary-700 dark:text-primary-300 font-bold bg-primary-50 dark:bg-primary-950/40 shadow-2xs' : 'border-gray-200 dark:border-gray-700'"
                                        x-text="rec"
                                    ></button>
                                </template>
                            </div>

                            <textarea
                                x-model="entry.catatan"
                                rows="2"
                                placeholder="Tuliskan catatan konkret perilaku siswa tersebut (misal: Sangat aktif menjawab pertanyaan guru, atau ramai saat teman presentasi)..."
                                class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 px-2.5 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500"
                            ></textarea>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</x-dynamic-component>
