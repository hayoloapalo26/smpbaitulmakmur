<?php

namespace App\Models;

class TeacherProfile extends User
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $guard_name = 'web';

    public function getMorphClass()
    {
        return User::class;
    }

    // Global scope to only show teachers/staff, not students or parents
    protected static function booted()
    {
        parent::booted();

        static::addGlobalScope('staff', function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['siswa', 'orang_tua']);
            });
        });
    }
}
