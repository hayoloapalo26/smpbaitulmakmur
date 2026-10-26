<?php
$dirResources = 'app/Filament/Staff/Resources/';
$dirPages = 'app/Filament/Staff/Pages/';

$files = array_merge(glob($dirResources . '*.php'), glob($dirPages . '*.php'));

$mapping = [
    // Tugas & Kewajiban (keep as is)
    'Tugas & Kewajiban' => 'Tugas & Kewajiban',
    
    // Administrasi & Staff
    'Tata Usaha' => 'Administrasi & Data Induk',
    'Data Induk' => 'Administrasi & Data Induk',
    'Perizinan' => 'Administrasi & Data Induk',

    // Akademik & Kurikulum
    'Akademik' => 'Akademik & Kurikulum',
    'Kurikulum' => 'Akademik & Kurikulum',
    
    // Kesiswaan & Karakter
    'Kesiswaan' => 'Kesiswaan & Karakter',
    'Karakter & Evaluasi' => 'Kesiswaan & Karakter',
    'Bimbingan Konseling' => 'Kesiswaan & Karakter',
    'Piket' => 'Kesiswaan & Karakter',
    'Tahfidz & Tahsin' => 'Kesiswaan & Karakter',
    'Ibadah & Karakter' => 'Kesiswaan & Karakter',
    
    // Manajemen Sekolah
    'Lintas Divisi' => 'Manajemen Sekolah',
    'Kepala Sekolah' => 'Manajemen Sekolah',
    'Lembaga' => 'Manajemen Sekolah',
    'Manajemen Sekolah' => 'Manajemen Sekolah',
    'Lainnya' => 'Manajemen Sekolah',
    'Manajemen Tugas' => 'Manajemen Sekolah', // Any leftovers
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find current group
    if (preg_match('/protected static \?string \$navigationGroup = \'(.*?)\';/', $content, $matches)) {
        $currentGroup = $matches[1];
        
        if (isset($mapping[$currentGroup])) {
            $newGroup = $mapping[$currentGroup];
            $content = str_replace(
                "protected static ?string \$navigationGroup = '$currentGroup';", 
                "protected static ?string \$navigationGroup = '$newGroup';", 
                $content
            );
            file_put_contents($file, $content);
        }
    }
}
echo "Groups consolidated.\n";
