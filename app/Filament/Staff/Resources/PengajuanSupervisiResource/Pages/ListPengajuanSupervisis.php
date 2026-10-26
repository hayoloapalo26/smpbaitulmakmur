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
            'penjamin_mutu' => Tab::make('1. Dijadwalkan Penjamin Mutu')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jalur_supervisi', 'penjamin_mutu')),
            'pengajuan_guru' => Tab::make('2. Permintaan Guru')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jalur_supervisi', 'pengajuan_guru')),
        ];
    }

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('kepala_sekolah') && !Auth::user()->hasAnyRole(['guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas'])) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('Ajukan Permintaan Supervisi Mandiri')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Jalur 2: Permintaan Supervisi Mandiri oleh Guru')
                ->modalDescription('Ajukan permintaan supervisi Anda. Permintaan ini akan diteruskan ke Tim Penjamin Mutu Yayasan dan Kepala Sekolah untuk dijadwalkan.'),
        ];
    }
}