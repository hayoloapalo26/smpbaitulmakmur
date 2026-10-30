<x-filament-widgets::widget>
    @php
        $pendingCount = $this->getPendingTasksCount();
        $todoUrl = $this->getTodoListUrl();
    @endphp

    <div class="relative overflow-hidden bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-r-xl shadow-sm">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#d97706 1px, transparent 1px); background-size: 16px 16px;"></div>
        
        <div class="relative flex flex-col sm:flex-row items-center justify-between p-4 sm:p-5 gap-4">
            
            {{-- Bagian Kiri & Tengah: Ikon dan Teks --}}
            <div class="flex items-center gap-4 text-amber-900 dark:text-amber-200 w-full sm:w-auto">
                <div class="flex-shrink-0 bg-amber-100 dark:bg-amber-800 p-2.5 rounded-full">
                    <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-medium">
                        Halo, {{ explode(' ', auth()->user()->name)[0] }}! Anda memiliki tugas insidentil.
                    </h3>
                    <p class="text-sm sm:text-base mt-0.5 opacity-90">
                        Ada <span class="font-black text-amber-700 dark:text-amber-300 text-lg mx-1">{{ $pendingCount }}</span> tugas insidentil yang menunggu untuk diselesaikan.
                    </p>
                </div>
            </div>

            {{-- Bagian Kanan: CTA Button --}}
            <div class="w-full sm:w-auto flex-shrink-0">
                <a href="{{ $todoUrl }}" 
                   class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-500 text-white text-sm font-bold rounded-lg shadow transition-colors duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 focus:outline-none">
                    Lihat & Kerjakan Tugas
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
