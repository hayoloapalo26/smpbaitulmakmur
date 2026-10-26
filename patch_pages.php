<?php

$dir = __DIR__ . '/app/Filament/Staff/Pages';
$files = glob($dir . '/*.php');

$roleMap = [
    'AbsensiManual' => "['tata_usaha', 'waka_kesiswaan', 'wali_kelas', 'superadmin']",
    'CharacterScoreReport' => "['bk', 'waka_kesiswaan', 'wali_kelas', 'kepala_sekolah', 'superadmin']",
    // Dashboard should be accessible by everyone
    'Dashboard' => "[]", 
    'DcmAnalysis' => "['bk', 'kepala_sekolah', 'superadmin']",
    'PiketDashboard' => "['guru_mapel', 'waka_kesiswaan', 'superadmin']", // Guru piket is usually any guru
    'PrayerAttendanceReport' => "['waka_kesiswaan', 'wali_kelas', 'kepala_sekolah', 'superadmin']",
    'ScannerPresensi' => "['tata_usaha', 'waka_kesiswaan', 'superadmin']",
    'TahfidzRanking' => "['guru_alquran', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'TeacherCommitment' => "['waka_kurikulum', 'kepala_sekolah', 'superadmin']",
    'YayasanReportPage' => "['kepala_sekolah', 'superadmin']",
    'KetercapaianProfilLulusan' => "['kepala_sekolah', 'superadmin']",
];

foreach ($files as $file) {
    $basename = basename($file, '.php');
    if ($basename === 'Dashboard') continue; // Don't restrict the main dashboard
    
    if (isset($roleMap[$basename])) {
        $content = file_get_contents($file);
        $roles = $roleMap[$basename];
        $newMethod = "
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole($roles);
    }
";

        if (preg_match('/public static function canAccess\(\): bool\s*\{.*?\}/s', $content)) {
            $content = preg_replace('/public static function canAccess\(\): bool\s*\{.*?\}/s', trim($newMethod), $content);
        } else {
            // Usually Pages extend Page, find the opening bracket of the class
            $content = preg_replace('/(class\s+[A-Za-z0-9_]+\s+extends\s+[A-Za-z0-9_\\\\]+\s*\{)/s', "$1\n" . trim($newMethod), $content);
        }
        
        file_put_contents($file, $content);
        echo "Updated Page $basename\n";
    }
}
echo "All pages updated.\n";
