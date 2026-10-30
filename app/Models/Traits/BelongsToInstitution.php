<?php

namespace App\Models\Traits;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToInstitution
{
    public static function bootBelongsToInstitution()
    {
        // Global Scope for Reading Data: each lembaga only sees its own institution_id
        static::addGlobalScope('institution', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // If user is Superadmin, no filter applied
                if ($user->hasRole('superadmin')) {
                    return;
                }

                $userInstitutionIds = $user->institution_ids;
                if (in_array(1, $userInstitutionIds) && ($user->hasRole('yayasan') || $user->hasRole('ketua_yayasan') || $user->hasRole('sekretaris_yayasan') || $user->hasRole('pembina_yayasan'))) {
                    // Yayasan murni / di portal yayasan dapat memantau seluruh lembaga
                    try {
                        $currentPanelId = \Filament\Facades\Filament::getCurrentPanel()?->getId();
                        if (!$currentPanelId || $currentPanelId === 'yayasan') {
                            return;
                        }
                    } catch (\Throwable $e) {
                        return;
                    }
                }

                $table = $builder->getModel()->getTable();
                if (!\Illuminate\Support\Facades\Schema::hasColumn($table, 'institution_id')) {
                    return;
                }

                $builder->where(function ($q) use ($table, $user, $userInstitutionIds) {
                    if (!empty($userInstitutionIds)) {
                        $q->whereIn($table . '.institution_id', $userInstitutionIds)
                          ->orWhereNull($table . '.institution_id');
                    } else {
                        $q->where($table . '.institution_id', $user->institution_id)
                          ->orWhereNull($table . '.institution_id');
                    }

                    // Khusus tabel students: izinkan staf SMP (institution_id = 2) melihat siswa jika sempat tercatat di ID 1 (Yayasan)
                    if ($table === 'students' && in_array(2, $userInstitutionIds)) {
                        $q->orWhere($table . '.institution_id', 1);
                    }

                    // Dukungan Guru Lintas Lembaga (Cross-Institution Teaching):
                    // 1. Jika record memiliki teacher_id atau guru_id (jadwal, jurnal, presensi, supervisi)
                    if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'teacher_id')) {
                        $q->orWhere($table . '.teacher_id', $user->id);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'guru_id')) {
                        $q->orWhere($table . '.guru_id', $user->id);
                    }

                    // 2. Jika tabel classrooms atau subjects:
                    // Izinkan guru mengakses kelas atau mapel lintas lembaga yang dijadwalkan untuknya
                    if ($table === 'classrooms') {
                        $q->orWhereIn($table . '.id', function ($sub) use ($user) {
                            $sub->select('classroom_id')
                                ->from('schedules')
                                ->where('teacher_id', $user->id);
                        });
                    }
                    if ($table === 'subjects') {
                        $q->orWhereIn($table . '.id', function ($sub) use ($user) {
                            $sub->select('subject_id')
                                ->from('schedules')
                                ->where('teacher_id', $user->id);
                        });
                    }
                });
            }
        });

        // Event for Creating & Saving Data: always ensure records match institution_id
        static::saving(function ($model) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'institution_id')) {
                return;
            }

            // Khusus data Siswa: Siswa tidak pernah berada di Yayasan (ID = 1).
            // Jika kosong, null, atau 1, otomatis arahkan ke unit SMP (ID = 2).
            if ($model instanceof \App\Models\Student) {
                if (empty($model->institution_id) || $model->institution_id == 1) {
                    $model->institution_id = 2;
                }
                return;
            }

            // 1. Prioritaskan turunan dari student_id (nilai, presensi, capaian quran, dll.)
            // Memastikan jika Ustadz Romli (Yayasan ID = 1) menginput nilai quran santri SMP,
            // nilai quran tersebut masuk ke rumahnya (SMP ID = 2), bukan Yayasan.
            if (!empty($model->student_id)) {
                $studentInstId = \App\Models\Student::withoutGlobalScopes()
                    ->where('id', $model->student_id)
                    ->value('institution_id');
                if ($studentInstId) {
                    $model->institution_id = $studentInstId;
                    return;
                }
            }

            // 2. Turunan dari classroom_id (misal jika bernilai 1 atau belum terisi)
            if (!empty($model->classroom_id)) {
                $classroomInstId = \App\Models\Classroom::withoutGlobalScopes()
                    ->where('id', $model->classroom_id)
                    ->value('institution_id');
                if ($classroomInstId) {
                    $model->institution_id = $classroomInstId;
                    return;
                }
            }

            // 3. Turunan dari schedule_id (misal: Jurnal Mengajar)
            if (!empty($model->schedule_id)) {
                $scheduleInstId = \App\Models\Schedule::withoutGlobalScopes()
                    ->where('id', $model->schedule_id)
                    ->value('institution_id');
                if ($scheduleInstId) {
                    $model->institution_id = $scheduleInstId;
                    return;
                }
            }

            // 4. Turunan dari subject_id
            if (!empty($model->subject_id)) {
                $subjectInstId = \App\Models\Subject::withoutGlobalScopes()
                    ->where('id', $model->subject_id)
                    ->value('institution_id');
                if ($subjectInstId) {
                    $model->institution_id = $subjectInstId;
                    return;
                }
            }

            // 5. Fallback: jika masih kosong atau jika bernilai Yayasan (1) pada record sekolah
            if (empty($model->institution_id) || $model->institution_id == 1) {
                if (auth()->check()) {
                    $user = auth()->user();
                    $nonYayasanId = collect($user->institution_ids)->first(fn ($id) => $id != 1);
                    if ($nonYayasanId) {
                        $model->institution_id = $nonYayasanId;
                        return;
                    }
                    if (!empty($user->institution_id)) {
                        $model->institution_id = $user->institution_id;
                    }
                }
            }
        });
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
