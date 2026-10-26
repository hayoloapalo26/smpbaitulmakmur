<?php
$file = __DIR__.'/../app/Filament/Staff/Pages/Dashboard.php';
$content = file_get_contents($file);
$content = str_replace(
    "public function getWidgets(): array\n    {\n        return [];\n    }",
    "public function getWidgets(): array\n    {\n        return [\n            \App\Filament\Staff\Widgets\TeacherScheduleCalendarWidget::class,\n            \App\Filament\Staff\Widgets\PendingTodoReminderWidget::class,\n        ];\n    }",
    $content
);
file_put_contents($file, $content);
echo "SUCCESS UPDATED GETWIDGETS";
