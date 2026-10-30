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
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['NAMA', 'Nama', 'Nama Lengkap', 'Nama Siswa', 'name']),

            ImportColumn::make('nis')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->guess(['NIS', 'NIPD/NIS', 'nis', 'Nomor Induk Siswa', 'Nomor Induk']),

            ImportColumn::make('nisn')
                ->rules(['nullable', 'max:255'])
                ->guess(['NISN', 'nisn', 'Nomor Induk Siswa Nasional']),

            ImportColumn::make('nik')
                ->rules(['nullable', 'max:255'])
                ->guess(['NIK', 'nik', 'Nomor Induk Kependudukan']),

            ImportColumn::make('no_kk')
                ->rules(['nullable', 'max:255'])
                ->guess(['Nomer KK (Kartu Keluarga)', 'No KK', 'Nomor KK', 'Nomer KK', 'no_kk']),

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
                ->guess(['Anak ke-', 'Anak ke', 'anak_ke']),

            ImportColumn::make('jumlah_saudara_kandung')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->guess(['Jumlah Saudara Kandung', 'Jumlah Saudara', 'jumlah_saudara_kandung', 'jumlah_saudara']),

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

        $query = Student::withoutGlobalScopes();
        if ($instId && !auth()->user()?->hasRole('superadmin')) {
            $query->where('institution_id', $instId);
        }

        if (!empty($nis)) {
            $existing = (clone $query)->where('nis', $nis)->first();
            if ($existing) {
                return $existing;
            }
        }

        $nisn = trim($this->data['nisn'] ?? '');
        if (!empty($nisn)) {
            $existing = (clone $query)->where('nisn', $nisn)->first();
            if ($existing) {
                return $existing;
            }
        }

        return new Student([
            'institution_id' => $instId ?? 2,
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
        if (empty($this->record->institution_id)) {
            $this->record->institution_id = auth()->user()?->institution_id ?? 2;
        }
        if (empty($this->record->character_score)) {
            $this->record->character_score = 80;
        }
        if (!empty($this->record->jumlah_saudara_kandung) && empty($this->record->jumlah_saudara)) {
            $this->record->jumlah_saudara = $this->record->jumlah_saudara_kandung;
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
