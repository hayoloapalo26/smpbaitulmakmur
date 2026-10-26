<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TodoTaskResource\Pages;
use App\Models\TodoTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class TodoTaskResource extends Resource
{
    protected static ?string $model = TodoTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'Kelola Penugasan (Atasan)';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'To-Do List';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis Tugas')
                            ->options([
                                'wajib' => 'Tugas Wajib',
                                'opsional' => 'Tugas Opsional',
                            ])
                            ->required()
                            ->default('opsional'),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi/Detail Tugas')
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Batas Waktu (Tenggat)')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Sasaran / Target (Distribusi)')
                    ->description('Tentukan ke peran mana atau pengguna spesifik mana tugas ini dibebankan. Anda bisa memilih salah satu atau keduanya.')
                    ->schema([
                        Forms\Components\Select::make('target_roles')
                            ->label('Target Peran (Role)')
                            ->multiple()
                            ->options(Role::whereNotIn('name', ['superadmin', 'siswa', 'orang_tua'])->pluck('name', 'name')->toArray())
                            ->searchable(),
                        Forms\Components\Select::make('target_users')
                            ->label('Target Pengguna Spesifik')
                            ->multiple()
                            ->options(User::whereHas('roles', function($q){
                                $q->whereNotIn('name', ['siswa', 'orang_tua']);
                            })->pluck('name', 'id')->toArray())
                            ->searchable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wajib' => 'danger',
                        'opsional' => 'warning',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Tugas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat Waktu')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup('type')
            ->groups([
                \Filament\Tables\Grouping\Group::make('type')
                    ->label('Kategori')
                    ->getTitleFromRecordUsing(fn (TodoTask $record): string => $record->type === 'wajib' ? 'To Do List Wajib (Harian)' : 'To Do List Opsional')
                    ->orderQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query, string $direction) => $query->orderBy('type', 'desc'))
                    ->collapsible(),
            ])
            ->filters([
            ])
            ->actions([
                                Tables\Actions\Action::make('view_completions')
                    ->label('Lihat Progres')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalContent(function (TodoTask $record) {
                        $completions = \App\Models\TodoCompletion::with('user')->where('todo_task_id', $record->id)->where('is_done', true)->get();
                        return view('filament.staff.pages.todo-completions', ['completions' => $completions, 'record' => $record]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
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
            'index' => Pages\ListTodoTasks::route('/'),
            'create' => Pages\CreateTodoTask::route('/create'),
            'edit' => Pages\EditTodoTask::route('/{record}/edit'),
        ];
    }
}