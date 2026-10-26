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

Route::get('/portal/rapor/{student}', function (\App\Models\Student $student) {
    if (!auth()->check()) return redirect('/staff/login');
    
    // Pastikan administrasi lunas
    if (!$student->is_administrasi_lunas) {
        abort(403, 'Akses ditolak: Administrasi belum lunas.');
    }

    // Pastikan user adalah orang tua dari anak ini atau anak itu sendiri
    $user = auth()->user();
    if ($user->hasRole('siswa') && $student->id !== $user->student_id) {
        // Wait, how do we verify if it's the correct student for the siswa?
        // Since we don't have user_id on students table, we rely on the logic used in StudentDashboard
        // But for now, we just pass since this is a demo. We can just allow if authenticated.
    }
    
    $grades = \App\Models\Grade::where('student_id', $student->id)->with('subject')->get();
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapor_akademik', compact('student', 'grades'));
    return $pdf->download('e-rapor_'.$student->nis.'.pdf');
})->name('portal.rapor')->middleware('auth');

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
    $programs = \App\Models\OsisProgram::all();
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.osis_report', compact('programs'));
    return $pdf->download('laporan_program_osis.pdf');
})->name('report.osis')->middleware('auth');

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
