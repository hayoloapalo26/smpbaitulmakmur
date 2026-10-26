<?php
$file = 'app/Filament/Staff/Resources/MandatoryTaskResource/Pages/CreateMandatoryTask.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources\MandatoryTaskResource\Pages;

use App\Filament\Staff\Resources\MandatoryTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMandatoryTask extends CreateRecord
{
    protected static string $resource = MandatoryTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['institution_id'] = auth()->user()->institution_id;
        return $data;
    }
}
PHP;
file_put_contents($file, $content);
echo "CreateMandatoryTask updated.\n";
