<?php
$content = <<<'PHP'
<?php
namespace App\Filament\Yayasan\Widgets;

use App\Models\Institution;
use App\Models\SchoolPillar;
use Filament\Widgets\Widget;

class InstitutionOverviewWidget extends Widget
{
    protected static string $view = 'filament.yayasan.widgets.institution-overview-widget';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool { 
        return auth()->check() && auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'tim_pengembang_yayasan']); 
    }

    public function getOverallInstitutionProgressProperty()
    {
        $institutions = $this->institutions;
        if (collect($institutions)->isEmpty()) {
            return 0;
        }
        $totalPercentage = collect($institutions)->sum('percentage');
        return round($totalPercentage / collect($institutions)->count());
    }

    public function getInstitutionsProperty()
    {
        // Ignore Yayasan (ID 1)
        return Institution::where('id', '!=', 1)->get()->map(function ($institution) {
            $instId = $institution->id;

            $studentCount = \App\Models\Student::withoutGlobalScopes()->where('institution_id', $instId)->count();
            
            $teacherCount = \App\Models\User::withoutGlobalScopes()->where('institution_id', $instId)->whereHas('roles', function($q) {
                $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
            })->count();

            // Real Percentage (Calculate from Pillar Tasks)
            $totalTasks = \App\Models\SchoolPillarTask::withoutGlobalScopes()->whereHas('pillar', function($q) use ($instId) {
                $q->where('institution_id', $instId);
            })->count();
            
            $selesaiTasks = \App\Models\SchoolPillarTask::withoutGlobalScopes()->whereHas('pillar', function($q) use ($instId) {
                $q->where('institution_id', $instId);
            })->where('status', 'selesai')->count();

            $percentage = $totalTasks > 0 ? round(($selesaiTasks / $totalTasks) * 100) : 0;

            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'percentage' => $percentage,
                'student_count' => $studentCount,
                'teacher_count' => $teacherCount,
            ];
        })->toArray();
    }
}
PHP;
file_put_contents(__DIR__.'/../app/Filament/Yayasan/Widgets/InstitutionOverviewWidget.php', $content);
echo 'SUCCESS';
