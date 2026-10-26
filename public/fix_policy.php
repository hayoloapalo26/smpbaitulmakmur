<?php
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\TeacherSupervision;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherSupervisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'ketua_yayasan', 'tim_pengembang_yayasan']);
    }

    public function view(User $user, TeacherSupervision $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum']);
    }

    public function update(User $user, TeacherSupervision $model): bool
    {
        return true;
    }

    public function delete(User $user, TeacherSupervision $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum']);
    }
}
PHP;
file_put_contents(__DIR__.'/../app/Policies/TeacherSupervisionPolicy.php', $content);
echo "SUCCESS UPDATED POLICY";
