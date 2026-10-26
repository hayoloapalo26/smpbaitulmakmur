<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DutyScheduleResource\Pages;
use App\Models\DutySchedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DutyScheduleResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']);
    }

    protected static ?string $model = DutySchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Jadwal Piket Guru';
    protected static ?string $pluralModelLabel = 'Jadwal Piket Guru';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->label('Guru yang Bertugas')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'bk']);
                    })->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                    
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Bertugas')
                    ->required()
                    ->native(false)
                    ->displayFormat('d F Y'),
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
                
                Tables\Columns\TextColumn::make('teacher.name')->searchable()
                    ->label('Guru')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('Piket Hari Ini')
                    ->query(fn ($query) => $query->whereDate('tanggal', \Carbon\Carbon::today())),
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
            'index' => Pages\ListDutySchedules::route('/'),
            'create' => Pages\CreateDutySchedule::route('/create'),
            'edit' => Pages\EditDutySchedule::route('/{record}/edit'),
        ];
    }
}
