<?php

namespace App\Filament\Staff\Resources\TeacherCertificateResource\Pages;

use App\Filament\Staff\Resources\TeacherCertificateResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageTeacherCertificates extends ManageRecords
{
    protected static string $resource = TeacherCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Unggah Sertifikat Baru')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Unggah Sertifikat Pelatihan / Kegiatan')
                ->modalDescription('Cantumkan link Google Drive sertifikat Anda agar keaktifan pelatihan Anda tercatat dan dapat ditinjau oleh Kepala Sekolah serta Yayasan.')
                ->modalWidth('2xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (empty($data['teacher_id'])) {
                        $data['teacher_id'] = Auth::id();
                    }
                    if (empty($data['institution_id']) && Auth::user()?->institution_id) {
                        $data['institution_id'] = Auth::user()->institution_id;
                    }
                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Sertifikat Berhasil Diunggah')
                        ->body('Data sertifikat kegiatan Anda telah berhasil disimpan dan dapat ditinjau oleh Kepala Sekolah serta Pengembang/Penjamin Mutu Yayasan.')
                ),
        ];
    }
}
