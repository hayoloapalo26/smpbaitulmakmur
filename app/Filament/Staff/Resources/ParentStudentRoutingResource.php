<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ParentStudentRoutingResource\Pages;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentGuardian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ParentStudentRoutingResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?string $modelLabel = 'Pemetaan Akun Wali Murid';
    protected static ?string $pluralModelLabel = 'Pemetaan Akun Wali Murid';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function canCreate(): bool
    {
        // Pembuatan akun orang tua baru dikelola oleh Superadmin di Manajemen Akun
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $authUser = Auth::user();

        // Hanya tampilkan akun pengguna dengan role orang_tua
        $query->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'));

        if (!$authUser->hasRole('superadmin')) {
            $instId = $authUser->institution_id ?: 2;
            // Tampilkan akun ortu yang terdaftar di lembaga ini, atau yang memiliki anak di lembaga ini
            $query->where(function ($q) use ($instId) {
                $q->where('institution_id', $instId)
                  ->orWhereHas('institutions', fn ($iq) => $iq->where('institutions.id', $instId))
                  ->orWhereIn('id', function ($sub) use ($instId) {
                      $sub->select('sg.user_id')
                          ->from('student_guardians as sg')
                          ->join('students as s', 's.id', '=', 'sg.student_id')
                          ->where('s.institution_id', $instId)
                          ->whereNotNull('sg.user_id');
                  })
                  ->orWhereIn('guardian_id', function ($sub) use ($instId) {
                      $sub->select('sg.guardian_id')
                          ->from('student_guardians as sg')
                          ->join('students as s', 's.id', '=', 'sg.student_id')
                          ->where('s.institution_id', $instId)
                          ->whereNotNull('sg.guardian_id');
                  });
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        $authUser = Auth::user();
        $instId = $authUser?->institution_id ?: 2;
        $isSuperadmin = $authUser?->hasRole('superadmin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Akun Orang Tua')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Unit / Lembaga')
                    ->badge()
                    ->color('info')
                    ->default(fn (User $record) => $record->institution?->name ?? 'Semua Unit')
                    ->visible($isSuperadmin),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Jumlah Anak')
                    ->badge()
                    ->color(fn ($record) => $record->linked_students->count() > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($record) => $record->linked_students->count() . ' Anak')
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->withCount('students')->orderBy('students_count', $direction);
                    }),

                Tables\Columns\TextColumn::make('children_list')
                    ->label('Daftar Anak (Routing Siswa)')
                    ->html()
                    ->formatStateUsing(function (User $record) {
                        $students = $record->linked_students;
                        if ($students->isEmpty()) {
                            return '<span class="text-xs text-rose-500 italic">⚠️ Belum ada anak terhubung (Perlu di-routing TU)</span>';
                        }

                        $badges = [];
                        foreach ($students as $s) {
                            $className = $s->classroom?->name ? " [{$s->classroom->name}]" : '';
                            $badges[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 mr-1 mb-1">'
                                . htmlspecialchars($s->name . $className)
                                . '</span>';
                        }
                        return implode(' ', $badges);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_pemetaan')
                    ->label('Status Pemetaan Anak')
                    ->options([
                        'sudah' => '✅ Sudah Terhubung Siswa',
                        'belum' => '⚠️ Belum Terhubung Siswa',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'sudah') {
                            $query->where(function ($q) {
                                $q->whereIn('id', function ($sub) {
                                    $sub->select('user_id')->from('student_guardians')->whereNotNull('user_id');
                                })->orWhereIn('guardian_id', function ($sub) {
                                    $sub->select('guardian_id')->from('student_guardians')->whereNotNull('guardian_id');
                                });
                            });
                        } elseif ($data['value'] === 'belum') {
                            $query->where(function ($q) {
                                $q->whereNotIn('id', function ($sub) {
                                    $sub->select('user_id')->from('student_guardians')->whereNotNull('user_id');
                                })->where(function ($subQ) {
                                    $subQ->whereNull('guardian_id')
                                         ->orWhereNotIn('guardian_id', function ($sub) {
                                             $sub->select('guardian_id')->from('student_guardians')->whereNotNull('guardian_id');
                                         });
                                });
                            });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('routing_siswa')
                    ->label('Routing / Hubungkan Siswa')
                    ->icon('heroicon-m-arrows-pointing-in')
                    ->color('primary')
                    ->modalHeading(fn (User $record) => "Routing Anak untuk Wali Murid: {$record->name}")
                    ->modalDescription('Pilih siswa yang merupakan anak dari akun orang tua ini. Satu akun dapat menaungi 2 anak atau lebih.')
                    ->modalWidth('2xl')
                    ->fillForm(function (User $record) {
                        $currentStudentIds = $record->linked_students->pluck('id')->toArray();
                        $firstPivot = StudentGuardian::where(function ($q) use ($record) {
                            $q->where('user_id', $record->id);
                            if ($record->guardian_id) {
                                $q->orWhere('guardian_id', $record->guardian_id);
                            }
                        })->first();

                        return [
                            'student_ids' => $currentStudentIds,
                            'relation' => $firstPivot?->relation ?? 'Ayah',
                        ];
                    })
                    ->form([
                        Forms\Components\Select::make('student_ids')
                            ->label('Pilih Siswa / Anak Kandung')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function () use ($authUser, $isSuperadmin, $instId) {
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
                            ->helperText('Centang/pilih satu atau lebih siswa yang merupakan anak dari orang tua ini.'),

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

                        // Bersihkan pemetaan sebelumnya untuk user/guardian ini
                        StudentGuardian::where(function ($q) use ($record, $guardian) {
                            $q->where('user_id', $record->id);
                            if ($guardian) {
                                $q->orWhere('guardian_id', $guardian->id);
                            }
                        })->delete();

                        // Masukkan pemetaan baru
                        foreach ($studentIds as $sid) {
                            StudentGuardian::create([
                                'student_id'  => $sid,
                                'guardian_id' => $guardian->id,
                                'user_id'     => $record->id,
                                'relation'    => $relation,
                            ]);
                        }

                        Notification::make()
                            ->title('Pemetaan Wali Murid Berhasil')
                            ->body("Akun {$record->name} berhasil dihubungkan dengan " . count($studentIds) . " anak.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('clear_routing')
                    ->label('Lepas Hubungan')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Lepas Seluruh Hubungan Anak?')
                    ->modalDescription('Seluruh kaitan data siswa dengan akun orang tua ini akan dilepas. Akun orang tua tetap ada di sistem.')
                    ->visible(fn (User $record) => $record->linked_students->isNotEmpty())
                    ->action(function (User $record) {
                        StudentGuardian::where(function ($q) use ($record) {
                            $q->where('user_id', $record->id);
                            if ($record->guardian_id) {
                                $q->orWhere('guardian_id', $record->guardian_id);
                            }
                        })->delete();

                        Notification::make()
                            ->title('Hubungan Berhasil Dilepas')
                            ->body("Seluruh kaitan siswa dengan akun {$record->name} telah dilepas.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageParentStudentRoutings::route('/'),
        ];
    }
}
