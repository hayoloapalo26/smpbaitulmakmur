<?php
$piketModels = [
    'AttributeViolation',
    'DailyAttendance',
    'DutySwapRequest',
    'PrayerAttendance'
];

foreach ($piketModels as $model) {
    $path = __DIR__ . "/app/Policies/{$model}Policy.php";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Replace the hari check with tanggal check
        $oldLogic = <<<'PHP'
        $dayMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $today = $dayMap[now()->format('l')];
        return \App\Models\DutySchedule::where('teacher_id', $user->id)->where('hari', $today)->exists();
PHP;

        $newLogic = <<<'PHP'
        return \App\Models\DutySchedule::where('teacher_id', $user->id)
            ->whereDate('tanggal', \Carbon\Carbon::today())
            ->exists();
PHP;
        
        $content = str_replace($oldLogic, $newLogic, $content);
        file_put_contents($path, $content);
        echo "Fixed {$model}Policy\n";
    }
}
