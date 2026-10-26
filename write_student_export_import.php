<?php
$content = <<<'PHP'
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
            ExportColumn::make('alamat_siswa')->label('Alamat Rumah Siswa'),
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
PHP;
file_put_contents('app/Filament/Exports/StudentExporter.php', $content);

$importer = <<<'PHP'
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
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:255'])->guess(['NAMA', 'name', 'Nama Lengkap']),
            ImportColumn::make('nis')->requiredMapping()->rules(['required', 'max:255'])->guess(['NIS', 'nis']),
            ImportColumn::make('nisn')->rules(['max:255'])->guess(['NISN', 'nisn']),
            ImportColumn::make('nik')->rules(['max:255'])->guess(['NIK', 'nik']),
            ImportColumn::make('no_kk')->rules(['max:255'])->guess(['Nomer KK (Kartu Keluarga)', 'Nomer KK', 'no_kk']),
            ImportColumn::make('tempat_lahir')->rules(['max:255'])->guess(['Tempat Lahir', 'tempat_lahir']),
            ImportColumn::make('tanggal_lahir')->rules(['date'])->guess(['Tanggal Lahir', 'tanggal_lahir']),
            ImportColumn::make('jenis_kelamin')->rules(['max:255'])->guess(['Jenis Kelamin', 'jenis_kelamin']),
            ImportColumn::make('agama')->rules(['max:255'])->guess(['Agama', 'agama']),
            ImportColumn::make('anak_ke')->numeric()->rules(['integer'])->guess(['Anak ke-', 'Anak ke', 'anak_ke']),
            ImportColumn::make('jumlah_saudara_kandung')->numeric()->rules(['integer'])->guess(['Jumlah Saudara Kandung', 'jumlah_saudara_kandung']),
            ImportColumn::make('asal_sekolah')->rules(['max:255'])->guess(['Asal sekolah', 'asal_sekolah']),
            ImportColumn::make('alamat_siswa')->rules(['max:65535'])->guess(['Alamat Rumah Siswa', 'alamat_siswa', 'Alamat']),
            
            ImportColumn::make('nama_ayah')->rules(['max:255'])->guess(['Nama Ayah', 'nama_ayah']),
            ImportColumn::make('tempat_lahir_ayah')->rules(['max:255'])->guess(['Tempat Lahir (Ayah)', 'tempat_lahir_ayah']),
            ImportColumn::make('tanggal_lahir_ayah')->rules(['date'])->guess(['Tanggal Lahir (Ayah)', 'tanggal_lahir_ayah']),
            ImportColumn::make('no_hp_ayah')->rules(['max:255'])->guess(['No. Telp/HP (Ayah)', 'no_hp_ayah']),
            ImportColumn::make('pendidikan_ayah')->rules(['max:255'])->guess(['Pendidikan (Ayah)', 'pendidikan_ayah']),
            ImportColumn::make('pekerjaan_ayah')->rules(['max:255'])->guess(['Pekerjaan (Ayah)', 'pekerjaan_ayah']),
            ImportColumn::make('penghasilan_ayah')->rules(['max:255'])->guess(['Rata-rata Penghasilan (Ayah)', 'penghasilan_ayah']),
            
            ImportColumn::make('nama_ibu')->rules(['max:255'])->guess(['Nama Ibu', 'nama_ibu']),
            ImportColumn::make('tempat_lahir_ibu')->rules(['max:255'])->guess(['Tempat Lahir (Ibu)', 'tempat_lahir_ibu']),
            ImportColumn::make('tanggal_lahir_ibu')->rules(['date'])->guess(['Tanggal Lahir (Ibu)', 'tanggal_lahir_ibu']),
            ImportColumn::make('no_hp_ibu')->rules(['max:255'])->guess(['No. Telp/HP', 'no_hp_ibu', 'No. Telp/HP (Ibu)']),
            ImportColumn::make('pendidikan_ibu')->rules(['max:255'])->guess(['Pendidikan', 'pendidikan_ibu', 'Pendidikan (Ibu)']),
            ImportColumn::make('pekerjaan_ibu')->rules(['max:255'])->guess(['Pekerjaan', 'pekerjaan_ibu', 'Pekerjaan (Ibu)']),
            ImportColumn::make('penghasilan_ibu')->rules(['max:255'])->guess(['Rata-rata Penghasilan', 'penghasilan_ibu', 'Rata-rata Penghasilan (Ibu)']),
            
            ImportColumn::make('nama_wali')->rules(['max:255'])->guess(['NAMA WALI', 'nama_wali']),
            ImportColumn::make('no_hp_wali')->rules(['max:255'])->guess(['NO HP', 'no_hp_wali']),
            ImportColumn::make('pendidikan_wali')->rules(['max:255'])->guess(['Pendidikan2', 'pendidikan_wali']),
            ImportColumn::make('pekerjaan_wali')->rules(['max:255'])->guess(['Pekerjaan2', 'pekerjaan_wali']),
            ImportColumn::make('penghasilan_wali')->rules(['max:255'])->guess(['Rata-rata Penghasilan2', 'penghasilan_wali']),
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
PHP;
file_put_contents('app/Filament/Imports/StudentImporter.php', $importer);
