<x-filament-panels::page>

    {{-- Header Stats --}}
    @php
        $categorySummary = $this->getCategorySummary();
        $totalResponses  = \App\Models\DcmResponse::count();
        $categories      = \App\Models\DcmResponse::getCategories();
    @endphp

    <div class="space-y-6">

        {{-- Info Banner --}}
        <div class="bg-gradient-to-r from-purple-600 to-violet-500 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Analisis Penskoran DCM</h2>
                    <p class="text-purple-100 text-sm mt-1">Problem Check List — Bimbingan Konseling</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-white/15 rounded-xl p-4 text-center min-w-[90px]">
                        <p class="text-2xl font-black">{{ $totalResponses }}</p>
                        <p class="text-xs text-purple-100">Siswa Mengisi</p>
                    </div>
                    <div class="bg-white/15 rounded-xl p-4 text-center min-w-[90px]">
                        <p class="text-2xl font-black">12</p>
                        <p class="text-xs text-purple-100">Indikator</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Layout: Sidebar kategori + Tabel poin --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Sidebar: Daftar Kategori --}}
            <div class="lg:col-span-4 space-y-2">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide px-1 mb-3">Pilih Indikator</h3>
                @foreach($categorySummary as $field => $cat)
                <button
                    wire:click="selectCategory('{{ $field }}')"
                    class="w-full text-left px-4 py-3 rounded-xl border transition-all flex items-center justify-between gap-3
                        {{ $selectedCategory === $field
                            ? 'bg-purple-600 border-purple-600 text-white shadow-md shadow-purple-100'
                            : 'bg-white border-gray-200 text-gray-700 hover:border-purple-300 hover:bg-purple-50' }}">
                    <span class="text-sm font-medium leading-tight">{{ $cat['short'] }}</span>
                    <span class="flex-shrink-0 text-xs font-bold px-2.5 py-1 rounded-full
                        {{ $selectedCategory === $field
                            ? 'bg-white/20 text-white'
                            : ($cat['total_points'] >= 10 ? 'bg-red-100 text-red-700' : ($cat['total_points'] >= 5 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $cat['total_points'] }} poin
                    </span>
                </button>
                @endforeach
            </div>

            {{-- Tabel Poin --}}
            <div class="lg:col-span-8">
                @php
                    $pointScores    = $this->getPointScores();
                    $currentCatInfo = $categories[$selectedCategory] ?? null;
                    $maxCount       = !empty($pointScores) ? max(array_column($pointScores, 'count')) : 1;
                @endphp

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $currentCatInfo['label'] ?? '' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Klik baris untuk melihat daftar siswa yang memilih poin tersebut</p>
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-700 font-semibold px-3 py-1 rounded-full">
                            {{ count($pointScores) }} poin
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse($pointScores as $idx => $poin)
                        <button
                            wire:click="showStudentsForPoint({{ $poin['no'] }})"
                            class="w-full px-5 py-3.5 hover:bg-purple-50 transition-colors text-left group flex items-center gap-4
                                {{ $selectedPoint === $poin['no'] ? 'bg-purple-50 ring-1 ring-inset ring-purple-200' : '' }}">

                            {{-- Rank badge --}}
                            <span class="flex-shrink-0 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center
                                {{ $idx === 0 ? 'bg-red-100 text-red-700' : ($idx === 1 ? 'bg-amber-100 text-amber-700' : ($idx === 2 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                                {{ $idx + 1 }}
                            </span>

                            {{-- Nomor poin + teks --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-gray-400 font-mono">No.{{ $poin['no'] }}</span>
                                    <span class="text-sm text-gray-800 font-medium group-hover:text-purple-800 truncate">
                                        {{ $poin['text'] }}
                                    </span>
                                </div>
                                {{-- Progress bar --}}
                                @if($poin['count'] > 0)
                                <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden w-full">
                                    <div class="h-full rounded-full bg-purple-500 transition-all"
                                        style="width: {{ $maxCount > 0 ? round(($poin['count'] / $maxCount) * 100) : 0 }}%">
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Count badge --}}
                            <span class="flex-shrink-0 text-sm font-bold px-3 py-1 rounded-full
                                {{ $poin['count'] >= 5 ? 'bg-red-100 text-red-700' : ($poin['count'] >= 2 ? 'bg-amber-100 text-amber-700' : ($poin['count'] >= 1 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400')) }}">
                                {{ $poin['count'] }} siswa
                            </span>

                            {{-- Arrow --}}
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-600 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        @empty
                        <div class="py-12 text-center text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm">Belum ada data untuk indikator ini.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: Daftar Siswa yang Memilih Poin --}}
        @if($showStudentModal && $selectedPoint)
        @php
            $students        = $this->getStudentsForPoint();
            $currentCatLabel = $categories[$selectedCategory]['label'] ?? '';
            $currentQuestion = $categories[$selectedCategory]['questions'][$selectedPoint] ?? '';
        @endphp
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            @keydown.escape.window="$wire.closeModal()">
            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeModal"></div>

            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden z-10">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-4 bg-purple-600 text-white">
                    <div>
                        <p class="text-xs text-purple-200 mb-1">{{ $currentCatLabel }} — No. {{ $selectedPoint }}</p>
                        <h3 class="font-bold text-base leading-snug">{{ $currentQuestion }}</h3>
                        <p class="text-purple-200 text-sm mt-1">{{ count($students) }} siswa memilih poin ini</p>
                    </div>
                    <button wire:click="closeModal" class="flex-shrink-0 p-1.5 hover:bg-white/20 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="overflow-y-auto flex-1 p-2">
                    @if(empty($students))
                        <div class="py-10 text-center text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm">Belum ada siswa yang memilih poin ini.</p>
                        </div>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Nama Siswa</th>
                                    <th class="px-4 py-2 text-left">NIS</th>
                                    <th class="px-4 py-2 text-left">Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($students as $i => $s)
                                <tr class="hover:bg-purple-50/50 transition-colors">
                                    <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $s['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $s['nis'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            {{ $s['classroom'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
