<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    const KATEGORI_NON_AKADEMIK = 'non_akademik';
    const KATEGORI_PRESTASI_KELAS = 'prestasi_kelas';
    const KATEGORI_AKADEMIK = 'akademik';

    const SUB_RELIGIUS = 'religius';
    const SUB_KARAKTER = 'karakter';
    const SUB_ADAB = 'adab';

    const LINGKUP_INDIVIDUAL = 'individual';
    const LINGKUP_KELAS = 'kelas';

    const REWARD_AKADEMIK = 'reward_akademik';
    const REWARD_RELIGI = 'reward_religi';
    const REWARD_NONE = 'tanpa_reward';

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                if ($student && !empty($student->institution_id)) {
                    $model->institution_id = $student->institution_id;
                }
                if ($student && empty($model->classroom_id) && !empty($student->classroom_id)) {
                    $model->classroom_id = $student->classroom_id;
                }
            } elseif (!empty($model->classroom_id)) {
                $classroom = Classroom::withoutGlobalScopes()->find($model->classroom_id);
                if ($classroom && !empty($classroom->institution_id)) {
                    $model->institution_id = $classroom->institution_id;
                }
            }
            if (empty($model->input_by) && auth()->check()) {
                $model->input_by = auth()->id();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'prestasi_non_akademik', 'non_akademik' => 'Prestasi Non-Akademik',
            'prestasi_kelas' => 'Prestasi Kelas',
            'prestasi_akademik', 'akademik' => 'Prestasi Akademik',
            'religius' => 'Prestasi Religius (Keagamaan)',
            default => ucwords(str_replace('_', ' ', $this->kategori ?? 'Non-Akademik')),
        };
    }

    public function getSubKategoriLabelAttribute(): ?string
    {
        return match ($this->sub_kategori) {
            'prestasi_religius', 'religius' => 'Prestasi Religius',
            'prestasi_karakter', 'karakter' => 'Prestasi Karakter',
            'prestasi_adab', 'adab' => 'Prestasi Adab',
            default => $this->sub_kategori ? ucwords(str_replace('_', ' ', $this->sub_kategori)) : null,
        };
    }

    public function getLingkupLabelAttribute(): string
    {
        return match ($this->lingkup) {
            'kelas' => 'Prestasi Kelas (Kelompok)',
            default => 'Individual (Perorangan)',
        };
    }

    public function getRewardLabelAttribute(): ?string
    {
        return match ($this->jenis_reward) {
            'reward_akademik' => 'Reward Akademik',
            'reward_religi' => 'Reward Religi',
            'tanpa_reward' => 'Tanpa Reward Khusus',
            default => $this->jenis_reward ? ucwords(str_replace('_', ' ', $this->jenis_reward)) : null,
        };
    }
}
