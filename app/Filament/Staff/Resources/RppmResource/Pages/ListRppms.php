<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\LearningPlanResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\TeachingMaterialResource;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
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
            Actions\Action::make('manage_cp')
                ->label('Capaian Pembelajaran (CP)')
                ->icon('heroicon-o-flag')
                ->color('primary')
                ->button()
                ->url(fn () => CapaianPembelajaranResource::getUrl()),

            Actions\Action::make('manage_tp')
                ->label('Tujuan Pembelajaran (TP)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->button()
                ->url(fn () => TujuanPembelajaranResource::getUrl()),

            Actions\Action::make('manage_atp')
                ->label('Alur Tujuan Pembelajaran (ATP)')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('info')
                ->button()
                ->url(fn () => AlurTujuanPembelajaranResource::getUrl()),

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
