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
            'quran' => Tab::make('📖 Kelompok Al-Qur\'an (Guru Pengampu)')
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
        ];
    }
}
