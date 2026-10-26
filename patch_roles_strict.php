<?php

$dir = __DIR__ . '/app/Filament/Staff/Resources';
$files = glob($dir . '/*Resource.php');

// Strict role map
$roleMap = [
    // === TATA USAHA & ADMIN ===
    'UserResource' => "['tata_usaha', 'superadmin']",
    'TeacherProfileResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'DocumentArchiveResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'LetterResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'InventoryResource' => "['tata_usaha', 'superadmin']",
    'MeetingMinuteResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'HumasProgramResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",

    // === KURIKULUM ===
    'AcademicYearResource' => "['waka_kurikulum', 'superadmin']",
    'SubjectResource' => "['waka_kurikulum', 'superadmin']",
    'TeacherSubjectClassResource' => "['waka_kurikulum', 'superadmin']",
    'ScheduleResource' => "['waka_kurikulum', 'superadmin']",
    'DutyScheduleResource' => "['waka_kurikulum', 'superadmin']",
    'LearningPlanResource' => "['waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'ProgramKurikulumResource' => "['waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'TeacherSupervisionResource' => "['kepala_sekolah', 'waka_kurikulum', 'superadmin']",

    // === KESISWAAN ===
    'StudentResource' => "['waka_kesiswaan', 'tata_usaha', 'superadmin', 'kepala_sekolah']",
    'StudentAchievementResource' => "['waka_kesiswaan', 'superadmin', 'kepala_sekolah']",
    'AlumniTracerResource' => "['waka_kesiswaan', 'superadmin']",
    'OsisProgramResource' => "['waka_kesiswaan', 'superadmin']",
    'ProgramKesiswaanResource' => "['waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'ExtracurricularResource' => "['waka_kesiswaan', 'superadmin']",

    // === BIMBINGAN KONSELING (BK) ===
    'StudentCaseResource' => "['bk', 'superadmin']",
    'ParentSummonResource' => "['bk', 'superadmin']",
    'AttributeViolationResource' => "['bk', 'waka_kesiswaan', 'superadmin']",
    'BkReportResource' => "['bk', 'kepala_sekolah', 'superadmin']",
    'BkWorkProgramResource' => "['bk', 'kepala_sekolah', 'superadmin']",
    'DcmResponseResource' => "['bk', 'superadmin']",

    // === GURU MAPEL & WALI KELAS ===
    'RppmResource' => "['guru_mapel', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'TeachingMaterialResource' => "['guru_mapel', 'waka_kurikulum', 'superadmin']",
    'TeachingJournalResource' => "['guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'SubjectAttendanceResource' => "['guru_mapel', 'waka_kurikulum', 'superadmin']",
    'GradeResource' => "['guru_mapel', 'wali_kelas', 'waka_kurikulum', 'superadmin']",
    'EmptyClassLogResource' => "['guru_mapel', 'waka_kurikulum', 'superadmin']",
    'CharacterScoreLogResource' => "['wali_kelas', 'guru_mapel', 'superadmin']", // To-do list guru

    // === GURU KHUSUS ===
    'QuranAchievementResource' => "['guru_alquran', 'waka_kurikulum', 'superadmin']",
    'ReadingQualityScoreResource' => "['guru_alquran', 'waka_kurikulum', 'superadmin']",
    'ExtracurricularAttendanceResource' => "['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']",

    // === GENERAL & ATTENDANCE ===
    'DailyAttendanceResource' => "['tata_usaha', 'waka_kesiswaan', 'superadmin', 'kepala_sekolah']",
    'PresensiIzinSiswaResource' => "['wali_kelas', 'guru_kelas', 'guru_piket', 'tata_usaha', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'TeacherAttendanceResource' => "['tata_usaha', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'StudentLeaveRequestResource' => "['waka_kesiswaan', 'tata_usaha', 'superadmin']",
    'TeacherLeaveRequestResource' => "['tata_usaha', 'superadmin']",
    'TeacherAbsenceReportResource' => "['waka_kurikulum', 'tata_usaha', 'superadmin']",
    'DutySwapRequestResource' => "['waka_kurikulum', 'superadmin']",
    'PrayerAttendanceResource' => "['waka_kesiswaan', 'superadmin']",
    'StudentHalanganResource' => "['waka_kesiswaan', 'superadmin']", // Keputrian
    'WorkProgramResource' => "['superadmin']", // Hanya superadmin
    'SchoolAccreditationResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']",
    'SchoolVisionMissionResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']",
    'ProgramEvaluationResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan']",
    'ProgramKepsekResource' => "['kepala_sekolah', 'superadmin']",
    'SchoolWorkPlanResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan']",
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $basename = basename($file, '.php');
    
    if (isset($roleMap[$basename])) {
        $roles = $roleMap[$basename];
        $newMethod = "
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole($roles);
    }
";

        // Remove any existing canAccess method safely
        $content = preg_replace('/public static function canAccess\(\): bool\s*\{(?:[^{}]*|\{(?:[^{}]*|\{[^{}]*\})*\})*\}/', '', $content);
        
        // Find class declaration and insert immediately after it
        $content = preg_replace('/(class\s+[A-Za-z0-9_]+\s+extends\s+[A-Za-z0-9_\\\\]+\s*\{)/s', "$1\n" . trim($newMethod) . "\n", $content);
        
        file_put_contents($file, $content);
        echo "Successfully patched Resource: $basename\n";
    }
}

// NOW PATCH PAGES
$dirPages = __DIR__ . '/app/Filament/Staff/Pages';
$filesPages = glob($dirPages . '/*.php');

$roleMapPages = [
    'AbsensiManual' => "['tata_usaha', 'waka_kesiswaan', 'superadmin']",
    'CharacterScoreReport' => "['wali_kelas', 'kepala_sekolah', 'superadmin']", // Laporan Karakter
    'Dashboard' => "[]", // Skip this
    'DcmAnalysis' => "['bk', 'superadmin']",
    'PiketDashboard' => "['guru_mapel', 'superadmin']", 
    'PrayerAttendanceReport' => "['waka_kesiswaan', 'superadmin']",
    'ScannerPresensi' => "['tata_usaha', 'superadmin']",
    'TahfidzRanking' => "['guru_alquran', 'waka_kurikulum', 'superadmin']",
    'TeacherCommitment' => "['waka_kurikulum', 'superadmin']",
    'YayasanReportPage' => "['kepala_sekolah', 'superadmin']",
];

foreach ($filesPages as $file) {
    $basename = basename($file, '.php');
    if ($basename === 'Dashboard' || $basename === 'PiketDashboard') continue; // Don't restrict the main dashboard or Piket (has custom logic)
    
    if (isset($roleMapPages[$basename])) {
        $content = file_get_contents($file);
        $roles = $roleMapPages[$basename];
        $newMethod = "
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole($roles);
    }
";

        // Remove any existing canAccess method safely
        $content = preg_replace('/public static function canAccess\(\): bool\s*\{(?:[^{}]*|\{(?:[^{}]*|\{[^{}]*\})*\})*\}/', '', $content);
        
        // Find class declaration
        $content = preg_replace('/(class\s+[A-Za-z0-9_]+\s+extends\s+[A-Za-z0-9_\\\\]+\s*\{)/s', "$1\n" . trim($newMethod) . "\n", $content);
        
        file_put_contents($file, $content);
        echo "Successfully patched Page: $basename\n";
    }
}
echo "All roles strictly updated.\n";
