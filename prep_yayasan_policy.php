<?php
$file = 'app/Policies/YayasanProgramPolicy.php';
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\YayasanProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class YayasanProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view yayasan programs if it's in their nav
    }

    public function view(User $user, YayasanProgram $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['yayasan', 'ketua yayasan', 'wakil ketua yayasan', 'sekretaris yayasan', 'tim pengembang yayasan']);
    }

    public function update(User $user, YayasanProgram $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, YayasanProgram $model): bool
    {
        return $this->create($user);
    }
}
PHP;
file_put_contents($file, $content);
echo "YayasanProgramPolicy configured.\n";
