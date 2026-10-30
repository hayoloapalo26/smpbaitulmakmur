<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherObservation extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_observasi' => 'date',
        'score' => 'float',
        'aspek_penilaian' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'mata_pelajaran_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public static function determinePredicate(float $score): string
    {
        if ($score >= 90) {
            return 'Sangat Baik (A)';
        }
        if ($score >= 80) {
            return 'Baik (B)';
        }
        if ($score >= 70) {
            return 'Cukup (C)';
        }
        return 'Perlu Bimbingan (D)';
    }

    public static function getPredicateColor(float $score): string
    {
        if ($score >= 80) {
            return 'success';
        }
        if ($score >= 70) {
            return 'warning';
        }
        return 'danger';
    }

    public static function getDefaultAspects(): array
    {
        return [
            [
                'aspek' => 'Kegiatan Pendahuluan',
                'deskripsi' => 'Pengondisian kelas, apersepsi, motivasi, dan penyampaian tujuan pembelajaran.',
            ],
            [
                'aspek' => 'Penguasaan Materi & Alur Deep Learning',
                'deskripsi' => 'Penguasaan materi konsep secara mendalam, alur pembelajaran runtut, bermakna, dan menyenangkan.',
            ],
            [
                'aspek' => 'Pelibatan Aktif Siswa & Diferensiasi',
                'deskripsi' => 'Penerapan pembelajaran aktif, pertanyaan pemantik, serta akomodasi kebutuhan gaya/kecepatan belajar siswa.',
            ],
            [
                'aspek' => 'Pengelolaan Kelas & Media Pembelajaran',
                'deskripsi' => 'Pemanfaatan media/TIK interaktif, manajemen interaksi kelas yang positif dan disiplin positif.',
            ],
            [
                'aspek' => 'Kegiatan Penutup & Asesmen',
                'deskripsi' => 'Refleksi bersama siswa, umpan balik konstruktif, asesmen formatif, dan tindak lanjut pembelajaran.',
            ],
        ];
    }

    protected static function booted()
    {
        static::saving(function (TeacherObservation $observation) {
            if ($observation->score !== null && empty($observation->predikat)) {
                $observation->predikat = static::determinePredicate((float) $observation->score);
            }
        });
    }
}
