<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class OptionalTask extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'created_by',
        'title',
        'description',
        'target_type',
        'target_roles',
        'target_user_ids',
        'due_date',
        'form_schema',
        'reward_points',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'due_date' => 'date',
        'form_schema' => 'array',
        'is_active' => 'boolean',
        'reward_points' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(OptionalTaskSubmission::class);
    }

    /**
     * Cek apakah tugas insidentil ini ditujukan untuk user yang sedang aktif.
     */
    public function isAssignedToUser(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Jika institusi berbeda, tolak
        if ($user->institution_id && $this->institution_id && $user->institution_id != $this->institution_id) {
            return false;
        }

        // 1. Target: Seluruh Warga Lembaga
        if ($this->target_type === 'all') {
            return true;
        }

        // 2. Target: Personel / User Tertentu
        if ($this->target_type === 'users') {
            $userIds = is_array($this->target_user_ids) ? $this->target_user_ids : [];
            return in_array($user->id, $userIds);
        }

        // 3. Target: Divisi / Role Tertentu (default)
        $targetRoles = is_array($this->target_roles) ? $this->target_roles : [];
        if (in_array('all', $targetRoles)) {
            return true;
        }

        $userRoles = $user->roles->pluck('name')->toArray();
        if (in_array('wali_kelas', $userRoles) && !in_array('guru_kelas', $userRoles)) {
            $userRoles[] = 'guru_kelas';
        }
        if (in_array('guru_kelas', $userRoles) && !in_array('wali_kelas', $userRoles)) {
            $userRoles[] = 'wali_kelas';
        }

        return !empty(array_intersect($targetRoles, $userRoles));
    }
}
