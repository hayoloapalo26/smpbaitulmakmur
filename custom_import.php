<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources\StudentResource\Pages;

use App\Filament\Staff\Resources\StudentResource;
use Filament\Actions;
use App\Filament\Exports\StudentExporter;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_csv')
                ->label('Import Data Siswa')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('Unggah Berkas CSV')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    
                    if (!file_exists($path) || !is_readable($path)) {
                        Notification::make()->title('Gagal membaca file.')->danger()->send();
                        return;
                    }

                    $handle = fopen($path, 'r');
                    
                    // Skip 2 header rows
                    fgetcsv($handle);
                    fgetcsv($handle);
                    
                    $count = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        // Jika kolom index 2 (Nama) kosong, atau berisi rekap "LAKI-LAKI", berhenti
                        if (empty($row[2]) || strpos(strtoupper($row[5] ?? ''), 'LAKI-LAKI') !== false) {
                            break;
                        }

                        // Parse Tanggal Lahir (contoh: "09 Mei 2011")
                        $tglLahir = null;
                        if (!empty($row[7])) {
                            $months = [
                                'Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04',
                                'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08',
                                'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'
                            ];
                            $t = strtr($row[7], $months);
                            try {
                                $tglLahir = Carbon::createFromFormat('d m Y', $t)->format('Y-m-d');
                            } catch (\Exception $e) {
                                // Ignore
                            }
                        }

                        // Cari classroom (misal: "8")
                        $classId = null;
                        if (!empty($row[1])) {
                            $classroom = \App\Models\Classroom::where('tingkat', $row[1])->first();
                            if ($classroom) {
                                $classId = $classroom->id;
                            }
                        }

                        // Gabungkan alamat
                        $alamat = $row[10] ?? '';
                        if (!empty($row[11])) $alamat .= ' RT ' . $row[11];
                        if (!empty($row[12])) $alamat .= ' RW ' . $row[12];
                        if (!empty($row[13])) $alamat .= ', Kel. ' . $row[13];
                        if (!empty($row[14])) $alamat .= ', Kec. ' . $row[14];
                        if (!empty($row[15])) $alamat .= ' ' . $row[15];

                        Student::updateOrCreate(
                            ['nis' => $row[3] ?? null],
                            [
                                'classroom_id' => $classId,
                                'name' => $row[2] ?? null,
                                'jenis_kelamin' => $row[4] ?? null,
                                'nisn' => $row[5] ?? null,
                                'tempat_lahir' => $row[6] ?? null,
                                'tanggal_lahir' => $tglLahir,
                                'nik' => $row[8] ?? null,
                                'agama' => $row[9] ?? null,
                                'alamat_siswa' => $alamat,
                                
                                'nama_ayah' => $row[16] ?? null,
                                'pendidikan_ayah' => $row[17] ?? null,
                                'pekerjaan_ayah' => $row[18] ?? null,
                                'penghasilan_ayah' => $row[19] ?? null,
                                'no_hp_ayah' => $row[20] ?? null,
                                
                                'nama_ibu' => $row[22] ?? null,
                                'pendidikan_ibu' => $row[23] ?? null,
                                'pekerjaan_ibu' => $row[24] ?? null,
                                'penghasilan_ibu' => $row[25] ?? null,
                                'no_hp_ibu' => (!empty($row[26]) && strlen($row[26]) > 10) ? $row[26] : null, // Sesuai CSV ibu hp kadang nyampur
                                
                                'asal_sekolah' => $row[27] ?? null,
                                'no_kk' => $row[28] ?? null,
                                'character_score' => 80
                            ]
                        );
                        $count++;
                    }
                    
                    fclose($handle);
                    
                    Notification::make()
                        ->title('Import Berhasil')
                        ->body("Data CSV Anda ($count data siswa) berhasil dimasukkan ke sistem!")
                        ->success()
                        ->send();
                }),
            Actions\ExportAction::make()->exporter(StudentExporter::class)->label('Export Data Siswa')->icon('heroicon-o-arrow-up-tray')->color('warning'),
            Actions\CreateAction::make(),
        ];
    }
}
PHP;
file_put_contents($file, $content);
echo "Custom Import created!\n";
