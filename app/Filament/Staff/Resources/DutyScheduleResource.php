<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DutyScheduleResource\Pages;
use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\Schedule;
use App\Services\DutyScheduleGeneratorService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DutyScheduleResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'waka_kesiswaan', 'superadmin', 'kepala_sekolah',
            'guru_mapel', 'guru_alquran', 'koordinator_madin', 'wali_kelas', 'guru_kelas', 'bk', 'guru_piket', 'tata_usaha'
        ]) ?? false;
    }

    protected static ?string $model = DutySchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Jadwal Piket Guru';
    protected static ?string $pluralModelLabel = 'Jadwal Piket Guru';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Filter berdasarkan institusi jika bukan superadmin
        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $query->where(function ($q) use ($user) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('duty_schedules', 'institution_id')) {
                    $q->where('institution_id', $user->institution_id);
                }
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penugasan Guru Piket')
                    ->description('Penugasan jadwal guru piket dirouting oleh Waka Kesiswaan selama 1 semester.')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru yang Bertugas')
                            ->options(function () {
                                $instId = Auth::user()?->institution_id;
                                return DutyScheduleGeneratorService::getEligibleTeachers($instId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Bertugas')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('hari', Schedule::getIndonesianDayName($state));
                                    $m = Carbon::parse($state)->month;
                                    $set('semester', ($m >= 7 && $m <= 12) ? 'Ganjil' : 'Genap');
                                }
                            }),

                        Forms\Components\Select::make('hari')
                            ->label('Hari')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                            ])
                            ->helperText('Otomatis disinkronkan dengan tanggal yang dipilih'),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Semester Ganjil',
                                'Genap' => 'Semester Genap',
                            ])
                            ->default(fn () => (Carbon::now()->month >= 7 && Carbon::now()->month <= 12) ? 'Ganjil' : 'Genap'),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                            ->searchable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Mengawasi gerbang masuk dan rekap presensi pagi')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('l, d F Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Senin' => 'info',
                        'Selasa' => 'success',
                        'Rabu' => 'warning',
                        'Kamis' => 'primary',
                        'Jumat' => 'danger',
                        'Sabtu' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru yang Bertugas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ganjil' => 'warning',
                        'Genap' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('⚡ Piket Hari Ini')
                    ->query(fn ($query) => $query->whereDate('tanggal', Carbon::today())),

                Tables\Filters\Filter::make('minggu_ini')
                    ->label('📅 Piket Minggu Ini')
                    ->query(fn ($query) => $query->whereBetween('tanggal', [
                        Carbon::now()->startOfWeek()->toDateString(),
                        Carbon::now()->endOfWeek()->toDateString(),
                    ])),

                Tables\Filters\SelectFilter::make('hari')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ]),

                Tables\Filters\SelectFilter::make('semester')
                    ->label('Semester')
                    ->options([
                        'Ganjil' => 'Semester Ganjil',
                        'Genap' => 'Semester Genap',
                    ]),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru')
                    ->searchable()
                    ->options(fn () => DutyScheduleGeneratorService::getEligibleTeachers(Auth::user()?->institution_id)->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDutySchedules::route('/'),
            'create' => Pages\CreateDutySchedule::route('/create'),
            'edit' => Pages\EditDutySchedule::route('/{record}/edit'),
        ];
    }
}
