<?php

namespace App\Http\Controllers;

use App\Models\TeacherCompetencyTest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UkgReportCardController extends Controller
{
    public function downloadPdf(TeacherCompetencyTest $ukg)
    {
        $ukg->load(['teacher', 'assessor', 'institution']);

        $pdf = Pdf::loadView('pdf.ukg_report_card', compact('ukg'))
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $teacherName = Str::slug($ukg->teacher->name ?? 'guru');
        $date = $ukg->tanggal ? $ukg->tanggal->format('Y-m-d') : date('Y-m-d');
        $fileName = "raport-pkg-{$teacherName}-{$date}.pdf";

        return $pdf->download($fileName);
    }

    public function printView(Request $request, TeacherCompetencyTest $ukg)
    {
        $ukg->load(['teacher', 'assessor', 'institution']);
        $request->merge(['print' => 1]);

        return view('pdf.ukg_report_card', compact('ukg'));
    }
}
