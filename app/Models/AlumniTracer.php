<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniTracer extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                if ($student && !empty($student->institution_id)) {
                    $model->institution_id = $student->institution_id;
                }
                if ($student) {
                    if (empty($model->nis)) $model->nis = $student->nis;
                    if (empty($model->nisn)) $model->nisn = $student->nisn;
                    if (empty($model->nama)) $model->nama = $student->name;
                    if (empty($model->no_telepon)) {
                        $model->no_telepon = $student->no_telepon_siswa ?? $student->no_telepon_orang_tua ?? $student->no_hp_ayah ?? $student->no_hp_ibu;
                    }
                    if (empty($model->alamat_domisili) && !empty($student->alamat_siswa)) {
                        $model->alamat_domisili = $student->alamat_siswa;
                    }
                }
            }
            if (empty($model->status_kegiatan)) {
                $model->status_kegiatan = 'belum_terlacak';
            }
            if (empty($model->input_by) && auth()->check()) {
                $model->input_by = auth()->id();
            }
            $model->data_pelacakan = json_encode([
                'status' => $model->status_kegiatan,
                'instansi' => $model->nama_instansi,
                'jurusan' => $model->jurusan_bidang,
            ]);
        });

        static::saved(function ($model) {
            if (!empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                if ($student && ($student->status_siswa !== 'alumni' || empty($student->tahun_lulus))) {
                    $student->update([
                        'status_siswa' => 'alumni',
                        'tahun_lulus' => $model->tahun_lulus ?? $student->tahun_lulus ?? date('Y'),
                    ]);
                }
            }
        });

        static::deleted(function ($model) {
            if (!empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                if ($student && $student->status_siswa === 'alumni') {
                    $student->update(['status_siswa' => 'aktif']);
                }
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_kegiatan) {
            'melanjutkan_pendidikan', 'kuliah' => 'Melanjutkan Pendidikan',
            'bekerja' => 'Bekerja',
            'wirausaha' => 'Wirausaha',
            'mencari_kerja' => 'Mencari Kerja / Menunggu',
            'belum_terlacak' => 'Belum Terlacak (Perlu Diisi)',
            default => ucwords(str_replace('_', ' ', $this->status_kegiatan ?? 'Belum Terlacak')),
        };
    }
}

