<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'avatar_url', 'username', 'is_active', 'institution_id', 'has_account', 'nuptk', 'no_induk_yayasan', 'gender', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public const ROLE_GURU_QURAN_YANBUA = 'guru_quran_yanbua';
    public const ROLE_GURU_QURAN_TAHSIN = 'guru_quran_tahsin';
    public const ROLE_GURU_QURAN_TAHFIDZ_DASAR = 'guru_quran_tahfidz_dasar';
    public const ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN = 'guru_quran_tahfidz_lanjutan';

    public const QURAN_TEACHER_ROLES = [
        'guru_alquran',
        'guru_quran_yanbua',
        'guru_quran_tahsin',
        'guru_quran_tahfidz_dasar',
        'guru_quran_tahfidz_lanjutan',
    ];

    public static function getQuranTeacherRoleLabels(): array
    {
        return [
            'guru_alquran' => 'Guru Al-Qur\'an',
            'guru_quran_yanbua' => 'Guru Al-Qur\'an Yanbu\'a',
            'guru_quran_tahsin' => 'Guru Al-Qur\'an Tahsin',
            'guru_quran_tahfidz_dasar' => 'Guru Al-Qur\'an Tahfidz Dasar',
            'guru_quran_tahfidz_lanjutan' => 'Guru Al-Qur\'an Tahfidz Lanjutan',
        ];
    }

    public function isQuranTeacher(): bool
    {
        return $this->hasAnyRole(self::QURAN_TEACHER_ROLES);
    }

    public function getQuranSpecializationLabel(): ?string
    {
        if ($this->hasRole(self::ROLE_GURU_QURAN_YANBUA)) return 'Yanbu\'a';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHSIN)) return 'Tahsin';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 'Tahfidz Dasar';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN)) return 'Tahfidz Lanjutan';
        if ($this->hasRole('guru_alquran')) return 'Al-Qur\'an';
        return null;
    }

    public function getQuranSpecializationKey(): ?string
    {
        if ($this->hasRole(self::ROLE_GURU_QURAN_YANBUA)) return 'yanbua';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHSIN)) return 'tahsin';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 'tahfidz_dasar';
        if ($this->hasRole(self::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN)) return 'tahfidz_lanjutan';
        return null;
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Always return true to allow Filament to process the login.
        // The App\Http\Middleware\RedirectPortalUsers middleware will securely 
        // redirect the user to their correct panel based on roles and institution_id
        // immediately after login or on any page load.
        return true;
    }

    protected static function booted()
    {
        static::saving(function (User $user) {
            if (!empty($user->email) && !str_contains($user->email, '@placeholder.local') && !empty($user->username)) {
                $user->has_account = true;
            }
        });
    }

    public function getHasAccountAttribute($value): bool
    {
        if ($value) {
            return true;
        }

        if (!empty($this->email) && !str_contains($this->email, '@placeholder.local') && !empty($this->username)) {
            return true;
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_account' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function santriQuran()
    {
        return $this->hasMany(Student::class, 'guru_quran_id');
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class, 'teacher_id');
    }

    public function substituteTeacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class, 'substitute_teacher_id');
    }

    public function teacherSubjectClasses()
    {
        return $this->hasMany(TeacherSubjectClass::class, 'teacher_id');
    }

    public function teachingSubjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject_classes', 'teacher_id', 'subject_id')->distinct();
    }

    public function certificates()
    {
        return $this->hasMany(TeacherCertificate::class, 'teacher_id');
    }

    /**
     * Memastikan user akun khusus "Guru Piket" tersedia di sistem secara terisolasi.
     */
    public static function ensureGuruPiketUser(?int $institutionId = 2): ?self
    {
        try {
            // 1. Cek akun khusus yang murni hanya untuk piket (bukan akun waka atau guru riil)
            $user = static::withoutGlobalScopes()
                ->where(function ($q) {
                    $q->where('username', 'gurupiket')
                      ->orWhere('email', 'gurupiket@smp.com');
                })
                ->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', ['waka_kurikulum', 'kepala_sekolah', 'superadmin']))
                ->first();

            // 2. Jika belum ada, buat user virtual khusus "Guru Piket" (jangan pernah rename guru asli)
            if (!$user) {
                $instId = $institutionId;
                if ($instId && !\App\Models\Institution::where('id', $instId)->exists()) {
                    $instId = \App\Models\Institution::first()?->id;
                }

                $user = static::withoutGlobalScopes()->create([
                    'name' => 'Guru Piket',
                    'username' => 'gurupiket',
                    'email' => 'gurupiket@smp.com',
                    'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                    'institution_id' => $instId,
                    'is_active' => true,
                ]);
            }

            // 3. Pastikan role guru_piket terpasang
            if ($user && method_exists($user, 'hasRole') && !$user->hasRole('guru_piket')) {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
                $user->assignRole($role);
            }

            return $user;
        } catch (\Throwable $e) {
            return static::withoutGlobalScopes()
                ->where('username', 'gurupiket')
                ->orWhere('name', 'Guru Piket')
                ->first();
        }
    }

    /**
     * Cek apakah user bertugas sebagai guru piket pada tanggal tertentu (default: hari ini).
     */
    public function isPiketOnDate($date = null): bool
    {
        // Hanya akun murni piket tanpa jabatan struktural/mapel yang selalu bernilai true
        if (method_exists($this, 'hasRole') && $this->hasRole('guru_piket') && !$this->hasAnyRole(array_merge([
            'waka_kurikulum', 'waka_kesiswaan', 'kepala_sekolah', 'guru_mapel', 'wali_kelas', 'guru_kelas', 'superadmin'
        ], self::QURAN_TEACHER_ROLES))) {
            return true;
        }

        $targetDate = $date ? \Carbon\Carbon::parse($date)->toDateString() : \Carbon\Carbon::today()->toDateString();
        $targetDay = Schedule::getIndonesianDayName($targetDate);

        return \App\Models\DutySchedule::where('teacher_id', $this->id)
            ->where(function ($q) use ($targetDate, $targetDay) {
                $q->where('hari', $targetDay)
                  ->orWhereDate('tanggal', $targetDate);
            })
            ->exists();
    }

    /**
     * Cek apakah user bertugas sebagai guru piket hari ini.
     */
    public function isPiketToday(): bool
    {
        return $this->isPiketOnDate();
    }

    /**
     * Mendapatkan daftar opsi Guru Pengganti (Inval) dengan 'Guru Piket' selalu di posisi teratas.
     * Hanya opsi tunggal 'Guru Piket' (tanpa nama guru) agar tidak terhitung jam inval.
     * Guru pengganti lainnya adalah daftar guru asli yang akan mendapatkan jam inval sesuai JP.
     *
     * @param int|null $excludedTeacherId ID guru yang sedang izin (agar tidak memilih dirinya sendiri)
     * @param User|null $currentUser User yang sedang login untuk filter institusi
     * @return array<int, string>
     */
    public static function getSubstituteTeacherOptions(?int $excludedTeacherId = null, ?self $currentUser = null): array
    {
        $piketUser = static::ensureGuruPiketUser();
        $piketId = $piketUser?->id;

        $user = $currentUser ?: \Illuminate\Support\Facades\Auth::user();

        $query = static::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', array_merge([
                    'guru_mapel',
                    'wali_kelas',
                    'guru_kelas',
                    'waka_kurikulum',
                ], self::QURAN_TEACHER_ROLES)))
                ->orWhereHas('schedules');
            });

        if ($piketId) {
            $query->where('id', '!=', $piketId);
        }

        $query->where('name', '!=', 'Guru Piket')
            ->where(function ($q) {
                $q->whereNull('username')->orWhere('username', '!=', 'gurupiket');
            })
            ->where(function ($q) {
                $q->whereNull('email')->orWhere('email', '!=', 'gurupiket@smp.com');
            });

        if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
            $query->where(function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id)
                  ->orWhere('institution_id', 1)
                  ->orWhereNull('institution_id');
            });
        }

        if ($excludedTeacherId) {
            $query->where('id', '!=', $excludedTeacherId);
        }

        $teachers = $query->orderBy('name')->get();

        $options = [];

        // 1. Opsi GURU PIKET selalu berada paling atas (Hanya 'Guru Piket', tanpa nama guru)
        if ($piketId && $piketId != $excludedTeacherId) {
            $options[$piketId] = 'Guru Piket';
        }

        // 2. Daftar guru-guru pengganti (riil) yang mendapatkan jam inval sesuai JP
        foreach ($teachers as $t) {
            if ($piketId && $t->id == $piketId) {
                continue;
            }
            $options[$t->id] = $t->name;
        }

        return $options;
    }
}
