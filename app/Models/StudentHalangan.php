<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentHalangan extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Apakah siswi sedang halangan pada tanggal tertentu?
     */
    public static function isHalangan(int $studentId, $tanggal): bool
    {
        return static::where('student_id', $studentId)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', $tanggal);
            })
            ->exists();
    }
}
