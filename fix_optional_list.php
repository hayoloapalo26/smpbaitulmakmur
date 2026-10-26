<?php
$file = "app/Filament/Staff/Pages/OptionalTodoList.php";
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Pages;

use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OptionalTodoList extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'To-Do List Opsional';
    protected static ?string $title = 'To-Do List Opsional';

    protected static string $view = 'filament.staff.pages.my-todo-list';
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->toArray();
        $institutionId = $user->institution_id;

        return $table
            ->query(
                OptionalTask::query()
                    ->where('is_active', true)
                    ->where('institution_id', $institutionId)
                    ->where(function ($query) use ($roles) {
                        foreach ($roles as $role) {
                            $query->orWhereJsonContains('target_roles', $role);
                        }
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Tugas Opsional')
                    ->description(fn (OptionalTask $record): string => $record->description ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reward_points')
                    ->label('Poin Apresiasi')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ? "+{$state} Poin" : 'Tidak Ada'),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Telah Dikerjakan')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        $count = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->count();
                        return $count . ' Kali';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('fill_form')
                    ->label('Kerjakan Tugas')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form(function (OptionalTask $record) {
                        $schema = [];
                        foreach ($record->form_schema ?? [] as $field) {
                            $name = $field['field_name'];
                            $type = $field['field_type'] ?? 'text';
                            $isRequired = $field['is_required'] ?? false;
                            
                            if ($type === 'checkbox') {
                                $schema[] = \Filament\Forms\Components\Checkbox::make($name)
                                    ->label($name)
                                    ->required($isRequired);
                            } elseif ($type === 'textarea') {
                                $schema[] = \Filament\Forms\Components\Textarea::make($name)
                                    ->label($name)
                                    ->required($isRequired);
                            } elseif ($type === 'file_upload') {
                                $schema[] = \Filament\Forms\Components\FileUpload::make($name)
                                    ->label($name)
                                    ->directory('optional_task_files')
                                    ->required($isRequired);
                            } else {
                                $schema[] = \Filament\Forms\Components\TextInput::make($name)
                                    ->label($name)
                                    ->required($isRequired);
                            }
                        }
                        return $schema;
                    })
                    ->action(function (array $data, OptionalTask $record) use ($user) {
                        OptionalTaskSubmission::create([
                            'optional_task_id' => $record->id,
                            'user_id' => $user->id,
                            'answers' => $data,
                            'submitted_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Tugas Opsional Berhasil Dikirim')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('view_history')
                    ->label('Riwayat Saya')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalContent(function (OptionalTask $record) use ($user) {
                        $submissions = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->orderByDesc('submitted_at')
                            ->get();
                        
                        return view('filament.staff.pages.optional-submission-history', [
                            'record' => $record,
                            'submissions' => $submissions
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }
}
PHP;
file_put_contents($file, $content);
echo "OptionalTodoList configured.\n";
