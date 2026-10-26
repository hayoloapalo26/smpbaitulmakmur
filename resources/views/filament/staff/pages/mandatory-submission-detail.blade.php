<div class="space-y-4">
    <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
        <h3 class="text-lg font-bold">{{ $record->title }}</h3>
        <p class="text-sm text-gray-500">{{ $record->description }}</p>
    </div>

    <div class="mt-4">
        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Jawaban Anda Hari Ini:</h4>
        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
            <table class="w-full text-sm text-left">
                <tbody>
                    @foreach($submission->answers ?? [] as $key => $value)
                        <tr class="border-b last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3 font-medium bg-gray-50 dark:bg-gray-800/50 w-1/3">{{ $key }}</td>
                            <td class="px-4 py-3 bg-white dark:bg-gray-900">
                                @if(is_bool($value))
                                    {{ $value ? 'Ya / Sudah' : 'Tidak / Belum' }}
                                @elseif(is_array($value) || str_contains((string)$value, 'mandatory_task_files/'))
                                    <!-- Jika file -->
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
        <p class="text-xs text-gray-400 mt-2">Disubmit pada: {{ $submission->submitted_at->format('d M Y, H:i') }}</p>
    </div>
</div>