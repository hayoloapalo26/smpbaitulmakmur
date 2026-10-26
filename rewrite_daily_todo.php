<?php
$file = 'app/Filament/Staff/Pages/DailyTodoList.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Pages;

use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DailyTodoList extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'To-Do List Harian (Wajib)';
    protected static ?string $title = 'To-Do List Harian (Wajib)';

    protected static string $view = 'filament.staff.pages.my-todo-list'; // We can keep this view or generic table view. Wait, we can just use static::$view = 'filament-panels::pages.list-records' or generic page.
    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->toArray();
        $institutionId = $user->institution_id;

        return $table
            ->query(
                MandatoryTask::query()
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
                    ->label('Tugas / Kegiatan')
                    ->description(fn (MandatoryTask $record): string => $record->description ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Hari Ini')
                    ->getStateUsing(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();
                        
                        return $submission ? 'Sudah Lapor' : 'Belum Lapor';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Lapor' => 'success',
                        'Belum Lapor' => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('fill_form')
                    ->label('Isi Laporan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->hidden(function (MandatoryTask $record) use ($user) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                    })
                    ->form(function (MandatoryTask $record) {
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
                            } elseif ($type === 'file') {
                                $schema[] = \Filament\Forms\Components\FileUpload::make($name)
                                    ->label($name)
                                    ->directory('mandatory_task_files')
                                    ->required($isRequired);
                            } else {
                                $schema[] = \Filament\Forms\Components\TextInput::make($name)
                                    ->label($name)
                                    ->required($isRequired);
                            }
                        }
                        return $schema;
                    })
                    ->action(function (array $data, MandatoryTask $record) use ($user) {
                        MandatoryTaskSubmission::create([
                            'mandatory_task_id' => $record->id,
                            'user_id' => $user->id,
                            'answers' => $data,
                            'submitted_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Harian Berhasil Dikirim')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('view_submission')
                    ->label('Lihat Laporan Hari Ini')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(function (MandatoryTask $record) use ($user) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                    })
                    ->modalContent(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();
                        
                        return view('filament.staff.pages.mandatory-submission-detail', [
                            'record' => $record,
                            'submission' => $submission
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }
}
PHP;
file_put_contents($file, $content);
echo "DailyTodoList rewritten.\n";
