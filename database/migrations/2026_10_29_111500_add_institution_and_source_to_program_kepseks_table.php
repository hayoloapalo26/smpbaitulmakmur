<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_kepseks')) {
            Schema::table('program_kepseks', function (Blueprint $table) {
                if (!Schema::hasColumn('program_kepseks', 'institution_id')) {
                    $table->foreignId('institution_id')->nullable()->after('user_id')->constrained('institutions')->nullOnDelete();
                }
                if (!Schema::hasColumn('program_kepseks', 'source_type')) {
                    $table->string('source_type')->nullable()->after('status_pelaksanaan');
                }
                if (!Schema::hasColumn('program_kepseks', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }
            });
        }

        if (Schema::hasTable('osis_programs')) {
            Schema::table('osis_programs', function (Blueprint $table) {
                if (!Schema::hasColumn('osis_programs', 'institution_id')) {
                    $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('osis_programs')) {
            Schema::table('osis_programs', function (Blueprint $table) {
                if (Schema::hasColumn('osis_programs', 'institution_id')) {
                    $table->dropConstrainedForeignId('institution_id');
                }
            });
        }

        if (Schema::hasTable('program_kepseks')) {
            Schema::table('program_kepseks', function (Blueprint $table) {
                if (Schema::hasColumn('program_kepseks', 'source_id')) {
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('program_kepseks', 'source_type')) {
                    $table->dropColumn('source_type');
                }
                if (Schema::hasColumn('program_kepseks', 'institution_id')) {
                    $table->dropConstrainedForeignId('institution_id');
                }
            });
        }
    }
};
