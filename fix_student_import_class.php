<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = file_get_contents($file);

$oldCode = <<<'PHP'
                        $classId = null;
                        if (!empty($row[1])) {
                            $classroom = \App\Models\Classroom::where('tingkat', $row[1])->first();
                            if ($classroom) {
                                $classId = $classroom->id;
                            }
                        }
PHP;

$newCode = <<<'PHP'
                        $classId = null;
                        if (!empty($row[1])) {
                            $classroom = \App\Models\Classroom::where('tingkat', $row[1])->first();
                            if (!$classroom) {
                                // Auto-create class if not exists
                                $user = \App\Models\User::first(); // get any user for wali_kelas_id
                                $classroom = \App\Models\Classroom::create([
                                    'tingkat' => $row[1],
                                    'name' => 'Kelas ' . $row[1],
                                    'wali_kelas_id' => $user ? $user->id : 1
                                ]);
                            }
                            $classId = $classroom->id;
                        }
PHP;

$content = str_replace($oldCode, $newCode, $content);
file_put_contents($file, $content);
echo "Updated ManageStudents.php\n";
