<?php

namespace App\Filament\Imports;

use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('classroom_id')
                ->relationship(resolveUsing: function ($state) {
                    if (empty($state)) return null;
                    $clean = strtoupper(trim((string)$state));
                    $tingkat = match (true) {
                        str_contains($clean, '7') || str_contains($clean, 'VII') => '7',
                        str_contains($clean, '8') || str_contains($clean, 'VIII') => '8',
                        str_contains($clean, '9') || str_contains($clean, 'IX') => '9',
                        default => null,
                    };
                    $query = \App\Models\Classroom::withoutGlobalScopes();
                    if ($tingkat) {
                        $found = (clone $query)->where('tingkat', $tingkat)->first();
                        if ($found) return $found;
                    }
                    return $query->where('name', 'like', "%{$state}%")->first();
                })
                ->rules(['nullable'])
                ->guess(['KELAS', 'Kelas', 'kelas', 'Rombel', 'Tingkat']),

            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['NAMA', 'Nama', 'NAMA SISWA', 'Nama Siswa', 'Nama Lengkap', 'name']),

            ImportColumn::make('nis')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['NIS', 'NIPD/NIS', 'NIPD', 'nis', 'Nomor Induk Siswa', 'Nomor Induk']),

            ImportColumn::make('nisn')
                ->rules(['nullable', 'max:255'])
                ->guess(['NISN', 'nisn', 'Nomor Induk Siswa Nasional']),

            ImportColumn::make('nik')
                ->rules(['nullable', 'max:255'])
                ->guess(['NIK', 'nik', 'Nomor Induk Kependudukan']),

            ImportColumn::make('no_kk')
                ->rules(['nullable', 'max:255'])
                ->guess(['Nomer KK (Kartu Keluarga)', 'No KK', 'No. KK', 'Nomor KK', 'Nomer KK', 'no_kk']),

            ImportColumn::make('tempat_lahir')
                ->rules(['nullable', 'max:255'])
                ->guess(['Tempat Lahir', 'tempat_lahir', 'Tempat Lahir Siswa']),

            ImportColumn::make('tanggal_lahir')
                ->rules(['nullable'])
                ->guess(['Tanggal Lahir', 'tanggal_lahir', 'Tgl Lahir', 'Tanggal Lahir Siswa']),

            ImportColumn::make('jenis_kelamin')
                ->rules(['nullable', 'max:255'])
                ->guess(['Jenis Kelamin', 'Jenis Kelamin.1', 'JK', 'jenis_kelamin', 'Gender']),

            ImportColumn::make('agama')
                ->rules(['nullable', 'max:255'])
                ->guess(['Agama', 'agama']),

            ImportColumn::make('anak_ke')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->guess(['ANAK KE-', 'Anak ke-', 'Anak ke', 'anak_ke']),

            ImportColumn::make('jumlah_saudara_kandung')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->guess(['DARI SAUDARA KANDUNG KE-', 'Jumlah Saudara Kandung', 'Jumlah Saudara', 'jumlah_saudara_kandung', 'jumlah_saudara']),

            ImportColumn::make('asal_sekolah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Asal sekolah', 'Sekolah Asal', 'Asal Sekolah', 'asal_sekolah']),

            ImportColumn::make('alamat_siswa')
                ->rules(['nullable', 'max:65535'])
                ->guess([
                    'Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)',
                    'Alamat Rumah Siswa',
                    'Alamat',
                    'alamat_siswa',
                    'Alamat Lengkap',
                ]),

            // AYAH
            ImportColumn::make('nama_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Nama Ayah', 'NAMA AYAH', 'nama_ayah']),

            ImportColumn::make('tempat_lahir_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Tempat Lahir (Ayah)', 'Tempat Lahir Ayah', 'tempat_lahir_ayah']),

            ImportColumn::make('tanggal_lahir_ayah')
                ->rules(['nullable'])
                ->guess(['Tanggal Lahir (Ayah)', 'Tanggal Lahir Ayah', 'tanggal_lahir_ayah']),

            ImportColumn::make('no_hp_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['No. Telp/HP (Ayah)', 'No Telp/HP (Ayah)', 'NO HP Ayah', 'Nomor HP Ayah', 'no_hp_ayah']),

            ImportColumn::make('pendidikan_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pendidikan (Ayah)', 'Pendidikan Ayah', 'Jenjang Pendidikan Ayah', 'pendidikan_ayah']),

            ImportColumn::make('pekerjaan_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pekerjaan (Ayah)', 'Pekerjaan Ayah', 'pekerjaan_ayah']),

            ImportColumn::make('penghasilan_ayah')
                ->rules(['nullable', 'max:255'])
                ->guess(['Rata-rata Penghasilan (Ayah)', 'Penghasilan Ayah', 'penghasilan_ayah']),

            // IBU
            ImportColumn::make('nama_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['Nama Ibu', 'NAMA IBU', 'nama_ibu']),

            ImportColumn::make('tempat_lahir_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['Tempat Lahir (Ibu)', 'Tempat Lahir Ibu', 'tempat_lahir_ibu']),

            ImportColumn::make('tanggal_lahir_ibu')
                ->rules(['nullable'])
                ->guess(['Tanggal Lahir (Ibu)', 'Tanggal Lahir Ibu', 'tanggal_lahir_ibu']),

            ImportColumn::make('no_hp_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['No. Telp/HP', 'No. Telp/HP (Ibu)', 'No Telp/HP (Ibu)', 'NO HP Ibu', 'Nomor HP Ibu', 'no_hp_ibu']),

            ImportColumn::make('pendidikan_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pendidikan', 'Pendidikan (Ibu)', 'Pendidikan Ibu', 'Jenjang Pendidikan Ibu', 'pendidikan_ibu']),

            ImportColumn::make('pekerjaan_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pekerjaan', 'Pekerjaan (Ibu)', 'Pekerjaan Ibu', 'pekerjaan_ibu']),

            ImportColumn::make('penghasilan_ibu')
                ->rules(['nullable', 'max:255'])
                ->guess(['Rata-rata Penghasilan', 'Rata-rata Penghasilan (Ibu)', 'Penghasilan Ibu', 'penghasilan_ibu']),

            // WALI
            ImportColumn::make('nama_wali')
                ->rules(['nullable', 'max:255'])
                ->guess(['NAMA WALI', 'Nama Wali', 'nama_wali']),

            ImportColumn::make('no_hp_wali')
                ->rules(['nullable', 'max:255'])
                ->guess(['NO HP', 'No. HP (Wali)', 'NO HP Wali', 'no_hp_wali', 'Nomor HP Wali']),

            ImportColumn::make('pendidikan_wali')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pendidikan2', 'Pendidikan (Wali)', 'Pendidikan Wali', 'pendidikan_wali']),

            ImportColumn::make('pekerjaan_wali')
                ->rules(['nullable', 'max:255'])
                ->guess(['Pekerjaan2', 'Pekerjaan (Wali)', 'Pekerjaan Wali', 'pekerjaan_wali']),

            ImportColumn::make('penghasilan_wali')
                ->rules(['nullable', 'max:255'])
                ->guess(['Rata-rata Penghasilan2', 'Penghasilan (Wali)', 'Penghasilan Wali', 'penghasilan_wali']),
        ];
    }

    public function resolveRecord(): ?Student
    {
        $nis = trim($this->data['nis'] ?? '');
        $instId = auth()->user()?->institution_id;
        if (empty($instId) || $instId == 1) {
            $instId = 2; // Default ke SMP Islam Baitul Makmur (Siswa tidak berada di Yayasan)
        }

        $query = Student::withoutGlobalScopes()->where(function ($q) use ($instId) {
            $q->where('institution_id', $instId)
              ->orWhere('institution_id', 1)
              ->orWhereNull('institution_id');
        });

        if (!empty($nis)) {
            $existing = (clone $query)->where('nis', $nis)->first();
            if ($existing) {
                $existing->institution_id = $instId;
                return $existing;
            }
        }

        $nisn = trim($this->data['nisn'] ?? '');
        if (!empty($nisn)) {
            $existing = (clone $query)->where('nisn', $nisn)->first();
            if ($existing) {
                $existing->institution_id = $instId;
                return $existing;
            }
        }

        return new Student([
            'institution_id' => $instId,
            'character_score' => 80,
        ]);
    }

    protected function beforeValidate(): void
    {
        // Normalisasi Jenis Kelamin (L / P)
        if (!empty($this->data['jenis_kelamin'])) {
            $jk = strtoupper(trim((string) $this->data['jenis_kelamin']));
            if (str_starts_with($jk, 'L') || str_contains($jk, 'LAKI')) {
                $this->data['jenis_kelamin'] = 'L';
            } elseif (str_starts_with($jk, 'P') || str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'WANITA')) {
                $this->data['jenis_kelamin'] = 'P';
            }
        }

        // Normalisasi Tanggal Lahir Siswa, Ayah, Ibu
        foreach (['tanggal_lahir', 'tanggal_lahir_ayah', 'tanggal_lahir_ibu'] as $dateField) {
            if (!empty($this->data[$dateField])) {
                $this->data[$dateField] = static::parseDateValue($this->data[$dateField]);
            } else {
                $this->data[$dateField] = null;
            }
        }

        if (!empty($this->data['jumlah_saudara_kandung'])) {
            $this->data['jumlah_saudara_kandung'] = (int) $this->data['jumlah_saudara_kandung'];
        }
        if (!empty($this->data['anak_ke'])) {
            $this->data['anak_ke'] = (int) $this->data['anak_ke'];
        }
    }

    protected function beforeSave(): void
    {
        if (empty($this->record->institution_id) || $this->record->institution_id == 1) {
            $this->record->institution_id = 2; // Pastikan selalu masuk ke SMP Islam Baitul Makmur
        }
        if (empty($this->record->character_score)) {
            $this->record->character_score = 80;
        }
        if (!empty($this->record->jumlah_saudara_kandung) && empty($this->record->jumlah_saudara)) {
            $this->record->jumlah_saudara = $this->record->jumlah_saudara_kandung;
        }

        // Resolusi Kelas Otomatis jika kolom Kelas pada berkas kosong / tidak ada:
        if (empty($this->record->classroom_id)) {
            // 1. Dari opsi dropdown target kelas wizard jika dipilih oleh pengguna
            if (!empty($this->options['target_classroom_id'])) {
                $this->record->classroom_id = (int) $this->options['target_classroom_id'];
            }

            // 2. Dari nama berkas impor (contoh: "Kelas 8.xlsx", "Data_Kelas_8", "8.xlsx")
            if (empty($this->record->classroom_id) && !empty($this->import?->file_name)) {
                $fn = strtoupper($this->import->file_name);
                $tingkat = match (true) {
                    str_contains($fn, '8') || str_contains($fn, 'VIII') => '8',
                    str_contains($fn, '7') || str_contains($fn, 'VII') => '7',
                    str_contains($fn, '9') || str_contains($fn, 'IX') => '9',
                    default => null,
                };
                if ($tingkat) {
                    $cls = \App\Models\Classroom::withoutGlobalScopes()
                        ->where('institution_id', 2)
                        ->where('tingkat', $tingkat)
                        ->first();
                    if ($cls) {
                        $this->record->classroom_id = $cls->id;
                    }
                }
            }

            // 3. Fallback cerdas berdasarkan rentang NIS resmi SMP
            if (empty($this->record->classroom_id) && !empty($this->record->nis)) {
                $nis = trim($this->record->nis);
                $k8Nis = ['0285','0286','0287','0288','0289','0290','0291','0293','0294','0295','0296','0297','0298','0299','0300','0301','0302','0304','0305','0306','0307','0308','0309','0310','0311','0312','0313','0314','0315','0319'];
                $k7Nis = ['0320','0321','0322','0323','0324','0325','0326','0327','0328','0329','0330','0331','0332','0333','0334','0335','0336','0337','0338','0339','0340','0341','0342','0343','0344','0345','0346','0347','0348','0349'];
                $k9Nis = ['0262','0282','0264','0265','0266','0268','0269','0280','0281','0270','0271','0272','0274','0275','0276','0277','0316','0317','0318'];

                $tingkat = null;
                if (in_array($nis, $k8Nis)) $tingkat = '8';
                elseif (in_array($nis, $k7Nis)) $tingkat = '7';
                elseif (in_array($nis, $k9Nis)) $tingkat = '9';

                if ($tingkat) {
                    $cls = \App\Models\Classroom::withoutGlobalScopes()
                        ->where('institution_id', 2)
                        ->where('tingkat', $tingkat)
                        ->first();
                    if ($cls) {
                        $this->record->classroom_id = $cls->id;
                    }
                }
            }
        }
    }

    public static function parseDateValue($raw): ?string
    {
        if (empty($raw) || $raw === '-' || $raw === '0000-00-00') {
            return null;
        }

        if (is_numeric($raw) && (float)$raw > 1000) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int)$raw)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        $rawStr = trim((string) $raw);
        $months = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08', 'agu' => '08',
            'sep' => '09', 'oct' => '10', 'okt' => '10', 'nov' => '11', 'dec' => '12', 'des' => '12',
        ];

        $lower = strtolower($rawStr);
        foreach ($months as $name => $num) {
            if (str_contains($lower, $name)) {
                $lower = str_replace($name, '-' . $num . '-', $lower);
                break;
            }
        }
        $lower = preg_replace('/[\s\/]+/', '-', trim($lower, " -/"));
        $lower = preg_replace('/-+/', '-', $lower);

        try {
            return Carbon::parse($lower)->format('Y-m-d');
        } catch (\Throwable $e) {
            try {
                return Carbon::parse($rawStr)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
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
