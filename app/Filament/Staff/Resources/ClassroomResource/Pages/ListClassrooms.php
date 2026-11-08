<?php

namespace App\Filament\Staff\Resources\ClassroomResource\Pages;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Models\Classroom;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClassrooms extends ListRecords
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Rombel'),
            'reguler' => Tab::make('🏫 Kelas Akademik (Wali Kelas)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('tipe', Classroom::TIPE_REGULER)->orWhereNull('tipe');
                })->whereRaw('LOWER(name) NOT LIKE ?', ['%tahsin%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%yanbu%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahfidz%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%quran%']))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->where('tipe', Classroom::TIPE_REGULER)->orWhereNull('tipe');
                })->whereRaw('LOWER(name) NOT LIKE ?', ['%tahsin%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%yanbu%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahfidz%'])
                  ->whereRaw('LOWER(name) NOT LIKE ?', ['%quran%'])->count()),
            'quran' => Tab::make('📖 Semua Kelompok Al-Qur\'an')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('tipe', Classroom::TIPE_QURAN)
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%tahsin%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%yanbu%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%tahfidz%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%quran%']);
                }))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->where('tipe', Classroom::TIPE_QURAN)
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%tahsin%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%yanbu%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%tahfidz%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%quran%']);
                })->count()),
            'yanbua' => Tab::make('📗 Yanbu\'a')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%yanbu%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%yanbu%']);
                }))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%yanbu%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%yanbu%']);
                })->count()),
            'tahsin' => Tab::make('📘 Tahsin')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%tahsin%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%tahsin%']);
                }))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%tahsin%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%tahsin%']);
                })->count()),
            'tahfidz_dasar' => Tab::make('📙 Tahfidz Dasar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereRaw('LOWER(name) LIKE ?', ['%dasar%'])
                            ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%dasar%']);
                    });
                }))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%dasar%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%dasar%']);
                })->count()),
            'tahfidz_lanjutan' => Tab::make('📕 Tahfidz Lanjutan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereRaw('LOWER(name) LIKE ?', ['%lanjutan%'])
                            ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%lanjutan%']);
                    });
                }))
                ->badge(fn () => Classroom::withoutGlobalScopes()->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%lanjutan%'])
                      ->orWhereRaw('LOWER(tingkat) LIKE ?', ['%lanjutan%']);
                })->count()),
        ];
    }
}
