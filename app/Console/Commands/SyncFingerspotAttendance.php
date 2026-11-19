<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\FingerspotAttendanceController;
use Illuminate\Console\Command;

class SyncFingerspotAttendance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fingerspot:sync {--cloud-id= : Cloud ID mesin Fingerspot} {--start= : Tanggal mulai (YYYY-MM-DD)} {--end= : Tanggal selesai (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Tarik dan sinkronisasi data presensi dari Cloud API developer.fingerspot.io ke sistem';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $controller = new FingerspotAttendanceController();
        $config = $controller->getCloudConfig();
        $cloudId = $this->option('cloud-id') ?: ($config['cloud_id'] ?? 'C260B8851F1C1030');

        if (empty($cloudId)) {
            $cloudId = 'C260B8851F1C1030';
        }

        $this->info("Menghubungi Cloud Fingerspot (Cloud ID: {$cloudId})...");

        $result = $controller->pullFromCloudApi(
            $cloudId,
            $this->option('start'),
            $this->option('end')
        );

        if ($result['success'] ?? false) {
            $this->info($result['message'] ?? 'Sinkronisasi berhasil.');
            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'Gagal sinkronisasi Fingerspot.');
        return self::FAILURE;
    }
}
