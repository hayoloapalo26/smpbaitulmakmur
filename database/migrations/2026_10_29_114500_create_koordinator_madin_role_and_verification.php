<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Spatie Role koordinator_madin
        $role = Role::firstOrCreate(['name' => 'koordinator_madin', 'guard_name' => 'web']);

        // Assign otomatis ke user Koordinator Madin jika sudah ada di database
        $madinUsers = User::where('jabatan', 'like', '%koordinator madin%')
            ->orWhere('jabatan', 'like', '%madin%')
            ->orWhere('email', 'elly.anasrul@smpi.com')
            ->get();

        foreach ($madinUsers as $u) {
            if (!$u->hasRole('koordinator_madin')) {
                $u->assignRole('koordinator_madin');
            }
        }

        // 2. Tambah kolom verifikasi pada quran_achievements
        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (!Schema::hasColumn('quran_achievements', 'status_verifikasi')) {
                    $table->string('status_verifikasi')->default('Menunggu Verifikasi')->after('tanggal');
                }
                if (!Schema::hasColumn('quran_achievements', 'catatan_koordinator')) {
                    $table->text('catatan_koordinator')->nullable()->after('status_verifikasi');
                }
                if (!Schema::hasColumn('quran_achievements', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('catatan_koordinator');
                }
                if (!Schema::hasColumn('quran_achievements', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 3. Tambah kolom verifikasi pada reading_quality_scores
        if (Schema::hasTable('reading_quality_scores')) {
            Schema::table('reading_quality_scores', function (Blueprint $table) {
                if (!Schema::hasColumn('reading_quality_scores', 'status_verifikasi')) {
                    $table->string('status_verifikasi')->default('Menunggu Verifikasi')->after('tanggal');
                }
                if (!Schema::hasColumn('reading_quality_scores', 'catatan_koordinator')) {
                    $table->text('catatan_koordinator')->nullable()->after('status_verifikasi');
                }
                if (!Schema::hasColumn('reading_quality_scores', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('catatan_koordinator');
                }
                if (!Schema::hasColumn('reading_quality_scores', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        // 4. Tambah kolom verifikasi pada grades
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'status_verifikasi')) {
                    $table->string('status_verifikasi')->default('Menunggu Verifikasi')->after('catatan_guru');
                }
                if (!Schema::hasColumn('grades', 'catatan_koordinator')) {
                    $table->text('catatan_koordinator')->nullable()->after('status_verifikasi');
                }
                if (!Schema::hasColumn('grades', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('catatan_koordinator');
                }
                if (!Schema::hasColumn('grades', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (Schema::hasColumn('grades', 'verified_by')) {
                    $table->dropConstrainedForeignId('verified_by');
                }
                if (Schema::hasColumn('grades', 'verified_at')) {
                    $table->dropColumn('verified_at');
                }
                if (Schema::hasColumn('grades', 'catatan_koordinator')) {
                    $table->dropColumn('catatan_koordinator');
                }
                if (Schema::hasColumn('grades', 'status_verifikasi')) {
                    $table->dropColumn('status_verifikasi');
                }
            });
        }

        if (Schema::hasTable('reading_quality_scores')) {
            Schema::table('reading_quality_scores', function (Blueprint $table) {
                if (Schema::hasColumn('reading_quality_scores', 'verified_by')) {
                    $table->dropConstrainedForeignId('verified_by');
                }
                if (Schema::hasColumn('reading_quality_scores', 'verified_at')) {
                    $table->dropColumn('verified_at');
                }
                if (Schema::hasColumn('reading_quality_scores', 'catatan_koordinator')) {
                    $table->dropColumn('catatan_koordinator');
                }
                if (Schema::hasColumn('reading_quality_scores', 'status_verifikasi')) {
                    $table->dropColumn('status_verifikasi');
                }
            });
        }

        if (Schema::hasTable('quran_achievements')) {
            Schema::table('quran_achievements', function (Blueprint $table) {
                if (Schema::hasColumn('quran_achievements', 'verified_by')) {
                    $table->dropConstrainedForeignId('verified_by');
                }
                if (Schema::hasColumn('quran_achievements', 'verified_at')) {
                    $table->dropColumn('verified_at');
                }
                if (Schema::hasColumn('quran_achievements', 'catatan_koordinator')) {
                    $table->dropColumn('catatan_koordinator');
                }
                if (Schema::hasColumn('quran_achievements', 'status_verifikasi')) {
                    $table->dropColumn('status_verifikasi');
                }
            });
        }
    }
};
