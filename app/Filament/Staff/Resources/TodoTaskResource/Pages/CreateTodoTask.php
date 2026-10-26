<?php

namespace App\Filament\Staff\Resources\TodoTaskResource\Pages;

use App\Filament\Staff\Resources\TodoTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTodoTask extends CreateRecord
{
    protected static string $resource = TodoTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}