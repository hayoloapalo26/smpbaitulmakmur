<div>
    <h2 class="text-xl font-bold mb-2">{{ $record->title }}</h2>
    <p class="mb-4 text-gray-600 dark:text-gray-400 text-sm">
        Tenggat Waktu: {{ $record->due_date ? \Carbon\Carbon::parse($record->due_date)->format('d M Y') : 'Tidak ada' }}
    </p>
    <div class="mb-6">
        <h3 class="font-semibold mb-1">Deskripsi Tugas:</h3>
        <p class="whitespace-pre-wrap">{{ $record->description ?: 'Tidak ada deskripsi.' }}</p>
    </div>
    
    @php
        $completion = \App\Models\TodoCompletion::where('todo_task_id', $record->id)
            ->where('user_id', $user->id)
            ->first();
    @endphp

    @if($completion && $completion->is_done)
        <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800">
            <h3 class="font-bold text-green-700 dark:text-green-400 flex items-center mb-2">
                <x-heroicon-o-check-circle class="w-5 h-5 mr-1"/> Tugas Sudah Diselesaikan
            </h3>
            <p class="text-sm"><strong>Selesai Pada:</strong> {{ \Carbon\Carbon::parse($completion->completed_at)->format('d M Y H:i') }}</p>
            @if($completion->note)
                <p class="text-sm mt-2"><strong>Catatan:</strong> {{ $completion->note }}</p>
            @endif
            @if($completion->proof_file)
                <p class="text-sm mt-2"><strong>Bukti:</strong> <a href="{{ Storage::url($completion->proof_file) }}" target="_blank" class="text-primary-600 underline">Lihat File</a></p>
            @endif
        </div>
    @else
        <div class="p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-200 dark:border-red-800">
            <h3 class="font-bold text-red-700 dark:text-red-400 flex items-center">
                <x-heroicon-o-x-circle class="w-5 h-5 mr-1"/> Belum Diselesaikan
            </h3>
            <p class="text-sm mt-1">Gunakan tombol "Tandai Selesai" pada tabel untuk melaporkan penyelesaian tugas ini.</p>
        </div>
    @endif
</div>