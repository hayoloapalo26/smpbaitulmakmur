<?php
$file = 'app/Filament/Staff/Pages/MyTodoList.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Pages;

use App\Models\TodoTask;
use App\Models\TodoCompletion;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyTodoList extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Tugas Saya';
    protected static ?string $navigationLabel = 'To-Do List Saya';
    protected static ?string $title = 'To-Do List Saya';

    protected static string $view = 'filament.staff.pages.my-todo-list';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->toArray();
        $userId = $user->id;

        return $table
            ->query(
                TodoTask::query()->where(function ($query) use ($roles, $userId) {
                    // Cek jika user spesifik ditargetkan
                    $query->whereJsonContains('target_users', (string)$userId)
                        ->orWhereJsonContains('target_users', $userId);

                    // Cek jika role ditargetkan
                    foreach ($roles as $role) {
                        $query->orWhereJsonContains('target_roles', $role);
                    }
                })
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis Tugas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wajib' => 'danger',
                        'opsional' => 'warning',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Tugas')
                    ->description(fn (TodoTask $record): string => $record->description ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat Waktu')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_penyelesaian')
                    ->label('Status')
                    ->getStateUsing(function (TodoTask $record) use ($user) {
                        $completion = TodoCompletion::where('todo_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->first();
                        
                        return $completion && $completion->is_done ? 'Selesai' : 'Belum Dikerjakan';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Selesai' => 'success',
                        'Belum Dikerjakan' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'wajib' => 'Tugas Wajib',
                        'opsional' => 'Tugas Opsional',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_done')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->hidden(function (TodoTask $record) use ($user) {
                        $completion = TodoCompletion::where('todo_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->first();
                        return $completion && $completion->is_done;
                    })
                    ->form([
                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('Catatan (Opsional)'),
                        \Filament\Forms\Components\FileUpload::make('proof_file')
                            ->label('File Bukti (Opsional)')
                            ->disk('public')
                            ->directory('todo_proofs'),
                    ])
                    ->action(function (array $data, TodoTask $record) use ($user) {
                        TodoCompletion::updateOrCreate(
                            ['todo_task_id' => $record->id, 'user_id' => $user->id],
                            [
                                'is_done' => true,
                                'completed_at' => now(),
                                'note' => $data['note'] ?? null,
                                'proof_file' => $data['proof_file'] ?? null,
                            ]
                        );
                        \Filament\Notifications\Notification::make()
                            ->title('Tugas Selesai')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('view_detail')
                    ->label('Detail Tugas')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalContent(fn (TodoTask $record) => view('filament.staff.pages.todo-detail', ['record' => $record, 'user' => $user]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }
}
PHP;
file_put_contents($file, $content);
echo "MyTodoList.php updated.\n";
