<?php
$file = 'app/Models/TodoTask.php';
$content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoTask extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_roles' => 'array',
        'target_users' => 'array',
        'due_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions()
    {
        return $this->hasMany(TodoCompletion::class);
    }
}
PHP;
file_put_contents($file, $content);

$file2 = 'app/Models/TodoCompletion.php';
$content2 = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoCompletion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(TodoTask::class, 'todo_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
PHP;
file_put_contents($file2, $content2);
echo "Models populated.\n";
