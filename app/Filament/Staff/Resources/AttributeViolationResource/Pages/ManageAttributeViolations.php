<?php

namespace App\Filament\Staff\Resources\AttributeViolationResource\Pages;

use App\Filament\Staff\Resources\AttributeViolationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAttributeViolations extends ManageRecords
{
    protected static string $resource = AttributeViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    if (isset($data['is_lengkap']) && $data['is_lengkap'] === 'Lengkap') {
                        $data['attribute_type'] = 'Lengkap';
                        $data['jenis_pelanggaran'] = '-';
                    }
                    unset($data['is_lengkap']);
                    
                    if (empty($data['jenis_pelanggaran'])) {
                        $data['jenis_pelanggaran'] = '-';
                    }
                    return $data;
                }),
        ];
    }
}
