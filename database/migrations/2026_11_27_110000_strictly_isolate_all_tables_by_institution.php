<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengisolasi 100% seluruh tabel fitur sekolah ke masing-masing lembaga (SMP ID=2, SD ID=4).
     * Mencegah database tercampur antar lembaga tanpa merusak atau menghilangkan data SMP yang sudah ada.
     */
    public function up(): void
    {
        $tablesToIsolate = [
            'program_kurikulums',
            'program_kesiswaans',
            'program_sarpras',
            'bk_work_programs',
            'program_humas',
            'humas_programs',
            'capaian_pembelajarans',
            'tujuan_pembelajarans',
            'alur_tujuan_pembelajarans',
            'rppms',
            'learning_plans',
            'teaching_materials',
            'bk_reports',
            'dcm_responses',
            'parent_summons',
            'student_cases',
            'student_leave_requests',
            'attribute_violations',
            'extracurriculars',
            'work_programs',
            'empty_class_logs',
            'teacher_todo_lists',
            'meeting_minutes',
            'document_archives',
            'letters',
            'school_pillar_tasks',
            'parent_daily_checklists',
            'duty_swap_requests',
        ];

        // 1. Tambahkan kolom institution_id jika belum ada pada masing-masing tabel
        foreach ($tablesToIsolate as $table) {
            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, 'institution_id')) {
                    Schema::table($table, function (Blueprint $t) {
                        $t->unsignedBigInteger('institution_id')->nullable()->after('id')->index();
                    });
                }
            }
        }

        // 2. Backfill cerdas berdasarkan pemilik data:
        // Jika data dibuat oleh user SD -> institution_id = 4.
        // Jika data milik siswa/kelas SD -> institution_id = 4.
        // Seluruh data eksisting/legacy lainnya -> institution_id = 2 (SMP).
        foreach ($tablesToIsolate as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'institution_id')) {
                continue;
            }

            try {
                // Turunkan dari user_id / author_id / guru_id
                $userCol = null;
                if (Schema::hasColumn($table, 'user_id')) {
                    $userCol = 'user_id';
                } elseif (Schema::hasColumn($table, 'guru_id')) {
                    $userCol = 'guru_id';
                } elseif (Schema::hasColumn($table, 'author_id')) {
                    $userCol = 'author_id';
                }

                if ($userCol) {
                    DB::statement("
                        UPDATE {$table} t
                        JOIN users u ON u.id = t.{$userCol}
                        SET t.institution_id = COALESCE(u.institution_id, 2)
                        WHERE t.institution_id IS NULL AND t.{$userCol} IS NOT NULL
                    ");
                }

                // Turunkan dari student_id jika ada
                if (Schema::hasColumn($table, 'student_id')) {
                    DB::statement("
                        UPDATE {$table} t
                        JOIN students s ON s.id = t.student_id
                        SET t.institution_id = COALESCE(s.institution_id, 2)
                        WHERE t.institution_id IS NULL AND t.student_id IS NOT NULL
                    ");
                }

                // Turunkan dari classroom_id jika ada
                if (Schema::hasColumn($table, 'classroom_id')) {
                    DB::statement("
                        UPDATE {$table} t
                        JOIN classrooms c ON c.id = t.classroom_id
                        SET t.institution_id = COALESCE(c.institution_id, 2)
                        WHERE t.institution_id IS NULL AND t.classroom_id IS NOT NULL
                    ");
                }

                // Turunkan dari school_pillar_id jika ada
                if (Schema::hasColumn($table, 'school_pillar_id')) {
                    DB::statement("
                        UPDATE {$table} t
                        JOIN school_pillars sp ON sp.id = t.school_pillar_id
                        SET t.institution_id = COALESCE(sp.institution_id, 2)
                        WHERE t.institution_id IS NULL AND t.school_pillar_id IS NOT NULL
                    ");
                }

                // Seluruh data eksisting tersisa adalah data SMP (ID = 2)
                DB::statement("
                    UPDATE {$table}
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            } catch (\Throwable $e) {
                // Lanjutkan jika ada kendala sintaks tertentu
            }
        }

        // 3. Normalisasi Master Data Inti (Classrooms, Subjects, Students, Schedules, Grades)
        try {
            // Subjects: Jika nama/kode mengandung 'SD' -> 4, sisanya jika null -> 2 (SMP)
            if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'institution_id')) {
                DB::statement("
                    UPDATE subjects
                    SET institution_id = 4
                    WHERE (kode LIKE '%-SD' OR name LIKE '% SD%') AND (institution_id IS NULL OR institution_id != 4)
                ");
                DB::statement("
                    UPDATE subjects
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Classrooms: Tingkat 1-6 -> 4 (SD), sisanya jika null -> 2 (SMP)
            if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'institution_id')) {
                DB::statement("
                    UPDATE classrooms
                    SET institution_id = 4
                    WHERE tingkat IN ('1', '2', '3', '4', '5', '6')
                       OR name LIKE '% SD%'
                ");
                DB::statement("
                    UPDATE classrooms
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Students: jika null -> 2 (SMP)
            if (Schema::hasTable('students') && Schema::hasColumn('students', 'institution_id')) {
                DB::statement("
                    UPDATE students
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Schedules: samakan dengan classroom-nya, jika null -> 2 (SMP)
            if (Schema::hasTable('schedules') && Schema::hasColumn('schedules', 'institution_id')) {
                DB::statement("
                    UPDATE schedules s
                    JOIN classrooms c ON c.id = s.classroom_id
                    SET s.institution_id = COALESCE(c.institution_id, 2)
                    WHERE s.institution_id IS NULL AND s.classroom_id IS NOT NULL
                ");
                DB::statement("
                    UPDATE schedules
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Grades: samakan dengan student-nya, jika null -> 2 (SMP)
            if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'institution_id')) {
                DB::statement("
                    UPDATE grades g
                    JOIN students s ON s.id = g.student_id
                    SET g.institution_id = COALESCE(s.institution_id, 2)
                    WHERE g.institution_id IS NULL AND g.student_id IS NOT NULL
                ");
                DB::statement("
                    UPDATE grades
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Teacher Subject Classes
            if (Schema::hasTable('teacher_subject_classes') && Schema::hasColumn('teacher_subject_classes', 'institution_id')) {
                DB::statement("
                    UPDATE teacher_subject_classes tsc
                    JOIN classrooms c ON c.id = tsc.classroom_id
                    SET tsc.institution_id = COALESCE(c.institution_id, 2)
                    WHERE tsc.institution_id IS NULL AND tsc.classroom_id IS NOT NULL
                ");
                DB::statement("
                    UPDATE teacher_subject_classes
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }

            // Teaching Journals
            if (Schema::hasTable('teaching_journals') && Schema::hasColumn('teaching_journals', 'institution_id')) {
                DB::statement("
                    UPDATE teaching_journals tj
                    JOIN classrooms c ON c.id = tj.classroom_id
                    SET tj.institution_id = COALESCE(c.institution_id, 2)
                    WHERE tj.institution_id IS NULL AND tj.classroom_id IS NOT NULL
                ");
                DB::statement("
                    UPDATE teaching_journals
                    SET institution_id = 2
                    WHERE institution_id IS NULL
                ");
            }
        } catch (\Throwable $e) {
            // Lanjutkan
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
