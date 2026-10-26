<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\LearningPlanResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\TeachingMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRppms extends ListRecords
{
    protected static string $resource = RppmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('perangkat_ajar')
                ->label('Tautan Dokumen / Perangkat Ajar')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(fn () => TeachingMaterialResource::getUrl()),
            Actions\Action::make('telaah_rppm')
                ->label('Instrumen Telaah RPPM')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false)
                ->url(fn () => LearningPlanResource::getUrl()),
            Actions\CreateAction::make()
                ->label('Buat RPPM Baru'),
        ];
    }
}
