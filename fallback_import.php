<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = file_get_contents($file);

$oldCode = <<<'PHP'
                            try {
                                $tglLahir = Carbon::createFromFormat('d m Y', $t)->format('Y-m-d');
                            } catch (\Exception $e) {
                                // Ignore
                            }
                        }
PHP;

$newCode = <<<'PHP'
                            try {
                                $tglLahir = Carbon::createFromFormat('d m Y', $t)->format('Y-m-d');
                            } catch (\Exception $e) {
                                $tglLahir = '2000-01-01'; // Default fallback
                            }
                        } else {
                            $tglLahir = '2000-01-01';
                        }
PHP;

$content = str_replace($oldCode, $newCode, $content);

// Also fallback jenis_kelamin
$content = str_replace(
    "'jenis_kelamin' => \$row[4] ?? null,",
    "'jenis_kelamin' => !empty(\$row[4]) ? \$row[4] : 'L', // Default to L if null",
    $content
);

file_put_contents($file, $content);
echo "Added fallback logic.\n";
