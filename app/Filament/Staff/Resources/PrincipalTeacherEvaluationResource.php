<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PrincipalTeacherEvaluationResource\Pages;
use App\Models\AcademicYear;
use App\Models\PrincipalTeacherEvaluation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PrincipalTeacherEvaluationResource extends Resource
{
    protected static ?string $model = PrincipalTeacherEvaluation::class;

    protected static ?string $slug = 'penilaian-kinerja-guru-kepala-sekolah';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Tugas Kepala Sekolah';

    protected static ?string $navigationLabel = 'Penilaian Kinerja Guru (Bulanan)';

    protected static ?string $modelLabel = 'Penilaian Kinerja Guru oleh Kepala Sekolah';

    protected static ?string $pluralModelLabel = 'Penilaian Kinerja Guru oleh Kepala Sekolah';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(array_merge([
            'superadmin',
            'kepala_sekolah',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'waka_kurikulum',
            'guru_mapel',
            'wali_kelas',
            'guru_ekstrakurikuler',
        ], User::QURAN_TEACHER_ROLES));
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['teacher', 'evaluator', 'academicYear']);
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Jika guru biasa, hanya bisa melihat hasil penilaian dirinya sendiri
        if (!$user->hasAnyRole(['superadmin', 'kepala_sekolah', 'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'])) {
            $query->where('teacher_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $instId = $user->institution_id ?? 2;

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penilaian & Guru')
                    ->description('Pilih guru yang dinilai serta periode bulan dan tahun evaluasi kinerja.')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru yang Dinilai')
                            ->options(function () use ($instId, $user) {
                                $q = User::withoutGlobalScopes()->where('is_active', true);
                                if ($user && !$user->hasRole('superadmin')) {
                                    $q->where(function ($sub) use ($instId) {
                                        $sub->where('institution_id', $instId)
                                            ->orWhere('institution_id', 1)
                                            ->orWhereNull('institution_id');
                                    });
                                }
                                $q->whereHas('roles', function ($rq) {
                                    $rq->whereIn('name', array_merge([
                                        'guru_mapel',
                                        'wali_kelas',
                                        'guru_ekstrakurikuler',
                                    ], User::QURAN_TEACHER_ROLES));
                                });
                                return $q->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn () => !Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah'])),

                        Forms\Components\Hidden::make('evaluator_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Hidden::make('institution_id')
                            ->default(fn () => Auth::user()?->institution_id ?? 2),

                        Forms\Components\Select::make('bulan')
                            ->label('Periode Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now()->month)
                            ->required(),

                        Forms\Components\TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_penilaian')
                            ->label('Tanggal Penilaian')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(fn () => AcademicYear::withoutGlobalScopes()->pluck('name', 'id'))
                            ->default(fn () => AcademicYear::withoutGlobalScopes()->where('is_active', true)->value('id'))
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Rincian & Skor Penilaian Kinerja Bulanan')
                    ->description('Evaluasi indikator kinerja guru dan tetapkan nilai akhir (Skala 0 - 100). Bobot KPI Guru: 20%.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('aspek_penilaian.kedisiplinan')
                                    ->label('Kedisiplinan & Ketepatan Waktu (0-100)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(85)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateTotalScore($get, $set);
                                    }),

                                Forms\Components\TextInput::make('aspek_penilaian.pembelajaran')
                                    ->label('Kualitas Pembelajaran & Interaksi Siswa (0-100)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(85)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateTotalScore($get, $set);
                                    }),

                                Forms\Components\TextInput::make('aspek_penilaian.administrasi')
                                    ->label('Kelengkapan Administrasi & Jurnal (0-100)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(85)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateTotalScore($get, $set);
                                    }),

                                Forms\Components\TextInput::make('aspek_penilaian.keteladanan')
                                    ->label('Keteladanan, Akhlak & Loyalitas (0-100)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(85)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        static::recalculateTotalScore($get, $set);
                                    }),
                            ]),

                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Kinerja Kepala Sekolah (Skor Akhir 0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(85)
                            ->required()
                            ->live()
                            ->helperText(function (Forms\Get $get) {
                                $score = (float) $get('score');
                                $predikat = PrincipalTeacherEvaluation::determinePredicate($score);
                                return "⭐ Predikat: {$predikat} (Bobot kontribusi ke KPI Guru: 20%)";
                            }),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan, Evaluasi & Arahan Pembinaan Kepala Sekolah')
                            ->placeholder('Tuliskan catatan apresiasi, evaluasi kekurangan, dan arahan peningkatan kinerja untuk guru bersangkutan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function recalculateTotalScore(Forms\Get $get, Forms\Set $set): void
    {
        $d1 = (float) ($get('aspek_penilaian.kedisiplinan') ?? 85);
        $d2 = (float) ($get('aspek_penilaian.pembelajaran') ?? 85);
        $d3 = (float) ($get('aspek_penilaian.administrasi') ?? 85);
        $d4 = (float) ($get('aspek_penilaian.keteladanan') ?? 85);

        $avg = round(($d1 + $d2 + $d3 + $d4) / 4, 1);
        $set('score', $avg);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(fn (PrincipalTeacherEvaluation $record) => $record->periode_bulan_tahun)
                    ->badge()
                    ->color('info')
                    ->sortable(['tahun', 'bulan']),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai KS (20%)')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1) . '%')
                    ->badge()
                    ->color(fn (PrincipalTeacherEvaluation $record) => PrincipalTeacherEvaluation::getPredicateBadgeColor((float)$record->score))
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn (PrincipalTeacherEvaluation $record) => PrincipalTeacherEvaluation::getPredicateBadgeColor((float)$record->score))
                    ->getStateUsing(fn (PrincipalTeacherEvaluation $record) => $record->predikat ?: PrincipalTeacherEvaluation::determinePredicate((float)$record->score)),

                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('Penilai (Kepala Sekolah)')
                    ->searchable()
                    ->placeholder('Kepala Sekolah'),

                Tables\Columns\TextColumn::make('tanggal_penilaian')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan KS')
                    ->limit(40)
                    ->tooltip(fn (PrincipalTeacherEvaluation $record) => $record->catatan ?: 'Tidak ada catatan')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ]),
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $current = now()->year;
                        return [
                            $current - 1 => (string)($current - 1),
                            $current => (string)$current,
                            $current + 1 => (string)($current + 1),
                        ];
                    }),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah']) ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah']) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah']) ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePrincipalTeacherEvaluations::route('/'),
        ];
    }
}
