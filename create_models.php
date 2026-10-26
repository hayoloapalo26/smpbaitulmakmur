<?php

// 1. InstitutionScope
$dir = 'app/Models/Scopes';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$scope = <<<'PHP'
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstitutionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->hasUser() && !auth()->user()->hasRole('superadmin')) {
            $builder->where($model->getTable() . '.institution_id', auth()->user()->institution_id);
        }
    }
}
PHP;
file_put_contents("$dir/InstitutionScope.php", $scope);

// 2. MandatoryTask Model
$model1 = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class MandatoryTask extends Model
{
    protected $fillable = [
        'institution_id',
        'title',
        'description',
        'target_roles',
        'form_schema',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'form_schema' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function submissions()
    {
        return $this->hasMany(MandatoryTaskSubmission::class);
    }
}
PHP;
file_put_contents('app/Models/MandatoryTask.php', $model1);

// 3. MandatoryTaskSubmission Model
$model2 = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MandatoryTaskSubmission extends Model
{
    protected $fillable = [
        'mandatory_task_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function mandatoryTask()
    {
        return $this->belongsTo(MandatoryTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
PHP;
file_put_contents('app/Models/MandatoryTaskSubmission.php', $model2);

echo "Models and Scope created.\n";
