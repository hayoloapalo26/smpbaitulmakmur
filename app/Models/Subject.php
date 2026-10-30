<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    public function teacherSubjectClasses()
    {
        return $this->hasMany(TeacherSubjectClass::class, 'subject_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_subject_classes', 'subject_id', 'teacher_id')->distinct();
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }

    /**
     * Cek apakah nama mapel merupakan rumpun Al-Qur'an (Tahfidz, Tahsin, Yanbu'a, BTQ, dll.)
     */
    public static function isQuranSubjectName(?string $name): bool
    {
        if (empty($name)) {
            return false;
        }

        $lower = mb_strtolower($name);
        $keywords = ['qur', 'tahfidz', 'tahfiz', 'tahsin', 'yanbu', 'btq', 'bta', 'tajwid'];

        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah subject ini adalah mapel Al-Qur'an
     */
    public function isQuranSubject(): bool
    {
        return static::isQuranSubjectName($this->name);
    }

    /**
     * Scope hanya mapel akademik (mengecualikan seluruh mapel Al-Qur'an)
     */
    public function scopeAkademik($query)
    {
        $keywords = ['%qur%', '%tahfidz%', '%tahfiz%', '%tahsin%', '%yanbu%', '%btq%', '%bta%', '%tajwid%'];

        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->whereRaw('LOWER(name) NOT LIKE ?', [$kw]);
            }
        });
    }

    /**
     * Scope khusus mapel Al-Qur'an
     */
    public function scopeQuranOnly($query)
    {
        $keywords = ['%qur%', '%tahfidz%', '%tahfiz%', '%tahsin%', '%yanbu%', '%btq%', '%bta%', '%tajwid%'];

        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhereRaw('LOWER(name) LIKE ?', [$kw]);
            }
        });
    }
}
