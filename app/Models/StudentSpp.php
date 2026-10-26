<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class StudentSpp extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'nominal' => 'decimal:2',
    ];

    public const STATUS_LUNAS = 'lunas';
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const BULAN_LIST = [
        'Juli' => 'Juli',
        'Agustus' => 'Agustus',
        'September' => 'September',
        'Oktober' => 'Oktober',
        'November' => 'November',
        'Desember' => 'Desember',
        'Januari' => 'Januari',
        'Februari' => 'Februari',
        'Maret' => 'Maret',
        'April' => 'April',
        'Mei' => 'Mei',
        'Juni' => 'Juni',
    ];

    public const METODE_PEMBAYARAN = [
        'Tunai' => 'Tunai',
        'Transfer Bank' => 'Transfer Bank',
        'QRIS / Dompet Digital' => 'QRIS / Dompet Digital',
        'Potongan Beasiswa' => 'Potongan Beasiswa',
        'Lainnya' => 'Lainnya',
    ];

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

    public function isLunas(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }
}
