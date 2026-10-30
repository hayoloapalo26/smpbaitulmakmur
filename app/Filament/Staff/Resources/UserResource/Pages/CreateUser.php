<?php

namespace App\Filament\Staff\Resources\UserResource\Pages;

use App\Filament\Staff\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\User;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['has_account'] = true;
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
                    Notification::make()
                        ->title('Akses Ditolak')
                        ->body('Anda tidak memiliki izin untuk menetapkan Role Struktural/Yayasan.')
                        ->danger()
                        ->send();
                    $this->halt();
                }
            }
        }

        // Handle institution_id for tata_usaha (jika tata_usaha buat user, otomatis masuk ke institusinya)
        if (!$authUser->hasRole('superadmin')) {
            if (empty($data['institution_id'])) {
                $data['institution_id'] = $authUser->institution_id;
            }
        }

        return $data;
    }
}
