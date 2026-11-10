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
            'tanggal_lahir' => 'date',
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

    public function extracurricularMembers()
    {
        return $this->hasMany(ExtracurricularMember::class, 'student_id');
    }

    public function extracurriculars()
    {
        return $this->belongsToMany(Extracurricular::class, 'extracurricular_members', 'student_id', 'extracurricular_id')
            ->withPivot(['is_active', 'catatan'])
            ->withTimestamps();
    }

    public function extracurricularGrades()
    {
        return $this->hasMany(ExtracurricularGrade::class, 'student_id');
    }

    public function raporGrades()
    {
        return $this->hasMany(RaporGrade::class, 'student_id');
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
            // Siswa di lingkungan Baitul Makmur tidak pernah berada di Yayasan (ID = 1).
            // Jika kosong, null, atau 1, otomatis arahkan ke unit SMP (ID = 2).
            if (empty($student->institution_id) || $student->institution_id == 1) {
                $student->institution_id = 2;
            }

            // Sinkronisasi otomatis antara id_finger, fingerspot_pin, dan hikvision_employee_no
            $fingerVal = $student->attributes['id_finger'] 
                ?? ($student->attributes['fingerspot_pin'] 
                ?? ($student->attributes['hikvision_employee_no'] ?? null));

            if (!empty($fingerVal)) {
                $student->attributes['id_finger'] = $fingerVal;
                $student->attributes['fingerspot_pin'] = $fingerVal;
                $student->attributes['hikvision_employee_no'] = $fingerVal;
            }

            // Sinkronisasi otomatis penempatan Al-Qur'an jika classroom_id mengarah ke rombel Al-Qur'an
            if (!empty($student->classroom_id)) {
                $cls = Classroom::withoutGlobalScopes()->find($student->classroom_id);
                if ($cls && $cls->isQuran()) {
                    if (empty($student->guru_quran_id) && $cls->wali_kelas_id) {
                        $student->guru_quran_id = $cls->wali_kelas_id;
                    }
                    if (empty($student->kelas_quran)) {
                        $nameLower = strtolower(($cls->name ?? '') . ' ' . ($cls->tingkat ?? ''));
                        if (str_contains($nameLower, 'yanbu')) {
                            $student->kelas_quran = 'yanbua';
                        } elseif (str_contains($nameLower, 'tahsin')) {
                            $student->kelas_quran = 'tahsin';
                        } elseif (str_contains($nameLower, 'dasar')) {
                            $student->kelas_quran = 'tahfidz_dasar';
                        } elseif (str_contains($nameLower, 'lanjutan')) {
                            $student->kelas_quran = 'tahfidz_lanjutan';
                        }
                    }
                }
            }

            // Jika guru_quran_id diatur tapi tanggal_penempatan_quran kosong, set ke hari ini
            if (!empty($student->guru_quran_id) && empty($student->tanggal_penempatan_quran)) {
                $student->tanggal_penempatan_quran = now()->toDateString();
            }
        });
    }
}

