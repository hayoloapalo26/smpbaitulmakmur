<?php

namespace App\Filament\Staff\Resources\PengajuanSupervisiResource\Pages;

use App\Filament\Staff\Resources\PengajuanSupervisiResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPengajuanSupervisis extends ListRecords
{
    protected static string $resource = PengajuanSupervisiResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Supervisi'),
            'kepala_sekolah' => Tab::make('1. Supervisi Kepala Sekolah')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jalur_supervisi', 'kepala_sekolah')),
            'penjamin_mutu' => Tab::make('2. Penjamin Mutu Yayasan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jalur_supervisi', 'penjamin_mutu')),
            'pengajuan_guru' => Tab::make('3. Permintaan Guru')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jalur_supervisi', 'pengajuan_guru')),
        ];
    }

    protected function getHeaderActions(): array
    {
        $isLeader = Auth::user()->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin']);

        if ($isLeader) {
            return [
                Actions\CreateAction::make()
                    ->label('Jadwalkan Supervisi Guru')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Jadwalkan Supervisi Guru oleh Kepala Sekolah')
                    ->modalDescription('Pilih guru di lembaga ini dan tentukan jadwal pelaksanaan observasi pembelajaran di kelas.')
                    ->modalWidth('2xl'),
            ];
        }

        return [
            Actions\CreateAction::make()
                ->label('Ajukan Permintaan Supervisi Mandiri')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Permintaan Supervisi Mandiri oleh Guru')
                ->modalDescription('Ajukan permintaan supervisi pembelajaran Anda kepada Kepala Sekolah dan Penjamin Mutu untuk dijadwalkan.')
                ->modalWidth('xl'),
        ];
    }
}