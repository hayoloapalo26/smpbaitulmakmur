<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherTodoListResource\Pages;
use App\Filament\Staff\Resources\TeacherTodoListResource\RelationManagers;
use App\Models\TeacherTodoList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherTodoListResource extends Resource
{
    protected static ?string $model = TeacherTodoList::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationLabel = 'To-Do List Harian';
    protected static ?string $modelLabel = 'Tugas Harian';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        // Guru hanya bisa melihat tugasnya sendiri. KS/Waka bisa lihat semua.
        if (auth()->user()->hasRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])) {
            return $query;
        }
        return $query->where('teacher_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->default(auth()->id())
                    ->disabled(!auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->required()
                    ->label('Guru'),
                Forms\Components\DatePicker::make('tanggal')
                    ->required()
                    ->default(now())
                    ->label('Tanggal Tugas'),
                Forms\Components\TextInput::make('tugas')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_done')
                    ->label('Tandai Selesai')
                    ->inline(false)
                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                        if ($state) {
                            $set('completed_at', now());
                        } else {
                            $set('completed_at', null);
                        }
                    }),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Selesai Pada')
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru')
                    ->searchable()
                    ->sortable()
                    ->visible(auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])),
                Tables\Columns\TextColumn::make('tugas')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_done')
                    ->label('Selesai')
                    ->boolean()
                    ->action(function ($record, $column) {
                        // Guru bisa klik centang langsung dari tabel
                        if ($record->teacher_id === auth()->id() || auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
                            $record->update([
                                'is_done' => !$record->is_done,
                                'completed_at' => !$record->is_done ? now() : null,
                            ]);
                        }
                    }),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
                Tables\Filters\TernaryFilter::make('is_done')
                    ->label('Status Penyelesaian')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherTodoLists::route('/'),
            'create' => Pages\CreateTeacherTodoList::route('/create'),
            'edit' => Pages\EditTeacherTodoList::route('/{record}/edit'),
        ];
    }
}
