<?php

namespace App\Filament\Staff\Resources\MandatoryTaskResource\Pages;

use App\Filament\Staff\Resources\MandatoryTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMandatoryTasks extends ListRecords
{
    protected static string $resource = MandatoryTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_daily')
                ->label('Pratinjau Tampilan Guru/Staf')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(\App\Filament\Staff\Pages\DailyTodoList::getUrl()),
            Actions\CreateAction::make()->label('Tambah Tugas Wajib'),
        ];
    }
}
