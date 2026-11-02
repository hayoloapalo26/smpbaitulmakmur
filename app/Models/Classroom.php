<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class Classroom extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    public const TIPE_REGULER = 'reguler';
    public const TIPE_QURAN = 'quran';

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function isQuran(): bool
    {
        if (($this->tipe ?? null) === self::TIPE_QURAN) {
            return true;
        }
        $check = strtolower(($this->name ?? '') . ' ' . ($this->tingkat ?? ''));
        return str_contains($check, 'quran')
            || str_contains($check, 'tahsin')
            || str_contains($check, 'yanbu')
            || str_contains($check, 'tahfidz')
            || str_contains($check, 'halaqah');
    }

    public function isReguler(): bool
    {
        return !$this->isQuran();
    }

    public function scopeReguler($query)
    {
        return $query->where(function ($q) {
            $q->where('tipe', self::TIPE_REGULER)
              ->orWhereNull('tipe');
        })->whereRaw('LOWER(name) NOT LIKE ?', ['%tahsin%'])
          ->whereRaw('LOWER(name) NOT LIKE ?', ['%yanbu%'])
          ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahfidz%'])
          ->whereRaw('LOWER(name) NOT LIKE ?', ['%quran%']);
    }

    public function scopeQuran($query)
    {
        return $query->where(function ($q) {
            $q->where('tipe', self::TIPE_QURAN)
              ->orWhereRaw('LOWER(name) LIKE ?', ['%tahsin%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%yanbu%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%tahfidz%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%quran%']);
        });
    }

    public function getPenanggungJawabRoleTitleAttribute(): string
    {
        return $this->isQuran() ? 'Guru Pengampu Al-Qur\'an' : 'Wali Kelas';
    }

    public function getTipeBadgeLabelAttribute(): string
    {
        return $this->isQuran() ? 'Kelompok Al-Qur\'an' : 'Kelas Akademik';
    }

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function guruPengampuQuran()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function getRealStudentsCount(): int
    {
        $instId = $this->institution_id ?? 2;

        if ($this->isQuran()) {
            $teacherId = $this->wali_kelas_id;
            $nameLower = strtolower(($this->name ?? '') . ' ' . ($this->tingkat ?? ''));
            $quranKey = null;
            if (str_contains($nameLower, 'yanbu')) {
                $quranKey = 'yanbua';
            } elseif (str_contains($nameLower, 'tahsin')) {
                $quranKey = 'tahsin';
            } elseif (str_contains($nameLower, 'dasar')) {
                $quranKey = 'tahfidz_dasar';
            } elseif (str_contains($nameLower, 'lanjutan')) {
                $quranKey = 'tahfidz_lanjutan';
            }

            return Student::withoutGlobalScopes()
                ->where(function ($instQ) use ($instId) {
                    if ($instId == 2) {
                        $instQ->where('students.institution_id', 2)
                              ->orWhere('students.institution_id', 1)
                              ->orWhereNull('students.institution_id');
                    } else {
                        $instQ->where('students.institution_id', $instId);
                    }
                })
                ->where(function ($q) use ($teacherId, $quranKey) {
                    $q->where('classroom_id', $this->id);
                    if ($teacherId) {
                        $q->orWhere('guru_quran_id', $teacherId);
                    }
                    if ($quranKey) {
                        if ($teacherId) {
                            $q->orWhere(function ($sub) use ($teacherId, $quranKey) {
                                $sub->where('kelas_quran', $quranKey)
                                    ->where(function ($t) use ($teacherId) {
                                        $t->where('guru_quran_id', $teacherId)
                                          ->orWhereNull('guru_quran_id');
                                    });
                            });
                        } else {
                            $q->orWhere('kelas_quran', $quranKey);
                        }
                    }
                })->count();
        }

        return Student::withoutGlobalScopes()
            ->where(function ($instQ) use ($instId) {
                if ($instId == 2) {
                    $instQ->where('students.institution_id', 2)
                          ->orWhere('students.institution_id', 1)
                          ->orWhereNull('students.institution_id');
                } else {
                    $instQ->where('students.institution_id', $instId);
                }
            })
            ->where('classroom_id', $this->id)
            ->count();
    }

    public function getRealStudents()
    {
        $instId = $this->institution_id ?? 2;

        if ($this->isQuran()) {
            $teacherId = $this->wali_kelas_id;
            $nameLower = strtolower(($this->name ?? '') . ' ' . ($this->tingkat ?? ''));
            $quranKey = null;
            if (str_contains($nameLower, 'yanbu')) {
                $quranKey = 'yanbua';
            } elseif (str_contains($nameLower, 'tahsin')) {
                $quranKey = 'tahsin';
            } elseif (str_contains($nameLower, 'dasar')) {
                $quranKey = 'tahfidz_dasar';
            } elseif (str_contains($nameLower, 'lanjutan')) {
                $quranKey = 'tahfidz_lanjutan';
            }

            return Student::withoutGlobalScopes()
                ->where(function ($instQ) use ($instId) {
                    if ($instId == 2) {
                        $instQ->where('students.institution_id', 2)
                              ->orWhere('students.institution_id', 1)
                              ->orWhereNull('students.institution_id');
                    } else {
                        $instQ->where('students.institution_id', $instId);
                    }
                })
                ->where(function ($q) use ($teacherId, $quranKey) {
                    $q->where('classroom_id', $this->id);
                    if ($teacherId) {
                        $q->orWhere('guru_quran_id', $teacherId);
                    }
                    if ($quranKey) {
                        if ($teacherId) {
                            $q->orWhere(function ($sub) use ($teacherId, $quranKey) {
                                $sub->where('kelas_quran', $quranKey)
                                    ->where(function ($t) use ($teacherId) {
                                        $t->where('guru_quran_id', $teacherId)
                                          ->orWhereNull('guru_quran_id');
                                    });
                            });
                        } else {
                            $q->orWhere('kelas_quran', $quranKey);
                        }
                    }
                })
                ->with('classroom')
                ->orderBy('name')
                ->get();
        }

        return Student::withoutGlobalScopes()
            ->where(function ($instQ) use ($instId) {
                if ($instId == 2) {
                    $instQ->where('students.institution_id', 2)
                          ->orWhere('students.institution_id', 1)
                          ->orWhereNull('students.institution_id');
                } else {
                    $instQ->where('students.institution_id', $instId);
                }
            })
            ->where('classroom_id', $this->id)
            ->with('classroom')
            ->orderBy('name')
            ->get();
    }

    public function getRealStudentsCountAttribute(): int
    {
        return $this->getRealStudentsCount();
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->getRealStudentsCount();
    }

    public function classPrograms()
    {
        return $this->hasMany(ClassProgram::class, 'classroom_id');
    }
}