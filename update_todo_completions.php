<?php
$file = 'app/Filament/Staff/Resources/TodoTaskResource.php';
$content = file_get_contents($file);

$actionCode = <<<'PHP'
                Tables\Actions\Action::make('view_completions')
                    ->label('Lihat Progres')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalContent(function (TodoTask $record) {
                        $completions = \App\Models\TodoCompletion::with('user')->where('todo_task_id', $record->id)->where('is_done', true)->get();
                        return view('filament.staff.pages.todo-completions', ['completions' => $completions, 'record' => $record]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(),
PHP;

$content = str_replace('Tables\Actions\EditAction::make(),', $actionCode, $content);
file_put_contents($file, $content);

$file2 = 'resources/views/filament/staff/pages/todo-completions.blade.php';
$content2 = <<<'HTML'
<div>
    <h2 class="text-xl font-bold mb-4">Laporan Penyelesaian Tugas</h2>
    <p class="mb-4"><strong>Judul Tugas:</strong> {{ $record->title }}</p>

    @if($completions->isEmpty())
        <div class="p-4 bg-gray-50 text-gray-500 rounded-lg text-center">
            Belum ada staf/guru yang menandai tugas ini sebagai selesai.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Waktu Penyelesaian</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3">Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completions as $c)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $c->user->name ?? 'Tidak diketahui' }}</td>
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($c->completed_at)->format('d M Y, H:i') }}</td>
                            <td class="px-4 py-3">{{ $c->note ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($c->proof_file)
                                    <a href="{{ Storage::url($c->proof_file) }}" target="_blank" class="text-primary-600 underline">Lihat File</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
HTML;
file_put_contents($file2, $content2);

echo "TodoTaskResource updated with completions view.\n";
