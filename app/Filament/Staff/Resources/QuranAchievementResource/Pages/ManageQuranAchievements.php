<?php

namespace App\Filament\Staff\Resources\QuranAchievementResource\Pages;

use App\Filament\Staff\Resources\QuranAchievementResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageQuranAchievements extends ManageRecords
{
    protected static string $resource = QuranAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('peringkat_tahfidz')
                ->label('Lihat Peringkat Tahfidz')
                ->icon('heroicon-o-trophy')
                ->color('warning')
                ->url(fn () => \App\Filament\Staff\Pages\TahfidzRanking::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
