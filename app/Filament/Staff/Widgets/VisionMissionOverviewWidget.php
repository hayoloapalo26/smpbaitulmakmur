<?php

namespace App\Filament\Staff\Widgets;

use App\Models\SchoolVisionMission;
use Filament\Widgets\Widget;

class VisionMissionOverviewWidget extends Widget
{
    protected static string $view = 'filament.staff.widgets.vision-mission-overview-widget';

    protected int | string | array $columnSpan = 'full';

    public ?SchoolVisionMission $activeVisionMission = null;

    public function mount(): void
    {
        $institutionId = auth()->user()?->institution_id;
        $this->activeVisionMission = SchoolVisionMission::getActive($institutionId);
    }
}
