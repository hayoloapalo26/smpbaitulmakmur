<?php

namespace App\Filament\Yayasan\Pages;

use Filament\Pages\Page;
use App\Models\YayasanTask;
use App\Models\YayasanTaskSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;

class InstruksiYayasan extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.staff.pages.instruksi-yayasan';
    protected static ?string $title = 'Instruksi Yayasan';
    protected static ?string $navigationGroup = 'Menu Utama';
    
    public function getTasks()
    {
        $user = Auth::user();
        $roles = $user->getRoleNames()->toArray();
        
        // Filter tasks where any of user roles is in target_roles JSON array
        $query = YayasanTask::where('is_active', true)->where(function ($q) use ($roles) {
            foreach ($roles as $role) {
                $q->orWhereJsonContains('target_roles', $role);
            }
        });
        
        return $query->latest()->get();
    }
    
    public function hasSubmitted($taskId)
    {
        return YayasanTaskSubmission::where('yayasan_task_id', $taskId)
            ->where('user_id', Auth::id())
            ->exists();
    }
    
    // Create an action for filling out a task
    public function submitTaskAction(): Action
    {
        return Action::make('submitTask')
            ->label(fn (array $arguments) => $this->hasSubmitted($arguments['task_id'] ?? 0) ? 'Sudah Dikerjakan' : 'Kerjakan Tugas')
            ->disabled(fn (array $arguments) => $this->hasSubmitted($arguments['task_id'] ?? 0))
            ->color(fn (array $arguments) => $this->hasSubmitted($arguments['task_id'] ?? 0) ? 'gray' : 'primary')
            ->icon('heroicon-o-pencil-square')
            ->form(function (array $arguments) {
                $taskId = $arguments['task_id'] ?? null;
                if (!$taskId) return [];
                
                $task = YayasanTask::find($taskId);
                if (!$task || !$task->form_schema) return [];
                
                $fields = [];
                foreach ($task->form_schema as $fieldData) {
                    // Unique name based on field title to avoid collision and identify answers easily
                    $fieldName = 'q_' . \Illuminate\Support\Str::slug($fieldData['field_name'], '_');
                    
                    $component = match($fieldData['field_type']) {
                        'text' => TextInput::make($fieldName)->label($fieldData['field_name']),
                        'textarea' => Textarea::make($fieldName)->label($fieldData['field_name']),
                        'checkbox' => Checkbox::make($fieldName)->label($fieldData['field_name']),
                        'file_upload' => TextInput::make($fieldName)
                            ->label($fieldData['field_name'] . ' (Link Google Drive)')
                            ->placeholder('https://drive.google.com/...')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen tugas untuk menghemat kapasitas hosting.')
                            ->maxLength(2048),
                        default => TextInput::make($fieldName)->label($fieldData['field_name'])
                    };
                    
                    if ($fieldData['is_required'] ?? false) {
                        $component->required();
                    }
                    
                    $fields[] = $component;
                }
                
                return $fields;
            })
            ->action(function (array $data, array $arguments) {
                $taskId = $arguments['task_id'];
                
                YayasanTaskSubmission::create([
                    'yayasan_task_id' => $taskId,
                    'user_id' => Auth::id(),
                    'answers' => $data,
                ]);
                
                \Filament\Notifications\Notification::make()
                    ->title('Berhasil mengirimkan form!')
                    ->success()
                    ->send();
            });
    }
}
