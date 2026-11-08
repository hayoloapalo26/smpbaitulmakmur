<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ClassroomResource\Pages;
use App\Models\Classroom;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassroomResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah', 'tata_usaha']);
    }

    protected static ?string $model = Classroom::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $navigationLabel = 'Data Kelas / Rombel';
    protected static ?string $modelLabel = 'Kelas / Rombel';
    protected static ?string $pluralModelLabel = 'Data Kelas / Rombel';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 'Akademik & Kurikulum';
        }
        return 'Administrasi & Data Induk';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 'Setting Wali Kelas & Rombel';
        }
        return 'Data Kelas / Rombel';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kelas & Rombel')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->relationship('institution', 'name')
                            ->label('Lembaga (Khusus Superadmin)')
                            ->visible(fn () => auth()->user()->hasRole('superadmin'))
                            ->required(fn () => auth()->user()->hasRole('superadmin')),

                        Forms\Components\Radio::make('tipe')
                            ->label('Jenis / Kategori Rombel')
                            ->options([
                                Classroom::TIPE_REGULER => '🏫 Kelas Reguler / Rombel Akademik (Wali Kelas)',
                                Classroom::TIPE_QURAN   => '📖 Kelompok / Halaqah Al-Qur\'an (Guru Pengampu Al-Qur\'an)',
                            ])
                            ->default(Classroom::TIPE_REGULER)
                            ->required()
                            ->live()
                            ->columnSpanFull()
                            ->helperText('Penting: Bedakan antara Rombel Akademik (Wali Kelas) dan Halaqah Al-Qur\'an (Guru Pengampu). Guru Al-Qur\'an TIDAK memiliki akses administrasi Wali Kelas.'),

                        Forms\Components\Select::make('preset_quran')
                            ->label('⚡ Pilihan Cepat Rombel Al-Qur\'an (Preset)')
                            ->options([
                                'yanbua'           => '📗 Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                'tahsin'           => '📘 Tahsin (Kualitas & Kaidah Tajwid)',
                                'tahfidz_dasar'    => '📙 Tahfidz Dasar (Hafalan Dasar)',
                                'tahfidz_lanjutan' => '📕 Tahfidz Lanjutan (Hafalan Lanjutan & Mutqin)',
                            ])
                            ->visible(fn (Forms\Get $get) => $get('tipe') === Classroom::TIPE_QURAN)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                match ($state) {
                                    'yanbua' => [
                                        $set('name', 'Yanbu\'a'),
                                        $set('tingkat', 'Yanbu\'a SMP'),
                                    ],
                                    'tahsin' => [
                                        $set('name', 'Tahsin'),
                                        $set('tingkat', 'Tahsin SMP'),
                                    ],
                                    'tahfidz_dasar' => [
                                        $set('name', 'Tahfidz Dasar'),
                                        $set('tingkat', 'Tahfidz Dasar SMP'),
                                    ],
                                    'tahfidz_lanjutan' => [
                                        $set('name', 'Tahfidz Lanjutan'),
                                        $set('tingkat', 'Tahfidz Lanjutan SMP'),
                                    ],
                                    default => null,
                                };
                            })
                            ->helperText('Pilih salah satu spesialisasi untuk mengisi otomatis Nama dan Tingkat Rombel Al-Qur\'an.'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kelas / Kelompok')
                            ->placeholder('Contoh: Kelas 7A / Yanbu\'a / Tahsin / Tahfidz Dasar / Tahfidz Lanjutan')
                            ->datalist([
                                'Yanbu\'a',
                                'Tahsin',
                                'Tahfidz Dasar',
                                'Tahfidz Lanjutan',
                                'Kelas 7A',
                                'Kelas 8A',
                                'Kelas 9A',
                            ])
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tingkat')
                            ->label('Tingkat / Keterangan')
                            ->placeholder('Contoh: 7 / SMP / Tahfidz Dasar SMP')
                            ->datalist([
                                '7',
                                '8',
                                '9',
                                'Yanbu\'a SMP',
                                'Tahsin SMP',
                                'Tahfidz Dasar SMP',
                                'Tahfidz Lanjutan SMP',
                            ])
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('wali_kelas_id')
                            ->label(fn (Forms\Get $get) => $get('tipe') === Classroom::TIPE_QURAN
                                ? '📖 Guru Pengampu Al-Qur\'an (Ustadz Pembimbing)'
                                : '👨‍🏫 Wali Kelas (Homeroom Teacher)')
                            ->options(function (Forms\Get $get) {
                                $isQuran = $get('tipe') === Classroom::TIPE_QURAN;
                                $query = User::where('is_active', true);

                                // Filter by institution if not superadmin
                                if (!auth()->user()->hasRole('superadmin')) {
                                    $query->where(function ($q) {
                                        $q->where('institution_id', auth()->user()->institution_id)
                                          ->orWhereNull('institution_id')
                                          ->orWhere('institution_id', 1);
                                    });
                                }

                                if ($isQuran) {
                                    // Pilihan khusus Guru Al-Qur'an
                                    $query->whereHas('roles', function ($q) {
                                        $q->whereIn('name', User::QURAN_TEACHER_ROLES);
                                    });
                                } else {
                                    // Pilihan Wali Kelas Akademik
                                    $query->whereHas('roles', function ($q) {
                                        $q->whereIn('name', ['guru_mapel', 'wali_kelas', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
                                    });
                                }

                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->disabled(fn () => !auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']))
                            ->helperText(function (Forms\Get $get) {
                                if (!auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin'])) {
                                    return 'Hanya Waka Kurikulum dan Superadmin yang dapat mengubah penetapan ini.';
                                }
                                if ($get('tipe') === Classroom::TIPE_QURAN) {
                                    return '💡 Ustadz/Ustadzah yang dipilih adalah Guru Pengampu Al-Qur\'an dan TIDAK AKAN diberi role Wali Kelas.';
                                }
                                return '💡 Guru yang dipilih akan ditetapkan sebagai Wali Kelas resmi untuk rombel akademik ini.';
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kelas / Kelompok')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipe')
                    ->label('Jenis Rombel')
                    ->badge()
                    ->color(fn (?string $state, Classroom $record): string => $record->isQuran() ? 'warning' : 'primary')
                    ->formatStateUsing(fn (?string $state, Classroom $record): string => $record->isQuran() ? '📖 Kelompok Al-Qur\'an' : '🏫 Kelas Akademik')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('waliKelas.name')
                    ->label('Wali Kelas / Guru Pengampu')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Classroom $record): string => $record->isQuran() ? 'warning' : 'success')
                    ->formatStateUsing(function (?string $state, Classroom $record): string {
                        if (!$state) return 'Belum Ditetapkan';
                        return $record->isQuran()
                            ? "📖 Guru Al-Qur'an: {$state}"
                            : "👨‍🏫 Wali Kelas: {$state}";
                    }),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Jml Siswa')
                    ->badge()
                    ->color(fn (Classroom $record): string => $record->getRealStudentsCount() > 0 ? 'success' : 'gray')
                    ->getStateUsing(fn (Classroom $record): int => $record->getRealStudentsCount())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withCount('students')->orderBy('students_count', $direction);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Filter Jenis Rombel')
                    ->options([
                        Classroom::TIPE_REGULER => '🏫 Kelas Akademik (Wali Kelas)',
                        Classroom::TIPE_QURAN   => '📖 Kelompok Al-Qur\'an (Guru Pengampu)',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('daftar_siswa')
                    ->label('Lihat Siswa')
                    ->icon('heroicon-m-users')
                    ->color('info')
                    ->modalHeading(fn (Classroom $record): string => "Daftar Siswa - {$record->name} (" . ($record->isQuran() ? 'Kelompok Al-Qur\'an' : 'Kelas Akademik') . ")")
                    ->modalDescription(function (Classroom $record): string {
                        $count = $record->getRealStudentsCount();
                        $wali = $record->waliKelas?->name ?? 'Belum Ditentukan';
                        $peran = $record->isQuran() ? 'Guru Pengampu Al-Qur\'an' : 'Wali Kelas';
                        return "Total Siswa: {$count} Santri/Siswa | {$peran}: {$wali}";
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(function (Classroom $record) {
                        $students = $record->getRealStudents();
                        if ($students->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('empty_info')
                                    ->label('Daftar Siswa')
                                    ->content(new \Illuminate\Support\HtmlString('<div class="p-4 text-center text-gray-500 dark:text-gray-400">Belum ada siswa yang terdaftar di kelas/kelompok ini.</div>')),
                            ];
                        }

                        $html = '<div class="space-y-2 max-h-96 overflow-y-auto">';
                        foreach ($students as $idx => $s) {
                            $num = $idx + 1;
                            $nis = $s->nis ?? '-';
                            $rombel = $s->classroom?->name ?? '-';
                            $extra = $record->isQuran()
                                ? "<span class=\"text-xs text-primary-600 bg-primary-50 dark:bg-primary-950/50 px-2 py-0.5 rounded ml-2 font-medium\">Rombel: {$rombel}</span>"
                                : ($s->kelas_quran_label ? "<span class=\"text-xs text-amber-600 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded ml-2 font-medium\">Qur'an: {$s->kelas_quran_label}</span>" : "");

                            $html .= "<div class=\"flex items-center justify-between p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700\">
                                <div>
                                    <span class=\"font-semibold text-gray-900 dark:text-gray-100\">{$num}. {$s->name}</span>
                                    <span class=\"text-xs text-gray-500 dark:text-gray-400 ml-2\">NIS: {$nis}</span>
                                </div>
                                <div>{$extra}</div>
                            </div>";
                        }
                        $html .= '</div>';

                        return [
                            Forms\Components\Placeholder::make('student_list')
                                ->label('Daftar Siswa Terdaftar')
                                ->content(new \Illuminate\Support\HtmlString($html)),
                        ];
                    }),
                Tables\Actions\EditAction::make()
                    ->after(function (Classroom $record) {
                        static::syncTeacherRoles($record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Classroom $record) {
                        if ($record->wali_kelas_id) {
                            static::syncTeacherRoles($record, $record->wali_kelas_id);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function syncTeacherRoles(Classroom $record, ?int $oldTeacherId = null): void
    {
        if ($record->wali_kelas_id) {
            $user = User::withoutGlobalScopes()->find($record->wali_kelas_id);
            if ($user) {
                if ($record->isReguler()) {
                    // Kelas Akademik: berikan role wali_kelas
                    if (!$user->hasRole('wali_kelas')) {
                        $user->assignRole('wali_kelas');
                    }
                } else {
                    // Kelompok Al-Qur'an: JANGAN berikan role wali_kelas!
                    // Jika user tidak memegang kelas reguler lain, cabut role wali_kelas jika ada
                    $hasOtherReguler = Classroom::withoutGlobalScopes()
                        ->where('id', '!=', $record->id)
                        ->where('wali_kelas_id', $user->id)
                        ->where(function ($q) {
                            $q->where('tipe', Classroom::TIPE_REGULER)->orWhereNull('tipe');
                        })
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahsin%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%yanbu%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahfidz%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%quran%'])
                        ->exists();

                    if (!$hasOtherReguler && $user->hasRole('wali_kelas')) {
                        $user->removeRole('wali_kelas');
                    }

                    if (!$user->isQuranTeacher()) {
                        $user->assignRole('guru_alquran');
                    }

                    // Sinkronisasi otomatis ke role spesialis Al-Qur'an
                    $classNameLower = strtolower($record->name . ' ' . $record->tingkat);
                    if (str_contains($classNameLower, 'yanbu')) {
                        if (!$user->hasRole('guru_quran_yanbua')) $user->assignRole('guru_quran_yanbua');
                    } elseif (str_contains($classNameLower, 'tahsin')) {
                        if (!$user->hasRole('guru_quran_tahsin')) $user->assignRole('guru_quran_tahsin');
                    } elseif (str_contains($classNameLower, 'dasar')) {
                        if (!$user->hasRole('guru_quran_tahfidz_dasar')) $user->assignRole('guru_quran_tahfidz_dasar');
                    } elseif (str_contains($classNameLower, 'lanjutan')) {
                        if (!$user->hasRole('guru_quran_tahfidz_lanjutan')) $user->assignRole('guru_quran_tahfidz_lanjutan');
                    }
                }
            }
        }

        // Jika guru lama diganti, pastikan dicek juga
        if ($oldTeacherId && $oldTeacherId != $record->wali_kelas_id) {
            $oldTeacher = User::withoutGlobalScopes()->find($oldTeacherId);
            if ($oldTeacher) {
                $stillHasReguler = Classroom::withoutGlobalScopes()
                    ->where('wali_kelas_id', $oldTeacher->id)
                    ->where(function ($q) {
                        $q->where('tipe', Classroom::TIPE_REGULER)->orWhereNull('tipe');
                    })
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahsin%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%yanbu%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%tahfidz%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%quran%'])
                    ->exists();

                if (!$stillHasReguler && $oldTeacher->hasRole('wali_kelas')) {
                    $oldTeacher->removeRole('wali_kelas');
                }
            }
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}