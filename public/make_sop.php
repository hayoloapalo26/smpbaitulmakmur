<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasTable('sops')) {
    Schema::create('sops', function (Blueprint $table) {
        $table->id();
        $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('judul_sop');
        $table->string('bidang');
        $table->date('tanggal_pembuatan');
        $table->string('link_dokumen');
        $table->text('keterangan_ks')->nullable();
        $table->enum('status', ['draft', 'belum_diperiksa', 'dalam_pemeriksaan', 'perbaikan', 'disetujui'])->default('draft');
        $table->timestamps();
    });
}

$modelContent = <<<'PHP'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToInstitution;

class Sop extends Model {
    use BelongsToInstitution;
    
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
PHP;
file_put_contents(app_path('Models/Sop.php'), $modelContent);
echo 'SUCCESS';
