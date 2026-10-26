<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('teacher_supervisions', function (Blueprint $table) {
    if (!Schema::hasColumn('teacher_supervisions', 'status')) {
        $table->enum('status', ['menunggu', 'dijadwalkan', 'ditolak', 'dinilai'])->default('menunggu')->after('tanggal');
        $table->dateTime('jadwal')->nullable()->after('status');
        $table->text('catatan_yayasan')->nullable()->after('hasil');
    }
    // Make supervisor_id nullable
    $table->foreignId('supervisor_id')->nullable()->change();
});

Schema::table('teacher_competency_tests', function (Blueprint $table) {
    if (!Schema::hasColumn('teacher_competency_tests', 'status')) {
        $table->enum('status', ['menunggu', 'dijadwalkan', 'ditolak', 'dinilai'])->default('menunggu')->after('tanggal');
        $table->dateTime('jadwal')->nullable()->after('status');
        $table->text('catatan_yayasan')->nullable()->after('catatan');
    }
    // Make assessor_id nullable
    $table->foreignId('assessor_id')->nullable()->change();
});
echo 'Database columns added.';

