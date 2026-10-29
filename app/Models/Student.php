<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->hasOne(User::class, 'student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function guruQuran()
    {
        return $this->belongsTo(User::class, 'guru_quran_id');
    }

    public function getKelasQuranLabelAttribute(): ?string
    {
        return match ($this->kelas_quran) {
            'yanbua' => 'Yanbu\'a',
            'tahsin' => 'Tahsin',
            'tahfidz_dasar' => 'Tahfidz Dasar',
            'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
            default => $this->kelas_quran ? ucfirst($this->kelas_quran) : null,
        };
    }

    protected function casts(): array
    {
        return [
            'tanggal_penempatan_quran' => 'date',
        ];
    }

    public function prayerAttendances()
    {
        return $this->hasMany(PrayerAttendance::class);
    }

    public function characterScoreLogs()
    {
        return $this->hasMany(CharacterScoreLog::class);
    }

    public function quranAchievements()
    {
        return $this->hasMany(QuranAchievement::class);
    }

    public function readingQualityScores()
    {
        return $this->hasMany(ReadingQualityScore::class);
    }

    public function studentAchievements()
    {
        return $this->hasMany(StudentAchievement::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function studentCases()
    {
        return $this->hasMany(StudentCase::class);
    }

    public function parentSummons()
    {
        return $this->hasMany(ParentSummon::class);
    }

    public function attributeViolations()
    {
        return $this->hasMany(AttributeViolation::class);
    }

    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class);
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function studentTodoLists()
    {
        return $this->hasMany(StudentTodoList::class);
    }

    public function parentDailyChecklists()
    {
        return $this->hasMany(ParentDailyChecklist::class);
    }

    public function studentLeaveRequests()
    {
        return $this->hasMany(StudentLeaveRequest::class);
    }

    public function extracurricularAttendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }

    public function dcmResponses()
    {
        return $this->hasMany(DcmResponse::class);
    }

    public function latestDcmResponse()
    {
        return $this->hasOne(DcmResponse::class)->latestOfMany();
    }

    public function alumniTracer()
    {
        return $this->hasOne(AlumniTracer::class, 'student_id');
    }

    public function sppPayments()
    {
        return $this->hasMany(StudentSpp::class, 'student_id');
    }

    public function savings()
    {
        return $this->hasMany(StudentSaving::class, 'student_id')->orderBy('tanggal', 'asc')->orderBy('id', 'asc');
    }

    public function getSaldoTabunganAttribute(): float
    {
        return StudentSaving::getLatestBalance($this->id);
    }

    /**
     * Accessor untuk Fingerspot PIN (otomatis fallback ke hikvision_employee_no jika belum diisi)
     */
    public function getFingerspotPinAttribute(): ?string
    {
        return $this->attributes['fingerspot_pin'] 
            ?? $this->attributes['hikvision_employee_no'] 
            ?? null;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (Student $student) {
            // Sinkronisasi otomatis antara fingerspot_pin dan hikvision_employee_no
            $hasFingerspot = !empty($student->attributes['fingerspot_pin'] ?? null);
            $hasHikvision  = !empty($student->attributes['hikvision_employee_no'] ?? null);

            if ($hasFingerspot && !$hasHikvision) {
                $student->attributes['hikvision_employee_no'] = $student->attributes['fingerspot_pin'];
            } elseif ($hasHikvision && !$hasFingerspot) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'fingerspot_pin')) {
                    $student->attributes['fingerspot_pin'] = $student->attributes['hikvision_employee_no'];
                }
            }
        });
    }
}

