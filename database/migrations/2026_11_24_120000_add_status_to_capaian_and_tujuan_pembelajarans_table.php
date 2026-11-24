<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom status di capaian_pembelajarans (default 'published' agar data lama tetap aktif)
        if (Schema::hasTable('capaian_pembelajarans')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                if (!Schema::hasColumn('capaian_pembelajarans', 'status')) {
                    $table->string('status', 20)->default('published')->after('deskripsi_cp');
                }
            });
        }

        // 2. Tambah kolom status di tujuan_pembelajarans (default 'published')
        if (Schema::hasTable('tujuan_pembelajarans')) {
            Schema::table('tujuan_pembelajarans', function (Blueprint $table) {
                if (!Schema::hasColumn('tujuan_pembelajarans', 'status')) {
                    $table->string('status', 20)->default('published')->after('semester');
                }
            });
        }

        // 3. Backfill data: CP dan TP yang HANYA terhubung ke RPPM berstatus 'draft' (belum pernah dipublikasikan)
        // disesuaikan statusnya menjadi 'draft' agar tidak muncul di Jurnal Mengajar sebelum fix dipublikasikan.
        if (Schema::hasTable('rppms') && Schema::hasTable('capaian_pembelajarans')) {
            $publishedCpIds = DB::table('rppms')
                ->where('status', 'published')
                ->whereNotNull('capaian_pembelajaran_id')
                ->pluck('capaian_pembelajaran_id')
                ->filter()
                ->unique()
                ->toArray();

            $draftCpIds = DB::table('rppms')
                ->where('status', 'draft')
                ->whereNotNull('capaian_pembelajaran_id')
                ->pluck('capaian_pembelajaran_id')
                ->filter()
                ->unique()
                ->toArray();

            $onlyDraftCpIds = array_values(array_diff($draftCpIds, $publishedCpIds));

            if (!empty($onlyDraftCpIds)) {
                DB::table('capaian_pembelajarans')
                    ->whereIn('id', $onlyDraftCpIds)
                    ->update(['status' => 'draft']);

                if (Schema::hasTable('tujuan_pembelajarans')) {
                    DB::table('tujuan_pembelajarans')
                        ->whereIn('capaian_pembelajaran_id', $onlyDraftCpIds)
                        ->update(['status' => 'draft']);
                }
            }

            // Cek juga TP dari selected_tp_ids pada RPPM draft
            if (Schema::hasTable('tujuan_pembelajarans')) {
                $draftRppms = DB::table('rppms')->where('status', 'draft')->get(['id', 'selected_tp_ids']);
                $publishedRppms = DB::table('rppms')->where('status', 'published')->get(['id', 'selected_tp_ids']);

                $publishedTpIds = [];
                foreach ($publishedRppms as $r) {
                    if (!empty($r->selected_tp_ids)) {
                        $ids = is_string($r->selected_tp_ids) ? json_decode($r->selected_tp_ids, true) : $r->selected_tp_ids;
                        if (is_array($ids)) {
                            $publishedTpIds = array_merge($publishedTpIds, $ids);
                        }
                    }
                }
                $publishedTpIds = array_unique(array_filter($publishedTpIds));

                $draftTpIds = [];
                foreach ($draftRppms as $r) {
                    if (!empty($r->selected_tp_ids)) {
                        $ids = is_string($r->selected_tp_ids) ? json_decode($r->selected_tp_ids, true) : $r->selected_tp_ids;
                        if (is_array($ids)) {
                            $draftTpIds = array_merge($draftTpIds, $ids);
                        }
                    }
                }
                $draftTpIds = array_unique(array_filter($draftTpIds));

                $onlyDraftTpIds = array_values(array_diff($draftTpIds, $publishedTpIds));
                if (!empty($onlyDraftTpIds)) {
                    DB::table('tujuan_pembelajarans')
                        ->whereIn('id', $onlyDraftTpIds)
                        ->update(['status' => 'draft']);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tujuan_pembelajarans')) {
            Schema::table('tujuan_pembelajarans', function (Blueprint $table) {
                if (Schema::hasColumn('tujuan_pembelajarans', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('capaian_pembelajarans')) {
            Schema::table('capaian_pembelajarans', function (Blueprint $table) {
                if (Schema::hasColumn('capaian_pembelajarans', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
