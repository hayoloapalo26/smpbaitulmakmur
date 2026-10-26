<div class="space-y-4">
    <div class="p-4 bg-gray-50 rounded-xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $record->title }}</h3>
        @if($record->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $record->description }}</p>
        @endif
    </div>

    @if($submissions->isEmpty())
        <div class="p-6 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
            Belum ada guru/staf yang mengirimkan laporan untuk tugas ini.
        </div>
    @else
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
            @foreach($submissions as $sub)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                    <div class="bg-gray-100 dark:bg-gray-800 px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2">
                        <div class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ $sub->user->name ?? 'Pengguna' }}
                        </div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ $sub->submitted_at ? $sub->submitted_at->format('d M Y, H:i') . ' WIB' : '-' }}
                        </div>
                    </div>
                    <table class="w-full text-sm text-left">
                        <tbody>
                            @forelse($sub->answers ?? [] as $key => $value)
                                <tr class="border-b last:border-0 border-gray-100 dark:border-gray-800">
                                    <td class="px-4 py-2.5 font-medium bg-gray-50/60 dark:bg-gray-800/40 w-1/3 text-gray-700 dark:text-gray-300">{{ $key }}</td>
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-gray-100">
                                        @if(is_bool($value))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $value ? 'Ya / Sudah' : 'Tidak / Belum' }}
                                            </span>
                                        @elseif(is_string($value) && (str_contains($value, 'mandatory_task_files/') || str_contains($value, 'optional_task_files/')))
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($value) }}" target="_blank" class="text-primary-600 font-semibold underline">Lihat Berkas / Bukti</a>
                                        @elseif(is_array($value))
                                            {{ implode(', ', $value) }}
                                        @else
                                            {{ $value ?: '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-2.5 text-gray-500 italic">Tuntas (Tanpa catatan tambahan)</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif
</div>
