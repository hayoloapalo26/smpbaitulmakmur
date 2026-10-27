<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentAchievementResource\Pages;
use App\Models\StudentAchievement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentAchievementResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah', 'wali_kelas', 'guru_kelas', 'tata_usaha', 'bk']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);
    }

    protected static ?string $model = StudentAchievement::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Prestasi Siswa & Kelas';
    protected static ?string $pluralModelLabel = 'Prestasi Siswa & Kelas';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Isolasi ketat antar lembaga: user non-superadmin & non-yayasan hanya melihat prestasi dari lembaganya
        if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
            $query->where(function ($q) use ($user) {
                $q->where('student_achievements.institution_id', $user->institution_id)
                  ->orWhereHas('student', function ($sq) use ($user) {
                      $sq->where('students.institution_id', $user->institution_id);
                  })
                  ->orWhereHas('classroom', function ($cq) use ($user) {
                      $cq->where('classrooms.institution_id', $user->institution_id);
                  });
            });
        }

        if ($user->hasRole('wali_kelas') && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            $classroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds)
                  ->orWhereHas('student', function ($sq) use ($classroomIds) {
                      $sq->whereIn('classroom_id', $classroomIds);
                  });
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Lingkup & Sasaran Prestasi')
                    ->description('Tentukan apakah prestasi diraih secara perorangan (individual) atau prestasi kelas/kelompok.')
                    ->schema([
                        Forms\Components\Radio::make('lingkup')
                            ->label('Lingkup Prestasi')
                            ->options([
                                'individual' => 'Individual (Perorangan Siswa)',
                                'kelas' => 'Prestasi Kelas (Satu Rombel / Kelompok)',
                            ])
                            ->default('individual')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state === 'kelas') {
                                    $set('kategori', 'prestasi_kelas');
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Pilih Rombongan Belajar / Kelas')
                            ->relationship(
                                name: 'classroom',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
                                        $query->where('institution_id', $user->institution_id);
                                    }
                                    return $query->orderBy('name');
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get) => $get('lingkup') === 'kelas')
                            ->required(fn (Get $get) => $get('lingkup') === 'kelas')
                            ->helperText('Pilih kelas yang meraih prestasi.')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('student_id')
                            ->label(fn (Get $get) => $get('lingkup') === 'kelas' ? 'Perwakilan Siswa / Ketua Kelas (Opsional)' : 'Pilih Siswa Berprestasi')
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Get $get) use ($user) {
                                    if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
                                        $query->where('institution_id', $user->institution_id);
                                    }
                                    if ($get('lingkup') === 'kelas' && $get('classroom_id')) {
                                        $query->where('classroom_id', $get('classroom_id'));
                                    }
                                    return $query->with('classroom')->orderBy('name');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Student $record) => "{$record->name} " . ($record->classroom ? "({$record->classroom->name})" : "") . " - NIS: {$record->nis}")
                            ->searchable(['name', 'nis'])
                            ->preload()
                            ->required(fn (Get $get) => $get('lingkup') !== 'kelas')
                            ->helperText(fn (Get $get) => $get('lingkup') === 'kelas' ? 'Opsional: Dapat diisi nama ketua kelas atau perwakilan siswa penerima piagam.' : 'Pilih siswa peraih prestasi yang telah terdaftar.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Kategori Prestasi & Detail Kejuaraan')
                    ->description('Kategorikan prestasi kesiswaan: Prestasi Non-Akademik, Prestasi Kelas (Religius, Karakter, Adab), atau Prestasi Akademik.')
                    ->schema([
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Prestasi')
                            ->options([
                                'non_akademik' => 'Prestasi Non-Akademik (Seni, Olahraga, Pidato, Pramuka, dll)',
                                'prestasi_kelas' => 'Prestasi Kelas (Religius, Karakter, Adab)',
                                'akademik' => 'Prestasi Akademik (Sains, Matematika, Lomba Mapel, KIR, dll)',
                            ])
                            ->default('non_akademik')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state === 'prestasi_kelas') {
                                    $set('sub_kategori', 'religius');
                                } else {
                                    $set('sub_kategori', null);
                                }
                            }),

                        Forms\Components\Select::make('sub_kategori')
                            ->label('Sub-Kategori Prestasi Kelas')
                            ->options([
                                'religius' => 'Prestasi Religius (Tahfidz, Sholat Berjamaah, Tadarus, Doa, dll)',
                                'karakter' => 'Prestasi Karakter (Disiplin, Gotong Royong, Kejujuran, Kepemimpinan)',
                                'adab' => 'Prestasi Adab (Sopan Santun, Adab Guru & Teman, Akhlakul Karimah, Kebersihan)',
                            ])
                            ->visible(fn (Get $get) => $get('kategori') === 'prestasi_kelas')
                            ->required(fn (Get $get) => $get('kategori') === 'prestasi_kelas')
                            ->helperText('Pilih fokus prestasi kelas: Religius, Karakter, atau Adab.'),

                        Forms\Components\Select::make('tingkat')
                            ->label('Tingkat Prestasi')
                            ->options([
                                'Sekolah / Gugus' => 'Sekolah / Gugus',
                                'Kecamatan' => 'Kecamatan',
                                'Kota / Kabupaten' => 'Kota / Kabupaten',
                                'Provinsi' => 'Provinsi',
                                'Nasional' => 'Nasional',
                                'Internasional' => 'Internasional',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('nama_prestasi')
                            ->label('Nama Prestasi / Juara')
                            ->placeholder('Contoh: Juara 1 Kelas Paling Beradab & Bersih Semester Ganjil')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Perolehan Prestasi')
                            ->default(now())
                            ->required(),

                        Forms\Components\FileUpload::make('sertifikat')
                            ->label('Unggah Sertifikat / Piagam / Bukti Foto (Opsional)')
                            ->directory('prestasi_sertifikat')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->nullable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Deskripsi Tambahan')
                            ->placeholder('Tambahkan keterangan penyelenggara lomba, lokasi, atau kriteria penilaian...')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('input_by')->default(Auth::id()),
                    ])->columns(2),

                Forms\Components\Section::make('Penghargaan & Reward Kesiswaan')
                    ->description('Wajib dicatat jika prestasi disertai dengan reward akademik atau reward religi dari sekolah/kesiswaan.')
                    ->schema([
                        Forms\Components\Select::make('jenis_reward')
                            ->label('Jenis Reward Kesiswaan')
                            ->options([
                                'reward_religi' => 'Reward Religi (Misal: Mushaf Al-Qur\'an, Doorprize Umroh/Ziarah, dll)',
                                'reward_akademik' => 'Reward Akademik (Misal: Beasiswa SPP, Piagam Juara, Kitab/Buku, dll)',
                                'tanpa_reward' => 'Tanpa Reward Khusus (Hanya Piagam / Pencatatan Standar)',
                            ])
                            ->default('tanpa_reward')
                            ->live()
                            ->helperText('Pilih apakah prestasi ini mendapatkan Reward Religi atau Reward Akademik.'),

                        Forms\Components\TextInput::make('reward_detail')
                            ->label('Bentuk / Rincian Reward yang Diterima')
                            ->placeholder('Contoh: Mushaf Al-Qur\'an Tajwid Warna + Bebas SPP 1 Bulan')
                            ->visible(fn (Get $get) => in_array($get('jenis_reward'), ['reward_religi', 'reward_akademik']))
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lingkup')
                    ->label('Lingkup')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'kelas' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'kelas' => 'Kelas',
                        default => 'Individual',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sasaran')
                    ->label('Siswa / Kelas')
                    ->state(function (StudentAchievement $record): string {
                        if ($record->lingkup === 'kelas' && empty($record->student_id)) {
                            return $record->classroom ? "Kelas {$record->classroom->name}" : 'Prestasi Kelas';
                        }
                        $name = $record->student?->name ?? '-';
                        if ($record->classroom) {
                            $name .= " ({$record->classroom->name})";
                        }
                        return $name;
                    })
                    ->description(fn (StudentAchievement $record): ?string => $record->student ? "NIS: {$record->student->nis}" : ($record->lingkup === 'kelas' ? 'Semua Siswa di Rombel' : null))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%");
                        })->orWhereHas('classroom', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'akademik' => 'info',
                        'prestasi_kelas', 'religius' => 'success',
                        'non_akademik' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (string $state, StudentAchievement $record): string {
                        if ($state === 'prestasi_kelas') {
                            $sub = match ($record->sub_kategori) {
                                'religius' => 'Religius',
                                'karakter' => 'Karakter',
                                'adab' => 'Adab',
                                default => 'Kelas',
                            };
                            return "Prestasi Kelas ({$sub})";
                        }
                        return match ($state) {
                            'akademik' => 'Prestasi Akademik',
                            'non_akademik' => 'Prestasi Non-Akademik',
                            'religius' => 'Prestasi Religius',
                            default => ucwords(str_replace('_', ' ', $state)),
                        };
                    }),

                Tables\Columns\TextColumn::make('nama_prestasi')
                    ->label('Nama Prestasi')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('jenis_reward')
                    ->label('Reward')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'reward_religi' => 'success',
                        'reward_akademik' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'reward_religi' => 'Reward Religi',
                        'reward_akademik' => 'Reward Akademik',
                        default => '-',
                    })
                    ->description(fn (StudentAchievement $record): ?string => $record->reward_detail),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lingkup')
                    ->label('Lingkup Prestasi')
                    ->options([
                        'individual' => 'Individual (Perorangan Siswa)',
                        'kelas' => 'Prestasi Kelas (Kelompok)',
                    ]),

                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori Prestasi')
                    ->options([
                        'non_akademik' => 'Prestasi Non-Akademik',
                        'prestasi_kelas' => 'Prestasi Kelas',
                        'akademik' => 'Prestasi Akademik',
                        'religius' => 'Prestasi Religius (Legacy)',
                    ]),

                Tables\Filters\SelectFilter::make('sub_kategori')
                    ->label('Sub-Kategori Kelas')
                    ->options([
                        'religius' => 'Religius',
                        'karakter' => 'Karakter',
                        'adab' => 'Adab',
                    ]),

                Tables\Filters\SelectFilter::make('jenis_reward')
                    ->label('Reward Kesiswaan')
                    ->options([
                        'reward_religi' => 'Reward Religi',
                        'reward_akademik' => 'Reward Akademik',
                        'tanpa_reward' => 'Tanpa Reward Khusus',
                    ]),

                Tables\Filters\SelectFilter::make('tingkat')
                    ->options([
                        'Sekolah / Gugus' => 'Sekolah / Gugus',
                        'Kecamatan' => 'Kecamatan',
                        'Kota / Kabupaten' => 'Kota / Kabupaten',
                        'Provinsi' => 'Provinsi',
                        'Nasional' => 'Nasional',
                        'Internasional' => 'Internasional',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ManageStudentAchievements::route('/'),
        ];
    }
}
