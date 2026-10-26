<?php
$file = 'app/Policies/StudentPolicy.php';
$content = file_get_contents($file);

$oldViewAny = <<<'PHP'
    public function viewAny($user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'wali_kelas', 'guru_mapel', 'guru_alquran', 'bk']);
    }
PHP;

$newViewAny = <<<'PHP'
    public function viewAny($user): bool
    {
        return $user->hasAnyRole([
            'yayasan', 'ketua yayasan', 'wakil ketua yayasan', 'sekretaris yayasan', 'tim pengembang yayasan',
            'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'wali_kelas', 'guru_mapel', 'guru_alquran', 'bk'
        ]);
    }

    public function view($user, $model): bool
    {
        return $this->viewAny($user);
    }
PHP;

$content = str_replace($oldViewAny, $newViewAny, $content);

// Also replace the old view method which might be hardcoded
$content = preg_replace('/public function view\(\$user, \$model\): bool\s*{\s*return \$user->hasRole\(\[.*?\]\);\s*}/s', '', $content);

file_put_contents($file, $content);
echo "StudentPolicy updated.\n";
