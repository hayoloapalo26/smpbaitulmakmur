<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DutySchedule extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (DutySchedule $model) {
            if (!empty($model->tanggal)) {
                // Auto-fill hari jika kolom tersedia
                if (Schema::hasColumn('duty_schedules', 'hari') && empty($model->hari)) {
                    $model->hari = Schedule::getIndonesianDayName($model->tanggal);
                }

                // Auto-fill semester jika kolom tersedia
                if (Schema::hasColumn('duty_schedules', 'semester') && empty($model->semester)) {
                    $month = Carbon::parse($model->tanggal)->month;
                    $model->semester = ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';
                }

                // Auto-fill academic_year_id jika kolom tersedia dan belum terisi
                if (Schema::hasColumn('duty_schedules', 'academic_year_id') && empty($model->academic_year_id)) {
                    $activeAy = AcademicYear::where('is_active', true)->first();
                    if ($activeAy) {
                        $model->academic_year_id = $activeAy->id;
                    }
                }
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Dapatkan nama hari dalam bahasa Indonesia
     */
    public function getHariAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        if (!empty($this->tanggal)) {
            return Schedule::getIndonesianDayName($this->tanggal);
        }

        return 'Senin';
    }

    /**
     * Dapatkan nama semester (Ganjil / Genap)
     */
    public function getSemesterAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->academic_year_id && $this->relationLoaded('academicYear') && $this->academicYear?->semester) {
            return $this->academicYear->semester;
        }

        if (!empty($this->tanggal)) {
            $month = Carbon::parse($this->tanggal)->month;
            return ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';
        }

        return 'Ganjil';
    }

    /**
     * Scope untuk jadwal piket hari ini
     */
    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', Carbon::today());
    }

    /**
     * Scope untuk jadwal semester tertentu
     */
    public function scopeSemester(Builder $query, string $semester): Builder
    {
        return $query->where(function ($q) use ($semester) {
            if (Schema::hasColumn('duty_schedules', 'semester')) {
                $q->where('semester', $semester);
            }
            if ($semester === 'Ganjil') {
                $q->orWhereRaw('MONTH(tanggal) BETWEEN 7 AND 12');
            } else {
                $q->orWhereRaw('MONTH(tanggal) BETWEEN 1 AND 6');
            }
        });
    }
}
