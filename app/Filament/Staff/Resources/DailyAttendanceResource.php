<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DailyAttendanceResource\Pages;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DailyAttendanceResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'tata_usaha'])) {
            return true;
        }

        // Untuk guru: HANYA jika guru yang bersangkutan terjadwal piket HARI INI
        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = DailyAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Data Presensi Pagi';
    protected static ?string $modelLabel = 'Presensi Pagi Siswa';
    protected static ?string $pluralModelLabel = 'Presensi Pagi Siswa';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query;
        }

        if ($user->hasRole('superadmin')) {
            return $query;
        }

        if ($user->institution_id && $user->institution_id != 1) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id);
            });
        }

        if ($user->hasAnyRole(['kepala_sekolah', 'waka_kesiswaan', 'guru_piket', 'tata_usaha']) || $user->isPiketToday()) {
            return $query;
        }

        if ($user->hasRole('wali_kelas')) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereHas('student', function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(Auth::id()),

                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(function () {
                        $user = Auth::user();
                        $q = Student::query();
                        if ($user && $user->institution_id && !$user->hasRole('superadmin') && $user->institution_id != 1) {
                            $q->where('institution_id', $user->institution_id);
                        }
                        return $q->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(Carbon::today())
                    ->required()
                    ->native(false)
                    ->displayFormat('d F Y'),

                Forms\Components\TimePicker::make('waktu_kedatangan')
                    ->label('Waktu Kedatangan')
                    ->default(now()->format('H:i'))
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                    ])
                    ->default('hadir')
                    ->required(),

                Forms\Components\Select::make('metode_absen')
                    ->label('Metode Presensi')
                    ->options([
                        'manual' => '✍️ Input Manual',
                        'fingerprint' => '👆 Sidik Jari (Fingerprint)',
                    ])
                    ->default('manual')
                    ->required(),

                Forms\Components\Toggle::make('is_late')
                    ->label('Terlambat Masuk Sekolah?'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Catatan / Keterangan')
                    ->placeholder('Contoh: Terlambat bangun, ban bocor, dsb.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (DailyAttendance $record): string => $record->student?->classroom?->name ? 'Kelas ' . $record->student->classroom->name : ''),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('waktu_kedatangan')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'info',
                        'sakit' => 'warning',
                        'alpa' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('metode_absen')
                    ->label('Metode Presensi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'fingerprint' => 'success',
                        'manual' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'fingerprint' => '👆 Fingerprint',
                        'manual' => '✍️ Manual',
                        default => ucfirst(str_replace('_', ' ', $state ?? 'Manual')),
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Terlambat?')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('⚡ Presensi Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('tanggal', Carbon::today())),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                    ]),

                Tables\Filters\SelectFilter::make('metode_absen')
                    ->label('Metode Presensi')
                    ->options([
                        'fingerprint' => '👆 Sidik Jari (Fingerprint)',
                        'manual' => '✍️ Input Manual',
                    ]),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Status Terlambat')
                    ->trueLabel('Terlambat')
                    ->falseLabel('Tepat Waktu'),

                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('student.classroom', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('hubungi_ortu')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (DailyAttendance $record) => $record->status !== 'hadir' && !empty($record->student?->no_telepon_orang_tua))
                    ->url(function (DailyAttendance $record) {
                        $phone = preg_replace('/[^0-9]/', '', $record->student->no_telepon_orang_tua);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }
                        $msg = "Assalamu'alaikum Warahmatullahi Wabarakatuh, Bapak/Ibu wali dari " . $record->student->name . ".\nKami dari pihak sekolah menginformasikan bahwa ananda hari ini tercatat " . strtoupper($record->status) . " di sekolah.\nMohon konfirmasi atau keterangannya. Terima kasih.";
                        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
                    })
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageDailyAttendances::route('/'),
        ];
    }
}
