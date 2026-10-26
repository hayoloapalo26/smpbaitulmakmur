<?php
$file = "app/Filament/Staff/Resources/OptionalTaskResource/Pages/CreateOptionalTask.php";
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOptionalTask extends CreateRecord
{
    protected static string $resource = OptionalTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['institution_id'] = auth()->user()->institution_id;
        return $data;
    }
}
PHP;
file_put_contents($file, $content);
echo "CreateOptionalTask configured.\n";
