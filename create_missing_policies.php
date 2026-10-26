<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$policiesToCreate = [
    'StudentAchievement' => "['waka_kesiswaan', 'wali_kelas']",
    'Extracurricular' => "['waka_kesiswaan']",
    'ExtracurricularAttendance' => "['waka_kesiswaan']",
    'OsisProgram' => "['waka_kesiswaan']",
    'AlumniTracer' => "['waka_kesiswaan']",
];

foreach ($policiesToCreate as $model => $rolesStr) {
    $content = <<<PHP
<?php

namespace App\Policies;

use App\Models\\$model;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class {$model}Policy
{
    public function viewAny(User \$user): bool
    {
        return \$user->hasRole($rolesStr);
    }

    public function view(User \$user, $model \$model): bool
    {
        return \$user->hasRole($rolesStr);
    }

    public function create(User \$user): bool
    {
        return \$user->hasRole(['waka_kesiswaan']);
    }

    public function update(User \$user, $model \$model): bool
    {
        return \$user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User \$user, $model \$model): bool
    {
        return \$user->hasRole(['waka_kesiswaan']);
    }
}
PHP;

    file_put_contents(__DIR__ . "/app/Policies/{$model}Policy.php", $content);
    echo "Created {$model}Policy\n";
}

// Special Policy for Piket modules
$piketModels = [
    'AttributeViolation',
    'DailyAttendance',
    'DutySwapRequest',
];

foreach ($piketModels as $model) {
    $content = <<<PHP
<?php

namespace App\Policies;

use App\Models\\$model;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class {$model}Policy
{
    private function isPiketOrWaka(User \$user): bool
    {
        if (\$user->hasRole(['waka_kesiswaan'])) return true;
        // Check if user is piket today
        \$dayMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        \$today = \$dayMap[now()->format('l')];
        return \App\Models\DutySchedule::where('teacher_id', \$user->id)->where('hari', \$today)->exists();
    }

    public function viewAny(User \$user): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function view(User \$user, $model \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function create(User \$user): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function update(User \$user, $model \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function delete(User \$user, $model \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }
}
PHP;

    file_put_contents(__DIR__ . "/app/Policies/{$model}Policy.php", $content);
    echo "Created {$model}Policy\n";
}

// PrayerAttendance Policy
$content = <<<PHP
<?php

namespace App\Policies;

use App\Models\PrayerAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrayerAttendancePolicy
{
    private function isPiketOrWaka(User \$user): bool
    {
        if (\$user->hasRole(['waka_kesiswaan', 'wali_kelas'])) return true;
        // Check if user is piket today
        \$dayMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        \$today = \$dayMap[now()->format('l')];
        return \App\Models\DutySchedule::where('teacher_id', \$user->id)->where('hari', \$today)->exists();
    }

    public function viewAny(User \$user): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function view(User \$user, PrayerAttendance \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function create(User \$user): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function update(User \$user, PrayerAttendance \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }

    public function delete(User \$user, PrayerAttendance \$model): bool
    {
        return \$this->isPiketOrWaka(\$user);
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Policies/PrayerAttendancePolicy.php", $content);
echo "Created PrayerAttendancePolicy\n";
