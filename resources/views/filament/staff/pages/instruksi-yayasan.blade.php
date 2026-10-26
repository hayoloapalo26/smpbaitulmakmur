<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $tasks = $this->getTasks();
        @endphp
        
        @if($tasks->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm">
                <x-heroicon-o-check-badge class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">Belum Ada Instruksi</h3>
                <p class="text-gray-500 mt-2">Saat ini tidak ada tugas atau instruksi dari Yayasan untuk Anda.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($tasks as $task)
                    @php
                        $submitted = $this->hasSubmitted($task->id);
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col transition hover:shadow-md">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $task->task_type === 'mandatory' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' }} mb-3">
                                    {{ $task->task_type === 'mandatory' ? 'Wajib' : 'Opsional' }}
                                </span>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
                                    {{ $task->title }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Dibuat: {{ $task->created_at->format('d M Y') }}</p>
                            </div>
                            
                            @if($submitted)
                                <span class="bg-green-50 text-green-600 p-2 rounded-full border border-green-100 dark:border-green-800 dark:bg-green-900/50">
                                    <x-heroicon-s-check class="w-5 h-5" />
                                </span>
                            @endif
                        </div>
                        
                        <div class="p-5 flex-grow">
                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                                {{ $task->description ?? 'Tidak ada deskripsi tambahan.' }}
                            </p>
                        </div>
                        
                        <div class="p-5 bg-gray-50 dark:bg-gray-800/50 mt-auto">
                            {{ ($this->submitTaskAction)(['task_id' => $task->id]) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    
    <x-filament-actions::modals />
</x-filament-panels::page>