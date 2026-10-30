<?php

namespace App\Filament\Staff\Resources\TeacherProfileResource\Pages;

use App\Filament\Staff\Resources\TeacherProfileResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTeacherProfile extends CreateRecord
{
    protected static string $resource = TeacherProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $isSuperadmin = auth()->user()?->hasRole('superadmin');

        // Hanya superadmin yang dapat membuat akun langsung aktif saat input data guru.
        // Untuk pihak lembaga (Tata Usaha, Kepala Sekolah, dll), guru yang diinputkan
        // otomatis berstatus BELUM MEMILIKI AKUN sesuai alur sistem (pihak lembaga menghubungi admin untuk dibuatkan akun).
        if ($isSuperadmin && !empty($data['email']) && !str_contains($data['email'], '@placeholder.local') && !empty($data['username'])) {
            $data['password'] = !empty($data['password']) ? Hash::make($data['password']) : Hash::make('guru123');
            $data['has_account'] = true;
            $data['is_active'] = $data['is_active'] ?? true;
        } else {
            $slugName = Str::slug($data['name'] ?? 'guru');
            $random = rand(1000, 9999);
            $data['username'] = $slugName . $random;
            $data['email'] = $slugName . $random . '@placeholder.local';
            $data['password'] = Hash::make(Str::random(32));
            $data['has_account'] = false;
            $data['is_active'] = false;
        }

        if (empty($data['institution_id']) && auth()->user()?->institution_id) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->roles()->count() === 0) {
            $jabatan = strtolower($this->record->jabatan ?? '');
            if (str_contains($jabatan, 'qur') || str_contains($jabatan, 'tahfidz') || str_contains($jabatan, 'tahsin') || str_contains($jabatan, 'yanbua')) {
                $this->record->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']));
            } else {
                $this->record->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']));
            }
        }

        if ($this->record->institution_id && \Illuminate\Support\Facades\Schema::hasTable('institution_user')) {
            try {
                \Illuminate\Support\Facades\DB::table('institution_user')->updateOrInsert(
                    ['user_id' => $this->record->id, 'institution_id' => $this->record->institution_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            } catch (\Throwable $e) {}
        }

        // Kirim notifikasi internal ke superadmin jika diinput oleh pihak lembaga
        if (!auth()->user()?->hasRole('superadmin')) {
            try {
                $superadmins = User::role('superadmin')->get();
                if ($superadmins->isNotEmpty()) {
                    $lembaga = $this->record->institution?->name ?? 'SMP Islam Baitul Makmur';
                    Notification::make()
                        ->title("Permohonan Akun Guru Baru: {$this->record->name}")
                        ->body("Pihak lembaga {$lembaga} telah menginput data guru baru ({$this->record->name}). Status: Belum Memiliki Akun. Silakan buatkan akun login resmi.")
                        ->icon('heroicon-o-user-plus')
                        ->warning()
                        ->sendToDatabase($superadmins);
                }
            } catch (\Throwable $e) {}
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->record->has_account) {
            return Notification::make()
                ->success()
                ->title('Data Guru & Akun Berhasil Dibuat')
                ->body("Data guru {$this->record->name} dan akun login berhasil disimpan.");
        }

        return Notification::make()
            ->warning()
            ->title('Data Guru Berhasil Diinputkan')
            ->body("Data guru {$this->record->name} berhasil disimpan dengan status 'Belum Memiliki Akun'. Silakan hubungi Admin Yayasan melalui tombol WhatsApp untuk pembuatan akun login.")
            ->duration(8000);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
