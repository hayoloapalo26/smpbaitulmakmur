<?php

namespace App\Filament\Staff\Resources\SchoolVisionMissionResource\Pages;

use App\Filament\Staff\Resources\SchoolVisionMissionResource;
use App\Filament\Staff\Widgets\VisionMissionOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolVisionMissions extends ManageRecords
{
    protected static string $resource = SchoolVisionMissionResource::class;

    protected ?string $subheading = 'Perumusan dan penetapan arah strategis satuan pendidikan yang mencakup Visi, Misi, dan Tujuan sekolah.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input / Perbarui Visi & Misi')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Rumuskan Visi, Misi & Tujuan Sekolah')
                ->modalDescription('Masukkan rumusan visi, rincian poin misi, dan target tujuan sekolah secara lengkap.')
                ->modalSubmitActionLabel('Simpan Dokumen Visi & Misi'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisionMissionOverviewWidget::class,
        ];
    }
}
