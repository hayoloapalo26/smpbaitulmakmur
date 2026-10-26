<?php

namespace App\Http\Controllers;

use App\Models\TeacherSupervision;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupervisiReportCardController extends Controller
{
    public function downloadPdf(TeacherSupervision $supervision)
    {
        $supervision->load(['teacher', 'supervisor', 'institution']);

        $pdf = Pdf::loadView('pdf.supervisi_report_card', compact('supervision'))
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $teacherName = Str::slug($supervision->teacher->name ?? 'guru');
        $date = $supervision->tanggal ? $supervision->tanggal->format('Y-m-d') : date('Y-m-d');
        $fileName = "report-card-supervisi-{$teacherName}-{$date}.pdf";

        return $pdf->download($fileName);
    }

    public function printView(Request $request, TeacherSupervision $supervision)
    {
        $supervision->load(['teacher', 'supervisor', 'institution']);
        $request->merge(['print' => 1]);

        return view('pdf.supervisi_report_card', compact('supervision'));
    }
}
