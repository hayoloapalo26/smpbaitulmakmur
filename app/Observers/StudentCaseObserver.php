<?php

namespace App\Observers;

use App\Models\StudentCase;
use App\Models\BkReport;

class StudentCaseObserver
{
    public function created(StudentCase $studentCase): void
    {
        if ($studentCase->recommended_to_bk) {
            BkReport::create([
                'student_id' => $studentCase->student_id,
                'student_case_id' => $studentCase->id,
                'tanggal' => now(),
                'isi_laporan' => '',
                'bk_teacher_id' => \App\Models\User::role('bk')->first()->id ?? 1,
            ]);
        }
    }

    public function updated(StudentCase $studentCase): void
    {
        if ($studentCase->isDirty('recommended_to_bk') && $studentCase->recommended_to_bk) {
            $existing = BkReport::where('student_case_id', $studentCase->id)->first();
            if (!$existing) {
                BkReport::create([
                    'student_id' => $studentCase->student_id,
                    'student_case_id' => $studentCase->id,
                    'tanggal' => now(),
                    'isi_laporan' => '',
                    'bk_teacher_id' => \App\Models\User::role('bk')->first()->id ?? 1,
                ]);
            }
        }
    }
}
