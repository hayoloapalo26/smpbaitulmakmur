<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherSubjectClassResource\Pages;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherSubjectClassResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) ?? false;
    }

    protected static ?string $model = TeacherSubjectClass::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Routing Guru Mapel';
    protected static ?string $modelLabel = 'Routing Guru Mapel';
    protected static ?string $pluralModelLabel = 'Routing Guru Mapel';
    protected static ?int $navigationSort = 2;

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
                            $query->where(function ($q) use ($user) {
                                $q->where('institution_id', $user->institution_id)
                                  ->orWhereNull('institution_id')
                                  ->orWhere('institution_id', 1);
                            });
                        }
                        $query->whereHas('roles', function ($q) {
                            $q->whereIn('name', ['guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'waka_kesiswaan']);
                        });
                    })
                    ->label('Guru Pengajar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('classroom_id')
                    ->relationship('classroom', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
                            $query->where('institution_id', $user->institution_id);
                        }
                    })
                    ->label('Kelas / Rombel')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->searchable()->sortable()->label('Guru Pengajar'),
                Tables\Columns\TextColumn::make('subject.name')->searchable()->sortable()->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('classroom.name')->searchable()->sortable()->label('Kelas / Rombel'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeacherSubjectClasses::route('/')];
    }
}
