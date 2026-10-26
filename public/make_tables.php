<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasTable('transport_assessments')) {
    Schema::create('transport_assessments', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score')->default(0);
        $table->timestamps();
    });
}
if (!Schema::hasTable('lab_assessments')) {
    Schema::create('lab_assessments', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score')->default(0);
        $table->timestamps();
    });
}
if (!Schema::hasTable('canteen_assessments')) {
    Schema::create('canteen_assessments', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score')->default(0);
        $table->timestamps();
    });
}

// Write the models
file_put_contents(app_path('Models/TransportAssessment.php'), "<?php\nnamespace App\Models;\nuse Illuminate\Database\Eloquent\Model;\nclass TransportAssessment extends Model {\n    protected \$guarded = [];\n}\n");
file_put_contents(app_path('Models/LabAssessment.php'), "<?php\nnamespace App\Models;\nuse Illuminate\Database\Eloquent\Model;\nclass LabAssessment extends Model {\n    protected \$guarded = [];\n}\n");
file_put_contents(app_path('Models/CanteenAssessment.php'), "<?php\nnamespace App\Models;\nuse Illuminate\Database\Eloquent\Model;\nclass CanteenAssessment extends Model {\n    protected \$guarded = [];\n}\n");

\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'TransportAssessment', '--panel' => 'yayasan', '--generate' => true]);
\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'LabAssessment', '--panel' => 'yayasan', '--generate' => true]);
\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'CanteenAssessment', '--panel' => 'yayasan', '--generate' => true]);

echo 'SUCCESS';
