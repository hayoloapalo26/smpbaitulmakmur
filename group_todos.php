<?php
$file = 'app/Filament/Staff/Pages/MyTodoList.php';
$content = file_get_contents($file);

// Replace the query part and filters to include grouping
$oldCode = <<<'PHP'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'wajib' => 'Tugas Wajib',
                        'opsional' => 'Tugas Opsional',
                    ]),
            ])
            ->actions([
PHP;

$newCode = <<<'PHP'
                    }),
            ])
            ->defaultGroup('type')
            ->groups([
                \Filament\Tables\Grouping\Group::make('type')
                    ->label('Kategori')
                    ->getTitleFromRecordUsing(fn (TodoTask $record): string => $record->type === 'wajib' ? 'To Do List Wajib (Harian)' : 'To Do List Opsional')
                    ->orderQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query, string $direction) => $query->orderBy('type', 'desc')) // Wajib (w) is after Opsional (o), so desc puts Wajib first
                    ->collapsible(),
            ])
            ->filters([
            ])
            ->actions([
PHP;

$content = str_replace($oldCode, $newCode, $content);
file_put_contents($file, $content);
echo "MyTodoList grouped.\n";
