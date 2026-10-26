<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('teacher_supervisions', function (Blueprint $table) {
    if (!Schema::hasColumn('teacher_supervisions', 'score')) {
        $table->integer('score')->nullable()->after('hasil');
    }
});

if (!Schema::hasTable('teacher_competency_tests')) {
    Schema::create('teacher_competency_tests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
        $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete(); // Tim Pengembang
        $table->date('tanggal');
        $table->integer('score')->default(0);
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}
echo 'SUCCESS: Database updated.';

