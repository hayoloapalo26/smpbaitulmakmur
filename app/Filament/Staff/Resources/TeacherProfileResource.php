<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherProfileResource\Pages;
use App\Models\TeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Exports\UserExporter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class TeacherProfileResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Fitur Data Guru hanya muncul di role Kepala Sekolah, Tata Usaha per lembaga masing-masing, dan Waka Kurikulum
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'tata_usaha', 'waka_kurikulum']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (!$user) return $query;

        // Superadmin dapat melihat seluruh data guru lintas lembaga
        if ($user->hasRole('superadmin')) {
            return $query->withoutGlobalScope('institution');
        }

        // Kepala Sekolah, Tata Usaha, dan Waka Kurikulum hanya melihat data guru di lembaga masing-masing
        if ($user->institution_id) {
            return $query->where(function ($q) use ($user) {
                $q->where('users.institution_id', $user->institution_id)
                  ->orWhereNull('users.institution_id');
            });
        }

        return $query;
    }

    protected static ?string $model = TeacherProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $navigationLabel = 'Data Guru';
    protected static ?string $modelLabel = 'Data Guru';
    protected static ?string $pluralModelLabel = 'Data Guru';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Pegawai')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('no_induk_yayasan')
                            ->label('No Induk Yayasan')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nuptk')
                            ->label('NUPTK')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir'),
                        Forms\Components\TextInput::make('satminkal')
                            ->label('Satminkal (Satuan Administrasi Pangkal)')
                            ->placeholder('contoh: SMP Islam Baitul Makmur')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Pendidikan & Jabatan')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Unit / Lembaga')
                            ->relationship('institution', 'name')
                            ->default(fn () => auth()->user()?->institution_id ?? 2)
                            ->searchable()
                            ->preload()
                            ->disabled(fn () => !auth()->user()?->hasRole('superadmin'))
                            ->dehydrated(),
                        Forms\Components\TextInput::make('jenjang_pendidikan')
                            ->label('Jenjang Pendidikan')
                            ->placeholder('contoh: S1 / S2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('jurusan_pendidikan')
                            ->label('Jurusan Pendidikan')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('universitas')
                            ->label('Universitas')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk / TMT'),
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->placeholder('contoh: Guru Mapel / Guru Al-Qur\'an')
                            ->maxLength(255),
                        Forms\Components\Select::make('status_kepegawaian')
                            ->label('Status')
                            ->options([
                                'GTY' => 'Guru Tetap Yayasan (GTY)',
                                'GTT' => 'Guru Tidak Tetap (GTT)',
                                'Honor' => 'Honorer',
                                'Karyawan' => 'Karyawan',
                            ]),
                        Forms\Components\TextInput::make('no_sk_gty')
                            ->label('No SK GTY')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_ukg')
                            ->label('No UKG')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Kontak & Alamat')
                    ->schema([
                        Forms\Components\TextInput::make('no_hp')
                            ->label('No HP')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('🔐 Akun Login Guru (Kredensial)')
                    ->description(fn () => auth()->user()?->hasRole('superadmin')
                        ? 'Pengaturan kredensial akun login guru.'
                        : 'Status Akun: Belum Memiliki Akun. Guru baru otomatis berstatus belum memiliki akun login. Pihak lembaga harap menghubungi Admin Yayasan via WhatsApp untuk pembuatan akun.')
                    ->schema([
                        Forms\Components\Placeholder::make('info_status_akun')
                            ->label('Status Akun Login')
                            ->content('Guru yang baru diinputkan otomatis berstatus BELUM MEMILIKI AKUN. Pihak lembaga harap menghubungi Admin Yayasan via tombol "Hubungi Admin" untuk proses pembuatan akun login resmi.')
                            ->visible(fn () => !auth()->user()?->hasRole('superadmin')),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Login')
                            ->email()
                            ->placeholder('contoh: ahmad@sekolah.sch.id')
                            ->unique('users', 'email', ignoreRecord: true)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin')),
                        Forms\Components\TextInput::make('username')
                            ->label('Username Login')
                            ->placeholder('contoh: ahmad_fauzi')
                            ->unique('users', 'username', ignoreRecord: true)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin')),
                        Forms\Components\TextInput::make('password')
                            ->label('Password (Isi jika ingin mengatur / mengubah password)')
                            ->password()
                            ->revealable()
                            ->placeholder('Default: guru123')
                            ->dehydrated(fn ($state) => filled($state))
                            ->nullable()
                            ->visible(fn () => auth()->user()?->hasRole('superadmin')),
                        Forms\Components\Select::make('roles')
                            ->label('Rekomendasi Role / Hak Akses Guru')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => \App\Models\User::getRoleDisplayName($record->name))
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(false)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_induk_yayasan')
                    ->label('NIY')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable()
                    ->label('Nama Pegawai')
                    ->searchable(),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga / Unit')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole('superadmin')),
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_kepegawaian')
                    ->label('Status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('has_account')
                    ->label('Status Akun')
                    ->state(fn ($record): string => $record->has_account ? 'Sudah Memiliki Akun' : 'Belum Memiliki Akun')
                    ->badge()
                    ->color(fn ($record): string => $record->has_account ? 'success' : 'danger')
                    ->icon(fn ($record): string => $record->has_account ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->action(function ($record, $livewire) {
                        if ($record->has_account) {
                            if (auth()->user()?->hasRole('superadmin')) {
                                $livewire->mountTableAction('edit_akun', $record);
                            }
                        } else {
                            if (auth()->user()?->hasRole('superadmin')) {
                                $livewire->mountTableAction('buat_akun', $record);
                            } else {
                                $livewire->mountTableAction('hubungi_admin', $record);
                            }
                        }
                    })
                    ->tooltip(fn ($record) => $record->has_account
                        ? (auth()->user()?->hasRole('superadmin') ? 'Klik untuk kelola akun' : 'Akun login sudah aktif')
                        : (auth()->user()?->hasRole('superadmin') ? 'Klik untuk buat akun' : 'Klik untuk hubungi Admin Yayasan via WhatsApp')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Unit / Lembaga')
                    ->relationship('institution', 'name')
                    ->visible(fn () => auth()->user()->hasRole('superadmin')),
                Tables\Filters\SelectFilter::make('status_akun')
                    ->label('Status Akun')
                    ->options([
                        '1' => 'Sudah Memiliki Akun',
                        '0' => 'Belum Memiliki Akun',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === '1') {
                            $query->where(function ($q) {
                                $q->where('has_account', true)
                                  ->orWhere(function ($sq) {
                                      $sq->whereNotNull('email')
                                         ->where('email', 'not like', '%@placeholder.local%')
                                         ->whereNotNull('username');
                                  });
                            });
                        } elseif ($data['value'] === '0') {
                            $query->where(function ($q) {
                                $q->where('has_account', false)
                                  ->where(function ($sq) {
                                      $sq->whereNull('email')
                                         ->orWhere('email', 'like', '%@placeholder.local%');
                                  });
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('hubungi_admin')
                    ->label('Hubungi Admin')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->button()
                    ->color('warning')
                    ->visible(fn ($record) => !auth()->user()?->hasRole('superadmin') && !$record->has_account)
                    ->tooltip('Hubungi Admin Yayasan via WhatsApp untuk permohonan pembuatan akun login guru ini')
                    ->url(function ($record) {
                        $phone = '6282322672548';
                        $lembaga = $record->institution?->name ?? 'SMP Islam Baitul Makmur';
                        $niy = $record->no_induk_yayasan ?: '-';
                        $nuptk = $record->nuptk ?: '-';
                        $jabatan = $record->jabatan ?: 'Guru';
                        $noHp = $record->no_hp ?: '-';
                        $pengirim = auth()->user()?->name ?? 'Pihak Lembaga';

                        $msg = "Assalamu'alaikum Admin Sistem Informasi Yayasan Baitul Makmur,\n\n"
                             . "Kami dari pihak lembaga *{$lembaga}* ingin mengajukan permohonan pembuatan/aktivasi akun login guru baru:\n\n"
                             . "• *Nama Guru*: {$record->name}\n"
                             . "• *Lembaga/Unit*: {$lembaga}\n"
                             . "• *Jabatan*: {$jabatan}\n"
                             . "• *NIY / NUPTK*: {$niy} / {$nuptk}\n"
                             . "• *No. HP Guru*: {$noHp}\n"
                             . "• *Diajukan oleh*: {$pengirim}\n\n"
                             . "Mohon bantuannya untuk dibuatkan akun login sistem bagi guru tersebut. Terima kasih.\nWassalamu'alaikum Wr. Wb.";

                        return "https://wa.me/{$phone}?text=" . rawurlencode($msg);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('buat_akun')
                    ->label('Buat Akun')
                    ->icon('heroicon-m-user-plus')
                    ->button()
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()?->hasRole('superadmin') && !$record->has_account)
                    ->modalHeading(fn ($record) => "Buat Akun Login: {$record->name}")
                    ->modalDescription('Lengkapi data akun login (email, username, password, dan foto) untuk guru/pegawai ini.')
                    ->form([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->maxSize(2048),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->default(fn ($record) => $record->name)
                            ->required(),
                        Forms\Components\Select::make('institution_id')
                            ->label('Unit / Lembaga')
                            ->relationship('institution', 'name')
                            ->default(fn ($record) => $record->institution_id)
                            ->required(),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->default(fn ($record) => $record->username && !str_starts_with($record->username, 'guru') && !str_contains($record->username, 'placeholder') ? $record->username : Str::slug($record->name))
                            ->required()
                            ->unique('users', 'username', ignoreRecord: true),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn ($record) => $record->email && !str_contains($record->email, 'placeholder') ? $record->email : Str::slug($record->name) . '@guru.local')
                            ->required()
                            ->unique('users', 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->default('guru123'),
                        Forms\Components\Select::make('roles')
                            ->label('Role / Hak Akses')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->default(function ($record) {
                                if ($record->roles()->exists()) {
                                    return $record->roles->pluck('id')->toArray();
                                }
                                $jabatan = strtolower($record->jabatan ?? '');
                                if (str_contains($jabatan, 'qur') || str_contains($jabatan, 'tahfidz') || str_contains($jabatan, 'tahsin') || str_contains($jabatan, 'yanbua')) {
                                    return [\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web'])->id];
                                }
                                return [\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web'])->id];
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => \App\Models\User::getRoleDisplayName($record->name))
                            ->preload(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'avatar_url' => $data['avatar_url'] ?? $record->avatar_url,
                            'name' => $data['name'] ?? $record->name,
                            'institution_id' => $data['institution_id'] ?? $record->institution_id,
                            'username' => $data['username'],
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                            'has_account' => true,
                            'is_active' => true,
                        ]);

                        if (!empty($data['roles'])) {
                            $record->syncRoles($data['roles']);
                        }

                        Notification::make()
                            ->title('Akun Guru Berhasil Dibuat')
                            ->body("Akun login untuk {$record->name} berhasil diaktifkan dengan username {$data['username']}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit_akun')
                    ->label('Kelola Akun')
                    ->icon('heroicon-m-key')
                    ->color('info')
                    ->visible(fn ($record) => auth()->user()?->hasRole('superadmin') && $record->has_account)
                    ->modalHeading(fn ($record) => "Kelola / Reset Akun Guru: {$record->name}")
                    ->modalDescription('Ubah data akun guru atau reset password baru jika guru lupa password.')
                    ->form([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->maxSize(2048)
                            ->default(fn ($record) => $record->avatar_url),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->default(fn ($record) => $record->username)
                            ->required()
                            ->unique('users', 'username', ignoreRecord: true),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn ($record) => $record->email)
                            ->required()
                            ->unique('users', 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Password Baru (Reset Password)')
                            ->helperText('Isi jika ingin mereset password (misal guru lupa password). Kosongkan jika password tidak diubah.')
                            ->password()
                            ->revealable()
                            ->nullable(),
                        Forms\Components\Select::make('roles')
                            ->label('Role / Hak Akses')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => \App\Models\User::getRoleDisplayName($record->name))
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(fn ($record) => $record->is_active),
                    ])
                    ->action(function ($record, array $data) {
                        $updateData = [
                            'avatar_url' => $data['avatar_url'] ?? $record->avatar_url,
                            'username' => $data['username'],
                            'email' => $data['email'],
                            'is_active' => $data['is_active'] ?? true,
                        ];
                        if (!empty($data['password'])) {
                            $updateData['password'] = Hash::make($data['password']);
                        }
                        $record->update($updateData);

                        if (isset($data['roles'])) {
                            $record->syncRoles($data['roles']);
                        }

                        Notification::make()
                            ->title('Akun Guru Berhasil Diperbarui')
                            ->body("Data akun guru {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('buat_akun_massal_guru')
                        ->label('Buat Akun Guru Massal')
                        ->icon('heroicon-m-user-plus')
                        ->color('warning')
                        ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun Login Massal untuk Guru Terpilih')
                        ->modalDescription('Sistem akan mengaktifkan akun login secara otomatis untuk semua guru terpilih yang belum memiliki akun. Password default adalah "guru123".')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $created = 0;
                            $defaultRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
                            foreach ($records as $record) {
                                if ($record->has_account) {
                                    continue;
                                }
                                $slug = Str::slug($record->name);
                                $username = $record->username && !str_starts_with($record->username, 'guru') && !str_contains($record->username, 'placeholder') 
                                    ? $record->username 
                                    : $slug;
                                $email = $record->email && !str_contains($record->email, 'placeholder') 
                                    ? $record->email 
                                    : $slug . rand(10, 99) . '@guru.local';

                                if (\App\Models\User::where('username', $username)->where('id', '!=', $record->id)->exists()) {
                                    $username .= rand(10, 99);
                                }
                                if (\App\Models\User::where('email', $email)->where('id', '!=', $record->id)->exists()) {
                                    $email = $slug . rand(100, 999) . '@guru.local';
                                }

                                $record->update([
                                    'username' => $username,
                                    'email' => $email,
                                    'password' => Hash::make('guru123'),
                                    'has_account' => true,
                                    'is_active' => true,
                                ]);

                                if ($record->roles()->count() === 0) {
                                    $jabatan = strtolower($record->jabatan ?? '');
                                    if (str_contains($jabatan, 'qur') || str_contains($jabatan, 'tahfidz') || str_contains($jabatan, 'tahsin') || str_contains($jabatan, 'yanbua')) {
                                        $record->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']));
                                    } else {
                                        $record->assignRole($defaultRole);
                                    }
                                }

                                $created++;
                            }

                            Notification::make()
                                ->title('Pembuatan Akun Guru Massal Berhasil')
                                ->body("{$created} akun guru berhasil diaktifkan dengan password default 'guru123'.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherProfiles::route('/'),
            'create' => Pages\CreateTeacherProfile::route('/create'),
            'edit' => Pages\EditTeacherProfile::route('/{record}/edit'),
        ];
    }
}
