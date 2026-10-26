<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSaving extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'saldo_awal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
    ];

    public const TIPE_SETOR = 'setor';
    public const TIPE_TARIK = 'tarik';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->kode_transaksi)) {
                $dateStr = $model->tanggal ? \Carbon\Carbon::parse($model->tanggal)->format('Ymd') : date('Ymd');
                $countToday = static::whereDate('created_at', today())->count() + 1;
                $model->kode_transaksi = 'TBG-' . $dateStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);
            }

            // Automatically compute saldo_awal and saldo_akhir if not set manually
            if ($model->student_id && (!isset($model->saldo_akhir) || $model->saldo_akhir == 0)) {
                $latestBalance = static::getLatestBalance($model->student_id);
                $model->saldo_awal = $latestBalance;
                if ($model->tipe === self::TIPE_TARIK) {
                    $model->saldo_akhir = max(0, $latestBalance - (float)$model->nominal);
                } else {
                    $model->saldo_akhir = $latestBalance + (float)$model->nominal;
                }
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id')->withoutGlobalScopes();
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id')->withoutGlobalScopes();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public static function getLatestBalance(int $studentId): float
    {
        $last = static::where('student_id', $studentId)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $last ? (float)$last->saldo_akhir : 0.0;
    }
}
