<?php
$file = 'app/Providers/AppServiceProvider.php';
$content = file_get_contents($file);

$oldGate = <<<'PHP'
        Gate::before(function ($user, $ability) {
            // HANYA superadmin yang memiliki akses penuh tanpa batas
            if ($user->hasRole('superadmin')) {
                return true;
            }
            return null;
        });
PHP;

$newGate = <<<'PHP'
        Gate::before(function ($user, $ability) {
            // 1. Hak Akses Mutlak (Superadmin)
            // Hanya Superadmin yang bisa melakukan APA SAJA (Create, Read, Update, Delete) di semua fitur
            if ($user->hasRole('superadmin')) {
                return true;
            }

            // 2. Hak Akses Pemantauan (Yayasan)
            // Yayasan bertugas melakukan pengawasan (Monitoring). 
            // Maka Yayasan diberi hak akses BACA (view, viewAny) ke seluruh data di sistem,
            // namun TIDAK BISA menambah/mengubah/menghapus data sekolah (kecuali diizinkan spesifik di Policy-nya).
            if ($user->hasAnyRole(['yayasan', 'ketua yayasan', 'wakil ketua yayasan', 'sekretaris yayasan', 'tim pengembang yayasan'])) {
                if (in_array($ability, ['view', 'viewAny'])) {
                    return true;
                }
            }
            
            return null;
        });
PHP;

$content = str_replace($oldGate, $newGate, $content);
file_put_contents($file, $content);
echo "AppServiceProvider updated with Tupoksi-based Gate.\n";
