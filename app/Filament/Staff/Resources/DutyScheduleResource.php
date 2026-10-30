<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DutyScheduleResource\Pages;
use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\DutySwapRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\DutyScheduleGeneratorService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DutyScheduleResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge([
            'waka_kesiswaan', 'superadmin', 'kepala_sekolah',
            'guru_mapel', 'koordinator_madin', 'wali_kelas', 'guru_kelas', 'bk', 'guru_piket', 'tata_usaha'
        ], \App\Models\User::QURAN_TEACHER_ROLES)) ?? false;
    }

    protected static ?string $model = DutySchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Jadwal Piket Guru';
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
                    ->description('Pilih hari dan tentukan guru yang bertugas. Anda dapat memilih lebih dari satu guru (multiple choice) untuk hari yang sama.')
                    ->schema([
                        Forms\Components\Select::make('hari')
                            ->label('Hari Piket')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Pilih hari piket rutin.'),

                        Forms\Components\Select::make('teacher_ids')
                            ->label('Guru yang Bertugas (Multiple Choice)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $instId = Auth::user()?->institution_id;
                                return DutyScheduleGeneratorService::getEligibleTeachers($instId)->pluck('name', 'id');
                            })
                            ->required()
                            ->helperText('Pilih satu atau beberapa guru yang bertugas di hari tersebut (bisa lebih dari 1 guru).'),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Semester Ganjil',
                                'Genap' => 'Semester Genap',
                            ])
                            ->default(fn () => (Carbon::now()->month >= 7 && Carbon::now()->month <= 12) ? 'Ganjil' : 'Genap')
                            ->required(),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                            ->searchable(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Khusus (Opsional)')
                            ->nullable()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->helperText('Biarkan KOSONG untuk penugasan rutin mingguan otomatis berdasarkan hari.'),

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
                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari Piket')
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
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru yang Bertugas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status_hari_ini')
                    ->label('Status Hari Ini')
                    ->badge()
                    ->state(function (DutySchedule $record): string {
                        $todayDay = Schedule::getIndonesianDayName(Carbon::today());
                        $todayDate = Carbon::today()->toDateString();
                        
                        // Periksa apakah guru ini aktif piket hari ini (termasuk status tukar piket)
                        if ($record->teacher && $record->teacher->isPiketToday() && ($record->hari === $todayDay || ($record->tanggal && Carbon::parse($record->tanggal)->toDateString() === $todayDate))) {
                            return '🔥 Bertugas Hari Ini';
                        }
                        return 'Jadwal Rutin';
                    })
                    ->color(fn (string $state): string => str_contains($state, 'Hari Ini') ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal Khusus')
                    ->date('d M Y')
                    ->placeholder('Rutin Mingguan')
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ->defaultSort('hari', 'asc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('⚡ Piket Hari Ini')
                    ->query(function ($query) {
                        $todayDay = Schedule::getIndonesianDayName(Carbon::today());
                        $todayDate = Carbon::today()->toDateString();
                        $query->where(function ($q) use ($todayDay, $todayDate) {
                            $q->where('hari', $todayDay)
                              ->orWhereDate('tanggal', $todayDate);
                        });
                    }),

                Tables\Filters\SelectFilter::make('hari')
                    ->label('Hari Piket')
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
                Tables\Actions\Action::make('ajukan_tukar_row')
                    ->label('Tukar Jadwal')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (DutySchedule $record) => $record->teacher_id === Auth::id() || Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']))
                    ->form([
                        Forms\Components\DatePicker::make('original_date')
                            ->label('Tanggal Piket Asli yang Ingin Ditukar')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false)
                            ->displayFormat('d F Y'),

                        Forms\Components\Select::make('substitute_teacher_id')
                            ->label('Guru Pengganti')
                            ->options(fn () => User::where('id', '!=', Auth::id())->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('new_date')
                            ->label('Tanggal Anda Menggantikan Guru Pengganti Tersebut')
                            ->required()
                            ->default(now()->addDays(2)->toDateString())
                            ->native(false)
                            ->displayFormat('d F Y'),
                    ])
                    ->action(function (DutySchedule $record, array $data) {
                        DutySwapRequest::create([
                            'requester_id' => $record->teacher_id,
                            'original_date' => $data['original_date'],
                            'substitute_teacher_id' => $data['substitute_teacher_id'],
                            'new_date' => $data['new_date'],
                            'status' => 'diajukan',
                            'institution_id' => Auth::user()?->institution_id,
                        ]);

                        Notification::make()
                            ->title('Pengajuan Tukar Piket Terkirim')
                            ->body('Permohonan tukar jadwal piket berhasil diajukan. Menunggu persetujuan.')
                            ->success()
                            ->send();
                    }),

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
