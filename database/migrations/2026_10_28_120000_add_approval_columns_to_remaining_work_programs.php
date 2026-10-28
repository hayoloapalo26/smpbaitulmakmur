<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Program Kesiswaan
        if (Schema::hasTable('program_kesiswaans')) {
            Schema::table('program_kesiswaans', function (Blueprint $table) {
                if (!Schema::hasColumn('program_kesiswaans', 'status_persetujuan')) {
                    $table->string('status_persetujuan')->default('Menunggu Persetujuan')->after('status_pelaksanaan');
                }
                if (!Schema::hasColumn('program_kesiswaans', 'catatan_kepsek')) {
                    $table->text('catatan_kepsek')->nullable()->after('status_persetujuan');
                }
                if (!Schema::hasColumn('program_kesiswaans', 'tanggal_persetujuan')) {
                    $table->timestamp('tanggal_persetujuan')->nullable()->after('catatan_kepsek');
                }
                if (!Schema::hasColumn('program_kesiswaans', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('tanggal_persetujuan')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 2. BK Work Programs
        if (Schema::hasTable('bk_work_programs')) {
            Schema::table('bk_work_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('bk_work_programs', 'status_persetujuan')) {
                    $table->string('status_persetujuan')->default('Menunggu Persetujuan')->after('status');
                }
                if (!Schema::hasColumn('bk_work_programs', 'catatan_kepsek')) {
                    $table->text('catatan_kepsek')->nullable()->after('status_persetujuan');
                }
                if (!Schema::hasColumn('bk_work_programs', 'tanggal_persetujuan')) {
                    $table->timestamp('tanggal_persetujuan')->nullable()->after('catatan_kepsek');
                }
                if (!Schema::hasColumn('bk_work_programs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('tanggal_persetujuan')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 3. Class Programs (Wali Kelas)
        if (Schema::hasTable('class_programs')) {
            Schema::table('class_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('class_programs', 'status_persetujuan')) {
                    $table->string('status_persetujuan')->default('Menunggu Persetujuan')->after('status');
                }
                if (!Schema::hasColumn('class_programs', 'catatan_kepsek')) {
                    $table->text('catatan_kepsek')->nullable()->after('status_persetujuan');
                }
                if (!Schema::hasColumn('class_programs', 'tanggal_persetujuan')) {
                    $table->timestamp('tanggal_persetujuan')->nullable()->after('catatan_kepsek');
                }
                if (!Schema::hasColumn('class_programs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('tanggal_persetujuan')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 4. OSIS Programs
        if (Schema::hasTable('osis_programs')) {
            Schema::table('osis_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('osis_programs', 'status_persetujuan')) {
                    $table->string('status_persetujuan')->default('Menunggu Persetujuan')->after('status');
                }
                if (!Schema::hasColumn('osis_programs', 'catatan_kepsek')) {
                    $table->text('catatan_kepsek')->nullable()->after('status_persetujuan');
                }
                if (!Schema::hasColumn('osis_programs', 'tanggal_persetujuan')) {
                    $table->timestamp('tanggal_persetujuan')->nullable()->after('catatan_kepsek');
                }
                if (!Schema::hasColumn('osis_programs', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('tanggal_persetujuan')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 5. Program Kurikulum (Pastikan approved_by tersedia)
        if (Schema::hasTable('program_kurikulums')) {
            Schema::table('program_kurikulums', function (Blueprint $table) {
                if (!Schema::hasColumn('program_kurikulums', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('tanggal_persetujuan')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('program_kesiswaans')) {
            Schema::table('program_kesiswaans', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['status_persetujuan', 'catatan_kepsek', 'tanggal_persetujuan', 'approved_by']);
            });
        }

        if (Schema::hasTable('bk_work_programs')) {
            Schema::table('bk_work_programs', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['status_persetujuan', 'catatan_kepsek', 'tanggal_persetujuan', 'approved_by']);
            });
        }

        if (Schema::hasTable('class_programs')) {
            Schema::table('class_programs', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['status_persetujuan', 'catatan_kepsek', 'tanggal_persetujuan', 'approved_by']);
            });
        }

        if (Schema::hasTable('osis_programs')) {
            Schema::table('osis_programs', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['status_persetujuan', 'catatan_kepsek', 'tanggal_persetujuan', 'approved_by']);
            });
        }
    }
};
