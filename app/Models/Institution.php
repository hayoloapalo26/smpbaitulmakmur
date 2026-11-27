<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $guarded = ['id'];
    public function yayasanPrograms()
    {
        return $this->belongsToMany(YayasanProgram::class, 'institution_yayasan_program')
            ->withPivot('is_completed', 'notes')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'institution_user')->withTimestamps();
    }

    /**
     * Dapatkan ID institusi yang sedang aktif secara aman.
     * Secara bawaan dalam sistem SMP ini selalu bernilai 2 (SMP),
     * kecuali diset secara spesifik dalam sesi atau user non-yayasan.
     */
    public static function getActiveInstitutionId(): int
    {
        $user = auth()->user();
        $instId = session('active_institution_id') ?? $user?->institution_id;

        // Yayasan (1) atau null otomatis diarahkan ke SMP (2)
        if (empty($instId) || (int) $instId === 1) {
            return 2;
        }

        return (int) $instId;
    }
}

