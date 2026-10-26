<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ScheduleResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'waka_kurikulum', 'superadmin', 'kepala_sekolah', 'tata_usaha',
            'guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas'
        ]);
    }

    protected static ?string $model = Schedule::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $modelLabel = 'Jadwal Pelajaran';
    protected static ?int $navigationSort = 4;

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'tata_usaha', 'superadmin'])) {
            return $query;
        }

        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required(),
                Forms\Components\Select::make('jam_ke')
                    ->label('Jam Pelajaran Ke- & Waktu Mengajar')
                    ->options(\App\Models\Schedule::getJamOptions())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('teacher_id')
                    ->label('Guru Pengajar')
                    ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) {
                        $user = Auth::user();
                        if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
                            $query->where(function ($q) use ($user) {
                                $q->where('institution_id', $user->institution_id)
                                  ->orWhereNull('institution_id')
                                  ->orWhere('institution_id', 1);
                            });
                        }
                        $query->whereHas('roles', function ($q) {
                            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Pelajaran & Waktu')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => \App\Models\Schedule::getJamLabelWithTime($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Pengajar')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('jam_ke', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('jam_ke')
                    ->label('Jam Pelajaran')
                    ->options(\App\Models\Schedule::getJamOptions()),
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
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name'),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru')
                    ->relationship('teacher', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchedules::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
