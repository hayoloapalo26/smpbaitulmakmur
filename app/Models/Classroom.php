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

    public function classPrograms()
    {
        return $this->hasMany(ClassProgram::class, 'classroom_id');
    }
}