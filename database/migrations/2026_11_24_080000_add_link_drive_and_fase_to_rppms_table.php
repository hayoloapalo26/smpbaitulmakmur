<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (!Schema::hasColumn('rppms', 'fase')) {
                    $table->string('fase', 10)->nullable()->default('D')->after('kelas_fase');
                }
                if (!Schema::hasColumn('rppms', 'fases')) {
                    $table->json('fases')->nullable()->after('fase');
                }
                if (!Schema::hasColumn('rppms', 'link_drive')) {
                    $table->text('link_drive')->nullable()->after('topik');
                }
            });

            // Backfill data lama jika ada
            $existingRppms = DB::table('rppms')->whereNull('fases')->get();
            foreach ($existingRppms as $rppm) {
                $fases = ['D'];
                $linkDrive = null;

                if (!empty($rppm->content)) {
                    $decoded = json_decode($rppm->content, true);
                    if (is_array($decoded) && !empty($decoded['link_drive'])) {
                        $linkDrive = $decoded['link_drive'];
                    }
                }

                $updates = [
                    'fase' => 'D',
                    'fases' => json_encode($fases),
                ];
                if ($linkDrive) {
                    $updates['link_drive'] = $linkDrive;
                }

                DB::table('rppms')->where('id', $rppm->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (Schema::hasColumn('rppms', 'link_drive')) {
                    $table->dropColumn('link_drive');
                }
                if (Schema::hasColumn('rppms', 'fases')) {
                    $table->dropColumn('fases');
                }
                if (Schema::hasColumn('rppms', 'fase')) {
                    $table->dropColumn('fase');
                }
            });
        }
    }
};
