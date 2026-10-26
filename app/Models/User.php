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
            $user = static::withoutGlobalScopes()
                ->where('name', 'Guru Piket')
                ->orWhere('username', 'gurupiket')
                ->first();

            if (!$user) {
                $user = static::withoutGlobalScopes()
                    ->where('email', 'gurusiti@smp.com')
                    ->first();

                $instId = $institutionId;
                if ($instId && !\App\Models\Institution::where('id', $instId)->exists()) {
                    $instId = \App\Models\Institution::first()?->id;
                }

                if ($user) {
                    $user->update([
                        'name' => 'Guru Piket',
                        'username' => 'gurupiket',
                        'email' => 'gurupiket@smp.com',
                    ]);
                } else {
                    $user = static::withoutGlobalScopes()->create([
                        'name' => 'Guru Piket',
                        'username' => 'gurupiket',
                        'email' => 'gurupiket@smp.com',
                        'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                        'institution_id' => $instId,
                        'is_active' => true,
                    ]);
                }
            }

            if ($user && method_exists($user, 'hasRole') && !$user->hasRole('guru_piket')) {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_piket', 'guard_name' => 'web']);
                $user->assignRole($role);
            }

            return $user;
        } catch (\Throwable $e) {
            return static::withoutGlobalScopes()->where('name', 'Guru Piket')->first();
        }
    }
}
