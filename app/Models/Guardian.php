<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $guarded = ['id'];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_guardians', 'guardian_id', 'student_id')
            ->withPivot('relation', 'user_id')
            ->withTimestamps();
    }

    public function studentGuardians()
    {
        return $this->hasMany(StudentGuardian::class, 'guardian_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'guardian_id');
    }

    public function getUserAccountAttribute(): ?User
    {
        if ($this->user) {
            return $this->user;
        }

        // Cari apakah ada akun user_id yang terhubung di tabel pivot student_guardians
        $userId = $this->studentGuardians()->whereNotNull('user_id')->value('user_id');
        if ($userId) {
            return User::withoutGlobalScopes()->find($userId);
        }

        return null;
    }

    public function getHasAccountAttribute(): bool
    {
        return $this->user_account !== null;
    }

    public function getIsRoutedAttribute(): bool
    {
        return $this->students()->exists() || $this->studentGuardians()->whereNotNull('student_id')->exists();
    }

    public function getAccountStatusAttribute(): string
    {
        $hasAccount = $this->has_account;
        $isRouted = $this->is_routed;

        if ($hasAccount && $isRouted) {
            return 'Akun Aktif';
        }

        if ($hasAccount && !$isRouted) {
            return 'Menunggu Routing TU';
        }

        if (!$hasAccount && $isRouted) {
            return 'Belum Dibuatkan Akun';
        }

        return 'Belum Dibuat';
    }
}
