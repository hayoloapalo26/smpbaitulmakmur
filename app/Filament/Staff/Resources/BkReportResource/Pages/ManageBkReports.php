<?php

namespace App\Filament\Staff\Resources\BkReportResource\Pages;

use App\Filament\Staff\Resources\BkReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBkReports extends ManageRecords
{
    protected static string $resource = BkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
