<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherSubjectClassResource\Pages;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherSubjectClassResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    protected static ?string $model = TeacherSubjectClass::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Setting Guru Mapel';
    protected static ?string $modelLabel = 'Setting Guru Mapel';
    protected static ?string $pluralModelLabel = 'Setting Guru Mapel';
    protected static ?int $navigationSort = 3;

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getEligibleTeachersQuery(?\Illuminate\Database\Eloquent\Builder $query = null, ?User $user = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = $query ?: User::query();
        $user = $user ?: auth()->user();

        $query->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', array_merge([
                    'guru_mapel',
                    'guru_kelas',
                ], User::QURAN_TEACHER_ROLES));
            });

        if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
            $query->where(function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id)
                  ->orWhereNull('institution_id')
                  ->orWhere('institution_id', 1);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Penugasan Guru & Mata Pelajaran')
                    ->description('Tentukan guru pengampu, mata pelajaran yang diajarkan, dan kelas tujuan. 1 guru dapat mengampu lebih dari 1 mata pelajaran.')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'name', modifyQueryUsing: fn ($query) => static::getEligibleTeachersQuery($query))
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $user = auth()->user();
                                $roles = $record->roles->pluck('name')->toArray();
                                $spec = [];
                                if (in_array('guru_quran_yanbua', $roles)) $spec[] = 'Yanbu\'a';
                                if (in_array('guru_quran_tahsin', $roles)) $spec[] = 'Tahsin';
                                if (in_array('guru_quran_tahfidz_dasar', $roles)) $spec[] = 'Tahfidz Dasar';
                                if (in_array('guru_quran_tahfidz_lanjutan', $roles)) $spec[] = 'Tahfidz Lanjutan';
                                if (in_array('guru_alquran', $roles) && empty($spec)) $spec[] = 'Al-Qur\'an';
                                $tag = !empty($spec) ? ' [Guru ' . implode(', ', $spec) . ']' : '';

                                $crossTag = '';
                                if ($user && $record->institution_id && $record->institution_id != $user->institution_id) {
                                    $instName = $record->institution?->name ?? 'Lintas Lembaga';
                                    $crossTag = " [Lintas Lembaga: {$instName}]";
                                }

                                return $record->name . $tag . $crossTag;
                            })
                            ->label('Guru Pengajar')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Mata Pelajaran')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('classroom_id')
                            ->relationship('classroom', 'name', modifyQueryUsing: function ($query) {
                                $user = auth()->user();
                                if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
                                    $query->where('institution_id', $user->institution_id);
                                }
                            })
                            ->label('Kelas / Rombel')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->sortable()
                    ->label('Guru Pengajar')
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('teacher.institution.name')
                    ->label('Asal Lembaga')
                    ->badge()
                    ->color(fn (TeacherSubjectClass $record) => $record->teacher?->institution_id == $record->institution_id ? 'gray' : 'warning')
                    ->formatStateUsing(fn ($state, TeacherSubjectClass $record) => $record->teacher?->institution_id == $record->institution_id ? 'Internal' : "Lintas: {$state}")
                    ->toggleable(),
                Tables\Columns\TextColumn::make('peran_guru')
                    ->label('Rumpun / Spesialisasi')
                    ->state(function (TeacherSubjectClass $record) {
                        $roles = $record->teacher?->roles?->pluck('name')->toArray() ?? [];
                        if (in_array('guru_quran_yanbua', $roles)) return 'Al-Qur\'an (Yanbu\'a)';
                        if (in_array('guru_quran_tahsin', $roles)) return 'Al-Qur\'an (Tahsin)';
                        if (in_array('guru_quran_tahfidz_dasar', $roles)) return 'Tahfidz Dasar';
                        if (in_array('guru_quran_tahfidz_lanjutan', $roles)) return 'Tahfidz Lanjutan';
                        if (in_array('guru_alquran', $roles)) return 'Guru Al-Qur\'an';
                        if (in_array('guru_mapel', $roles)) return 'Guru Mapel';
                        return 'Guru';
                    })
                    ->badge()
                    ->colors([
                        'warning' => fn ($state) => str_contains($state, 'Yanbu\'a'),
                        'info' => fn ($state) => str_contains($state, 'Tahsin'),
                        'success' => fn ($state) => str_contains($state, 'Tahfidz'),
                        'primary' => fn ($state) => $state === 'Guru Mapel' || $state === 'Guru Al-Qur\'an',
                    ]),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable()
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->searchable()
                    ->sortable()
                    ->label('Kelas / Rombel')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_quran')
                    ->label('Rumpun Guru')
                    ->options([
                        'quran' => 'Seluruh Guru Al-Qur\'an',
                        'yanbua' => 'Guru Yanbu\'a',
                        'tahsin' => 'Guru Tahsin',
                        'tahfidz_dasar' => 'Guru Tahfidz Dasar',
                        'tahfidz_lanjutan' => 'Guru Tahfidz Lanjutan',
                        'mapel' => 'Guru Mapel Umum',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (($data['value'] ?? null) === 'quran') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->whereIn('name', \App\Models\User::QURAN_TEACHER_ROLES));
                        } elseif (($data['value'] ?? null) === 'yanbua') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->where('name', 'guru_quran_yanbua'));
                        } elseif (($data['value'] ?? null) === 'tahsin') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->where('name', 'guru_quran_tahsin'));
                        } elseif (($data['value'] ?? null) === 'tahfidz_dasar') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->where('name', 'guru_quran_tahfidz_dasar'));
                        } elseif (($data['value'] ?? null) === 'tahfidz_lanjutan') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->where('name', 'guru_quran_tahfidz_lanjutan'));
                        } elseif (($data['value'] ?? null) === 'mapel') {
                            $query->whereHas('teacher.roles', fn ($r) => $r->where('name', 'guru_mapel'));
                        }
                    }),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Filter Guru')
                    ->relationship('teacher', 'name', modifyQueryUsing: fn ($query) => static::getEligibleTeachersQuery($query))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mapel')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->label('Filter Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('input_nilai')
                    ->label('Input Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (TeacherSubjectClass $record) => url('/staff/input-nilai-mapel?subject_id=' . $record->subject_id . '&classroom_id=' . $record->classroom_id)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeacherSubjectClasses::route('/')];
    }
}
