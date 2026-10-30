<?php

namespace App\Filament\Exports;

use App\Models\Student;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StudentExporter extends Exporter
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('NO'),
            ExportColumn::make('name')->label('NAMA'),
            ExportColumn::make('nis')->label('NIS'),
            ExportColumn::make('nisn')->label('NISN'),
            ExportColumn::make('nik')->label('NIK'),
            ExportColumn::make('no_kk')->label('Nomer KK (Kartu Keluarga)'),
            ExportColumn::make('tempat_lahir')->label('Tempat Lahir'),
            ExportColumn::make('tanggal_lahir')->label('Tanggal Lahir'),
            ExportColumn::make('jenis_kelamin')->label('Jenis Kelamin'),
            ExportColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin.1')
                ->state(fn (Student $record): string => $record->jenis_kelamin === 'L' ? 'Laki-laki' : ($record->jenis_kelamin === 'P' ? 'Perempuan' : '')),
            ExportColumn::make('agama')->label('Agama'),
            ExportColumn::make('anak_ke')->label('Anak ke-'),
            ExportColumn::make('jumlah_saudara_kandung')->label('Jumlah Saudara Kandung'),
            ExportColumn::make('asal_sekolah')->label('Asal sekolah'),
            ExportColumn::make('alamat_siswa')->label('Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)'),
            ExportColumn::make('nama_ayah')->label('Nama Ayah'),
            ExportColumn::make('tempat_lahir_ayah')->label('Tempat Lahir (Ayah)'),
            ExportColumn::make('tanggal_lahir_ayah')->label('Tanggal Lahir (Ayah)'),
            ExportColumn::make('no_hp_ayah')->label('No. Telp/HP (Ayah)'),
            ExportColumn::make('pendidikan_ayah')->label('Pendidikan (Ayah)'),
            ExportColumn::make('pekerjaan_ayah')->label('Pekerjaan (Ayah)'),
            ExportColumn::make('penghasilan_ayah')->label('Rata-rata Penghasilan (Ayah)'),
            ExportColumn::make('nama_ibu')->label('Nama Ibu'),
            ExportColumn::make('tempat_lahir_ibu')->label('Tempat Lahir (Ibu)'),
            ExportColumn::make('tanggal_lahir_ibu')->label('Tanggal Lahir (Ibu)'),
            ExportColumn::make('no_hp_ibu')->label('No. Telp/HP'),
            ExportColumn::make('pendidikan_ibu')->label('Pendidikan'),
            ExportColumn::make('pekerjaan_ibu')->label('Pekerjaan'),
            ExportColumn::make('penghasilan_ibu')->label('Rata-rata Penghasilan'),
            ExportColumn::make('nama_wali')->label('NAMA WALI'),
            ExportColumn::make('no_hp_wali')->label('NO HP'),
            ExportColumn::make('pendidikan_wali')->label('Pendidikan2'),
            ExportColumn::make('pekerjaan_wali')->label('Pekerjaan2'),
            ExportColumn::make('penghasilan_wali')->label('Rata-rata Penghasilan2'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor data siswa telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diekspor.';
        }
        return $body;
    }
}