<?php

namespace App\Filament\Staff\Widgets;

use App\Models\MandatoryTask;
use App\Models\OptionalTask;
use Carbon\Carbon;
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
        
        $userRoles = $user->roles->pluck('name')->toArray();
        if (empty($userRoles)) {
            return self::$cachedCount = 0;
        }

        $today = Carbon::today();
        $institutionId = $user->institution_id;

        // 1. Mandatory Tasks Query (Wajib Harian)
        $pendingMandatory = MandatoryTask::where('is_active', true)
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->where(function ($query) use ($userRoles) {
                foreach ($userRoles as $role) {
                    $query->orWhereJsonContains('target_roles', $role);
                }
            })
            ->whereDoesntHave('submissions', function ($query) use ($user, $today) {
                $query->where('user_id', $user->id)
                      ->whereDate('submitted_at', $today);
            })
            ->count();

        self::$cachedCount = $pendingMandatory;
        
        return self::$cachedCount;
    }

    public function getTodoListUrl(): string
    {
        return \App\Filament\Staff\Pages\DailyTodoList::getUrl();
    }
}
