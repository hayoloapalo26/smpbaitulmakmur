<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DailyAttendanceResource\Pages;
use App\Models\DailyAttendance;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DailyAttendanceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = DailyAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Presensi Pagi (Guru Piket)';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole(['kepala_sekolah', 'waka_kesiswaan'])) {
            return $query;
        }

        if ($user->hasRole('wali_kelas')) {
            $classroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
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
                Forms\Components\Hidden::make('teacher_id')->default(Auth::id()),
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(Student::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')->default(Carbon::today())->required(),
                Forms\Components\TimePicker::make('waktu_kedatangan')->required(),
                Forms\Components\Select::make('status')
                    ->options(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'])
                    ->default('hadir')->required(),
                Forms\Components\Toggle::make('is_late')->label('Terlambat?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa')->searchable(),
                Tables\Columns\TextColumn::make('waktu_kedatangan')->time('H:i')->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('waktu_kepulangan')->time('H:i')->label('Jam Pulang')->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'info',
                        'sakit' => 'warning',
                        'alpa' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_late')->boolean()->label('Terlambat?'),
            ])
            ->actions([
                Tables\Actions\Action::make('hubungi_ortu')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (DailyAttendance $record) => $record->status !== 'hadir' && $record->student->no_telepon_orang_tua)
                    ->url(fn (DailyAttendance $record) => 'https://wa.me/'.preg_replace('/[^0-9]/', '', $record->student->no_telepon_orang_tua).'?text=Assalamu%27alaikum%2C%20Bapak%2FIbu.%20Kami%20dari%20pihak%20sekolah%20menginformasikan%20bahwa%20ananda%20'.urlencode($record->student->name).'%20hari%20ini%20tidak%20hadir%20di%20sekolah%20dengan%20keterangan%20'.strtoupper($record->status).'.%20Mohon%20konfirmasinya.')
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->disabled(fn () => now()->format('H:i') > '09:00' && !auth()->user()->hasRole('kepala_sekolah')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDailyAttendances::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
