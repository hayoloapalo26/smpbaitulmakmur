<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRppms extends ListRecords
{
    protected static string $resource = RppmResource::class;

    public function getTitle(): string
    {
        return 'Perangkat Ajar';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_generator')
                ->label('🚀 Generator RPPM')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('warning')
                ->url('https://gemini.google.com/share/d/1e2rmqWovfw8uUldDF8Ql-uBCv6_Z1_Nv?usp=sharing', shouldOpenInNewTab: true),

            Actions\CreateAction::make()
                ->label('+ Buat Perangkat Ajar Baru')
                ->visible(fn () => !auth()->user()?->hasRole('kepala_sekolah')),
        ];
    }
}
