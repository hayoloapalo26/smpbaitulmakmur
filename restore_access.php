<?php

$files_to_restore = [
    'app/Policies/StudentPolicy.php' => [
        "search" => "'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',",
        "replace" => "'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan',"
    ],
    'app/Policies/TeacherSupervisionPolicy.php' => [
        "search" => "'ketua_yayasan'",
        "replace" => "'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan'"
    ],
    'app/Policies/TodoTaskPolicy.php' => [
        "search" => "'sekretaris_yayasan',",
        "replace" => "'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan',"
    ],
    'app/Policies/YayasanProgramPolicy.php' => [
        "search" => "return \$this->create(\$user);",
        "replace" => "return true; // Everyone can view yayasan programs if it's in their nav"
    ],
    'app/Policies/YayasanProgramPolicy.php ' => [ // Note the space to allow multiple replacements in same file if needed, but here we just do it manually below
        "search" => "'sekretaris_yayasan']",
        "replace" => "'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']"
    ],
    'app/Filament/Yayasan/Resources/PermintaanSupervisiResource.php' => [
        "search" => "'ketua_yayasan']",
        "replace" => "'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']"
    ],
    'app/Filament/Yayasan/Resources/PermintaanUkgResource.php' => [
        "search" => "'ketua_yayasan']",
        "replace" => "'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']"
    ],
    'app/Filament/Yayasan/Widgets/InstitutionOverviewWidget.php' => [
        "search" => "'sekretaris_yayasan']",
        "replace" => "'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']"
    ],
    'app/Filament/Staff/Resources/TodoTaskResource.php' => [
        "search" => "'sekretaris_yayasan',",
        "replace" => "'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan',"
    ]
];

foreach($files_to_restore as $file => $action) {
    $real_file = trim($file);
    if(!file_exists($real_file)) {
        echo "File not found: $real_file\n";
        continue;
    }
    $content = file_get_contents($real_file);
    $content = str_replace($action['search'], $action['replace'], $content);
    file_put_contents($real_file, $content);
    echo "Restored in $real_file\n";
}

// Manually fix YayasanProgramPolicy create method
$ypp = 'app/Policies/YayasanProgramPolicy.php';
$content = file_get_contents($ypp);
$content = str_replace("'sekretaris_yayasan']", "'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']", $content);
file_put_contents($ypp, $content);

