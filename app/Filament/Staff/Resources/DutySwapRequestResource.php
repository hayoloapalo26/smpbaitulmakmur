<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DutySwapRequestResource\Pages;
use App\Models\DutySwapRequest;
use App\Models\DutySchedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DutySwapRequestResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'guru_piket', 
            'guru_mapel', 'guru_alquran', 'koordinator_madin', 'wali_kelas', 'guru_kelas', 'bk', 'tata_usaha'
        ]) ?? false;
    }

    protected static ?string $model = DutySwapRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Tukar Jadwal Piket';
    protected static ?string $modelLabel = 'Tukar Jadwal Piket';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        // Show requests made by me OR where I am the substitute
        return $query->where('requester_id', $user->id)->orWhere('substitute_teacher_id', $user->id);
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('requester_id')->default(Auth::id()),
                Forms\Components\DatePicker::make('original_date')->label('Jadwal Asli Saya')->required(),
                Forms\Components\Select::make('substitute_teacher_id')
                    ->label('Guru Pengganti')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('new_date')->label('Tanggal Pengganti (Hari Guru Tersebut)')->required(),
                Forms\Components\Select::make('status')
                    ->options(['diajukan' => 'Diajukan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'])
                    ->default('diajukan')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requester.name')->label('Pemohon'),
                Tables\Columns\TextColumn::make('original_date')->date()->label('Jadwal Asli'),
                Tables\Columns\TextColumn::make('substituteTeacher.name')->label('Pengganti'),
                Tables\Columns\TextColumn::make('new_date')->date()->label('Jadwal Pengganti'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'diajukan' && ($record->substitute_teacher_id === Auth::id() || Auth::user()->hasAnyRole(['waka_kesiswaan', 'superadmin'])))
                    ->action(function ($record) {
                        $record->update(['status' => 'disetujui']);
                        
                        // Swap logic
                        // Find requester's schedule on original_date
                        $reqSched = DutySchedule::where('teacher_id', $record->requester_id)->where('tanggal', $record->original_date)->first();
                        $subSched = DutySchedule::where('teacher_id', $record->substitute_teacher_id)->where('tanggal', $record->new_date)->first();
                        
                        if ($reqSched) {
                            $reqSched->teacher_id = $record->substitute_teacher_id;
                            $reqSched->save();
                        }
                        
                        if ($subSched) {
                            $subSched->teacher_id = $record->requester_id;
                            $subSched->save();
                        }
                    }),
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'diajukan' && ($record->substitute_teacher_id === Auth::id() || Auth::user()->hasAnyRole(['waka_kesiswaan', 'superadmin'])))
                    ->action(fn ($record) => $record->update(['status' => 'ditolak'])),
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
            'index' => Pages\ManageDutySwapRequests::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
