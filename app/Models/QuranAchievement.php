<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranAchievement extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => 'array',
        'verified_at' => 'datetime',
        'scale_score' => 'integer',
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saved(function (QuranAchievement $record) {
            // Sinkronisasi otomatis ke reading_quality_scores agar satu pintu penilaian
            // langsung terintegrasi dengan rapor, Madin, dan profil kelulusan
            if ($record->scale_score !== null) {
                \App\Models\ReadingQualityScore::updateOrCreate(
                    [
                        'student_id' => $record->student_id,
                        'teacher_id' => $record->teacher_id,
                        'tanggal'    => $record->tanggal,
                        'program'    => $record->program ?? 'tahsin',
                    ],
                    [
                        'scale_score'   => (int) $record->scale_score,
                        'jilid_halaman' => $record->jilid_halaman,
                        'catatan_guru'  => $record->catatan,
                        'predikat'      => $record->predikat ?? Grade::determineScale($record->scale_score)['predikat'],
                    ]
                );
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getPredikatColorAttribute(): string
    {
        return match ($this->predikat) {
            'Mumtaz'        => 'success',
            'Jayyid Jiddan' => 'info',
            'Jayyid'        => 'warning',
            'Maqbul'        => 'danger',
            default         => 'gray',
        };
    }

    public function getProgramLabelAttribute(): string
    {
        return match ($this->program) {
            'yanbua'           => 'Yanbu\'a',
            'tahsin'           => 'Tahsin',
            'tahfidz_dasar'    => 'Tahfidz Dasar',
            'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
            'tahfidz'          => 'Tahfidz',
            default            => ucfirst(str_replace('_', ' ', (string)($this->program ?? 'Tahfidz'))),
        };
    }
}
