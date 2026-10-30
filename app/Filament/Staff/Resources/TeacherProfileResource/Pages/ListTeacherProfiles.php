<?php

namespace App\Filament\Staff\Resources\TeacherProfileResource\Pages;

use App\Filament\Staff\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Filament\Notifications\Notification;
use App\Filament\Exports\UserExporter;
use App\Models\TeacherProfile;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherProfiles extends ListRecords
{
    protected static string $resource = TeacherProfileResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Guru / Pegawai'),
            'belum_dibuat' => Tab::make('Belum Memiliki Akun')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('has_account', false)
                      ->where(function ($sq) {
                          $sq->whereNull('email')
                             ->orWhere('email', 'like', '%@placeholder.local%');
                      });
                }))
                ->badge(fn () => TeacherProfile::where('has_account', false)->where(function ($sq) {
                    $sq->whereNull('email')
                       ->orWhere('email', 'like', '%@placeholder.local%');
                })->count())
                ->badgeColor('danger'),
            'sudah_dibuat' => Tab::make('Sudah Memiliki Akun')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('has_account', true)
                      ->orWhere(function ($sq) {
                          $sq->whereNotNull('email')
                             ->where('email', 'not like', '%@placeholder.local%')
                             ->whereNotNull('username');
                      });
                }))
                ->badge(fn () => TeacherProfile::where(function ($q) {
                    $q->where('has_account', true)
                      ->orWhere(function ($sq) {
                          $sq->whereNotNull('email')
                             ->where('email', 'not like', '%@placeholder.local%')
                             ->whereNotNull('username');
                      });
                })->count())
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Data Guru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
            Actions\Action::make('import_ptk')
                ->label('Import Data PTK')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('Unggah Berkas CSV PTK')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'text/x-csv', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    
                    if (!file_exists($path) || !is_readable($path)) {
                        Notification::make()->title('Gagal membaca file.')->danger()->send();
                        return;
                    }

                    $handle = fopen($path, 'r');
                    $headers = fgetcsv($handle); 
                    
                    if (!$headers) return;
                    
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
                            $tempat = count($ttlParts) >= 2 ? trim($ttlParts[0]) : trim($tempatTanggalLahir);
                        }

                        $user = User::firstOrNew(['email' => $email]);
                        if (!$user->exists) {
                            $user->password = Hash::make('password');
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
        ];
    }
}