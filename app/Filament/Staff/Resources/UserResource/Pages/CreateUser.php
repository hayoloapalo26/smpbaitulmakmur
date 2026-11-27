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

            // Validasi duplikasi khusus akun Orang Tua
            $isParentRole = \Spatie\Permission\Models\Role::whereIn('id', $data['roles'])
                ->where('name', 'orang_tua')
                ->exists();

            if ($isParentRole && !empty($data['name'])) {
                $trimmedName = strtolower(trim($data['name']));
                $existingParent = User::withoutGlobalScopes()
                    ->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'))
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$trimmedName])
                    ->first();

                if ($existingParent) {
                    Notification::make()
                        ->title('Akun Orang Tua Sudah Ada')
                        ->body("Akun orang tua atas nama '{$data['name']}' sudah ada di sistem (Username: {$existingParent->username}). Satu orang tua hanya butuh 1 akun untuk seluruh anaknya.")
                        ->warning()
                        ->send();
                    $this->halt();
                }
            }
        }

        // Handle institutions for superadmin and non-superadmin
        if (!$authUser->hasRole('superadmin')) {
            if (empty($data['institutions'])) {
                $data['institutions'] = $authUser->institution_id ? [$authUser->institution_id] : [];
            }
            if (empty($data['institution_id'])) {
                $data['institution_id'] = $authUser->institution_id;
            }
        } else {
            if (!empty($data['institutions']) && is_array($data['institutions'])) {
                if (empty($data['institution_id']) || !in_array($data['institution_id'], $data['institutions'])) {
                    $data['institution_id'] = reset($data['institutions']);
                }
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $institutions = $this->data['institutions'] ?? [];
        if (!empty($institutions)) {
            $this->record->institutions()->sync($institutions);
        } elseif ($this->record->institution_id) {
            $this->record->institutions()->sync([$this->record->institution_id]);
        }

        if ($this->record->hasRole('orang_tua')) {
            $this->record->ensureGuardianRecord();
        }
    }
}
