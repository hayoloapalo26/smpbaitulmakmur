<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Pages\OptionalTodoList;
use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOptionalTasks extends ListRecords
{
    protected static string $resource = OptionalTaskResource::class;

    protected static ?string $title = 'Kelola Tugas Insidentil';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_staff')
                ->label('Pratinjau Lembar Tugas Staf')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(OptionalTodoList::getUrl()),
            Actions\CreateAction::make()
                ->label('Buat Tugas Insidentil Baru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
