<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\QuranAchievementResource\Pages;
use App\Models\QuranAchievement;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
    protected static ?string $modelLabel = 'Capaian Quran';
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
        $user = Auth::user();
        $studentIds = [];

        if (!$user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            $studentIds = TeacherSubjectClass::where('teacher_id', $user->id)
                ->join('students', 'students.classroom_id', '=', 'teacher_subject_classes.classroom_id')
                ->pluck('students.id')
                ->toArray();
        }

        // List 114 Surahs
        $surahs = self::getSurahs();

        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default($user->id),
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(function () use ($user, $studentIds) {
                        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
                            return Student::when($user->institution_id && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $user->institution_id))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        }
                        if (!empty($studentIds)) {
                            return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                        }
                        return Student::when($user->institution_id, fn ($q) => $q->where('institution_id', $user->institution_id))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->live() // so we can react and show history
                    ->required(),
                Forms\Components\CheckboxList::make('type')
                    ->label('Tipe Capaian')
                    ->options([
                        'ziyadah' => 'Ziyadah',
                        'murajaah' => 'Muraja\'ah',
                    ])
                    ->columns(2)
                    ->required(),
                Forms\Components\Select::make('juz')
                    ->options(array_combine(range(1, 30), range(1, 30)))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('surah')
                    ->options($surahs)
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('ayah')
                    ->label('Ayat')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->default(Carbon::now())
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan Tambahan')
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('riwayat_capaian')
                    ->label('Riwayat Capaian Siswa')
                    ->columnSpanFull()
                    ->content(function (Forms\Get $get) use ($surahs) {
                        $studentId = $get('student_id');
                        if (!$studentId) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Pilih siswa terlebih dahulu untuk melihat riwayat.</span>');
                        
                        $history = \App\Models\QuranAchievement::where('student_id', $studentId)
                            ->orderBy('tanggal', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                            
                        if ($history->isEmpty()) return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Belum ada riwayat capaian.</span>');
                        
                        $html = '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">';
                        foreach ($history as $item) {
                            $types = is_array($item->type) ? implode(', ', array_map('ucfirst', $item->type)) : ucfirst($item->type);
                            $surahName = $surahs[$item->surah] ?? $item->surah;
                            $html .= "<li><strong>" . \Carbon\Carbon::parse($item->tanggal)->format('d M Y') . "</strong>: " 
                                   . "Juz {$item->juz}, Surat {$surahName}, Ayat {$item->ayah} - <span class=\"text-emerald-600 font-semibold\">[{$types}]</span>"
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
                Tables\Columns\TextColumn::make('tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa'),
                Tables\Columns\TextColumn::make('student.classroom.name')->label('Kelas')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('teacher.name')->label('Guru Penginput')->searchable()->badge()->color('info'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_map('ucfirst', $state)) : ucfirst($state)),
                Tables\Columns\TextColumn::make('juz')->label('Juz'),
                Tables\Columns\TextColumn::make('surah')
                    ->label('Surah')
                    ->formatStateUsing(function ($state) {
                        return self::getSurahs()[$state] ?? $state;
                    }),
                Tables\Columns\TextColumn::make('ayah')->label('Ayat'),
                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Diterima' => 'success',
                        'Perlu Perbaikan' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('catatan')->limit(25)->toggleable(),
            ])
            ->filters([
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
                        \Filament\Notifications\Notification::make()
                            ->title('Setoran Berhasil Diterima')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
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
                            \Filament\Notifications\Notification::make()
                                ->title('Semua setoran hafalan terpilih berhasil diterima')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageQuranAchievements::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}

