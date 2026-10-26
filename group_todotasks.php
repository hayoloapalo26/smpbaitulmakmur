<?php
$file = 'app/Filament/Staff/Resources/TodoTaskResource.php';
$content = file_get_contents($file);

$oldCode = <<<'PHP'
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'wajib' => 'Tugas Wajib',
                        'opsional' => 'Tugas Opsional',
                    ]),
            ])
PHP;

$newCode = <<<'PHP'
            ->defaultGroup('type')
            ->groups([
                \Filament\Tables\Grouping\Group::make('type')
                    ->label('Kategori')
                    ->getTitleFromRecordUsing(fn (TodoTask $record): string => $record->type === 'wajib' ? 'To Do List Wajib (Harian)' : 'To Do List Opsional')
                    ->orderQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query, string $direction) => $query->orderBy('type', 'desc'))
                    ->collapsible(),
            ])
            ->filters([
            ])
PHP;

$content = str_replace($oldCode, $newCode, $content);
file_put_contents($file, $content);
echo "TodoTaskResource grouped.\n";
