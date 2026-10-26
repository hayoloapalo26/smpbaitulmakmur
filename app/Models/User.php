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

#[Fillable(['name', 'email', 'password', 'avatar_url', 'username', 'is_active', 'institution_id', 'has_account'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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

    public function certificates()
    {
        return $this->hasMany(TeacherCertificate::class, 'teacher_id');
    }

    /**
     * Memastikan user akun khusus "Guru Piket" tersedia di sistem.
     */
    public static function ensureGuruPiketUser(?int $institutionId = 2): ?self
    {
        try {
            // 1. Cek berdasarkan username khusus gurupiket atau email gurupiket@smp.com
            $user = static::withoutGlobalScopes()
                ->where('username', 'gurupiket')
                ->orWhere('email', 'gurupiket@smp.com')
                ->orWhere(function ($q) {
                    $q->where('name', 'Guru Piket')
                      ->where('email', 'like', '%@smp.com');
                })
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
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', [
                    'guru_mapel',
                    'guru_alquran',
                    'wali_kelas',
                    'guru_kelas',
                    'waka_kurikulum',
                ]))
                ->orWhereHas('schedules');
            })
            ->where('name', '!=', 'Guru Piket')
            ->where('username', '!=', 'gurupiket')
            ->where('email', 'not like', '%@smp.com');

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
