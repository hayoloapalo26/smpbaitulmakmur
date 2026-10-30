<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentQuranPlacementResource\Pages;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StudentQuranPlacementResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $slug = 'penempatan-siswa-quran';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Akademik & Kurikulum';

    protected static ?string $navigationLabel = 'Penempatan Siswa Al-Qur\'an';

    protected static ?string $modelLabel = 'Penempatan Siswa Al-Qur\'an';

    protected static ?string $pluralModelLabel = 'Penempatan Siswa Al-Qur\'an';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'koordinator_madin', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'koordinator_madin', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['classroom', 'guruQuran']);
        $user = Auth::user();

        // Isolasi lembaga jika bukan superadmin
        if ($user && !$user->hasRole('superadmin') && $user->institution_id != 1) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'institution_id')) {
                $query->where('students.institution_id', $user->institution_id);
            } else {
                $query->whereHas('classroom', fn ($cq) => $cq->where('institution_id', $user->institution_id));
            }
        }

        return $query;
    }

    public static function getGuruQuranOptions(?int $institutionId = null): array
    {
        $query = User::query()
            ->when($institutionId && $institutionId != 1, fn ($q) => $q->where(function ($sub) use ($institutionId) {
                $sub->where('institution_id', $institutionId)
                    ->orWhere('institution_id', 1)
                    ->orWhereNull('institution_id')
                    ->orWhereHas('institutions', fn ($iq) => $iq->where('institutions.id', $institutionId));
            }))
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', User::QURAN_TEACHER_ROLES))
                  ->orWhere('has_account', true);
            });

        $users = $query->orderBy('name')->get();

        // Prioritaskan guru dengan role spesialis Al-Qur'an
        $specialistList = [];
        $generalList = [];

        foreach ($users as $u) {
            $roles = $u->getRoleNames()->filter(fn ($r) => in_array($r, User::QURAN_TEACHER_ROLES))->values();
            $crossTag = ($u->institution_id == 1) ? ' [Yayasan / Lintas Lembaga]' : '';
            if ($roles->isNotEmpty()) {
                $labels = $roles->map(fn ($r) => User::getQuranTeacherRoleLabels()[$r] ?? $r)->implode(', ');
                $specialistList[$u->id] = "{$u->name} [{$labels}]{$crossTag}";
            } else {
                $generalList[$u->id] = "{$u->name} (Guru){$crossTag}";
            }
        }

        return !empty($specialistList) ? ($specialistList + $generalList) : $generalList;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Student $record): string => 'NIS: ' . ($record->nis ?? '-') . ' | NISN: ' . ($record->nisn ?? '-')),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas Reguler')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Belum Masuk Kelas'),

                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (strtoupper($state ?? '')) {
                        'L', 'LAKI-LAKI' => 'L',
                        'P', 'PEREMPUAN' => 'P',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match (strtoupper($state ?? '')) {
                        'L', 'LAKI-LAKI' => 'info',
                        'P', 'PEREMPUAN' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('kelas_quran')
                    ->label('Kelas Al-Qur\'an')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Student $record) => $record->kelas_quran_label ?? '⚠️ Belum Ditempatkan')
                    ->color(fn (?string $state): string => match ($state) {
                        'yanbua'           => 'purple',
                        'tahsin'           => 'warning',
                        'tahfidz_dasar'    => 'success',
                        'tahfidz_lanjutan' => 'info',
                        default            => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('guruQuran.name')
                    ->label('Guru Pengampu')
                    ->description(fn (Student $record): ?string => $record->guruQuran?->getQuranSpecializationLabel())
                    ->placeholder('⚠️ Belum Ditentukan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_penempatan_quran')
                    ->label('Tgl Penempatan')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('catatan_penempatan_quran')
                    ->label('Catatan Penempatan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('status_penempatan')
                    ->label('Status Penempatan')
                    ->options([
                        'belum' => '⚠️ Belum Ditempatkan',
                        'sudah' => '✅ Sudah Ditempatkan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (($data['value'] ?? null) === 'belum') {
                            $query->whereNull('kelas_quran');
                        } elseif (($data['value'] ?? null) === 'sudah') {
                            $query->whereNotNull('kelas_quran');
                        }
                    }),

                SelectFilter::make('kelas_quran')
                    ->label('Program Al-Qur\'an')
                    ->options([
                        'yanbua'           => 'Yanbu\'a',
                        'tahsin'           => 'Tahsin',
                        'tahfidz_dasar'    => 'Tahfidz Dasar',
                        'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
                    ]),

                SelectFilter::make('classroom_id')
                    ->label('Kelas Reguler')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('guru_quran_id')
                    ->label('Guru Pengampu')
                    ->options(fn () => static::getGuruQuranOptions(Auth::user()?->institution_id))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('tempatkan')
                    ->label(fn (Student $record): string => $record->kelas_quran ? 'Ubah Penempatan' : 'Tempatkan Al-Qur\'an')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color(fn (Student $record): string => $record->kelas_quran ? 'warning' : 'primary')
                    ->modalHeading('Penempatan Siswa ke Kelas Al-Qur\'an')
                    ->modalDescription('Tentukan kelompok Al-Qur\'an dan guru pengampu untuk siswa ini. Nilai dan progres hafalan akan otomatis terhubung ke akun guru Al-Qur\'an yang dipilih.')
                    ->fillForm(fn (Student $record): array => [
                        'kelas_quran'              => $record->kelas_quran,
                        'guru_quran_id'            => $record->guru_quran_id,
                        'tanggal_penempatan_quran' => $record->tanggal_penempatan_quran?->toDateString() ?? now()->toDateString(),
                        'catatan_penempatan_quran' => $record->catatan_penempatan_quran,
                    ])
                    ->form([
                        Forms\Components\Placeholder::make('student_info')
                            ->label('Data Siswa Terpilih')
                            ->content(fn (Student $record): string => "{$record->name} | Rombel: " . ($record->classroom?->name ?? 'Belum Ditentukan') . " | NIS: " . ($record->nis ?? '-')),

                        Forms\Components\Select::make('kelas_quran')
                            ->label('1. Program / Kelompok Al-Qur\'an')
                            ->options([
                                'yanbua'           => '1. Al-Qur\'an (Yanbu\'a) - Metode Baca Tulis & Makhraj',
                                'tahsin'           => '2. Al-Qur\'an (Tahsin) - Kualitas & Kaidah Tajwid',
                                'tahfidz_dasar'    => '3. Al-Qur\'an (Tahfidz Dasar) - Juz 30 / Juz \'Amma',
                                'tahfidz_lanjutan' => '4. Al-Qur\'an (Tahfidz Lanjutan) - Juz 1 s/d 29',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('guru_quran_id')
                            ->label('2. Guru Al-Qur\'an Pengampu')
                            ->options(fn (Student $record) => static::getGuruQuranOptions($record->institution_id ?? Auth::user()?->institution_id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Guru yang dipilih akan otomatis menerima siswa ini di dropdown penilaian Al-Qur\'an miliknya.'),

                        Forms\Components\DatePicker::make('tanggal_penempatan_quran')
                            ->label('3. Tanggal Mulai Penempatan')
                            ->default(now()->toDateString())
                            ->required(),

                        Forms\Components\Textarea::make('catatan_penempatan_quran')
                            ->label('4. Catatan Khusus Penempatan (Opsional)')
                            ->placeholder('Contoh: Penempatan awal di Yanbu\'a Jilid 2, Target Juz 30 semester ini.')
                            ->rows(2),
                    ])
                    ->action(function (Student $record, array $data): void {
                        $record->update([
                            'kelas_quran'              => $data['kelas_quran'],
                            'guru_quran_id'            => $data['guru_quran_id'],
                            'tanggal_penempatan_quran' => $data['tanggal_penempatan_quran'],
                            'catatan_penempatan_quran' => $data['catatan_penempatan_quran'] ?? null,
                        ]);

                        $guru = User::find($data['guru_quran_id']);
                        $guruName = $guru?->name ?? 'Guru Pengampu';

                        Notification::make()
                            ->title('Siswa Berhasil Ditempatkan')
                            ->body("{$record->name} berhasil ditempatkan ke kelas {$record->kelas_quran_label} dengan guru {$guruName}.")
                            ->success()
                            ->send();
                    }),

                Action::make('reset_penempatan')
                    ->label('Reset')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Student $record): bool => !empty($record->kelas_quran))
                    ->requiresConfirmation()
                    ->modalHeading('Reset Penempatan Al-Qur\'an')
                    ->modalDescription(fn (Student $record): string => "Apakah Anda yakin ingin membatalkan penempatan Al-Qur'an untuk {$record->name}? Siswa ini akan kembali berstatus Belum Ditempatkan.")
                    ->action(function (Student $record): void {
                        $record->update([
                            'kelas_quran'              => null,
                            'guru_quran_id'            => null,
                            'tanggal_penempatan_quran' => null,
                            'catatan_penempatan_quran' => null,
                        ]);

                        Notification::make()
                            ->title('Penempatan Direset')
                            ->body("Penempatan Al-Qur'an untuk {$record->name} telah dikosongkan.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('bulk_tempatkan')
                        ->label('Tempatkan Siswa Terpilih (Bulk)')
                        ->icon('heroicon-m-user-group')
                        ->color('success')
                        ->modalHeading('Penempatan Massal Siswa ke Kelas Al-Qur\'an')
                        ->modalDescription('Tempatkan semua siswa yang dipilih ke program Al-Qur\'an dan Guru Pengampu yang sama.')
                        ->form([
                            Forms\Components\Select::make('kelas_quran')
                                ->label('1. Program / Kelompok Al-Qur\'an')
                                ->options([
                                    'yanbua'           => '1. Al-Qur\'an (Yanbu\'a) - Metode Baca Tulis & Makhraj',
                                    'tahsin'           => '2. Al-Qur\'an (Tahsin) - Kualitas & Kaidah Tajwid',
                                    'tahfidz_dasar'    => '3. Al-Qur\'an (Tahfidz Dasar) - Juz 30 / Juz \'Amma',
                                    'tahfidz_lanjutan' => '4. Al-Qur\'an (Tahfidz Lanjutan) - Juz 1 s/d 29',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('guru_quran_id')
                                ->label('2. Guru Al-Qur\'an Pengampu')
                                ->options(fn () => static::getGuruQuranOptions(Auth::user()?->institution_id))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Seluruh siswa yang dipilih akan masuk ke daftar nilai guru ini.'),

                            Forms\Components\DatePicker::make('tanggal_penempatan_quran')
                                ->label('3. Tanggal Mulai Penempatan')
                                ->default(now()->toDateString())
                                ->required(),

                            Forms\Components\Textarea::make('catatan_penempatan_quran')
                                ->label('4. Catatan Penempatan Massal (Opsional)')
                                ->placeholder('Contoh: Penempatan rombongan kelas 7A ke Tahfidz Dasar.')
                                ->rows(2),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'kelas_quran'              => $data['kelas_quran'],
                                    'guru_quran_id'            => $data['guru_quran_id'],
                                    'tanggal_penempatan_quran' => $data['tanggal_penempatan_quran'],
                                    'catatan_penempatan_quran' => $data['catatan_penempatan_quran'] ?? null,
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title('Penempatan Massal Berhasil')
                                ->body("Sebanyak {$count} siswa berhasil ditempatkan ke kelas Al-Qur'an.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentQuranPlacements::route('/'),
        ];
    }
}
