<?php

$dir = __DIR__ . '/app/Filament/Staff/Resources';
$files = glob($dir . '/*Resource.php');

$roleMap = [
    // === TATA USAHA & ADMIN ===
    'UserResource' => "['tata_usaha', 'superadmin']",
    'TeacherProfileResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'DocumentArchiveResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'LetterResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",
    'InventoryResource' => "['tata_usaha', 'superadmin']",
    'MeetingMinuteResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']",
    'HumasProgramResource' => "['tata_usaha', 'superadmin', 'kepala_sekolah']",

    // === KURIKULUM ===
    'AcademicYearResource' => "['waka_kurikulum', 'superadmin', 'tata_usaha']",
    'SubjectResource' => "['waka_kurikulum', 'superadmin']",
    'TeacherSubjectClassResource' => "['waka_kurikulum', 'superadmin']",
    'ScheduleResource' => "['waka_kurikulum', 'superadmin']",
    'DutyScheduleResource' => "['waka_kurikulum', 'waka_kesiswaan', 'superadmin']",
    'LearningPlanResource' => "['waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'ProgramKurikulumResource' => "['waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'TeacherSupervisionResource' => "['kepala_sekolah', 'waka_kurikulum', 'superadmin']",

    // === KESISWAAN ===
    'StudentResource' => "['waka_kesiswaan', 'tata_usaha', 'bk', 'wali_kelas', 'superadmin', 'kepala_sekolah']",
    'StudentAchievementResource' => "['waka_kesiswaan', 'wali_kelas', 'superadmin', 'kepala_sekolah']",
    'AlumniTracerResource' => "['waka_kesiswaan', 'bk', 'superadmin']",
    'OsisProgramResource' => "['waka_kesiswaan', 'superadmin', 'kepala_sekolah']",
    'ProgramKesiswaanResource' => "['waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'ExtracurricularResource' => "['waka_kesiswaan', 'superadmin']",

    // === BIMBINGAN KONSELING (BK) ===
    'StudentCaseResource' => "['bk', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'ParentSummonResource' => "['bk', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'AttributeViolationResource' => "['bk', 'waka_kesiswaan', 'guru_mapel', 'superadmin']",
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
    'CharacterScoreLogResource' => "['wali_kelas', 'bk', 'waka_kesiswaan', 'superadmin']",

    // === GURU KHUSUS ===
    'QuranAchievementResource' => "['guru_alquran', 'waka_kurikulum', 'superadmin']",
    'ReadingQualityScoreResource' => "['guru_alquran', 'waka_kurikulum', 'superadmin']",
    'ExtracurricularAttendanceResource' => "['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']",

    // === GENERAL & ATTENDANCE ===
    'DailyAttendanceResource' => "['tata_usaha', 'waka_kesiswaan', 'wali_kelas', 'superadmin', 'kepala_sekolah']",
    'PresensiIzinSiswaResource' => "['wali_kelas', 'guru_kelas', 'guru_piket', 'tata_usaha', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']",
    'TeacherAttendanceResource' => "['tata_usaha', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'StudentLeaveRequestResource' => "['waka_kesiswaan', 'wali_kelas', 'tata_usaha', 'superadmin']",
    'TeacherLeaveRequestResource' => "['tata_usaha', 'kepala_sekolah', 'superadmin']",
    'TeacherAbsenceReportResource' => "['waka_kurikulum', 'tata_usaha', 'kepala_sekolah', 'superadmin']",
    'DutySwapRequestResource' => "['waka_kurikulum', 'superadmin']",
    'PrayerAttendanceResource' => "['waka_kesiswaan', 'wali_kelas', 'guru_mapel', 'superadmin']",
    'StudentHalanganResource' => "['waka_kesiswaan', 'bk', 'wali_kelas', 'superadmin']",
    'WorkProgramResource' => "['superadmin']", // Matikan untuk selain superadmin (ganti dgn fitur spesifik)
    'SchoolAccreditationResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']",
    'SchoolVisionMissionResource' => "['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']",
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

        // Check if canAccess exists
        if (preg_match('/public static function canAccess\(\): bool\s*\{.*?\}/s', $content)) {
            // Replace existing
            $content = preg_replace('/public static function canAccess\(\): bool\s*\{.*?\}/s', trim($newMethod), $content);
        } else {
            // Insert before form method
            $content = preg_replace('/public static function form\(Form \$form\): Form/', trim($newMethod) . "\n\n    public static function form(Form \$form): Form", $content);
        }
        
        file_put_contents($file, $content);
        echo "Updated $basename\n";
    }
}
echo "All roles updated.\n";
