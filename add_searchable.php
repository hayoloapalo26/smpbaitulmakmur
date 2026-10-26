<?php

$dir = __DIR__.'/app/Filament/Staff/Resources';
$files = scandir($dir);

$searchableColumns = [
    "'name'", "'student.name'", "'teacher.name'", "'judul'", "'nomor'", "'perihal'",
    "'nama_program'", "'nama_prestasi'", "'nama_barang'", "'nis'", "'nik'", "'email'", "'nuptk'"
];

foreach ($files as $file) {
    if (strpos($file, '.php') === false) continue;
    
    $path = $dir.'/'.$file;
    $content = file_get_contents($path);
    $modified = false;
    
    foreach ($searchableColumns as $col) {
        // Regex to find TextColumn::make($col) that does NOT have ->searchable()
        // It looks for TextColumn::make('name') followed by chained methods, up to a semicolon or comma
        // We just do a simple string replace for make('name') to make('name')->searchable() if it doesn't exist
        
        // Let's use regex to find TextColumn::make($col) and if the line or block doesn't have searchable(), we add it.
        // Actually, just replacing `make($col)` with `make($col)->searchable()` is safe if we first check if it already has ->searchable() 
        // But the chaining might be across multiple lines.
        
        // A simpler way:
        if (strpos($content, "make($col)") !== false && strpos($content, "make($col)->searchable()") === false) {
            // Check if ->searchable() is anywhere in the 3 lines after this
            // We can just add it right after make('...')
            $content = str_replace("make($col)", "make($col)->searchable()", $content);
            // Wait, what if it already has ->searchable() on the next line? 
            // We will end up with ->searchable()->searchable() which is valid in Filament! It just overrides.
            $modified = true;
        }
    }
    
    // Set global search attribute
    if (strpos($content, '$recordTitleAttribute') === false) {
        $titleAttr = null;
        if (strpos($content, "make('name')") !== false) $titleAttr = "'name'";
        elseif (strpos($content, "make('judul')") !== false) $titleAttr = "'judul'";
        elseif (strpos($content, "make('nama_program')") !== false) $titleAttr = "'nama_program'";
        elseif (strpos($content, "make('nama_barang')") !== false) $titleAttr = "'nama_barang'";
        
        if ($titleAttr) {
            $insert = "\n    protected static ?string \$recordTitleAttribute = $titleAttr;\n";
            // insert after protected static ?string $navigationIcon...
            $content = preg_replace('/(protected static \?string \$modelLabel = [^;]+;)/', "$1" . $insert, $content);
            $modified = true;
        }
    }
    
    if ($modified) {
        // Cleanup double searchable
        $content = str_replace("->searchable()->searchable()", "->searchable()", $content);
        $content = str_replace("->searchable()\n                ->searchable()", "->searchable()", $content);
        $content = preg_replace("/->searchable\(\)[\s\n]*->searchable\(\)/", "->searchable()", $content);
        
        file_put_contents($path, $content);
        echo "Updated $file\n";
    }
}
