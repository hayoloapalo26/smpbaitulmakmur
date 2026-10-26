<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentAchievementResource\Pages;
use App\Models\StudentAchievement;
use Filament\Forms;
use Filament\Forms\Form;
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
    protected static ?string $modelLabel = 'Prestasi Siswa';
    protected static ?string $pluralModelLabel = 'Prestasi Siswa';

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
                  });
            });
        }

        if ($user->hasRole('wali_kelas') && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            $classroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereHas('student', function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds);
            });
        }

        return $query;
    }

    

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Siswa & Prestasi')
                    ->description('Input data prestasi siswa sesuai dengan lembaga masing-masing.')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Pilih Siswa Berprestasi')
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
                                        $query->where('institution_id', $user->institution_id);
                                    }
                                    return $query->with('classroom')->orderBy('name');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Student $record) => "{$record->name} " . ($record->classroom ? "({$record->classroom->name})" : "") . " - NIS: {$record->nis}")
                            ->searchable(['name', 'nis'])
                            ->preload()
                            ->required()
                            ->helperText('Hanya menampilkan data siswa lembaga Anda yang telah diinput oleh Tata Usaha (TU).')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Prestasi')
                            ->options([
                                'akademik' => 'Akademik (Sains, Matematika, Lomba Mata Pelajaran, Riset/KIR, dll)',
                                'non_akademik' => 'Non-Akademik (Seni, Olahraga, Pidato/Storytelling, Pramuka, dll)',
                                'religius' => 'Religius (Tahfidz, Tahsin, MTQ/MHQ, Da\'i/Ceramah, Kaligrafi, Keagamaan, dll)',
                            ])
                            ->default('religius')
                            ->required(),

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
                            ->placeholder('Contoh: Juara 1 MHQ Tahfidz Juz 30 Tingkat Kota')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Perolehan Prestasi')
                            ->default(now())
                            ->required(),

                        Forms\Components\FileUpload::make('sertifikat')
                            ->label('Unggah Sertifikat / Piagam / Bukti (Opsional)')
                            ->directory('prestasi_sertifikat')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->nullable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Deskripsi Tambahan')
                            ->placeholder('Tambahkan keterangan penyelenggara lomba, lokasi, atau catatan pendukung lainnya...')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('input_by')->default(Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'akademik' => 'info',
                        'religius' => 'success',
                        'non_akademik' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'akademik' => 'Akademik',
                        'religius' => 'Religius',
                        'non_akademik' => 'Non-Akademik',
                        default => ucwords(str_replace('_', ' ', $state)),
                    }),
                Tables\Columns\TextColumn::make('nama_prestasi')
                    ->label('Nama Prestasi')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori Prestasi')
                    ->options([
                        'akademik' => 'Akademik',
                        'non_akademik' => 'Non-Akademik',
                        'religius' => 'Religius (Keagamaan / Tahfidz / Tahsin)',
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentAchievements::route('/'),
        ];
    }
}
