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
     * Dapatkan daftar guru piket yang bertugas pada tanggal tertentu.
     * Mengintegrasikan jadwal khusus tanggal dan formasi rutin mingguan yang diatur Waka Kesiswaan,
     * serta memperhitungkan permohonan tukar piket yang telah disetujui.
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
        $month = $carbon->month;
        $targetSemester = ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';

        // 1. Ambil jadwal: Prioritaskan tanggal khusus, lalu hari rutin mingguan di semester aktif
        $buildQuery = function (?int $instFilter) use ($targetDate, $dayName, $targetSemester) {
            $query = static::withoutGlobalScopes()
                ->with(['teacher' => fn ($q) => $q->withoutGlobalScopes()])
                ->where(function ($q) use ($targetDate, $dayName, $targetSemester) {
                    $q->whereDate('tanggal', $targetDate)
                      ->orWhere(function ($sq) use ($dayName, $targetSemester) {
                          $sq->where('hari', $dayName)
                             ->where(function ($ssq) use ($targetSemester) {
                                 $ssq->where('semester', $targetSemester)
                                     ->orWhereNull('semester');
                             });
                      });
                });

            if ($instFilter && $instFilter != 1 && Schema::hasColumn('duty_schedules', 'institution_id')) {
                $query->where(function ($q) use ($instFilter) {
                    $q->where('institution_id', $instFilter)
                      ->orWhereNull('institution_id');
                });
            }

            return $query->orderByRaw("CASE WHEN DATE(tanggal) = ? THEN 0 WHEN semester = ? THEN 1 ELSE 2 END", [$targetDate, $targetSemester])
                ->orderBy('id')
                ->get();
        };

        // Query dengan instFilter
        $records = $buildQuery($institutionId);

        // Jika dengan filter institusi menghasilkan kurang dari 2 guru, cari secara global (tanpa filter institusi)
        // agar formasi yang diatur Waka Kesiswaan selalu ditemukan lengkap
        $uniqueTeachersCount = $records->pluck('teacher_id')->filter()->unique()->count();
        if ($uniqueTeachersCount < 2 && $institutionId) {
            $globalRecords = $buildQuery(null);
            if ($globalRecords->count() > $records->count()) {
                $records = $globalRecords;
            }
        }

        // Jika masih kosong, coba query tanpa filter semester
        if ($records->isEmpty()) {
            $fallbackQuery = static::withoutGlobalScopes()
                ->with(['teacher' => fn ($q) => $q->withoutGlobalScopes()])
                ->where(function ($q) use ($targetDate, $dayName) {
                    $q->whereDate('tanggal', $targetDate)
                      ->orWhere('hari', $dayName);
                })
                ->orderBy('id')
                ->get();

            if ($fallbackQuery->isNotEmpty()) {
                $records = $fallbackQuery;
            }
        }

        $teacherMap = collect();
        foreach ($records as $rec) {
            $teacher = $rec->teacher;
            if ($teacher && ($teacher->is_active ?? true)) {
                $teacherMap->put($teacher->id, $teacher);
            }
        }

        // 2. Terapkan Permohonan Tukar Piket yang Disetujui (Approved Duty Swap)
        if (class_exists(DutySwapRequest::class)) {
            // a. Jika jadwal piket guru di targetDate sudah disetujui ditukar ke pengganti
            $swapsOut = DutySwapRequest::with('substituteTeacher')
                ->where('status', 'disetujui')
                ->whereDate('original_date', $targetDate)
                ->get();

            foreach ($swapsOut as $swap) {
                $teacherMap->forget($swap->requester_id);
                if ($swap->substituteTeacher && ($swap->substituteTeacher->is_active ?? true)) {
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
                if ($swap->requester && ($swap->requester->is_active ?? true)) {
                    $teacherMap->put($swap->requester->id, $swap->requester);
                }
            }
        }

        // 3. Fallback jika masih kurang dari 2 guru:
        // Coba cari guru yang memiliki role guru_piket atau guru eligible lainnya yang belum ada di map
        if ($teacherMap->count() < 2) {
            $existingIds = $teacherMap->keys()->toArray();
            $needed = 2 - $teacherMap->count();

            $piketCandidates = User::withoutGlobalScopes()
                ->where('is_active', true)
                ->whereNotIn('id', $existingIds)
                ->where('name', '!=', 'Guru Piket')
                ->where('username', '!=', 'gurupiket')
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', 'guru_piket'));
                })
                ->limit($needed)
                ->get();

            foreach ($piketCandidates as $t) {
                $teacherMap->put($t->id, $t);
            }

            // Jika masih kurang dari 2, ambil guru eligible dari DutyScheduleGeneratorService
            if ($teacherMap->count() < 2 && class_exists(DutyScheduleGeneratorService::class)) {
                $needed = 2 - $teacherMap->count();
                $existingIds = $teacherMap->keys()->toArray();
                $eligible = DutyScheduleGeneratorService::getEligibleTeachers($institutionId)
                    ->whereNotIn('id', $existingIds)
                    ->take($needed);

                foreach ($eligible as $t) {
                    $teacherMap->put($t->id, $t);
                }
            }
        }

        return $teacherMap->values();
    }
}
