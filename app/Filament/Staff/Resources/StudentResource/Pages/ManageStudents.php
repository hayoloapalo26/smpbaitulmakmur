<?php

namespace App\Filament\Staff\Resources\StudentResource\Pages;

use App\Filament\Staff\Resources\StudentResource;
use Filament\Actions;
use App\Filament\Exports\StudentExporter;
use App\Filament\Imports\StudentImporter;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Siswa'),
            'belum_dibuat' => Tab::make('Belum Memiliki Akun')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('user'))
                ->badge(fn () => Student::whereDoesntHave('user')->count())
                ->badgeColor('danger'),
            'sudah_dibuat' => Tab::make('Sudah Memiliki Akun')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user'))
                ->badge(fn () => Student::whereHas('user')->count())
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Import Standar Filament (Mendukung Excel XLSX & CSV dengan Wizard Mapping Otomatis)
            Actions\ImportAction::make('import_wizard')
                ->label('Import Siswa (Excel / CSV)')
                ->importer(StudentImporter::class)
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),

            // 2. Unduh Format Template CSV Resmi 34 Kolom
            Actions\Action::make('unduh_template_csv')
                ->label('Unduh Template CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $headers = [
                        'NO',
                        'NAMA',
                        'NIS',
                        'NISN',
                        'NIK',
                        'Nomer KK (Kartu Keluarga)',
                        'Tempat Lahir',
                        'Tanggal Lahir',
                        'Jenis Kelamin',
                        'Jenis Kelamin',
                        'Agama',
                        'Anak ke-',
                        'Jumlah Saudara Kandung',
                        'Asal sekolah',
                        'Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)',
                        'Nama Ayah',
                        'Tempat Lahir (Ayah)',
                        'Tanggal Lahir (Ayah)',
                        'No. Telp/HP (Ayah)',
                        'Pendidikan (Ayah)',
                        'Pekerjaan (Ayah)',
                        'Rata-rata Penghasilan (Ayah)',
                        'Nama Ibu',
                        'Tempat Lahir (Ibu)',
                        'Tanggal Lahir (Ibu)',
                        'No. Telp/HP',
                        'Pendidikan',
                        'Pekerjaan',
                        'Rata-rata Penghasilan',
                        'NAMA WALI',
                        'NO HP',
                        'Pendidikan2',
                        'Pekerjaan2',
                        'Rata-rata Penghasilan2',
                    ];

                    $sampleRow = [
                        '1',
                        'Ahmad Fulan',
                        '2026001',
                        '0081234567',
                        '3515012345670001',
                        '3515012345670002',
                        'Sidoarjo',
                        '2011-05-12',
                        'L',
                        'Laki-laki',
                        'Islam',
                        '1',
                        '2',
                        'SD Baitul Makmur',
                        'Jl. Melati No. 10 RT 02 RW 03, Pabean, Sedati, Sidoarjo',
                        'Bapak Abdullah',
                        'Surabaya',
                        '1980-01-15',
                        '081234567890',
                        'S1',
                        'Karyawan Swasta',
                        'Rp 5.000.000 - Rp 10.000.000',
                        'Ibu Siti Aminah',
                        'Sidoarjo',
                        '1984-08-20',
                        '081298765432',
                        'SMA',
                        'Ibu Rumah Tangga',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                    ];

                    return response()->streamDownload(function () use ($headers, $sampleRow) {
                        $output = fopen('php://output', 'w');
                        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
                        fputcsv($output, $headers);
                        fputcsv($output, $sampleRow);
                        fclose($output);
                    }, 'template_import_siswa_' . date('Ymd_His') . '.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),

            // 3. Upload Cepat (Mendukung Format Kelas 7, 8, dan 9 - CSV / XLSX)
            Actions\Action::make('import_csv')
                ->label('Upload Cepat (CSV / Excel)')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('target_classroom_id')
                        ->label('Kelas Target (Opsional)')
                        ->options(function () {
                            $instId = auth()->user()?->institution_id;
                            if (empty($instId) || $instId == 1) $instId = 2;
                            return \App\Models\Classroom::withoutGlobalScopes()
                                ->where(function ($q) use ($instId) {
                                    $q->where('institution_id', $instId)
                                      ->orWhere('institution_id', 1)
                                      ->orWhereNull('institution_id');
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->placeholder('Otomatis deteksi dari kolom KELAS (7/8/9) atau pilih manual')
                        ->searchable()
                        ->nullable(),

                    FileUpload::make('file')
                        ->label('Unggah Berkas Siswa (CSV / XLSX)')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/csv',
                            'text/x-csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/plain',
                        ])
                        ->required()
                        ->helperText('Otomatis mendeteksi layout Kelas 7, Kelas 8, dan Kelas 9 baik file CSV maupun Excel.'),
                ])
                ->action(function (array $data) {
                    $fileVal = $data['file'] ?? null;
                    if (is_array($fileVal)) {
                        $fileVal = reset($fileVal);
                    }

                    $instId = auth()->user()?->institution_id;
                    if (empty($instId) || $instId == 1) {
                        $instId = 2;
                    }

                    $targetClassroomId = !empty($data['target_classroom_id']) ? (int) $data['target_classroom_id'] : null;
                    $importService = new \App\Services\StudentImportService();
                    $result = null;

                    if (is_string($fileVal) && Storage::disk('local')->exists($fileVal)) {
                        $ext = strtolower(pathinfo($fileVal, PATHINFO_EXTENSION));
                        if (in_array($ext, ['xlsx', 'xls'])) {
                            $filePath = Storage::disk('local')->path($fileVal);
                            $result = $importService->importFromFile($filePath, $targetClassroomId, $instId);
                        } else {
                            $content = Storage::disk('local')->get($fileVal);
                            $result = $importService->importFromContent($content, $targetClassroomId, $instId);
                        }
                    } elseif ($fileVal instanceof \Illuminate\Http\UploadedFile) {
                        $ext = strtolower($fileVal->getClientOriginalExtension());
                        if (in_array($ext, ['xlsx', 'xls'])) {
                            $result = $importService->importFromFile($fileVal->getRealPath(), $targetClassroomId, $instId);
                        } else {
                            $content = file_get_contents($fileVal->getRealPath());
                            $result = $importService->importFromContent($content, $targetClassroomId, $instId);
                        }
                    } elseif (is_string($fileVal) && file_exists($fileVal)) {
                        $result = $importService->importFromFile($fileVal, $targetClassroomId, $instId);
                    } else {
                        Notification::make()->title('Gagal membaca berkas yang diunggah.')->danger()->send();
                        return;
                    }

                    if (!$result['success']) {
                        Notification::make()->title($result['message'] ?? 'Impor gagal.')->danger()->send();
                        return;
                    }

                    Notification::make()
                        ->title('Impor Data Siswa Selesai!')
                        ->body("Total: {$result['total']} siswa ({$result['created']} siswa baru, {$result['updated']} siswa diperbarui, {$result['skipped']} baris judul/rekap diabaikan).")
                        ->success()
                        ->send();
                }),

            // 4. Export Data Siswa
            Actions\ExportAction::make()
                ->exporter(StudentExporter::class)
                ->label('Export Data Siswa')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning'),

            // 5. Tambah Siswa Manual
            Actions\CreateAction::make(),
        ];
    }
}
