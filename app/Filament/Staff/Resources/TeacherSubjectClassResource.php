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
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    protected static ?string $model = TeacherSubjectClass::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Routing Guru Mapel';
    protected static ?string $modelLabel = 'Routing Guru Mapel';
    protected static ?string $pluralModelLabel = 'Routing Guru Mapel';
    protected static ?int $navigationSort = 3;

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Penugasan Guru & Mata Pelajaran')
                    ->description('Tentukan guru pengampu, mata pelajaran yang diajarkan, dan kelas tujuan. 1 guru dapat mengampu lebih dari 1 mata pelajaran.')
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
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->sortable()
                    ->label('Guru Pengajar')
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable()
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->searchable()
                    ->sortable()
                    ->label('Kelas / Rombel')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->label('Filter Guru')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mapel')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->label('Filter Kelas')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('input_nilai')
                    ->label('Input Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (TeacherSubjectClass $record) => url('/staff/input-nilai-mapel?subject_id=' . $record->subject_id . '&classroom_id=' . $record->classroom_id)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeacherSubjectClasses::route('/')];
    }
}
