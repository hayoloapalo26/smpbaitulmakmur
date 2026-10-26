<?php

namespace App\Filament\Imports;

use App\Models\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:255'])->guess(['Nama', 'NAMA', 'Nama Lengkap']),
            ImportColumn::make('nis')->requiredMapping()->rules(['required', 'max:255'])->guess(['NIPD/NIS', 'NIS', 'nis']),
            ImportColumn::make('nisn')->rules(['max:255'])->guess(['NISN', 'nisn']),
            ImportColumn::make('nik')->rules(['max:255'])->guess(['NIK', 'nik']),
            ImportColumn::make('no_kk')->rules(['max:255'])->guess(['No KK', 'Nomer KK (Kartu Keluarga)', 'Nomer KK']),
            ImportColumn::make('tempat_lahir')->rules(['max:255'])->guess(['Tempat Lahir', 'tempat_lahir']),
            ImportColumn::make('tanggal_lahir')->rules(['date'])->guess(['Tanggal Lahir', 'tanggal_lahir']),
            ImportColumn::make('jenis_kelamin')->rules(['max:255'])->guess(['JK', 'Jenis Kelamin', 'jenis_kelamin']),
            ImportColumn::make('agama')->rules(['max:255'])->guess(['Agama', 'agama']),
            ImportColumn::make('anak_ke')->numeric()->rules(['integer'])->guess(['Anak ke-', 'Anak ke']),
            ImportColumn::make('jumlah_saudara_kandung')->numeric()->rules(['integer'])->guess(['Jumlah Saudara Kandung', 'jumlah_saudara']),
            ImportColumn::make('asal_sekolah')->rules(['max:255'])->guess(['Sekolah Asal', 'Asal sekolah']),
            ImportColumn::make('alamat_siswa')->rules(['max:65535'])->guess(['Alamat', 'Alamat Rumah Siswa']),
            
            ImportColumn::make('nama_ayah')->rules(['max:255'])->guess(['Nama Ayah', 'NAMA AYAH', 'Nama Ayah ']),
            ImportColumn::make('pendidikan_ayah')->rules(['max:255'])->guess(['Jenjang Pendidikan Ayah', 'Pendidikan Ayah']),
            ImportColumn::make('pekerjaan_ayah')->rules(['max:255'])->guess(['Pekerjaan Ayah', 'Pekerjaan Ayah ']),
            ImportColumn::make('penghasilan_ayah')->rules(['max:255'])->guess(['Penghasilan Ayah', 'Rata-rata Penghasilan Ayah']),
            ImportColumn::make('no_hp_ayah')->rules(['max:255'])->guess(['NOMOR HP AYAH', 'NO HP Ayah']),
            
            ImportColumn::make('nama_ibu')->rules(['max:255'])->guess(['Nama Ibu', 'NAMA IBU']),
            ImportColumn::make('pendidikan_ibu')->rules(['max:255'])->guess(['Jenjang Pendidikan Ibu', 'Pendidikan Ibu']),
            ImportColumn::make('pekerjaan_ibu')->rules(['max:255'])->guess(['Pekerjaan Ibu', 'Pekerjaan Ibu ']),
            ImportColumn::make('penghasilan_ibu')->rules(['max:255'])->guess(['Penghasilan Ibu', 'Rata-rata Penghasilan Ibu']),
            ImportColumn::make('no_hp_ibu')->rules(['max:255'])->guess(['NOMOR HP IBU', 'NO HP Ibu']),
            
            ImportColumn::make('nama_wali')->rules(['max:255'])->guess(['Nama Wali', 'NAMA WALI']),
            ImportColumn::make('no_hp_wali')->rules(['max:255'])->guess(['NO HP Wali', 'NO HP WALI']),
            ImportColumn::make('pendidikan_wali')->rules(['max:255'])->guess(['Pendidikan Wali', 'Pendidikan2']),
            ImportColumn::make('pekerjaan_wali')->rules(['max:255'])->guess(['Pekerjaan Wali', 'Pekerjaan2']),
            ImportColumn::make('penghasilan_wali')->rules(['max:255'])->guess(['Penghasilan Wali', 'Rata-rata Penghasilan2']),
        ];
    }

    public function resolveRecord(): ?Student
    {
        return Student::firstOrNew([
            'nis' => $this->data['nis'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor data siswa telah selesai dan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diimpor.';
        }
        return $body;
    }
}