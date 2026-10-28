<?php

namespace App\Observers;

use App\Models\ParentSummon;
use App\Models\StudentCase;

class ParentSummonObserver
{
    public function created(ParentSummon $parentSummon): void
    {
        // Jika terhubung ke kasus siswa, otomatis catatkan penerbitan surat ke kasus tersebut
        if ($parentSummon->student_case_id) {
            $case = StudentCase::find($parentSummon->student_case_id);
            if ($case) {
                $tgl = $parentSummon->tanggal_pertemuan
                    ? \Carbon\Carbon::parse($parentSummon->tanggal_pertemuan)->translatedFormat('d M Y')
                    : '-';
                $noSurat = $parentSummon->nomor_surat ?? 'SP-' . $parentSummon->id;
                $logMsg = "\n[SURAT PANGGILAN ORANG TUA DITERBITKAN]\n"
                    . "Nomor Surat: {$noSurat}\n"
                    . "Jadwal Menghadap: {$tgl} di {$parentSummon->tempat_pertemuan}\n"
                    . "Keperluan: {$parentSummon->kasus}";

                // Tambahkan ke catatan level penanganan aktif
                if ($case->ditangani_oleh === 'bk') {
                    $case->update([
                        'catatan_bk' => trim(($case->catatan_bk ?? '') . "\n" . $logMsg),
                    ]);
                } else {
                    $case->update([
                        'catatan_wali_kelas' => trim(($case->catatan_wali_kelas ?? '') . "\n" . $logMsg),
                    ]);
                }
            }
        }
    }

    public function updated(ParentSummon $parentSummon): void
    {
        // Jika status kehadiran orang tua diubah atau hasil pertemuan diisi
        if ($parentSummon->student_case_id && ($parentSummon->isDirty('status') || $parentSummon->isDirty('hasil_pertemuan'))) {
            $case = StudentCase::find($parentSummon->student_case_id);
            if ($case && $parentSummon->status === 'hadir' && !empty($parentSummon->hasil_pertemuan)) {
                $tgl = now()->translatedFormat('d M Y');
                $logMsg = "\n[HASIL PERTEMUAN ORANG TUA ({$tgl})]\n"
                    . "Orang tua/wali siswa telah hadir memenuhi panggilan (" . ($parentSummon->nomor_surat ?? '-') . ").\n"
                    . "Hasil Kesepakatan: {$parentSummon->hasil_pertemuan}";

                if ($case->ditangani_oleh === 'bk') {
                    $case->update([
                        'catatan_bk' => trim(($case->catatan_bk ?? '') . "\n" . $logMsg),
                    ]);
                } else {
                    $case->update([
                        'catatan_wali_kelas' => trim(($case->catatan_wali_kelas ?? '') . "\n" . $logMsg),
                    ]);
                }
            }
        }
    }
}
