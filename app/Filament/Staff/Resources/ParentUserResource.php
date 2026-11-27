<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ParentUserResource\Pages;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentUserResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('superadmin');
    }

    protected static ?string $model = User::class;
    protected static ?string $slug = 'parent-users';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $modelLabel = 'Akun Orang Tua';
    protected static ?string $pluralModelLabel = 'Manajemen Akun Orang Tua';
    protected static ?string $navigationLabel = 'Manajemen Akun Orang Tua';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 99;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'))
            ->with(['guardian', 'students.classroom', 'institution']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun Login Orang Tua')
                    ->description('Kelola akun login untuk wali murid / orang tua siswa. 1 Akun cukup menaungi seluruh anak yang bersekolah.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap Orang Tua / Wali')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, string $context) {
                                if ($context === 'create' && filled($state)) {
                                    $slug = Str::slug($state, '');
                                    $set('username', 'ortu.' . $slug);
                                    $set('email', 'ortu.' . $slug . '@baitulmakmur.sch.id');
                                }
                            }),

                        Forms\Components\TextInput::make('username')
                            ->label('Username Login')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Akun')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150),

                        Forms\Components\TextInput::make('phone')
                            ->label('No. Telepon / WhatsApp')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('password')
                            ->label('Password Login')
                            ->password()
                            ->revealable()
                            ->default(fn (string $context) => $context === 'create' ? 'password123' : null)
                            ->helperText(fn (string $context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password.' : 'Password default: password123')
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context) => $context === 'create'),

                        Forms\Components\Select::make('institution_id')
                            ->label('Unit / Lembaga')
                            ->relationship('institution', 'name')
                            ->default(2)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Akun Aktif')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Routing Siswa / Anak Kandung')
                    ->description('Hubungkan akun orang tua ini dengan siswa-siswanya. 1 Akun orang tua dapat menaungi 2 anak atau lebih.')
                    ->schema([
                        Forms\Components\Select::make('students')
                            ->label('Pilih Siswa / Anak yang Dinaungi')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Student::withoutGlobalScopes()
                                    ->where('status_siswa', 'aktif')
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Orang Tua')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP / WA')
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('students_list')
                    ->label('Wali Murid Atas Nama Siswa')
                    ->html()
                    ->formatStateUsing(function (User $record) {
                        $students = $record->linked_students;
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
                    }),

                Tables\Columns\TextColumn::make('status_routing')
                    ->label('Status Akun')
                    ->badge()
                    ->state(function (User $record) {
                        return $record->linked_students->isNotEmpty() ? 'Akun Aktif' : 'Menunggu Routing Siswa';
                    })
                    ->color(fn (string $state) => $state === 'Akun Aktif' ? 'success' : 'warning')
                    ->icon(fn (string $state) => $state === 'Akun Aktif' ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                    ->description(function (User $record) {
                        $count = $record->linked_students->count();
                        return $count > 0 ? "{$count} Siswa Terhubung" : 'Belum dirouting anak';
                    }),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Unit / Lembaga')
                    ->badge()
                    ->color('info')
                    ->default('SMP'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Unit / Lembaga')
                    ->relationship('institution', 'name'),

                Tables\Filters\SelectFilter::make('status_routing')
                    ->label('Status Routing')
                    ->options([
                        'aktif' => '✅ Akun Aktif (Sudah Ada Siswa)',
                        'belum' => '⏳ Menunggu Routing (Belum Ada Siswa)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'aktif') {
                            $query->whereHas('students');
                        } elseif ($data['value'] === 'belum') {
                            $query->whereDoesntHave('students');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('routing_siswa')
                    ->label('Routing Siswa')
                    ->icon('heroicon-m-arrows-pointing-in')
                    ->color('primary')
                    ->modalHeading(fn (User $record) => "Routing Anak untuk: {$record->name}")
                    ->modalDescription('Pilih siswa yang merupakan anak dari orang tua ini. Satu akun dapat menaungi 2 anak atau lebih.')
                    ->fillForm(function (User $record) {
                        $guardian = $record->ensureGuardianRecord();
                        return [
                            'student_ids' => StudentGuardian::where('user_id', $record->id)
                                ->orWhere('guardian_id', $guardian->id)
                                ->pluck('student_id')
                                ->toArray(),
                            'relation' => StudentGuardian::where('user_id', $record->id)->first()?->relation ?? 'Ayah',
                        ];
                    })
                    ->form([
                        Forms\Components\Select::make('student_ids')
                            ->label('Pilih Siswa / Anak Kandung')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function () {
                                return Student::withoutGlobalScopes()
                                    ->where('status_siswa', 'aktif')
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
                    ->action(function (User $record, array $data) {
                        $guardian = $record->ensureGuardianRecord();
                        $studentIds = (array) ($data['student_ids'] ?? []);
                        $relation = $data['relation'] ?? 'Ayah';

                        StudentGuardian::where('guardian_id', $guardian->id)
                            ->orWhere('user_id', $record->id)
                            ->delete();

                        foreach ($studentIds as $sid) {
                            StudentGuardian::create([
                                'student_id'  => $sid,
                                'guardian_id' => $guardian->id,
                                'user_id'     => $record->id,
                                'relation'    => $relation,
                            ]);
                        }

                        $record->update(['is_active' => true, 'has_account' => true, 'guardian_id' => $guardian->id]);

                        Notification::make()
                            ->title('Routing Siswa Berhasil')
                            ->body("Akun {$record->name} berhasil dihubungkan dengan " . count($studentIds) . " siswa.")
                            ->success()
                            ->send();
                    }),

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
                        Notification::make()->title('Password Berhasil Direset')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListParentUsers::route('/'),
            'create' => Pages\CreateParentUser::route('/create'),
            'edit'   => Pages\EditParentUser::route('/{record}/edit'),
        ];
    }
}
