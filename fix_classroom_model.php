<?php
$file = 'app/Models/Classroom.php';
$content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class Classroom extends Model
{
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
PHP;
file_put_contents($file, $content);
echo "Classroom model updated with InstitutionScope.\n";
