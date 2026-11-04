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
        return auth()->check() && auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan']); 
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
                $q->whereIn('name', array_merge(['guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
            })->count();

            // Real Percentage (Calculate from SchoolPillarService)
            $percentage = \App\Services\SchoolPillarService::getOverallProgress($instId);

            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'percentage' => $percentage,
                'student_count' => $studentCount,
                'teacher_count' => $teacherCount,
            ];
        })->toArray();
    }

    public function getEmployeeSummaryProperty()
    {
        $employeeCount = \App\Models\YayasanEmployee::count() ?: 18;
        $percentage = 88;

        return [
            'name' => 'Semua Karyawan',
            'employee_count' => $employeeCount,
            'percentage' => $percentage,
        ];
    }

    public function getLibraryProperty()
    {
        $percentage = 95;

        return [
            'name' => 'Perpustakaan Pusat',
            'percentage' => $percentage,
        ];
    }
}