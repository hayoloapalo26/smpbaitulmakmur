<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentViolationLog extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'      => 'date',
        'poin'         => 'integer',
        'acc_piket'    => 'boolean',
        'acc_piket_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->classroom_id) && !empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                $model->classroom_id = $student?->classroom_id;
            }

            if (empty($model->institution_id) && !empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                $model->institution_id = $student?->institution_id;
            }
        });

        // Hubungkan ke CharacterScoreLog agar kedisiplinan dan poin karakter siswa terintegrasi secara konsisten
        static::created(function (self $model) {
            try {
                CharacterScoreLog::create([
                    'student_id'   => $model->student_id,
                    'source'       => 'guru_piket',
                    'category'     => 'kedisiplinan',
                    'description'  => "Pelanggaran: {$model->bentuk_pelanggaran} (Poin: {$model->poin})" . ($model->tindak_lanjut_langsung ? " | Tindak Lanjut: {$model->tindak_lanjut_langsung}" : ''),
                    'point_change' => -abs($model->poin),
                    'tanggal'      => $model->tanggal,
                    'submitted_by' => $model->teacher_id,
                    'institution_id' => $model->institution_id,
                ]);
            } catch (\Throwable $e) {
                // Abaikan jika tidak tersedia kolom khusus, jangan sampai menghentikan penyimpanan log pelanggaran utama
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function accPiketUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_piket_by');
    }

    /**
     * Hitung Predikat Nilai Sikap berdasarkan rentang indikator resmi:
     * A   : 0 - 5 Poin
     * A-  : 6 - 15 Poin
     * B+  : 16 - 30 Poin
     * B   : 31 - 45 Poin
     * B-  : 46 - 60 Poin
     * C+  : 61 - 85 Poin
     * C   : 86 - 105 Poin
     * C-  : 106 - 120 Poin
     * D   : 121 - 140 Poin
     * E   : 141 - 150 Poin (dan >150 Poin)
     */
    public static function getPredikatNilaiSikap(int $poin): array
    {
        $poin = max(0, $poin);

        return match (true) {
            $poin <= 5   => ['predikat' => 'A',  'label' => 'Sangat Baik (A)',              'color' => 'success', 'desc' => 'Sangat disiplin dan berakhlak mulia'],
            $poin <= 15  => ['predikat' => 'A-', 'label' => 'Baik Sekali (A-)',             'color' => 'success', 'desc' => 'Kedisiplinan sangat baik dengan pelanggaran minimal'],
            $poin <= 30  => ['predikat' => 'B+', 'label' => 'Baik Plus (B+)',              'color' => 'primary', 'desc' => 'Sikap baik dan taat tata tertib'],
            $poin <= 45  => ['predikat' => 'B',  'label' => 'Baik (B)',                    'color' => 'info',    'desc' => 'Kedisiplinan cukup konsisten'],
            $poin <= 60  => ['predikat' => 'B-', 'label' => 'Cukup Baik (B-)',             'color' => 'info',    'desc' => 'Perlu sedikit pengingat tata tertib'],
            $poin <= 85  => ['predikat' => 'C+', 'label' => 'Cukup Plus (C+)',              'color' => 'warning', 'desc' => 'Mulai sering melanggar, perlu perhatian wali kelas'],
            $poin <= 105 => ['predikat' => 'C',  'label' => 'Cukup (C)',                   'color' => 'warning', 'desc' => 'Kedisiplinan kurang, perlu pembinaan awal BK'],
            $poin <= 120 => ['predikat' => 'C-', 'label' => 'Kurang Cukup (C-)',           'color' => 'warning', 'desc' => 'Perlu pembinaan intensif Wali Kelas & BK'],
            $poin <= 140 => ['predikat' => 'D',  'label' => 'Kurang / Perlu Perhatian (D)', 'color' => 'danger',  'desc' => 'Pelanggaran berat/berulang, perlu pemanggilan ortu'],
            default      => ['predikat' => 'E',  'label' => 'Sangat Kurang (E)',            'color' => 'danger',  'desc' => 'Perlu penanganan khusus Kesiswaan & Kepala Sekolah'],
        };
    }
}
