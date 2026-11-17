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
            // Auto-fill hari jika tanggal diisi tapi hari belum ditentukan
            if (!empty($model->tanggal) && empty($model->hari)) {
                $model->hari = Schedule::getIndonesianDayName($model->tanggal);
            }

            // Auto-fill semester jika belum terisi
            if (empty($model->semester)) {
                if (!empty($model->tanggal)) {
                    $month = Carbon::parse($model->tanggal)->month;
                    $model->semester = ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';
                } else {
                    $month = Carbon::now()->month;
                    $model->semester = ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';
                }
            }

            // Auto-fill academic_year_id jika belum terisi
            if (empty($model->academic_year_id)) {
                $activeAy = AcademicYear::where('is_active', true)->first();
                if ($activeAy) {
                    $model->academic_year_id = $activeAy->id;
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
     * Scope untuk jadwal piket hari ini (otomatis mencocokkan hari atau tanggal)
     */
    public function scopeHariIni(Builder $query): Builder
    {
        $today = Carbon::today();
        $dayName = Schedule::getIndonesianDayName($today);

        return $query->where(function ($q) use ($today, $dayName) {
            $q->where('hari', $dayName)
              ->orWhereDate('tanggal', $today);
        });
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

    /**
     * Dapatkan daftar guru piket yang bertugas pada tanggal tertentu (memperhitungkan hari rutin, tanggal khusus, dan tukar piket).
     *
     * @param string|\Carbon\Carbon|null $date
     * @param int|null $institutionId
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public static function getDutyTeachersForDate($date = null, ?int $institutionId = null): \Illuminate\Support\Collection
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::today();
        $targetDate = $carbon->toDateString();
        $dayName = Schedule::getIndonesianDayName($carbon);

        // 1. Prioritas 1: Ambil jadwal khusus berdasarkan tanggal yang cocok
        $specificQuery = static::withoutGlobalScopes()
            ->with('teacher')
            ->whereDate('tanggal', $targetDate);

        if ($institutionId && $institutionId != 1 && Schema::hasColumn('duty_schedules', 'institution_id')) {
            $specificQuery->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
            });
        }

        $records = $specificQuery->orderBy('id')->get();

        // 2. Prioritas 2: Jika tidak ada jadwal tanggal khusus, ambil jadwal mingguan rutin berdasarkan hari
        if ($records->isEmpty()) {
            $routineQuery = static::withoutGlobalScopes()
                ->with('teacher')
                ->where('hari', $dayName)
                ->whereNull('tanggal');

            if ($institutionId && $institutionId != 1 && Schema::hasColumn('duty_schedules', 'institution_id')) {
                $routineQuery->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
                });
            }

            $records = $routineQuery->orderBy('id')->get();

            // Jika masih kosong, cari berdasarkan hari tanpa batasan whereNull('tanggal')
            if ($records->isEmpty()) {
                $fallbackRoutine = static::withoutGlobalScopes()
                    ->with('teacher')
                    ->where('hari', $dayName);

                if ($institutionId && $institutionId != 1 && Schema::hasColumn('duty_schedules', 'institution_id')) {
                    $fallbackRoutine->where(function ($q) use ($institutionId) {
                        $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
                    });
                }

                $records = $fallbackRoutine->orderBy('id')->get();
            }
        }

        $teacherMap = collect();
        foreach ($records as $rec) {
            if ($rec->teacher && $rec->teacher->is_active) {
                $teacherMap->put($rec->teacher->id, $rec->teacher);
            }
        }

        // 3. Terapkan Permohonan Tukar Piket yang Disetujui (Approved Duty Swap)
        if (class_exists(DutySwapRequest::class)) {
            // a. Jika jadwal piket guru di targetDate sudah disetujui ditukar ke pengganti
            $swapsOut = DutySwapRequest::with('substituteTeacher')
                ->where('status', 'disetujui')
                ->whereDate('original_date', $targetDate)
                ->get();

            foreach ($swapsOut as $swap) {
                $teacherMap->forget($swap->requester_id);
                if ($swap->substituteTeacher && $swap->substituteTeacher->is_active) {
                    $teacherMap->put($swap->substituteTeacher->id, $swap->substituteTeacher);
                }
            }

            // b. Jika ada guru pemohon yang mengambil shift pengganti pada targetDate (new_date)
            $swapsIn = DutySwapRequest::with('requester')
                ->where('status', 'disetujui')
                ->whereDate('new_date', $targetDate)
                ->get();

            foreach ($swapsIn as $swap) {
                $teacherMap->forget($swap->substitute_teacher_id);
                if ($swap->requester && $swap->requester->is_active) {
                    $teacherMap->put($swap->requester->id, $swap->requester);
                }
            }
        }

        // 4. Fallback aman jika pada hari tersebut jadwal di DutySchedule belum diatur sama sekali
        if ($teacherMap->isEmpty()) {
            $piketCandidates = User::withoutGlobalScopes()
                ->where('is_active', true)
                ->where('name', '!=', 'Guru Piket')
                ->where('username', '!=', 'gurupiket')
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', 'guru_piket'));
                });

            if ($institutionId && $institutionId != 1) {
                $piketCandidates->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
                });
            }

            $teachers = $piketCandidates->limit(2)->get();
            foreach ($teachers as $t) {
                $teacherMap->put($t->id, $t);
            }
        }

        return $teacherMap->values();
    }
}
