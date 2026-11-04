<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ExtracurricularGradeResource\Pages;
use App\Models\AcademicYear;
use App\Models\Extracurricular;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularMember;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExtracurricularGradeResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = ExtracurricularGrade::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Kesiswaan & Karakter';
        }
        if ($user?->hasRole('guru_ekstrakurikuler') && !$user?->hasAnyRole(['superadmin', 'kepala_sekolah'])) {
            return 'Tugas Guru Ekstrakurikuler';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        return 'Nilai Ekstrakurikuler';
    }

    public static function getModelLabel(): string
    {
        return 'Nilai Ekstrakurikuler';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Nilai Ekstrakurikuler';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['extracurricular', 'student.classroom', 'academicYear', 'pembina']);
        $user = Auth::user();
        if (!$user) return $query;

        // Waka Kesiswaan, Kepala Sekolah, Superadmin melihat seluruh nilai eskul
        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        // Guru Ekstrakurikuler hanya melihat nilai eskul binaannya
        if ($user->hasRole('guru_ekstrakurikuler')) {
            $myEskulIds = Extracurricular::where('pembina_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $myEskulIds) {
                if (!empty($myEskulIds)) {
                    $q->whereIn('extracurricular_id', $myEskulIds);
                }
                $q->orWhere('pembina_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isGuruEskul = $user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);
        $isKesiswaan = $user && $user->hasRole('waka_kesiswaan') && !$user->hasRole('superadmin');

        $activeYear = AcademicYear::where('is_active', true)->first();

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Ekstrakurikuler & Siswa')
                    ->description('Tentukan kegiatan ekstrakurikuler, periode semester, dan santri/siswa yang dinilai.')
                    ->schema([
                        Forms\Components\Select::make('extracurricular_id')
                            ->label('Ekstrakurikuler')
                            ->options(function () use ($user, $isGuruEskul) {
                                return Extracurricular::query()
                                    ->where('is_active', true)
                                    ->when($isGuruEskul, fn ($q) => $q->where('pembina_id', $user->id))
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled($isKesiswaan),

                        Forms\Components\Select::make('student_id')
                            ->label('Santri / Siswa')
                            ->options(function (Forms\Get $get) {
                                $eskulId = $get('extracurricular_id');
                                $query = Student::query()->with('classroom');
                                if ($eskulId) {
                                    $memberIds = ExtracurricularMember::where('extracurricular_id', $eskulId)
                                        ->where('is_active', true)
                                        ->pluck('student_id')
                                        ->toArray();
                                    if (!empty($memberIds)) {
                                        $query->whereIn('id', $memberIds);
                                    }
                                }
                                return $query->orderBy('name')->get()->mapWithKeys(function ($s) {
                                    $cls = $s->classroom ? " [{$s->classroom->name}]" : '';
                                    return [$s->id => "{$s->name}{$cls}"];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->disabled($isKesiswaan),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(fn () => AcademicYear::orderBy('id', 'desc')->pluck('name', 'id'))
                            ->default($activeYear?->id)
                            ->required()
                            ->disabled($isKesiswaan),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Ganjil',
                                'Genap'  => 'Genap',
                            ])
                            ->default($activeYear?->semester ?? 'Ganjil')
                            ->required()
                            ->disabled($isKesiswaan),
                    ])->columns(2),

                Forms\Components\Section::make('Penilaian Capaian & Predikat Akhir Semester')
                    ->description('Masukkan nilai angka, predikat kelulusan capaian, dan deskripsi kompetensi santri.')
                    ->schema([
                        Forms\Components\TextInput::make('nilai_angka')
                            ->label('Nilai Angka (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 88')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if ($state !== null && is_numeric($state)) {
                                    $score = (float) $state;
                                    if ($score >= 85) {
                                        $set('predikat', 'A');
                                    } elseif ($score >= 75) {
                                        $set('predikat', 'B');
                                    } elseif ($score >= 60) {
                                        $set('predikat', 'C');
                                    } else {
                                        $set('predikat', 'D');
                                    }
                                }
                            })
                            ->disabled($isKesiswaan),

                        Forms\Components\Select::make('predikat')
                            ->label('Predikat Capaian')
                            ->options([
                                'A' => 'A (Sangat Baik)',
                                'B' => 'B (Baik)',
                                'C' => 'C (Cukup)',
                                'D' => 'D (Kurang)',
                            ])
                            ->default('A')
                            ->required()
                            ->native(false)
                            ->disabled($isKesiswaan),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi Capaian / Catatan Akhir Semester')
                            ->placeholder('Contoh: Sangat aktif dalam kegiatan ekstrakurikuler, menunjukkan kedisiplinan tinggi dan penguasaan teknik yang sangat memuaskan.')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled($isKesiswaan),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isKesiswaan = $user && $user->hasRole('waka_kesiswaan') && !$user->hasRole('superadmin');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable(),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('extracurricular.name')
                    ->label('Ekstrakurikuler')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nilai_angka')
                    ->label('Nilai')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'A' => 'A (Sangat Baik)',
                        'B' => 'B (Baik)',
                        'C' => 'C (Cukup)',
                        default => 'D (Kurang)',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi Capaian')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->deskripsi)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pembina.name')
                    ->label('Pembina / Penilai')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('extracurricular_id')
                    ->label('Ekstrakurikuler')
                    ->relationship('extracurricular', 'name'),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name'),

                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap'  => 'Genap',
                    ]),

                Tables\Filters\SelectFilter::make('predikat')
                    ->options([
                        'A' => 'A (Sangat Baik)',
                        'B' => 'B (Baik)',
                        'C' => 'C (Cukup)',
                        'D' => 'D (Kurang)',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(!$isKesiswaan),
                Tables\Actions\DeleteAction::make()
                    ->visible(!$isKesiswaan),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(!$isKesiswaan),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExtracurricularGrades::route('/'),
            'create' => Pages\CreateExtracurricularGrade::route('/create'),
            'edit'   => Pages\EditExtracurricularGrade::route('/{record}/edit'),
        ];
    }
}
