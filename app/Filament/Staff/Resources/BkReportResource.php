<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\BkReportResource\Pages;
use App\Models\BkReport;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BkReportResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = BkReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Laporan BK';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole(['kepala_sekolah', 'waka_kesiswaan', 'bk'])) {
            return $query;
        }

        if ($user->hasRole('wali_kelas')) {
            $waliKelasClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereHas('student', function ($q) use ($waliKelasClassroomIds) {
                $q->whereIn('classroom_id', $waliKelasClassroomIds);
            });
        }

        return $query->where('id', -1);
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student.name')->searchable()->label('Siswa')->disabled(),
                Forms\Components\DatePicker::make('tanggal')->disabled(),
                Forms\Components\Textarea::make('isi_laporan')
                    ->label('Isi Laporan BK')
                    ->required()
                    ->disabled(fn () => !Auth::user()->hasRole('bk')),
                Forms\Components\Hidden::make('bk_teacher_id')->default(Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa')->searchable(),
                Tables\Columns\TextColumn::make('isi_laporan')->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageBkReports::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
