<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherCertificate extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'jumlah_jam' => 'integer',
    ];

    public static function getKategoriOptions(): array
    {
        return [
            'Pelatihan / Bimtek' => 'Pelatihan / Bimtek',
            'Workshop / Lokakarya' => 'Workshop / Lokakarya',
            'Webinar / Seminar' => 'Webinar / Seminar',
            'Sertifikasi Kompetensi' => 'Sertifikasi Kompetensi / Profesi',
            'Diklat / Kursus' => 'Pendidikan & Pelatihan (Diklat)',
            'Penghargaan / Lomba' => 'Penghargaan / Kejuaraan',
            'Lainnya' => 'Lainnya',
        ];
    }

    public static function getKategoriBadgeColor(string $kategori): string
    {
        return match ($kategori) {
            'Pelatihan / Bimtek' => 'primary',
            'Workshop / Lokakarya' => 'info',
            'Webinar / Seminar' => 'warning',
            'Sertifikasi Kompetensi' => 'success',
            'Diklat / Kursus' => 'purple',
            'Penghargaan / Lomba' => 'danger',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherCertificate $cert) {
            if (empty($cert->teacher_id) && auth()->check()) {
                $cert->teacher_id = auth()->id();
            }

            if (empty($cert->institution_id)) {
                if (!empty($cert->teacher_id)) {
                    $teacherInstId = User::withoutGlobalScopes()
                        ->where('id', $cert->teacher_id)
                        ->value('institution_id');
                    if ($teacherInstId) {
                        $cert->institution_id = $teacherInstId;
                    }
                }

                if (empty($cert->institution_id) && auth()->check() && !empty(auth()->user()->institution_id)) {
                    $cert->institution_id = auth()->user()->institution_id;
                }
            }
        });

        static::saving(function (TeacherCertificate $cert) {
            if (empty($cert->institution_id) && !empty($cert->teacher_id)) {
                $teacherInstId = User::withoutGlobalScopes()
                    ->where('id', $cert->teacher_id)
                    ->value('institution_id');
                if ($teacherInstId) {
                    $cert->institution_id = $teacherInstId;
                }
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
