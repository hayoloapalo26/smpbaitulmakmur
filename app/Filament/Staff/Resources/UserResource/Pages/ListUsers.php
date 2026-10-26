<?php

namespace App\Filament\Staff\Resources\UserResource\Pages;

use App\Filament\Staff\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Filament\Exports\UserExporter;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_ptk')
                ->label('Import Data PTK')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Upload CSV')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/public/' . $data['file']);
                    
                    if (!file_exists($path)) {
                        Notification::make()->title('Gagal membaca file.')->danger()->send();
                        return;
                    }

                    $handle = fopen($path, 'r');
                    $headers = fgetcsv($handle); 
                    
                    if (!$headers) {
                        Notification::make()->title('File kosong atau format salah.')->danger()->send();
                        return;
                    }
                    
                    $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);
                    
                    $count = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        $namaLengkap = '';
                        $email = null;
                        $satminkal = null;
                        $gender = null;
                        $awalMasuk = null;
                        $nuptk = null;
                        $jabatan = null;
                        $mapel = null;
                        $alamat = null;
                        $noHp = null;
                        $tempatTanggalLahir = null;
                        $pendidikan = null;
                        $universitas = null;

                        foreach ($row as $index => $value) {
                            $col = $headers[$index] ?? '';
                            $val = trim($value);
                            
                            if (str_contains($col, 'nama')) $namaLengkap = $val;
                            if (str_contains($col, 'email')) $email = $val;
                            if (str_contains($col, 'satminkal')) $satminkal = $val;
                            if (str_contains($col, 'kelamin') || str_contains($col, 'jk')) $gender = $val;
                            if (str_contains($col, 'masuk')) $awalMasuk = $val;
                            if (str_contains($col, 'nuptk')) $nuptk = $val;
                            if (str_contains($col, 'jabatan')) $jabatan = $val;
                            if (str_contains($col, 'mapel')) $mapel = $val;
                            if (str_contains($col, 'alamat') || str_contains($col, 'domisili')) $alamat = $val;
                            if (str_contains($col, 'hp') || str_contains($col, 'telepon')) $noHp = $val;
                            if (str_contains($col, 'lahir')) $tempatTanggalLahir = $val;
                            if (str_contains($col, 'pendidikan')) $pendidikan = $val;
                            if (str_contains($col, 'universitas')) $universitas = $val;
                        }

                        if (empty($namaLengkap)) continue;

                        if (empty($email)) {
                            $parts = explode(' ', str_replace(['.', ','], '', strtolower($namaLengkap)));
                            $baseName = $parts[0] ?? 'guru';
                            if (strlen($baseName) < 3 && isset($parts[1])) $baseName .= $parts[1];
                            $email = $baseName . rand(10, 999) . '@smpi.com';
                        }
                        
                        $tempat = null;
                        if (!empty($tempatTanggalLahir)) {
                            $ttlParts = explode(',', $tempatTanggalLahir);
                            if (count($ttlParts) >= 2) {
                                $tempat = trim($ttlParts[0]);
                            } else {
                                $tempat = trim($tempatTanggalLahir);
                            }
                        }

                        $user = User::firstOrNew(['email' => $email]);
                        if (!$user->exists) {
                            $user->password = Hash::make('password');
                            $user->username = explode('@', $email)[0];
                        }

                        $user->name = $namaLengkap;
                        $user->satminkal = $satminkal;
                        $user->gender = $gender == 'L' || $gender == 'Laki-laki' ? 'L' : ($gender == 'P' || $gender == 'Perempuan' ? 'P' : null);
                        $user->tanggal_masuk = $awalMasuk; 
                        $user->nuptk = $nuptk;
                        $user->jabatan = $jabatan;
                        $user->alamat = $alamat;
                        $user->no_hp = $noHp;
                        $user->tempat_lahir = $tempat;
                        $user->jenjang_pendidikan = $pendidikan;
                        $user->universitas = $universitas;
                        
                        $user->save();
                        
                        if (!empty($mapel)) {
                            $user->assignRole('guru_mapel');
                        } else {
                            if ($user->roles->count() === 0) {
                                $user->assignRole('guru_mapel'); 
                            }
                        }

                        $count++;
                    }
                    
                    fclose($handle);
                    
                    Notification::make()
                        ->title('Import Berhasil')
                        ->body("Data CSV Anda ($count data PTK) berhasil dimasukkan ke sistem! Password default adalah 'password'.")
                        ->success()
                        ->send();
                }),
            Actions\ExportAction::make()->exporter(UserExporter::class)->label('Export Data PTK')->icon('heroicon-o-arrow-up-tray')->color('warning'),
            Actions\CreateAction::make(),
        ];
    }
}