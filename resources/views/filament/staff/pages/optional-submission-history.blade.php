<div class="space-y-6">
    <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
        <h3 class="text-lg font-bold">{{ $record->title }}</h3>
        <p class="text-sm text-gray-500">{{ $record->description }}</p>
    </div>

    @forelse($submissions as $sub)
    <div class="mt-4 border rounded-lg overflow-hidden dark:border-gray-700">
        <div class="bg-gray-100 dark:bg-gray-900 px-4 py-2 border-b dark:border-gray-700 flex justify-between items-center">
            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">
                Pengerjaan #{{ $loop->iteration }}
            </span>
            <span class="text-xs text-gray-500">
                {{ $sub->submitted_at->format('d M Y, H:i') }}
            </span>
        </div>
        <table class="w-full text-sm text-left">
            <tbody>
                @foreach($sub->answers ?? [] as $key => $value)
                    <tr class="border-b last:border-0 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium bg-gray-50 dark:bg-gray-800/50 w-1/3">{{ $key }}</td>
                        <td class="px-4 py-3 bg-white dark:bg-gray-900">
                            @if(is_bool($value))
                                {{ $value ? 'Ya / Sudah' : 'Tidak / Belum' }}
                            @elseif(is_array($value) || str_contains((string)$value, 'optional_task_files/'))
                                <a href="{{ Storage::url($value) }}" target="_blank" class="text-primary-600 underline">Lihat File</a>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
    <div class="p-4 text-center text-gray-500">
        Anda belum pernah mengerjakan tugas ini.
    </div>
    @endforelse
</div>