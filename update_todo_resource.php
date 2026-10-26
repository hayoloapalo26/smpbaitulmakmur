<?php
$file = 'app/Filament/Staff/Resources/TodoTaskResource.php';
$content = <<<'PHP'
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
    protected static ?string $navigationGroup = 'Manajemen Tugas';
    protected static ?string $navigationLabel = 'Kelola To-Do List';
    protected static ?string $pluralModelLabel = 'To-Do List';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user->hasAnyRole(['superadmin', 'yayasan', 'ketua yayasan', 'wakil ketua yayasan', 'sekretaris yayasan', 'tim pengembang yayasan', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
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
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'wajib' => 'Tugas Wajib',
                        'opsional' => 'Tugas Opsional',
                    ]),
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
            'index' => Pages\ListTodoTasks::route('/'),
            'create' => Pages\CreateTodoTask::route('/create'),
            'edit' => Pages\EditTodoTask::route('/{record}/edit'),
        ];
    }
}
PHP;
file_put_contents($file, $content);

$file2 = 'app/Filament/Staff/Resources/TodoTaskResource/Pages/CreateTodoTask.php';
$content2 = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources\TodoTaskResource\Pages;

use App\Filament\Staff\Resources\TodoTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTodoTask extends CreateRecord
{
    protected static string $resource = TodoTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
PHP;
file_put_contents($file2, $content2);
echo "TodoTaskResource updated.\n";
