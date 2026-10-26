<?php
$file = 'app/Policies/TodoTaskPolicy.php';
$content = <<<'PHP'
<?php

namespace App\Policies;

use App\Models\TodoTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TodoTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['yayasan', 'ketua yayasan', 'wakil ketua yayasan', 'sekretaris yayasan', 'tim pengembang yayasan', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
    }

    public function view(User $user, TodoTask $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, TodoTask $model): bool
    {
        return $user->id === $model->created_by || $user->hasRole('kepala_sekolah');
    }

    public function delete(User $user, TodoTask $model): bool
    {
        return $user->id === $model->created_by || $user->hasRole('kepala_sekolah');
    }
}
PHP;
file_put_contents($file, $content);
echo "TodoTaskPolicy configured.\n";
