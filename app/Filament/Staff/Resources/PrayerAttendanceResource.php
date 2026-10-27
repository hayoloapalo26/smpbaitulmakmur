<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PrayerAttendanceResource\Pages;
use App\Models\PrayerAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PrayerAttendanceResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            'guru_piket', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'
        ]) || $user->isPiketToday();
    }

    protected static ?string $model = PrayerAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-moon';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Presensi Sholat';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $query->where('institution_id', $user->institution_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn() => Auth::id()),
                
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Bertugas')
                    ->default(now())
                    ->required(),
                    
                Forms\Components\Select::make('jenis_sholat')
                    ->label('Jenis Sholat')
                    ->options([
                        'Dhuha' => 'Sholat Dhuha',
                        'Dhuhur' => 'Sholat Dhuhur',
                        'Ashar' => 'Sholat Ashar',
                    ])
                    ->default('Dhuhur')
                    ->required(),

                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
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

                Forms\Components\Toggle::make('is_khusyu')
                    ->label('Siswa Sholat dengan Khusyu?')
                    ->default(true)
                    ->helperText('Matikan jika siswa bergurau/tidak tertib. Akan otomatis memotong skor karakter.'),
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
                    
                Tables\Columns\TextColumn::make('jenis_sholat')
                    ->label('Sholat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dhuha' => 'warning',
                        'Dhuhur' => 'success',
                        'Ashar' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')->searchable()
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'warning',
                        'sakit' => 'info',
                        'alpa' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_khusyu')
                    ->label('Khusyu')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_sholat')
                    ->label('Filter Sholat')
                    ->options([
                        'Dhuha' => 'Dhuha',
                        'Dhuhur' => 'Dhuhur',
                        'Ashar' => 'Ashar',
                    ]),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tanggal'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tanggal', $date),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePrayerAttendances::route('/'),
        ];
    }
    
    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
