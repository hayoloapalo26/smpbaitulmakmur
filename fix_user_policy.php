<?php
$file = 'app/Policies/UserPolicy.php';
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny($user): bool
    {
        return $user->hasAnyRole([
            'kepala_sekolah', 'yayasan', 'ketua yayasan', 'wakil ketua yayasan', 
            'sekretaris yayasan', 'tim pengembang yayasan', 'tata_usaha', 
            'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas'
        ]);
    }

    public function view($user, $model): bool
    {
        return $this->viewAny($user) || $user->id === $model->id;
    }

    public function create($user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'tata_usaha', 'yayasan']);
    }

    public function update($user, $model): bool
    {
        return $this->create($user) || $user->id === $model->id;
    }

    public function delete($user, $model): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'yayasan']);
    }

    public function restore($user, $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete($user, $model): bool
    {
        return $this->delete($user, $model);
    }
}
PHP;
file_put_contents($file, $content);
echo "UserPolicy updated.\n";
