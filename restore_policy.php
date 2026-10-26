<?php
$file = 'app/Policies/TeacherProfilePolicy.php';
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'kepala_sekolah', 'yayasan', 'ketua yayasan', 'wakil ketua yayasan', 
            'sekretaris yayasan', 'tim pengembang yayasan', 'tata_usaha', 
            'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas', 'bk'
        ]);
    }

    public function view(User $user, TeacherProfile $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'tata_usaha']);
    }

    public function update(User $user, TeacherProfile $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, TeacherProfile $model): bool
    {
        return $user->hasRole(['tata_usaha', 'kepala_sekolah']);
    }
}
PHP;
file_put_contents($file, $content);
echo "TeacherProfilePolicy restored.\n";
