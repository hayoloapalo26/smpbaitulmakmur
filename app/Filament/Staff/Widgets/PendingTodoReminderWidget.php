<?php

namespace App\Filament\Staff\Widgets;

use App\Filament\Staff\Pages\OptionalTodoList;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PendingTodoReminderWidget extends Widget
{
    protected static string $view = 'filament.staff.widgets.pending-todo-reminder-widget';

    protected static ?int $sort = -1;
    protected int | string | array $columnSpan = 'full';

    protected static ?int $cachedCount = null;

    public static function canView(): bool
    {
        $user = Auth::user();
        if (!$user || $user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return false;
        }

        $widget = new static();
        return $widget->getPendingTasksCount() > 0;
    }

    public function getPendingTasksCount(): int
    {
        if (self::$cachedCount !== null) {
            return self::$cachedCount;
        }

        $user = Auth::user();
        if (!$user) {
            return self::$cachedCount = 0;
        }

        $institutionId = $user->institution_id;

        // Query seluruh tugas insidentil yang sedang aktif
        $candidateTasks = OptionalTask::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($institutionId && $institutionId != 1, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $pendingCount = 0;
        foreach ($candidateTasks as $task) {
            if ($task->isAssignedToUser($user)) {
                $hasSubmitted = OptionalTaskSubmission::where('optional_task_id', $task->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$hasSubmitted) {
                    $pendingCount++;
                }
            }
        }

        self::$cachedCount = $pendingCount;
        return self::$cachedCount;
    }

    public function getTodoListUrl(): string
    {
        return OptionalTodoList::getUrl();
    }
}
