<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('NO'),
            ExportColumn::make('name')->label('NAMA LENGKAP'),
            ExportColumn::make('satminkal')->label('KET SATMINKAL (SATUAN INDUK PANGKAL)'),
            ExportColumn::make('gender')->label('Jenis Kelamin'),
            ExportColumn::make('tanggal_masuk')->label('Awal Masuk'),
            ExportColumn::make('nuptk')->label('NUPTK'),
            ExportColumn::make('jabatan')->label('JABATAN'),
            ExportColumn::make('roles.name')->label('ROLES/MAPEL'),
            ExportColumn::make('alamat')->label('ALAMAT RUMAH / DOMISILI SAAT INI'),
            ExportColumn::make('no_hp')->label('NO HP'),
            ExportColumn::make('tempat_lahir')->label('Tempat Lahir'),
            ExportColumn::make('tanggal_lahir')->label('Tanggal Lahir'),
            ExportColumn::make('jenjang_pendidikan')->label('PENDIDIKAN TERAKHIR'),
            ExportColumn::make('universitas')->label('UNIVERSITAS'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor data PTK telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diekspor.';
        }
        return $body;
    }
}