<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('superadmin');
    }

    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $modelLabel = 'Manajemen Akun (User)';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 99;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if (!$user->hasRole('superadmin')) {
            if ($user->institution_id) {
                $query->where(function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id)
                      ->orWhereHas('institutions', fn ($iq) => $iq->where('institutions.id', $user->institution_id));
                });
            }
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        $protectedRoles = [
            'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan',
            'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras'
        ];

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->deleteUploadedFileUsing(function ($file) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                            })
                            ->disabled(function (?User $record) {
                                $authUser = auth()->user();
                                if ($authUser->hasRole('superadmin')) return false;
                                // Allow if editing themselves
                                if ($record && $authUser->id === $record->id) return false;
                                // Allow yayasan
                                if ($authUser->hasRole('sekretaris_yayasan') && (!$record || $record->institution_id === null)) return false;
                                // Allow tata_usaha & kepala_sekolah for their institution
                                if ($authUser->hasAnyRole(['tata_usaha', 'kepala_sekolah']) && (!$record || $authUser->institution_id === $record->institution_id)) return false;
                                return true;
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->helperText(fn (string $context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password. Isi jika user lupa password.' : 'Masukkan password login akun.')
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Otorisasi & Unit')
                    ->schema([
                        Forms\Components\Select::make('institutions')
                            ->label('Unit / Lembaga (Multiple Choice)')
                            ->relationship('institutions', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Bisa memilih lebih dari satu unit/lembaga (contoh: Yayasan dan SMP jika menjabat di Yayasan sekaligus mengajar di SMP).')
                            ->disabled(fn () => !auth()->user()->hasRole('superadmin')),
                        Forms\Components\Select::make('roles')
                            ->label('Role / Hak Akses')
                            ->multiple()
                            ->relationship('roles', 'name', modifyQueryUsing: function (Builder $query) use ($protectedRoles) {
                                if (!auth()->user()->hasRole('superadmin')) {
                                    $query->whereNotIn('name', $protectedRoles);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => User::getRoleDisplayName($record->name))
                            ->disabled(function (?User $record) use ($protectedRoles) {
                                if (!$record) return false;
                                if (auth()->user()->hasRole('superadmin')) return false;
                                return $record->roles()->whereIn('name', $protectedRoles)->exists();
                            })
                            ->preload(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('username')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('institutions.name')
                    ->label('Unit / Lembaga')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->default(fn (User $record) => $record->institution?->name ? [$record->institution->name] : '-'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role / Hak Akses')
                    ->badge()
                    ->formatStateUsing(fn ($state) => User::getRoleDisplayName($state)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institutions')
                    ->label('Unit / Lembaga')
                    ->relationship('institutions', 'name'),
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role / Hak Akses')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => User::getRoleDisplayName($record->name)),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->default('password123'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['password'])]);
                        \Filament\Notifications\Notification::make()->title('Password Berhasil Direset')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
