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
        Schema::table('student_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('student_achievements', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            }

            $table->string('kategori')->default('non_akademik')->change();

            if (!Schema::hasColumn('student_achievements', 'sub_kategori')) {
                $table->string('sub_kategori')->nullable()->after('kategori');
            }
            if (!Schema::hasColumn('student_achievements', 'lingkup')) {
                $table->string('lingkup')->default('individual')->after('sub_kategori');
            }
            if (!Schema::hasColumn('student_achievements', 'classroom_id')) {
                $table->foreignId('classroom_id')->nullable()->after('student_id')->constrained('classrooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('student_achievements', 'jenis_reward')) {
                $table->string('jenis_reward')->nullable()->after('tingkat');
            }
            if (!Schema::hasColumn('student_achievements', 'reward_detail')) {
                $table->text('reward_detail')->nullable()->after('jenis_reward');
            }
            $table->foreignId('student_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_achievements', function (Blueprint $table) {
            if (Schema::hasColumn('student_achievements', 'reward_detail')) {
                $table->dropColumn('reward_detail');
            }
            if (Schema::hasColumn('student_achievements', 'jenis_reward')) {
                $table->dropColumn('jenis_reward');
            }
            if (Schema::hasColumn('student_achievements', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
                $table->dropColumn('classroom_id');
            }
            if (Schema::hasColumn('student_achievements', 'lingkup')) {
                $table->dropColumn('lingkup');
            }
            if (Schema::hasColumn('student_achievements', 'sub_kategori')) {
                $table->dropColumn('sub_kategori');
            }
            if (Schema::hasColumn('student_achievements', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            }
        });
    }
};
