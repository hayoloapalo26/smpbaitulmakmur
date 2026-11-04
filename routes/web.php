<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/staff');
});

Route::get('/portal', function () {
    if (auth()->check()) {
        if (auth()->user()->hasRole('siswa')) {
            return redirect()->route('portal.student');
        } elseif (auth()->user()->hasRole('orang_tua')) {
            return redirect()->route('portal.parent');
        }
    }
    return redirect('/staff/login'); // Fallback to filament login
})->name('portal.dashboard');

Route::get('/portal/student', \App\Livewire\Portal\StudentDashboard::class)->name('portal.student')->middleware('auth');
Route::get('/portal/parent', \App\Livewire\Portal\ParentDashboard::class)->name('portal.parent')->middleware('auth');

Route::get('/portal/rapor/{student}', function (\App\Models\Student $student, \Illuminate\Http\Request $request) {
    if (!auth()->check()) return redirect('/staff/login');
    
    // Pastikan administrasi lunas
    if (!$student->is_administrasi_lunas) {
        abort(403, 'Akses ditolak: Administrasi belum lunas.');
    }

    $academicYearId = $request->query('academic_year_id');
    $semester = $request->query('semester', 'Ganjil');

    // Rapor resmi portal menggunakan nilai olahan / resmi
    $rapor = \App\Services\RaporCalculationService::getRaporData($student, $academicYearId, $semester, 'olah');
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapor_akademik', compact('rapor'));
    return $pdf->download('e-rapor_'.$student->nis.'.pdf');
})->name('portal.rapor')->middleware('auth');

