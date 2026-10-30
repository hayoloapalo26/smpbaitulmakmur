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

            // 3. Upload Cepat CSV Langsung (Auto-Detect Header 34 Kolom)
            Actions\Action::make('import_csv')
                ->label('Upload Cepat CSV')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('Unggah Berkas CSV')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required()
                        ->helperText('Unggah berkas CSV sesuai dengan variabel format yang telah ditentukan.'),
                ])
                ->action(function (array $data) {
                    $fileVal = $data['file'] ?? null;
                    if (is_array($fileVal)) {
                        $fileVal = reset($fileVal);
                    }

                    $handle = null;
                    if ($fileVal instanceof \Illuminate\Http\UploadedFile) {
                        $handle = fopen($fileVal->getRealPath(), 'r');
                    } elseif (is_string($fileVal)) {
                        if (Storage::disk('local')->exists($fileVal)) {
                            $handle = Storage::disk('local')->readStream($fileVal);
                        } elseif (file_exists($fileVal)) {
                            $handle = fopen($fileVal, 'r');
                        } else {
                            $path = Storage::disk('local')->path($fileVal);
                            if (file_exists($path)) {
                                $handle = fopen($path, 'r');
                            }
                        }
                    }

                    if (!$handle) {
                        Notification::make()->title('Gagal membaca berkas CSV.')->danger()->send();
                        return;
                    }

                    $firstLine = fgets($handle);
                    if ($firstLine === false) {
                        fclose($handle);
                        Notification::make()->title('File CSV kosong.')->danger()->send();
                        return;
                    }

                    $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
                    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

                    $headers = str_getcsv($firstLine, $delimiter);
                    $headerMap = [];
                    foreach ($headers as $idx => $h) {
                        $clean = trim(strtolower($h));
                        $headerMap[$clean] = $idx;
                    }

                    $hasHeader = isset($headerMap['nama']) || isset($headerMap['nis']) || isset($headerMap['name']);
                    if (!$hasHeader) {
                        rewind($handle);
                    }

                    $instId = auth()->user()?->institution_id ?? 2;
                    $created = 0;
                    $updated = 0;

                    $getVal = function ($names, $fallbackIdx = null) use (&$currentRow, $headerMap) {
                        if (!is_array($names)) $names = [$names];
                        foreach ($names as $name) {
                            $clean = trim(strtolower($name));
                            if (isset($headerMap[$clean]) && isset($currentRow[$headerMap[$clean]])) {
                                $val = trim((string) $currentRow[$headerMap[$clean]]);
                                if ($val !== '') return $val;
                            }
                        }
                        if ($fallbackIdx !== null && isset($currentRow[$fallbackIdx])) {
                            $val = trim((string) $currentRow[$fallbackIdx]);
                            if ($val !== '') return $val;
                        }
                        return null;
                    };

                    while (($currentRow = fgetcsv($handle, 0, $delimiter)) !== false) {
                        if (empty(array_filter($currentRow))) {
                            continue;
                        }

                        $name = $getVal(['NAMA', 'Nama', 'Nama Lengkap', 'Nama Siswa'], 1);
                        if (empty($name) || str_contains(strtoupper($name), 'LAKI-LAKI') || str_contains(strtoupper($name), 'TOTAL')) {
                            continue;
                        }

                        $nis = $getVal(['NIS', 'NIPD/NIS', 'Nomor Induk Siswa', 'nis'], 2);
                        if (empty($nis)) {
                            $nis = 'NIS-' . strtoupper(Str::random(6));
                        }

                        $nisn = $getVal(['NISN', 'nisn'], 3);
                        $nik = $getVal(['NIK', 'nik'], 4);
                        $noKk = $getVal(['Nomer KK (Kartu Keluarga)', 'No KK', 'Nomor KK', 'Nomer KK'], 5);
                        $tempatLahir = $getVal(['Tempat Lahir', 'tempat_lahir'], 6);
                        $tglLahir = StudentImporter::parseDateValue($getVal(['Tanggal Lahir', 'tanggal_lahir', 'Tgl Lahir'], 7));

                        $rawJk = $getVal(['Jenis Kelamin', 'Jenis Kelamin.1', 'JK', 'Gender'], 8);
                        $jk = 'L';
                        if (!empty($rawJk)) {
                            $upperJk = strtoupper($rawJk);
                            if (str_starts_with($upperJk, 'P') || str_contains($upperJk, 'PEREMPUAN') || str_contains($upperJk, 'WANITA')) {
                                $jk = 'P';
                            }
                        }

                        $agama = $getVal(['Agama', 'agama'], 10);
                        $anakKe = $getVal(['Anak ke-', 'Anak ke', 'anak_ke'], 11);
                        $jumlahSaudara = $getVal(['Jumlah Saudara Kandung', 'Jumlah Saudara', 'jumlah_saudara_kandung'], 12);
                        $asalSekolah = $getVal(['Asal sekolah', 'Sekolah Asal', 'Asal Sekolah'], 13);
                        $alamat = $getVal([
                            'Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)',
                            'Alamat Rumah Siswa',
                            'Alamat',
                            'alamat_siswa'
                        ], 14);

                        // Ayah
                        $namaAyah = $getVal(['Nama Ayah', 'NAMA AYAH'], 15);
                        $tempatLahirAyah = $getVal(['Tempat Lahir (Ayah)', 'Tempat Lahir Ayah'], 16);
                        $tglLahirAyah = StudentImporter::parseDateValue($getVal(['Tanggal Lahir (Ayah)', 'Tanggal Lahir Ayah'], 17));
                        $noHpAyah = $getVal(['No. Telp/HP (Ayah)', 'No Telp/HP (Ayah)', 'NO HP Ayah', 'Nomor HP Ayah'], 18);
                        $pendidikanAyah = $getVal(['Pendidikan (Ayah)', 'Pendidikan Ayah', 'Jenjang Pendidikan Ayah'], 19);
                        $pekerjaanAyah = $getVal(['Pekerjaan (Ayah)', 'Pekerjaan Ayah'], 20);
                        $penghasilanAyah = $getVal(['Rata-rata Penghasilan (Ayah)', 'Penghasilan Ayah'], 21);

                        // Ibu
                        $namaIbu = $getVal(['Nama Ibu', 'NAMA IBU'], 22);
                        $tempatLahirIbu = $getVal(['Tempat Lahir (Ibu)', 'Tempat Lahir Ibu'], 23);
                        $tglLahirIbu = StudentImporter::parseDateValue($getVal(['Tanggal Lahir (Ibu)', 'Tanggal Lahir Ibu'], 24));
                        $noHpIbu = $getVal(['No. Telp/HP', 'No. Telp/HP (Ibu)', 'No Telp/HP (Ibu)', 'NO HP Ibu'], 25);
                        $pendidikanIbu = $getVal(['Pendidikan', 'Pendidikan (Ibu)', 'Pendidikan Ibu'], 26);
                        $pekerjaanIbu = $getVal(['Pekerjaan', 'Pekerjaan (Ibu)', 'Pekerjaan Ibu'], 27);
                        $penghasilanIbu = $getVal(['Rata-rata Penghasilan', 'Rata-rata Penghasilan (Ibu)', 'Penghasilan Ibu'], 28);

                        // Wali
                        $namaWali = $getVal(['NAMA WALI', 'Nama Wali'], 29);
                        $noHpWali = $getVal(['NO HP', 'No. HP (Wali)', 'NO HP Wali'], 30);
                        $pendidikanWali = $getVal(['Pendidikan2', 'Pendidikan (Wali)', 'Pendidikan Wali'], 31);
                        $pekerjaanWali = $getVal(['Pekerjaan2', 'Pekerjaan (Wali)', 'Pekerjaan Wali'], 32);
                        $penghasilanWali = $getVal(['Rata-rata Penghasilan2', 'Penghasilan (Wali)', 'Penghasilan Wali'], 33);

                        $student = Student::withoutGlobalScopes()
                            ->where('institution_id', $instId)
                            ->where('nis', $nis)
                            ->first();

                        $dataToSave = [
                            'institution_id' => $instId,
                            'name' => $name,
                            'nisn' => $nisn,
                            'nik' => $nik,
                            'no_kk' => $noKk,
                            'tempat_lahir' => $tempatLahir,
                            'tanggal_lahir' => $tglLahir,
                            'jenis_kelamin' => $jk,
                            'agama' => $agama,
                            'anak_ke' => !empty($anakKe) ? (int)$anakKe : null,
                            'jumlah_saudara_kandung' => !empty($jumlahSaudara) ? (int)$jumlahSaudara : null,
                            'jumlah_saudara' => !empty($jumlahSaudara) ? (int)$jumlahSaudara : null,
                            'asal_sekolah' => $asalSekolah,
                            'alamat_siswa' => $alamat,

                            'nama_ayah' => $namaAyah,
                            'tempat_lahir_ayah' => $tempatLahirAyah,
                            'tanggal_lahir_ayah' => $tglLahirAyah,
                            'no_hp_ayah' => $noHpAyah,
                            'pendidikan_ayah' => $pendidikanAyah,
                            'pekerjaan_ayah' => $pekerjaanAyah,
                            'penghasilan_ayah' => $penghasilanAyah,

                            'nama_ibu' => $namaIbu,
                            'tempat_lahir_ibu' => $tempatLahirIbu,
                            'tanggal_lahir_ibu' => $tglLahirIbu,
                            'no_hp_ibu' => $noHpIbu,
                            'pendidikan_ibu' => $pendidikanIbu,
                            'pekerjaan_ibu' => $pekerjaanIbu,
                            'penghasilan_ibu' => $penghasilanIbu,

                            'nama_wali' => $namaWali,
                            'no_hp_wali' => $noHpWali,
                            'pendidikan_wali' => $pendidikanWali,
                            'pekerjaan_wali' => $pekerjaanWali,
                            'penghasilan_wali' => $penghasilanWali,
                        ];

                        if ($student) {
                            $student->update($dataToSave);
                            $updated++;
                        } else {
                            $dataToSave['nis'] = $nis;
                            $dataToSave['character_score'] = 80;
                            Student::create($dataToSave);
                            $created++;
                        }
                    }

                    fclose($handle);

                    Notification::make()
                        ->title('Impor Data Siswa Berhasil')
                        ->body("Berhasil memproses data: {$created} siswa baru ditambahkan, {$updated} siswa diperbarui!")
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
