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
            Actions\Action::make('open_generator')
                ->label('🚀 Generator Pembuatan RPPM')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url('https://gemini.google.com/share/d/1e2rmqWovfw8uUldDF8Ql-uBCv6_Z1_Nv?usp=sharing', shouldOpenInNewTab: true),
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