Route::get('/report/rapor/{student}', function (\App\Models\Student $student, \Illuminate\Http\Request $request) {
    if (!auth()->check()) return redirect('/staff/login');
    $user = auth()->user();
    if (!$user->hasAnyRole(['wali_kelas', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin', 'tata_usaha'])) {
        abort(403, 'Akses ditolak.');
    }

    $academicYearId = $request->query('academic_year_id');
    $semester = $request->query('semester', 'Ganjil');
    $mode = $request->query('mode', 'olah');

    $rapor = \App\Services\RaporCalculationService::getRaporData($student, $academicYearId, $semester, $mode);
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapor_akademik', compact('rapor'));
    return $pdf->download('rapor_'.$student->nis.'.pdf');
})->name('report.rapor.student.pdf')->middleware('auth');

Route::get('/report/rapor-leger/{classroom}', function (\App\Models\Classroom $classroom, \Illuminate\Http\Request $request) {
    if (!auth()->check()) return redirect('/staff/login');
    $user = auth()->user();
    if (!$user->hasAnyRole(['wali_kelas', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin', 'tata_usaha'])) {
        abort(403, 'Akses ditolak.');
    }

    $academicYearId = $request->query('academic_year_id');
    $semester = $request->query('semester', 'Ganjil');
    $mode = $request->query('mode', 'olah');

    $leger = \App\Services\RaporCalculationService::getClassLeger($classroom->id, $academicYearId, $semester, $mode);
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.leger_nilai_kelas', compact('leger'));
    return $pdf->download('leger_kelas_'.$classroom->name.'.pdf');
})->name('report.rapor.leger.pdf')->middleware('auth');

Route::get('/report/character/{student}', function (\App\Models\Student $student) {
    $logs = \App\Models\CharacterScoreLog::where('student_id', $student->id)->orderBy('tanggal', 'desc')->get();
    
    if (auth()->user() && auth()->user()->hasRole('wali_kelas')) {
        \App\Models\CharacterScoreLog::where('student_id', $student->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.character_report', compact('student', 'logs'));
    return $pdf->download('rapor_karakter_'.$student->nis.'.pdf');
})->name('report.character')->middleware('auth');

Route::get('/report/prayer', function (\Illuminate\Http\Request $request) {
    $user = auth()->user();
    $instId = (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1)
        ? $user->institution_id
        : $request->query('institution_id');

    $query = \App\Models\Student::withoutGlobalScopes()
        ->with(['classroom', 'institution', 'dailyAttendances', 'prayerAttendances'])
        ->orderBy('name');

    if ($instId) {
        $query->where('institution_id', $instId);
    }
    if ($request->filled('classroom_id')) {
        $query->where('classroom_id', $request->query('classroom_id'));
    }

    $students = $query->get();
    $institutionName = $instId
        ? (\App\Models\Institution::withoutGlobalScopes()->where('id', $instId)->value('name') ?? 'Lembaga')
        : 'Semua Lembaga';

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.prayer_report', compact('students', 'institutionName'));
    return $pdf->download('rekap_absensi_dan_sholat_siswa.pdf');
})->name('report.prayer')->middleware('auth');

Route::get('/report/osis', function () {
    $user = auth()->user();
    if (!$user || !$user->hasAnyRole(['pembina_osis', 'kepala_sekolah', 'superadmin'])) {
        abort(403, 'Akses khusus Pembina OSIS, Kepala Sekolah, dan Superadmin.');
    }
    $query = \App\Models\OsisProgram::query();
    if ($user->institution_id && !$user->hasRole('superadmin')) {
        $query->where('institution_id', $user->institution_id);
    }
    $programs = $query->orderBy('tanggal', 'desc')->get();
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.osis_report', compact('programs'));
    return $pdf->download('laporan_program_osis.pdf');
})->name('report.osis')->middleware('auth');

Route::get('/report/extracurricular-attendance', function (\Illuminate\Http\Request $request) {
    $user = auth()->user();
    if (!$user || !$user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
        abort(403, 'Akses khusus Guru Ekstrakurikuler, Waka Kesiswaan, dan Kepala Sekolah.');
    }

    $extracurricularId = $request->query('extracurricular_id');
    $extracurricular = $extracurricularId ? \App\Models\Extracurricular::with('pembina')->find($extracurricularId) : null;

    $month = $request->query('month');
    $year = $request->query('year', date('Y'));

    $query = \App\Models\ExtracurricularAttendance::with(['student.classroom', 'extracurricular']);

    if ($extracurricularId) {
        $query->where('extracurricular_id', $extracurricularId);
    } elseif ($user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
        $myEskuls = \App\Models\Extracurricular::where('pembina_id', $user->id)->pluck('id');
        $query->whereIn('extracurricular_id', $myEskuls);
    }

    if ($month) {
        $query->whereMonth('tanggal', $month);
    }
    if ($year) {
        $query->whereYear('tanggal', $year);
    }

    $attendances = $query->orderBy('tanggal', 'desc')->get();

    $studentsGrouped = $attendances->groupBy('student_id');
    $recapData = [];

    foreach ($studentsGrouped as $studentId => $records) {
        $firstRec = $records->first();
        $student = $firstRec->student;
        $total = $records->count();
        $hadir = $records->where('status', 'hadir')->count();
        $izin = $records->where('status', 'izin')->count();
        $sakit = $records->where('status', 'sakit')->count();
        $alpa = $records->where('status', 'alpa')->count();
        $percent = $total > 0 ? round(($hadir / $total) * 100) : 0;

        $recapData[] = [
            'nis' => $student?->nis ?? '-',
            'name' => $student?->name ?? 'Siswa #' . $studentId,
            'kelas' => $student?->classroom?->name ?? '-',
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'total' => $total,
            'percent' => $percent,
        ];
    }

    usort($recapData, fn ($a, $b) => strcmp($a['name'], $b['name']));

    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $periodeLabel = $month ? ($monthNames[(int)$month] ?? '') . ' ' . $year : 'Tahun ' . $year;

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.extracurricular_attendance_report', compact('extracurricular', 'recapData', 'periodeLabel'));
    return $pdf->download('rekap_presensi_ekstrakurikuler.pdf');
})->name('report.extracurricular-attendance')->middleware('auth');

Route::get('/supervisi/report-card/{supervision}/pdf', [\App\Http\Controllers\SupervisiReportCardController::class, 'downloadPdf'])
    ->name('supervisi.report-card.pdf')
    ->middleware('auth');

Route::get('/supervisi/report-card/{supervision}/print', [\App\Http\Controllers\SupervisiReportCardController::class, 'printView'])
    ->name('supervisi.report-card.print')
    ->middleware('auth');

Route::get('/ukg/report-card/{ukg}/pdf', [\App\Http\Controllers\UkgReportCardController::class, 'downloadPdf'])
    ->name('ukg.report-card.pdf')
    ->middleware('auth');

Route::get('/ukg/report-card/{ukg}/print', [\App\Http\Controllers\UkgReportCardController::class, 'printView'])
    ->name('ukg.report-card.print')
    ->middleware('auth');

// =========================================================
// FITUR CETAK DOKUMEN & INPUTAN RESMI (PDF ENGINE)
// =========================================================
Route::middleware('auth')->prefix('print')->name('academic.')->group(function () {
    // 1. RPPM & Perangkat Pembelajaran (Modul Ajar)
    Route::get('/rppm/{rppm}', [\App\Http\Controllers\AcademicPrintController::class, 'rppmPdf'])->name('rppm.pdf');

    // 2. Jurnal Mengajar
    Route::get('/journal/{journal}', [\App\Http\Controllers\AcademicPrintController::class, 'journalPdf'])->name('journal.pdf');
    Route::get('/journal-rekap', [\App\Http\Controllers\AcademicPrintController::class, 'journalRekapPdf'])->name('journal.rekap.pdf');

    // 3. Alur Tujuan Pembelajaran (ATP)
    Route::get('/atp/{atp}', [\App\Http\Controllers\AcademicPrintController::class, 'atpPdf'])->name('atp.pdf');

    // 4. Capaian Pembelajaran (CP)
    Route::get('/cp/{cp}', [\App\Http\Controllers\AcademicPrintController::class, 'cpPdf'])->name('cp.pdf');
    Route::get('/cp-rekap', [\App\Http\Controllers\AcademicPrintController::class, 'cpRekapPdf'])->name('cp.rekap.pdf');

    // 5. Tujuan Pembelajaran (TP)
    Route::get('/tp-rekap', [\App\Http\Controllers\AcademicPrintController::class, 'tpRekapPdf'])->name('tp.rekap.pdf');

    // 6. Biodata Lengkap Siswa (Buku Induk)
    Route::get('/student/{student}', [\App\Http\Controllers\AcademicPrintController::class, 'studentPdf'])->name('student.pdf');

    // 7. Pengajuan Cuti / Izin Guru
    Route::get('/leave-request/{leaveRequest}', [\App\Http\Controllers\AcademicPrintController::class, 'leaveRequestPdf'])->name('leave-request.pdf');

    // 8. Notulen Rapat
    Route::get('/meeting-minute/{meetingMinute}', [\App\Http\Controllers\AcademicPrintController::class, 'meetingMinutePdf'])->name('meeting-minute.pdf');

    // 9. Lembar Kasus & Catatan Pembinaan Siswa
    Route::get('/student-case/{studentCase}', [\App\Http\Controllers\AcademicPrintController::class, 'studentCasePdf'])->name('student-case.pdf');

    // 10. Piagam / Bukti Prestasi Siswa
    Route::get('/student-achievement/{studentAchievement}', [\App\Http\Controllers\AcademicPrintController::class, 'studentAchievementPdf'])->name('student-achievement.pdf');

    // 11. Sertifikat & Portofolio Guru
    Route::get('/certificate/{certificate}', [\App\Http\Controllers\AcademicPrintController::class, 'certificatePdf'])->name('certificate.pdf');

    // 12. Rekapitulasi Presensi & Beban Jam Mengajar Guru Bulanan
    Route::get('/teacher-attendance-rekap', [\App\Http\Controllers\AcademicPrintController::class, 'teacherAttendanceReportPdf'])->name('teacher-attendance.rekap.pdf');
});



// =========================================================
// FINGERSPOT REVO W230N INTEGRATION (Cloud Webhook & Direct)
// =========================================================
Route::prefix('api/fingerspot')->group(function () {
    // Test koneksi & info endpoint
    Route::get('/ping', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'ping'])
        ->name('fingerspot.ping');

    // Endpoint utama: Webhook dari developer.fingerspot.io atau Direct HTTP Push
    Route::post('/attendance', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'receive'])
        ->name('fingerspot.attendance')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

    Route::get('/attendance', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'receive'])
        ->name('fingerspot.attendance.get');

    Route::post('/webhook', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'receive'])
        ->name('fingerspot.webhook')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
});

// =========================================================
// FINGERSPOT DIRECT ADMS / ICLOCK PUSH PROTOCOL
// (Ketika mesin Revo W230N langsung diarahkan ke IP Server kita)
// =========================================================
Route::prefix('iclock')->group(function () {
    Route::match(['get', 'post'], '/cdata', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'cdata'])
        ->name('fingerspot.iclock.cdata')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

    Route::get('/getrequest', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'getrequest'])
        ->name('fingerspot.iclock.getrequest');

    Route::post('/devicecmd', [\App\Http\Controllers\Api\FingerspotAttendanceController::class, 'devicecmd'])
        ->name('fingerspot.iclock.devicecmd')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
});

// =========================================================
// HIKVISION DEVICE INTEGRATION (Legacy / Backward Compatible)
// =========================================================
Route::prefix('api/hikvision')->group(function () {
    // Test koneksi perangkat
    Route::get('/ping', [\App\Http\Controllers\Api\HikvisionAttendanceController::class, 'ping'])
        ->name('hikvision.ping');

    // Endpoint utama: Hikvision push event ke sini setiap ada scan
    Route::post('/attendance', [\App\Http\Controllers\Api\HikvisionAttendanceController::class, 'receive'])
        ->name('hikvision.attendance')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

    // Juga terima GET (beberapa model Hikvision pakai GET)
    Route::get('/attendance', [\App\Http\Controllers\Api\HikvisionAttendanceController::class, 'receive'])
        ->name('hikvision.attendance.get');
});

Route::get('/chk-usr', function() { require_once base_path('check_users.php'); });
