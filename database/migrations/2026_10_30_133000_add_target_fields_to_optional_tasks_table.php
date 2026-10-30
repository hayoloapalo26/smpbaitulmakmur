<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('optional_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('optional_tasks', 'target_type')) {
                $table->string('target_type')->default('roles')->after('description');
            }
            if (!Schema::hasColumn('optional_tasks', 'target_user_ids')) {
                $table->json('target_user_ids')->nullable()->after('target_roles');
            }
            if (!Schema::hasColumn('optional_tasks', 'due_date')) {
                $table->date('due_date')->nullable()->after('target_user_ids');
            }
            if (!Schema::hasColumn('optional_tasks', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('institution_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('optional_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('optional_tasks', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('optional_tasks', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('optional_tasks', 'target_user_ids')) {
                $table->dropColumn('target_user_ids');
            }
            if (Schema::hasColumn('optional_tasks', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
