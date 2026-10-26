<?php
$file = "app/Policies/MandatoryTaskPolicy.php";
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\MandatoryTask;
use App\Models\User;

class MandatoryTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']);
    }

    public function view(User $user, MandatoryTask $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['kepala_sekolah', 'superadmin']);
    }

    public function update(User $user, MandatoryTask $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MandatoryTask $model): bool
    {
        return $this->create($user);
    }
}
PHP;
file_put_contents($file, $content);
echo "Policy updated.\n";
