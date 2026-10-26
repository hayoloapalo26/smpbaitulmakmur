<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:255'])->guess(['NAMA LENGKAP', 'NAMA', 'name']),
            ImportColumn::make('email')->requiredMapping()->rules(['required', 'email', 'max:255'])->guess(['EMAIL', 'email']),
            ImportColumn::make('satminkal')->rules(['max:255'])->guess(['KET SATMINKAL (SATUAN INDUK PANGKAL)', 'KET SATMINKAL', 'satminkal']),
            ImportColumn::make('gender')->rules(['max:255'])->guess(['Jenis Kelamin', 'gender']),
            ImportColumn::make('tanggal_masuk')->rules(['date'])->guess(['Awal Masuk', 'tanggal_masuk']),
            ImportColumn::make('nuptk')->rules(['max:255'])->guess(['NUPTK', 'nuptk']),
            ImportColumn::make('jabatan')->rules(['max:255'])->guess(['JABATAN', 'jabatan']),
            ImportColumn::make('alamat')->rules(['max:65535'])->guess(['ALAMAT RUMAH / DOMISILI SAAT INI', 'ALAMAT', 'alamat']),
            ImportColumn::make('no_hp')->rules(['max:255'])->guess(['NO HP', 'no_hp']),
            ImportColumn::make('tempat_lahir')->rules(['max:255'])->guess(['Tempat Lahir', 'tempat_lahir']),
            ImportColumn::make('tanggal_lahir')->rules(['date'])->guess(['Tanggal Lahir', 'tanggal_lahir']),
            ImportColumn::make('jenjang_pendidikan')->rules(['max:255'])->guess(['PENDIDIKAN TERAKHIR', 'jenjang_pendidikan']),
            ImportColumn::make('universitas')->rules(['max:255'])->guess(['UNIVERSITAS', 'universitas']),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Try to find existing by email, otherwise create new
        $user = User::firstOrNew(['email' => $this->data['email'] ?? null]);
        if (!$user->exists) {
            $user->password = Hash::make('password'); // Default password for imported users
        }
        return $user;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor data PTK telah selesai dan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diimpor.';
        }
        return $body;
    }
}