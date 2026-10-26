<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NewsPolicy
{
    public function viewAny(User $user): bool
    {
        return true; 
    }

    public function view(User $user, News $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['yayasan', 'ketua_yayasan', 'kepala_sekolah', 'humas', 'tata_usaha']);
    }

    public function update(User $user, News $model): bool
    {
        return $this->create($user) || $user->id === $model->author_id;
    }

    public function delete(User $user, News $model): bool
    {
        return $user->hasAnyRole(['yayasan', 'kepala_sekolah']);
    }
}