<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReadingQualityScoreResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Guru Al-Qur'an menggunakan 1 fitur terintegrasi satu pintu "Penilaian & Capaian Al-Qur'an",
        // sehingga menu terpisah ini dinonaktifkan dari navigasi mereka agar tidak dua kali kerja.
        if ($user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']));
    }

    protected static ?string $model = ReadingQualityScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Skor Kualitas Bacaan & Tahsin';
    protected static ?string $pluralModelLabel = 'Skor Kualitas Bacaan & Tahsin';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('koordinator_madin')) {
            return 'Madrasah Diniyah (Madin)';
        }
        if ($user?->isQuranTeacher()) {
            return 'Fitur Guru Al-Qur\'an';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::id()),

                Forms\Components\Placeholder::make('info_kelas_otomatis')
                    ->label('Kelompok / Kelas Al-Qur\'an Binaan')
                    ->content(function () {
                        $user = Auth::user();
                        $label = $user?->getQuranSpecializationLabel() ?? 'Al-Qur\'an';
                        $teacherName = $user?->name ?? 'Guru Pengampu';
                        return new \Illuminate\Support\HtmlString("
                            <div class='flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 font-bold text-xs'>
                                <span class='text-base'>📖</span>
                                <div>
                                    <div>Kelas {$label} • Dibina oleh: {$teacherName}</div>
                                    <div class='text-[10px] text-amber-600 dark:text-amber-400 font-normal'>Daftar santri otomatis tersaring dari penempatan Waka Kurikulum (tanpa perlu memilih kelas).</div>
                                </div>
                            </div>
                        ");
                    })
                    ->visible(fn () => Auth::user()?->isQuranTeacher() ?? false),

                Forms\Components\Select::make('program')
                    ->label('1. Kelompok / Kelas Al-Qur\'an')
                    ->options([
                        'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                        'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                        'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                        'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                        'tahfidz'          => '📖 Kelas Tahfidz Al-Qur\'an (Umum)',
                    ])
                    ->default(function () {
                        $user = Auth::user();
                        if ($user?->isQuranTeacher()) {
                            return $user->getQuranSpecializationKey() ?? 'tahsin';
                        }
                        return 'tahsin';
                    })
                    ->required()
                    ->live()
                    ->dehydrated(true)
                    ->hidden(fn () => Auth::user()?->isQuranTeacher() ?? false)
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null))
                    ->helperText('Pilih kelas Al-Qur\'an untuk memfilter siswa sesuai penempatan Waka Kurikulum.'),

                Forms\Components\Select::make('classroom_id')
                    ->hidden(),

                Forms\Components\Select::make('student_id')
                    ->label('Nama Siswa (Lengkap Dengan Status Kelas Al-Qur\'an)')
                    ->options(function (Forms\Get $get) {
                        $user = Auth::user();
                        if (!$user) return [];
                        $classroomId = $get('classroom_id');
                        $selectedProgram = $get('program');

                        if ($user->isQuranTeacher()) {
                            $selectedProgram = $user->getQuranSpecializationKey();
                        }

                        $query = Student::query()->with(['classroom', 'guruQuran']);

                        if ($user->isQuranTeacher() && !$user->hasRole('superadmin')) {
                            $query->where(function ($q) use ($user, $selectedProgram) {
                                $q->where('guru_quran_id', $user->id);
                                if ($selectedProgram) {
                                    $q->orWhere('kelas_quran', $selectedProgram);
                                }
                            });
                        } elseif ($selectedProgram && in_array($selectedProgram, ['tahsin', 'yanbua', 'tahfidz_dasar', 'tahfidz_lanjutan'])) {
                            $query->where('kelas_quran', $selectedProgram);
                        } elseif (!empty($classroomId)) {
                            $query->where('classroom_id', $classroomId);
                        }

                        $students = $query->orderBy('name')->get();

                        // Fallback aman jika belum ada penempatan:
                        if ($students->isEmpty()) {
                            if ($user->isQuranTeacher()) {
                                $students = Student::where('guru_quran_id', $user->id)->with(['classroom', 'guruQuran'])->orderBy('name')->get();
                            } else {
                                $students = Student::with(['classroom', 'guruQuran'])->orderBy('name')->get();
                            }
                        }

                        return $students->mapWithKeys(function ($s) {
                            $quranLabel = $s->kelas_quran_label 
                                ? " • [Kelas Al-Qur'an: {$s->kelas_quran_label}]" 
                                : " • [⚠️ Belum Ada Kelas Al-Qur'an]";
                            $guru = $s->guruQuran?->name ? " (Guru: {$s->guruQuran->name})" : '';
                            $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                            return [
                                $s->id => "{$s->name}{$nis}{$quranLabel}{$guru}"
                            ];
                        })->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        $user = Auth::user();
                        $query = Student::query()->with(['classroom', 'guruQuran'])
                            ->where(function ($sq) use ($search) {
                                $sq->where('name', 'like', "%{$search}%")
                                   ->orWhere('nis', 'like', "%{$search}%");
                            });

                        if ($user?->isQuranTeacher() && !$user->hasRole('superadmin')) {
                            $spec = $user->getQuranSpecializationKey();
                            $query->where(function ($q) use ($user, $spec) {
                                $q->where('guru_quran_id', $user->id);
                                if ($spec) {
                                    $q->orWhere('kelas_quran', $spec);
                                }
                            });
                        }

                        return $query->orderBy('name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($s) {
                                $quranLabel = $s->kelas_quran_label 
                                    ? " • [Kelas Al-Qur'an: {$s->kelas_quran_label}]" 
                                    : " • [⚠️ Belum Ada Kelas Al-Qur'an]";
                                $guru = $s->guruQuran?->name ? " (Guru: {$s->guruQuran->name})" : '';
                                $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                                return [
                                    $s->id => "{$s->name}{$nis}{$quranLabel}{$guru}"
                                ];
                            })->toArray();
                    })
                    ->getOptionLabelUsing(function ($value) {
                        if (!$value) return null;
                        $s = Student::with(['classroom', 'guruQuran'])->find($value);
                        if (!$s) return null;
                        $quranLabel = $s->kelas_quran_label 
                            ? " • [Kelas Al-Qur'an: {$s->kelas_quran_label}]" 
                            : " • [⚠️ Belum Ada Kelas Al-Qur'an]";
                        $guru = $s->guruQuran?->name ? " (Guru: {$s->guruQuran->name})" : '';
                        $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                        return "{$s->name}{$nis}{$quranLabel}{$guru}";
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $student = Student::with(['classroom', 'guruQuran'])->find($state);
                            if ($student?->kelas_quran) {
                                $set('program', $student->kelas_quran);
                            }
                            if ($student?->classroom_id) {
                                $set('classroom_id', $student->classroom_id);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('jilid_halaman')
                    ->label('4. Jilid / Halaman / Juz Penilaian (Opsional)')
                    ->placeholder('Contoh: Jilid 3 Hal. 15 / Juz 30 Surah An-Naba')
                    ->helperText('Catatan materi yang dinilai (misal Jilid Yanbu\'a atau surat yang diuji).'),

                Forms\Components\TextInput::make('scale_score')
                    ->label('5. Skor Kualitas Bacaan (0 - 100)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required()
                    ->prefix('Skor:')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state !== null && $state !== '') {
                            $scale = Grade::determineScale((float)$state);
                            $set('predikat', $scale['predikat']);
                        }
                    })
                    ->helperText(function (Forms\Get $get) {
                        $score = $get('scale_score');
                        if ($score === null || $score === '') {
                            return '💡 Skala Nilai: 100 (Excellent) | 90 - 99 (Sangat Baik) | 80 - 89 (Baik) | 70 - 79 (Cukup Baik) | < 70 (Perlu Pembinaan).';
                        }
                        $scale = Grade::determineScale((float)$score);
                        if ($scale['is_tuntas']) {
                            return "✅ Predikat: {$scale['label']} (Tuntas).";
                        }
                        return "⚠️ Predikat: {$scale['label']} (Perlu Pembinaan Intensif).";
                    }),

                Forms\Components\Hidden::make('predikat'),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('6. Tanggal Penilaian')
                    ->default(Carbon::now())
                    ->required(),

                Forms\Components\Textarea::make('catatan_guru')
                    ->label('7. Catatan Evaluasi Guru (Makhraj, Tajwid, Fashahah, Adab)')
                    ->placeholder('Contoh: Makharijul huruf sangat tepat, kaidah mad thobi\'i konsisten, fashahah dan kelancaran sangat baik.')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),

                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->label('Nama Siswa')
                    ->weight('bold')
                    ->description(fn (ReadingQualityScore $record) => 'NIS: ' . ($record->student?->nis ?? '-')),

                Tables\Columns\TextColumn::make('program')
                    ->label('Kelas Al-Qur\'an')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yanbua'           => 'emerald',
                        'tahsin'           => 'info',
                        'tahfidz_dasar'    => 'primary',
                        'tahfidz_lanjutan' => 'purple',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state, ReadingQualityScore $record) => $record->program_label),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Penginput')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->description(fn (ReadingQualityScore $record) => $record->teacher?->getQuranSpecializationLabel() ? "[{$record->teacher->getQuranSpecializationLabel()}]" : null),

                Tables\Columns\TextColumn::make('scale_score')
                    ->label('Skor')
                    ->sortable()
                    ->weight('bold')
                    ->color(function (ReadingQualityScore $record): string {
                        return $record->predikat_color;
                    }),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat Skala')
                    ->badge()
                    ->color(fn (ReadingQualityScore $record): string => $record->predikat_color)
                    ->formatStateUsing(fn (ReadingQualityScore $record): string => $record->predikat),

                Tables\Columns\TextColumn::make('jilid_halaman')
                    ->label('Jilid / Hal.')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Diterima' => 'success',
                        'Perlu Perbaikan' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('program')
                    ->label('Filter Program')
                    ->options([
                        'yanbua'           => 'Yanbu\'a',
                        'tahsin'           => 'Tahsin',
                        'tahfidz_dasar'    => 'Tahfidz Dasar',
                        'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
                        'tahfidz'          => 'Tahfidz (Umum)',
                    ]),

                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                        'Diterima' => 'Diterima',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                    ]),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru Penginput')
                    ->relationship('teacher', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status_verifikasi !== 'Diterima' && (auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Skor Kualitas Bacaan')
                    ->modalDescription('Apakah Anda yakin menerima dan memverifikasi skor kualitas bacaan siswa ini?')
                    ->action(function ($record) {
                        $record->update([
                            'status_verifikasi' => 'Diterima',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Skor Kualitas Berhasil Diterima & Diverifikasi')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('terima_massal')
                        ->label('Terima Semua Terpilih')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update([
                                'status_verifikasi' => 'Diterima',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Seluruh Skor Terpilih Berhasil Diverifikasi')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReadingQualityScores::route('/'),
        ];
    }
}
