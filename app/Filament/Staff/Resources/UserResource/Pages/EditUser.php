<?php

namespace App\Filament\Staff\Resources\UserResource\Pages;

use App\Filament\Staff\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $authUser = auth()->user();
        
        // Proteksi Penetapan Role
        if (isset($data['roles']) && is_array($data['roles'])) {
            if (!$authUser->hasRole('superadmin')) {
                $protectedRoles = [
                    'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan',
                    'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras'
                ];
                
                $protectedRoleIds = \Spatie\Permission\Models\Role::whereIn('name', $protectedRoles)->pluck('id')->toArray();
                
                // Cek apakah ada role terproteksi di-submit
                $intersect = array_intersect($data['roles'], $protectedRoleIds);
                if (!empty($intersect)) {
                    // Pastikan record sebelumnya memang punya role itu (tidak diubah)
                    $existingProtectedRoles = $this->record->roles()->whereIn('name', $protectedRoles)->pluck('id')->toArray();
                    
                    $newProtectedAdded = array_diff($intersect, $existingProtectedRoles);
                    if (!empty($newProtectedAdded)) {
                        Notification::make()
                            ->title('Akses Ditolak')
                            ->body('Anda tidak memiliki izin untuk menambah Role Struktural/Yayasan.')
                            ->danger()
                            ->send();
                        $this->halt();
                    }
                }
                
                // Cek apakah ada role terproteksi yang dihapus
                $existingProtectedRoles = $this->record->roles()->whereIn('name', $protectedRoles)->pluck('id')->toArray();
                $removedProtected = array_diff($existingProtectedRoles, $data['roles']);
                if (!empty($removedProtected)) {
                    Notification::make()
                        ->title('Akses Ditolak')
                        ->body('Anda tidak memiliki izin untuk mencabut Role Struktural/Yayasan.')
                        ->danger()
                        ->send();
                    $this->halt();
                }
            }
        }

        // Proteksi Lapis Kedua Manipulasi Avatar (melawan Inspect Element)
        // Kita periksa apakah payload mengirimkan state 'avatar_url' yang berbeda dengan db
        if (array_key_exists('avatar_url', $data) && $data['avatar_url'] !== $this->record->avatar_url) {
            $hasAvatarAccess = false;
            
            if ($authUser->hasRole('superadmin')) {
                $hasAvatarAccess = true;
            } elseif ($authUser->id === $this->record->id) {
                $hasAvatarAccess = true; // Allow editing themselves
            } elseif ($authUser->hasRole('sekretaris_yayasan') && $this->record->institution_id === null) {
                $hasAvatarAccess = true;
            } elseif ($authUser->hasAnyRole(['tata_usaha', 'kepala_sekolah']) && $authUser->institution_id === $this->record->institution_id) {
                $hasAvatarAccess = true;
            }

            if (!$hasAvatarAccess) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.avatar_url' => 'Anda tidak memiliki hak akses untuk mengubah foto profil pengguna ini',
                ]);
            }
        }

        return $data;
    }
}
