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

    public function parentUsers()
    {
        return $this->belongsToMany(User::class, 'student_guardians', 'student_id', 'user_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians', 'student_id', 'guardian_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function studentGuardians()
    {
        return $this->hasMany(StudentGuardian::class, 'student_id');
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

    public function violationLogs()
    {
        return $this->hasMany(StudentViolationLog::class);
    }

    public function violationRecords()
    {
        return $this->hasMany(StudentViolationRecord::class);
    }

    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class);
    }

    public function dailyAttendances()
    {
        return $this->hasMany(DailyAttendance::class);
    }

    /**
     * Dapatkan presensi harian siswa berdasarkan tanggal dan tipe sesi tertentu.
     * Mengunci dan memprioritaskan scan pertama (waktu paling awal), kecuali untuk akun Lana (testing).
     */
    public function getAttendanceOnDate(string $date, string|array $tipe): ?DailyAttendance
    {
        $tipes = is_array($tipe) ? $tipe : [$tipe];

        // Jika mencari 'sholat_dhuhur', otomatis sertakan 'keputrian' (kegiatan khusus siswi putri di hari Jumat)
        if (in_array('sholat_dhuhur', $tipes) && !in_array('keputrian', $tipes)) {
            $tipes[] = 'keputrian';
        }

        $isLana = $this->name && stripos($this->name, 'Lana') !== false;

        if ($this->relationLoaded('dailyAttendances')) {
            $filtered = $this->dailyAttendances
                ->filter(function ($att) use ($date, $tipes) {
                    $attDate = \Carbon\Carbon::parse($att->tanggal)->toDateString();
                    return $attDate === $date && in_array($att->tipe_absen, $tipes);
                });

            return $isLana
                ? $filtered->sortByDesc('id')->first()
                : $filtered->sortBy(fn ($att) => ($att->waktu_kedatangan ?: '99:99:99') . '_' . str_pad((string)$att->id, 10, '0', STR_PAD_LEFT))->first();
        }

        $query = $this->dailyAttendances()
            ->withoutGlobalScopes()
            ->whereDate('tanggal', $date)
            ->whereIn('tipe_absen', $tipes);

        return $isLana
            ? $query->latest('id')->first()
            : $query->orderByRaw('COALESCE(waktu_kedatangan, "99:99:99") ASC, id ASC')->first();
    }

    /**
     * Cek apakah siswa berstatus Izin, Sakit, atau Alpa pada tanggal tertentu.
     */
    public function getGeneralExcusedOnDate(string $date): ?DailyAttendance
    {
        if ($this->relationLoaded('dailyAttendances')) {
            return $this->dailyAttendances
                ->filter(function ($att) use ($date) {
                    $attDate = \Carbon\Carbon::parse($att->tanggal)->toDateString();
                    return $attDate === $date && in_array($att->status, ['sakit', 'izin', 'alpa']);
                })
                ->first();
        }

        return $this->dailyAttendances()
            ->withoutGlobalScopes()
            ->whereDate('tanggal', $date)
            ->whereIn('status', ['sakit', 'izin', 'alpa'])
            ->first();
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
     * Accessor untuk Fingerspot PIN (otomatis fallback ke id_finger / hikvision_employee_no jika belum diisi)
     */
    public function getFingerspotPinAttribute(): ?string
    {
        return $this->attributes['fingerspot_pin']
            ?? $this->attributes['id_finger']
            ?? $this->attributes['hikvision_employee_no']
            ?? null;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (Student $student) {
            // Siswa di lingkungan Baitul Makmur tidak pernah berada di Yayasan (ID = 1).
            // Tentukan lembaga secara dinamis: prioritaskan classroom, user pembuat, atau default SMP (ID = 2).
            if (empty($student->institution_id) || $student->institution_id == 1) {
                $resolvedInstId = null;
                if (!empty($student->classroom_id)) {
                    $resolvedInstId = \App\Models\Classroom::withoutGlobalScopes()
                        ->where('id', $student->classroom_id)
                        ->value('institution_id');
                }
                if (!$resolvedInstId && auth()->check()) {
                    $user = auth()->user();
                    $nonYayasanId = collect($user->institution_ids)->first(fn ($id) => $id != 1);
                    $resolvedInstId = $nonYayasanId ?: $user->institution_id;
                }
                $student->institution_id = ($resolvedInstId && $resolvedInstId != 1) ? $resolvedInstId : 2;
            }

            // Sinkronisasi dua arah antara id_finger, fingerspot_pin, dan hikvision_employee_no
            // Prioritaskan kolom yang baru saja diubah (isDirty) agar perubahan ID Finger tidak tertimpa nilai lama
            // Selalu bersihkan (trim) dari spasi tak sengaja
            $cleanPin = function ($val) {
                if ($val === null || $val === '') {
                    return null;
                }
                return trim((string) $val);
            };

            if ($student->isDirty('id_finger')) {
                $fingerVal = $cleanPin($student->attributes['id_finger'] ?? null);
                $student->attributes['id_finger'] = $fingerVal;
                $student->attributes['fingerspot_pin'] = $fingerVal;
                $student->attributes['hikvision_employee_no'] = $fingerVal;
            } elseif ($student->isDirty('fingerspot_pin')) {
                $fingerVal = $cleanPin($student->attributes['fingerspot_pin'] ?? null);
                $student->attributes['id_finger'] = $fingerVal;
                $student->attributes['fingerspot_pin'] = $fingerVal;
                $student->attributes['hikvision_employee_no'] = $fingerVal;
            } elseif ($student->isDirty('hikvision_employee_no')) {
                $fingerVal = $cleanPin($student->attributes['hikvision_employee_no'] ?? null);
                $student->attributes['id_finger'] = $fingerVal;
                $student->attributes['fingerspot_pin'] = $fingerVal;
                $student->attributes['hikvision_employee_no'] = $fingerVal;
            } else {
                $fingerVal = $cleanPin(
                    $student->attributes['id_finger']
                    ?? ($student->attributes['fingerspot_pin']
                    ?? ($student->attributes['hikvision_employee_no'] ?? null))
                );

                if ($fingerVal !== null && $fingerVal !== '') {
                    $student->attributes['id_finger'] = $fingerVal;
                    $student->attributes['fingerspot_pin'] = $fingerVal;
                    $student->attributes['hikvision_employee_no'] = $fingerVal;
                }
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

        // Sinkronisasi otomatis orang tua ke Data Orang Tua (Guardians) saat siswa disimpan
        static::saved(function (Student $student) {
            $parentName = $student->nama_ayah ?: ($student->nama_ibu ?: ($student->nama_wali ?: null));
            if (!empty($parentName) && trim($parentName) !== '') {
                $relation = $student->nama_ayah ? 'Ayah' : ($student->nama_ibu ? 'Ibu' : 'Wali');
                $phone = $student->no_hp_ayah ?: ($student->no_hp_ibu ?: ($student->no_hp_wali ?: ($student->no_telepon_orang_tua ?: '-')));
                $instId = $student->institution_id ?: 2;

                $guardian = Guardian::firstOrCreate(
                    [
                        'name' => trim($parentName),
                        'institution_id' => $instId,
                    ],
                    [
                        'phone' => trim($phone),
                        'address' => $student->alamat ?? '-',
                    ]
                );

                StudentGuardian::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'guardian_id' => $guardian->id,
                    ],
                    [
                        'relation' => $relation,
                    ]
                );
            }
        });
    }
}

