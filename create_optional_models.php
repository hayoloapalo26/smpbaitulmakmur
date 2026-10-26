<?php
$model1 = <<<'PHP'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class OptionalTask extends Model
{
    protected $fillable = [
        'institution_id',
        'title',
        'description',
        'target_roles',
        'form_schema',
        'reward_points',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'form_schema' => 'array',
        'is_active' => 'boolean',
        'reward_points' => 'integer',
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
        return $this->hasMany(OptionalTaskSubmission::class);
    }
}
PHP;
file_put_contents('app/Models/OptionalTask.php', $model1);

$model2 = <<<'PHP'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionalTaskSubmission extends Model
{
    protected $fillable = [
        'optional_task_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function optionalTask()
    {
        return $this->belongsTo(OptionalTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
PHP;
file_put_contents('app/Models/OptionalTaskSubmission.php', $model2);

$policy = <<<'PHP'
<?php
namespace App\Policies;

use App\Models\OptionalTask;
use App\Models\User;

class OptionalTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']);
    }
    public function view(User $user, OptionalTask $model): bool
    {
        return $this->viewAny($user);
    }
    public function create(User $user): bool
    {
        return $user->hasRole(['kepala_sekolah', 'superadmin']);
    }
    public function update(User $user, OptionalTask $model): bool
    {
        return $this->create($user);
    }
    public function delete(User $user, OptionalTask $model): bool
    {
        return $this->create($user);
    }
}
PHP;
file_put_contents('app/Policies/OptionalTaskPolicy.php', $policy);

echo "Models and policy created.\n";
