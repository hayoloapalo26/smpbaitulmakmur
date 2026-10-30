<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\QuranAchievementResource\Pages;
use App\Models\Classroom;
use App\Models\QuranAchievement;
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

class QuranAchievementResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']));
    }

    protected static ?string $model = QuranAchievement::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Capaian Quran & Setoran Hafalan';
    protected static ?string $pluralModelLabel = 'Capaian Quran & Setoran Hafalan';
    protected static ?int $navigationSort = 1;

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

    public static function getSurahs(): array
    {
        return [
            '1' => 'Al-Fatihah', '2' => 'Al-Baqarah', '3' => 'Ali Imran', '4' => 'An-Nisa', '5' => 'Al-Maidah',
            '6' => 'Al-Anam', '7' => 'Al-Araf', '8' => 'Al-Anfal', '9' => 'At-Taubah', '10' => 'Yunus',
            '11' => 'Hud', '12' => 'Yusuf', '13' => 'Ar-Rad', '14' => 'Ibrahim', '15' => 'Al-Hijr',
            '16' => 'An-Nahl', '17' => 'Al-Isra', '18' => 'Al-Kahf', '19' => 'Maryam', '20' => 'Taha',
            '21' => 'Al-Anbiya', '22' => 'Al-Hajj', '23' => 'Al-Muminun', '24' => 'An-Nur', '25' => 'Al-Furqan',
            '26' => 'Ash-Shuara', '27' => 'An-Naml', '28' => 'Al-Qasas', '29' => 'Al-Ankabut', '30' => 'Ar-Rum',
            '31' => 'Luqman', '32' => 'As-Sajdah', '33' => 'Al-Ahzab', '34' => 'Saba', '35' => 'Fatir',
            '36' => 'Ya-Sin', '37' => 'As-Saffat', '38' => 'Sad', '39' => 'Az-Zumar', '40' => 'Ghafir',
            '41' => 'Fussilat', '42' => 'Ash-Shura', '43' => 'Az-Zukhruf', '44' => 'Ad-Dukhan', '45' => 'Al-Jathiyah',
            '46' => 'Al-Ahqaf', '47' => 'Muhammad', '48' => 'Al-Fath', '49' => 'Al-Hujurat', '50' => 'Qaf',
            '51' => 'Adh-Dhariyat', '52' => 'At-Tur', '53' => 'An-Najm', '54' => 'Al-Qamar', '55' => 'Ar-Rahman',
            '56' => 'Al-Waqiah', '57' => 'Al-Hadid', '58' => 'Al-Mujadila', '59' => 'Al-Hashr', '60' => 'Al-Mumtahanah',
            '61' => 'As-Saff', '62' => 'Al-Jumuah', '63' => 'Al-Munafiqun', '64' => 'At-Taghabun', '65' => 'At-Talaq',
            '66' => 'At-Tahrim', '67' => 'Al-Mulk', '68' => 'Al-Qalam', '69' => 'Al-Haqqah', '70' => 'Al-Maarij',
            '71' => 'Nuh', '72' => 'Al-Jinn', '73' => 'Al-Muzzammil', '74' => 'Al-Muddaththir', '75' => 'Al-Qiyamah',
            '76' => 'Al-Insan', '77' => 'Al-Mursalat', '78' => 'An-Naba', '79' => 'An-Naziat', '80' => 'Abasa',
            '81' => 'At-Takwir', '82' => 'Al-Infitar', '83' => 'Al-Mutaffifin', '84' => 'Al-Inshiqaq', '85' => 'Al-Buruj',
            '86' => 'At-Tariq', '87' => 'Al-Ala', '88' => 'Al-Ghashiyah', '89' => 'Al-Fajr', '90' => 'Al-Balad',
            '91' => 'Ash-Shams', '92' => 'Al-Lail', '93' => 'Ad-Duha', '94' => 'Ash-Sharh', '95' => 'At-Tin',
            '96' => 'Al-Alaq', '97' => 'Al-Qadr', '98' => 'Al-Bayyinah', '99' => 'Az-Zalzalah', '100' => 'Al-Adiyat',
            '101' => 'Al-Qariah', '102' => 'At-Takathur', '103' => 'Al-Asr', '104' => 'Al-Humazah', '105' => 'Al-Fil',
            '106' => 'Quraish', '107' => 'Al-Maun', '108' => 'Al-Kawthar', '109' => 'Al-Kafirun', '110' => 'An-Nasr',
            '111' => 'Al-Masad', '112' => 'Al-Ikhlas', '113' => 'Al-Falaq', '114' => 'An-Nas'
        ];
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
        $surahs = self::getSurahs();

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
                            <div class='flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 font-bold text-xs'>
                                <span class='text-base'>📖</span>
                                <div>
                                    <div>Kelas {$label} • Dibina oleh: {$teacherName}</div>
                                    <div class='text-[10px] text-emerald-600 dark:text-emerald-400 font-normal'>Daftar santri otomatis tersaring dari penempatan Waka Kurikulum (tanpa perlu memilih kelas).</div>
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
                    ->helperText('Pilih kelas Al-Qur\'an untuk menyaring siswa sesuai penempatan Waka Kurikulum.'),

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
                    })
                    ->required(),

                Forms\Components\CheckboxList::make('type')
                    ->label('4. Tipe Capaian')
                    ->options([
                        'ziyadah'       => 'Ziyadah (Setoran Baru)',
                        'murajaah'      => 'Muraja\'ah (Pengulangan)',
                        'tasmi'         => 'Tasmi\' (Sekali Duduk)',
                        'ujian_tahfidz' => 'Ujian Tahfidz / Mutqin',
                    ])
                    ->columns(2)
                    ->required(),

                Forms\Components\Select::make('juz')
                    ->label('5. Juz')
                    ->options(array_combine(range(1, 30), range(1, 30)))
                    ->default(function () {
                        $user = Auth::user();
                        if ($user?->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 30;
                        return 1;
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('surah')
                    ->label('6. Surah')
                    ->options($surahs)
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('ayah')
                    ->label('7. Ayat')
                    ->placeholder('Contoh: 1-15 / 1-Akhir')
                    ->required(),

                Forms\Components\Select::make('predikat')
                    ->label('8. Predikat Kelulusan / Kualitas Setoran')
                    ->options([
                        'Mumtaz'        => 'Mumtaz (Istimewa / Nilai 90-100)',
                        'Jayyid Jiddan' => 'Jayyid Jiddan (Sangat Baik / Nilai 80-89)',
                        'Jayyid'        => 'Jayyid (Baik / Nilai 70-79)',
                        'Maqbul'        => 'Maqbul (Perlu Pembinaan / Nilai < 70)',
                    ])
                    ->default('Jayyid Jiddan')
                    ->required(),

                Forms\Components\TextInput::make('jilid_halaman')
                    ->label('Jilid / Halaman (Opsional untuk Yanbu\'a/Tahsin)')
                    ->placeholder('Contoh: Jilid 4 Hal. 20'),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Setoran')
                    ->default(Carbon::now())
                    ->required(),

                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan Tambahan Guru (Kelancaran & Mutqin)')
                    ->placeholder('Contoh: Hafalan lancar mutqin, makharijul huruf tepat, dan waqaf ibtida sempurna.')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('riwayat_capaian')
                    ->label('Riwayat Setoran Siswa Sebelumnya')
                    ->columnSpanFull()
                    ->content(function (Forms\Get $get) use ($surahs) {
                        $studentId = $get('student_id');
                        if (!$studentId) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Pilih siswa terlebih dahulu untuk melihat riwayat.</span>');
                        
                        $history = QuranAchievement::where('student_id', $studentId)
                            ->orderBy('tanggal', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                            
                        if ($history->isEmpty()) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Belum ada riwayat capaian.</span>');
                        
                        $html = '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-700 dark:text-gray-300">';
                        foreach ($history as $item) {
                            $types = is_array($item->type) ? implode(', ', array_map('ucfirst', $item->type)) : ucfirst($item->type);
                            $surahName = $surahs[$item->surah] ?? $item->surah;
                            $predikatLabel = $item->predikat ? " [{$item->predikat}]" : '';
                            $html .= "<li><strong>" . Carbon::parse($item->tanggal)->format('d M Y') . "</strong>: " 
                                   . "Juz {$item->juz}, Surat {$surahName}, Ayat {$item->ayah} - <span class=\"text-emerald-600 font-semibold\">[{$types}]{$predikatLabel}</span>"
                                   . ($item->catatan ? " <em>({$item->catatan})</em>" : "")
                                   . "</li>";
                        }
                        $html .= '</ul>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),
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
                    ->description(fn (QuranAchievement $record) => 'NIS: ' . ($record->student?->nis ?? '-')),

                Tables\Columns\TextColumn::make('program')
                    ->label('Kelas Al-Qur\'an')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'tahfidz_dasar'    => 'primary',
                        'tahfidz_lanjutan' => 'purple',
                        'yanbua'           => 'emerald',
                        'tahsin'           => 'info',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state, QuranAchievement $record) => $record->program_label),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Penginput')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->description(fn (QuranAchievement $record) => $record->teacher?->getQuranSpecializationLabel() ? "[{$record->teacher->getQuranSpecializationLabel()}]" : null),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_map('ucfirst', $state)) : ucfirst($state)),

                Tables\Columns\TextColumn::make('juz')
                    ->label('Juz')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('surah')
                    ->label('Surah')
                    ->formatStateUsing(function ($state) {
                        return self::getSurahs()[$state] ?? $state;
                    }),

                Tables\Columns\TextColumn::make('ayah')
                    ->label('Ayat'),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Mumtaz'        => 'success',
                        'Jayyid Jiddan' => 'info',
                        'Jayyid'        => 'warning',
                        default         => 'danger',
                    })
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
                        'tahfidz_dasar'    => 'Tahfidz Dasar',
                        'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
                        'tahfidz'          => 'Tahfidz (Umum)',
                        'yanbua'           => 'Yanbu\'a',
                        'tahsin'           => 'Tahsin',
                    ]),

                Tables\Filters\SelectFilter::make('predikat')
                    ->label('Filter Predikat')
                    ->options([
                        'Mumtaz'        => 'Mumtaz',
                        'Jayyid Jiddan' => 'Jayyid Jiddan',
                        'Jayyid'        => 'Jayyid',
                        'Maqbul'        => 'Maqbul',
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
                    ->modalHeading('Terima Inputan Setoran Al-Qur\'an')
                    ->modalDescription('Apakah Anda yakin menerima dan memvalidasi capaian Al-Qur\'an siswa ini?')
                    ->action(function ($record) {
                        $record->update([
                            'status_verifikasi' => 'Diterima',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Setoran Berhasil Diterima & Diverifikasi')
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
                                ->title('Semua setoran hafalan terpilih berhasil diverifikasi')
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
            'index' => Pages\ManageQuranAchievements::route('/'),
        ];
    }
}
