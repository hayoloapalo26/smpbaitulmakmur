<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\WorkProgram;

class YayasanReportPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $title = 'Laporan Yayasan (To-Do List)';
    protected static ?string $slug = 'laporan-yayasan';

    protected static string $view = 'filament.staff.pages.yayasan-report-page';

    

    public function getViewData(): array
    {
        $mingguan = WorkProgram::where('laporan_yayasan', 'mingguan')->get();
        $bulanan = WorkProgram::where('laporan_yayasan', 'bulanan')->get();
        $tahunan = WorkProgram::where('laporan_yayasan', 'tahunan')->get();
        
        $kpiDivisi = \Illuminate\Support\Facades\DB::table('work_programs')
            ->whereNotNull('skor_penilaian')
            ->selectRaw('divisi, AVG(skor_penilaian) as rata_rata, COUNT(id) as total_program')
            ->groupBy('divisi')
            ->get();

        $overallKpi = \Illuminate\Support\Facades\DB::table('work_programs')
            ->whereNotNull('skor_penilaian')
            ->avg('skor_penilaian') ?? 0;

        return [
            'mingguan' => $mingguan,
            'bulanan' => $bulanan,
            'tahunan' => $tahunan,
            'kpiDivisi' => $kpiDivisi,
            'overallKpi' => $overallKpi,
        ];
    }
}
