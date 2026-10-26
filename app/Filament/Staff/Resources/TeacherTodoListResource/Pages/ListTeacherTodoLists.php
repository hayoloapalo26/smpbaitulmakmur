<?php

namespace App\Filament\Staff\Resources\TeacherTodoListResource\Pages;

use App\Filament\Staff\Resources\TeacherTodoListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherTodoLists extends ListRecords
{
    protected static string $resource = TeacherTodoListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
