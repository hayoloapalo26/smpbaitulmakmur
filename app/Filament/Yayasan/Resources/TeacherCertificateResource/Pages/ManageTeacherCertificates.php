<?php

namespace App\Filament\Yayasan\Resources\TeacherCertificateResource\Pages;

use App\Filament\Yayasan\Resources\TeacherCertificateResource;
use App\Filament\Yayasan\Resources\TeacherCertificateResource\Widgets\TeacherCertificateStatsWidget;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherCertificates extends ManageRecords
{
    protected static string $resource = TeacherCertificateResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherCertificateStatsWidget::class,
        ];
    }
}
