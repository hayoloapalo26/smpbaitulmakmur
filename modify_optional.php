<?php
$file = 'app/Filament/Staff/Pages/OptionalTodoList.php';
$content = file_get_contents($file);

$content = str_replace('class MyTodoList extends Page', 'class OptionalTodoList extends Page', $content);
$content = str_replace('protected static ?string $navigationGroup = \'Tugas Saya\';', 'protected static ?string $navigationGroup = \'Tugas & Kewajiban\';', $content);
$content = str_replace('protected static ?string $navigationLabel = \'To-Do List Saya\';', 'protected static ?string $navigationLabel = \'To-Do List Opsional\';', $content);
$content = str_replace('protected static ?string $title = \'To-Do List Saya\';', 'protected static ?string $title = \'To-Do List Opsional\';', $content);
$content = str_replace("protected static string \$view = 'filament.staff.pages.my-todo-list';", "protected static string \$view = 'filament.staff.pages.my-todo-list';\n    protected static ?int \$navigationSort = 2;", $content);

// Change the query to filter by 'opsional'
$oldQuery = <<<'PHP'
                TodoTask::query()->where(function ($query) use ($roles, $userId) {
                    // Cek jika user spesifik ditargetkan
                    $query->whereJsonContains('target_users', (string)$userId)
                        ->orWhereJsonContains('target_users', $userId);

                    // Cek jika role ditargetkan
                    foreach ($roles as $role) {
                        $query->orWhereJsonContains('target_roles', $role);
                    }
                })
PHP;

$newQuery = <<<'PHP'
                TodoTask::query()->where('type', 'opsional')->where(function ($query) use ($roles, $userId) {
                    $query->whereJsonContains('target_users', (string)$userId)
                        ->orWhereJsonContains('target_users', $userId);
                    foreach ($roles as $role) {
                        $query->orWhereJsonContains('target_roles', $role);
                    }
                })
PHP;
$content = str_replace($oldQuery, $newQuery, $content);

// Remove grouping and type filter
$content = preg_replace('/->defaultGroup\(\'type\'\).*?->collapsible\(\),\s*\]\)/s', '', $content);
$content = preg_replace('/Tables\\\\Columns\\\\TextColumn::make\(\'type\'\).*?->searchable\(\),/s', '', $content);

file_put_contents($file, $content);
echo "OptionalTodoList updated.\n";
