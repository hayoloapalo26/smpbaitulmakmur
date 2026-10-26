<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOptionalTasks extends ListRecords
{
    protected static string $resource = OptionalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_optional')
                ->label('Pratinjau Tampilan Guru/Staf')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(\App\Filament\Staff\Pages\OptionalTodoList::getUrl()),
            Actions\CreateAction::make()->label('Tambah Tugas Opsional'),
        ];
    }
}
