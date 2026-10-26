<?php
$file = 'app/Filament/Staff/Resources/ClassroomResource.php';
$content = file_get_contents($file);

$search = "Forms\Components\TextInput::make('name')";
$replace = "Forms\Components\Select::make('institution_id')
                            ->relationship('institution', 'name')
                            ->label('Lembaga (Khusus Superadmin)')
                            ->visible(fn () => auth()->user()->hasRole('superadmin'))
                            ->required(fn () => auth()->user()->hasRole('superadmin')),
                        " . $search;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Added institution_id select for superadmin.\n";
