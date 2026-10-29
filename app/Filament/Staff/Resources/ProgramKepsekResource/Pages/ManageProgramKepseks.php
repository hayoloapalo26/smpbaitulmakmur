<?php

namespace App\Filament\Staff\Resources\ProgramKepsekResource\Pages;

use App\Filament\Staff\Resources\ProgramKepsekResource;
use App\Models\ProgramKepsek;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramKepseks extends ManageRecords
{
    protected static string $resource = ProgramKepsekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Proker Manual')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('syncFromAllDivisions')
                ->label('Tarik Program Kerja (Seluruh Bidang & Yayasan)')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Tarik Program Kerja Seluruh Bidang & Yayasan')
                ->modalDescription('Fitur ini akan menarik seluruh program kerja yang dibuat oleh bidang-bidang (Kurikulum, Kesiswaan, Sarpras, Humas, BK, Wali Kelas, OSIS) serta program kerja yang dibuat oleh Yayasan yang ditujukan ke lembaga ini secara otomatis ke dalam Proker Manajemen Sekolah.')
                ->modalSubmitActionLabel('Mulai Tarik Program Kerja')
                ->action(function () {
                    $result = ProgramKepsek::syncFromAllDivisionsAndYayasan();
                    $total = $result['total'];
                    $details = $result['details'];

                    if ($total > 0) {
                        $desc = "Berhasil menarik {$total} program kerja baru: "
                            . ($details['yayasan'] > 0 ? "Yayasan ({$details['yayasan']}), " : "")
                            . ($details['kurikulum'] > 0 ? "Kurikulum ({$details['kurikulum']}), " : "")
                            . ($details['kesiswaan'] > 0 ? "Kesiswaan ({$details['kesiswaan']}), " : "")
                            . ($details['sarpras'] > 0 ? "Sarpras ({$details['sarpras']}), " : "")
                            . ($details['humas'] > 0 ? "Humas ({$details['humas']}), " : "")
                            . ($details['bk'] > 0 ? "BK ({$details['bk']}), " : "")
                            . ($details['wali_kelas'] > 0 ? "Wali Kelas ({$details['wali_kelas']}), " : "")
                            . ($details['osis'] > 0 ? "OSIS ({$details['osis']})" : "");

                        Notification::make()
                            ->title("Sinkronisasi Selesai ($total Program Ditarik)")
                            ->body(rtrim(trim($desc), ','))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Semua Program Kerja Sudah Lengkap')
                            ->body('Tidak ada program kerja baru dari bidang maupun yayasan yang perlu disinkronkan.')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }
}
