<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\GuardianResource\Pages;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $modelLabel = 'Data Orang Tua';
    protected static ?string $pluralModelLabel = 'Data Orang Tua';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['students.classroom', 'institution', 'user', 'studentGuardians.student.classroom']);
        $authUser = Auth::user();

        if ($authUser && !$authUser->hasRole('superadmin')) {
            $instId = $authUser->institution_id ?: 2;
            $query->where(function ($q) use ($instId) {
                $q->where('guardians.institution_id', $instId)
                  ->orWhereHas('students', fn ($sq) => $sq->where('students.institution_id', $instId))
                  ->orWhereNull('guardians.institution_id');
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Biodata Orang Tua / Wali Murid')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Orang Tua / Wali')
                            ->required()
                            ->maxLength(255)
                            ->helperText('💡 Cukup buat 1 data orang tua meskipun mempunyai 2 anak atau lebih. Hubungkan seluruh anaknya pada pilihan Siswa di bawah.'),

                        Forms\Components\TextInput::make('phone')
                            ->label('No. Telepon / WhatsApp')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx')
                            ->maxLength(50),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Rumah')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('institution_id')
                            ->label('Unit / Lembaga')
                            ->relationship('institution', 'name')
                            ->default(fn () => Auth::user()?->institution_id ?: 2)
                            ->visible(fn () => Auth::user()?->hasRole('superadmin'))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Siswa / Anak Yang Dinaungi')
                    ->description('Hubungkan orang tua ini dengan siswa-siswanya di sekolah. 1 orang tua dapat menaungi 2 anak atau lebih.')
                    ->schema([
                        Forms\Components\Select::make('students')
                            ->label('Pilih Siswa / Anak')
                            ->relationship('students', 'name', modifyQueryUsing: function (Builder $query) {
                                $authUser = Auth::user();
                                if ($authUser && !$authUser->hasRole('superadmin')) {
                                    $instId = $authUser->institution_id ?: 2;
                                    $query->where('institution_id', $instId);
                                }
                                $query->where('status_siswa', 'aktif');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} (NIS: {$record->nis}) - " . ($record->classroom?->name ?? 'Tanpa Kelas'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih satu atau lebih siswa yang merupakan anak dari orang tua ini.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $authUser = Auth::user();
        $instId = $authUser?->institution_id ?: 2;
        $isSuperadmin = $authUser?->hasRole('superadmin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Orang Tua / Wali')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_list')
                    ->label('Wali Murid Atas Nama Siswa')
                    ->html()
                    ->formatStateUsing(function (Guardian $record) {
                        $students = $record->students;
                        if ($students->isEmpty()) {
                            $studentIds = $record->studentGuardians()->whereNotNull('student_id')->pluck('student_id')->unique();
                            if ($studentIds->isNotEmpty()) {
                                $students = Student::with('classroom')->whereIn('id', $studentIds)->get();
                            }
                        }

                        if ($students->isEmpty()) {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800">⚠️ Belum Dirouting TU</span>';
                        }

                        $badges = [];
                        foreach ($students as $s) {
                            $className = $s->classroom?->name ? " [Kelas {$s->classroom->name}]" : '';
                            $badges[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 mr-1 mb-1 border border-emerald-200 dark:border-emerald-800">'
                                . '👤 ' . htmlspecialchars($s->name . $className)
                                . '</span>';
                        }
                        return implode(' ', $badges);
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('students', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('nis', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),

                Tables\Columns\TextColumn::make('status_akun')
                    ->label('Status Akun Login')
                    ->badge()
                    ->state(function (Guardian $record) {
                        $hasAccount = $record->has_account;
                        $isRouted = $record->students->isNotEmpty() || $record->studentGuardians()->whereNotNull('student_id')->exists();

                        if ($hasAccount && $isRouted) {
                            return 'Akun Aktif';
                        }
                        if ($hasAccount && !$isRouted) {
                            return 'Menunggu Routing TU';
                        }
                        if (!$hasAccount && $isRouted) {
                            return 'Belum Dibuatkan Akun';
                        }
                        return 'Belum Dibuat';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Akun Aktif' => 'success',
                        'Menunggu Routing TU' => 'warning',
                        'Belum Dibuatkan Akun' => 'info',
                        default => 'danger',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'Akun Aktif' => 'heroicon-m-check-badge',
                        'Menunggu Routing TU' => 'heroicon-m-clock',
                        'Belum Dibuatkan Akun' => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-x-circle',
                    })
                    ->description(function (Guardian $record) {
                        $user = $record->user_account;
                        $studentCount = $record->students->count() ?: $record->studentGuardians()->whereNotNull('student_id')->count();
                        if ($user && $studentCount > 0) {
                            return "Login: {$user->username} • {$studentCount} Siswa Terhubung";
                        }
                        if ($user) {
                            return "Akun: {$user->username} (TU belum routing anak)";
                        }
                        if ($studentCount > 0) {
                            return "{$studentCount} Siswa terhubung (Superadmin belum buat akun)";
                        }
                        return 'Belum dirouting & belum ada akun';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('info')
                    ->default(fn (Guardian $record) => $record->institution?->name ?? 'SMP')
                    ->visible($isSuperadmin),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_akun')
                    ->label('Status Akun & Routing')
                    ->options([
                        'aktif' => '✅ Akun Aktif (Sudah Dirouting & Ada Akun)',
                        'menunggu_routing' => '⏳ Menunggu Routing TU (Ada Akun, Belum Ada Anak)',
                        'menunggu_akun' => '⚠️ Belum Dibuatkan Akun (Sudah Ada Anak)',
                        'belum' => '❌ Belum Dirouting & Belum Ada Akun',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'aktif') {
                            $query->where(function ($q) {
                                $q->whereHas('user')
                                  ->orWhereHas('studentGuardians', fn ($sq) => $sq->whereNotNull('user_id'));
                            })->where(function ($q) {
                                $q->whereHas('students')
                                  ->orWhereHas('studentGuardians', fn ($sq) => $sq->whereNotNull('student_id'));
                            });
                        } elseif ($data['value'] === 'menunggu_routing') {
                            $query->where(function ($q) {
                                $q->whereHas('user')
                                  ->orWhereHas('studentGuardians', fn ($sq) => $sq->whereNotNull('user_id'));
                            })->whereDoesntHave('students')
                              ->whereDoesntHave('studentGuardians', fn ($sq) => $sq->whereNotNull('student_id'));
                        } elseif ($data['value'] === 'menunggu_akun') {
                            $query->whereDoesntHave('user')
                                  ->whereDoesntHave('studentGuardians', fn ($sq) => $sq->whereNotNull('user_id'))
                                  ->where(function ($q) {
                                      $q->whereHas('students')
                                        ->orWhereHas('studentGuardians', fn ($sq) => $sq->whereNotNull('student_id'));
                                  });
                        } elseif ($data['value'] === 'belum') {
                            $query->whereDoesntHave('user')
                                  ->whereDoesntHave('studentGuardians', fn ($sq) => $sq->whereNotNull('user_id'))
                                  ->whereDoesntHave('students')
                                  ->whereDoesntHave('studentGuardians', fn ($sq) => $sq->whereNotNull('student_id'));
                        }
                    }),

                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Unit / Lembaga')
                    ->relationship('institution', 'name')
                    ->visible($isSuperadmin),
            ])
            ->actions([
                Tables\Actions\Action::make('routing_siswa')
                    ->label('Routing Siswa')
                    ->icon('heroicon-m-arrows-pointing-in')
                    ->color('primary')
                    ->modalHeading(fn (Guardian $record) => "Routing Anak untuk: {$record->name}")
                    ->modalDescription('Pilih siswa yang merupakan anak dari orang tua ini. Satu akun/data orang tua dapat menaungi 2 anak atau lebih.')
                    ->modalWidth('2xl')
                    ->fillForm(function (Guardian $record) {
                        return [
                            'student_ids' => $record->students()->pluck('students.id')->toArray(),
                            'relation' => $record->studentGuardians()->first()?->relation ?? 'Ayah',
                        ];
                    })
                    ->form([
                        Forms\Components\Select::make('student_ids')
                            ->label('Pilih Siswa / Anak Kandung')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function () use ($isSuperadmin, $instId) {
                                $studentsQuery = Student::withoutGlobalScopes()
                                    ->where('status_siswa', 'aktif');

                                if (!$isSuperadmin) {
                                    $studentsQuery->where('institution_id', $instId);
                                }

                                return $studentsQuery
                                    ->with('classroom')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($s) {
                                        $kelas = $s->classroom?->name ? " - Kelas {$s->classroom->name}" : '';
                                        $inst = $s->institution_id == 4 ? ' [SD]' : ' [SMP]';
                                        return [$s->id => "{$s->name} (NIS: {$s->nis}){$kelas}{$inst}"];
                                    })
                                    ->toArray();
                            })
                            ->helperText('Pilih satu atau lebih siswa yang merupakan anak dari orang tua ini.'),

                        Forms\Components\Select::make('relation')
                            ->label('Hubungan dengan Siswa')
                            ->options([
                                'Ayah' => '👨 Ayah Kandung / Ayah',
                                'Ibu'  => '👩 Ibu Kandung / Ibu',
                                'Wali' => '🤝 Wali Murid',
                            ])
                            ->default('Ayah')
                            ->required(),
                    ])
                    ->action(function (Guardian $record, array $data) {
                        $studentIds = (array) ($data['student_ids'] ?? []);
                        $relation = $data['relation'] ?? 'Ayah';
                        $userAccount = $record->user_account;
                        $userId = $userAccount?->id;

                        // Bersihkan mapping lama untuk guardian ini
                        StudentGuardian::where('guardian_id', $record->id)->delete();

                        // Masukkan mapping baru
                        foreach ($studentIds as $sid) {
                            StudentGuardian::create([
                                'student_id'  => $sid,
                                'guardian_id' => $record->id,
                                'user_id'     => $userId,
                                'relation'    => $relation,
                            ]);
                        }

                        // Pastikan user_account jika ada tetap aktif dan terhubung
                        if ($userAccount) {
                            $userAccount->update([
                                'guardian_id' => $record->id,
                                'is_active' => true,
                                'has_account' => true,
                            ]);
                        }

                        Notification::make()
                            ->title('Routing Siswa Berhasil Disimpan')
                            ->body("Data orang tua {$record->name} berhasil dihubungkan dengan " . count($studentIds) . " anak. Status akun otomatis terintegrasi.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('buat_akun_login')
                    ->label('Buat Akun Login')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->visible(fn (Guardian $record) => auth()->user()->hasRole('superadmin') && !$record->has_account)
                    ->modalHeading(fn (Guardian $record) => "Buatkan Akun Login Orang Tua: {$record->name}")
                    ->modalDescription('Buat akun login portal (email, username, password) untuk orang tua ini. Akun langsung aktif dengan role orang_tua.')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->default(fn (Guardian $record) => $record->name)
                            ->required(),

                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->default(fn (Guardian $record) => 'ortu.' . Str::slug($record->name, ''))
                            ->unique('users', 'username')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn (Guardian $record) => 'ortu.' . Str::slug($record->name, '') . '@baitulmakmur.sch.id')
                            ->unique('users', 'email')
                            ->required(),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->default('password123')
                            ->required(),
                    ])
                    ->action(function (Guardian $record, array $data) {
                        // Cek apakah akun user untuk orang tua ini sudah ada di sistem
                        $user = User::withoutGlobalScopes()
                            ->where(function ($q) use ($record, $data) {
                                $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($record->name))])
                                  ->orWhere('username', $data['username'])
                                  ->orWhere('email', $data['email']);
                            })
                            ->first();

                        if ($user) {
                            $user->update([
                                'guardian_id'    => $record->id,
                                'institution_id' => $record->institution_id ?: 2,
                                'is_active'      => true,
                                'has_account'    => true,
                            ]);
                        } else {
                            $user = User::create([
                                'guardian_id'    => $record->id,
                                'institution_id' => $record->institution_id ?: 2,
                                'name'           => $data['name'],
                                'username'       => $data['username'],
                                'email'          => $data['email'],
                                'password'       => Hash::make($data['password']),
                                'has_account'    => true,
                                'is_active'      => true,
                            ]);
                        }

                        if (!$user->hasRole('orang_tua')) {
                            $user->assignRole('orang_tua');
                        }

                        // Update user_id pada tabel pivot student_guardians
                        StudentGuardian::where('guardian_id', $record->id)->update(['user_id' => $user->id]);

                        Notification::make()
                            ->title('Akun Orang Tua Siap Digunakan')
                            ->body("Akun login untuk {$record->name} berhasil disiapkan (Username: {$user->username}). Status akun otomatis Aktif.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('tautkan_akun_user')
                    ->label('Tautkan Akun User')
                    ->icon('heroicon-m-link')
                    ->color('info')
                    ->visible(fn (Guardian $record) => auth()->user()->hasAnyRole(['tata_usaha', 'superadmin']) && !$record->has_account)
                    ->modalHeading(fn (Guardian $record) => "Tautkan Akun User ke: {$record->name}")
                    ->modalDescription('Pilih akun orang tua yang sudah dibuat oleh Superadmin untuk ditautkan ke data orang tua ini.')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Pilih Akun User Orang Tua')
                            ->options(function () {
                                return User::withoutGlobalScopes()
                                    ->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'))
                                    ->whereNull('guardian_id')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (Guardian $record, array $data) {
                        $user = User::find($data['user_id']);
                        if ($user) {
                            $user->update([
                                'guardian_id' => $record->id,
                                'is_active'   => true,
                                'has_account' => true,
                            ]);
                            StudentGuardian::where('guardian_id', $record->id)->update(['user_id' => $user->id]);

                            Notification::make()
                                ->title('Akun Berhasil Ditautkan')
                                ->body("Akun {$user->name} berhasil ditautkan dengan data orang tua {$record->name}.")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('superadmin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('superadmin')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGuardians::route('/'),
            'create' => Pages\CreateGuardian::route('/create'),
            'edit'   => Pages\EditGuardian::route('/{record}/edit'),
        ];
    }
}
