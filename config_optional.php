<?php
$file = "app/Filament/Staff/Resources/OptionalTaskResource.php";
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\OptionalTaskResource\Pages;
use App\Models\OptionalTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class OptionalTaskResource extends Resource
{
    protected static ?string $model = OptionalTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationGroup = 'Manajemen Tugas';
    protected static ?string $navigationLabel = 'Rakit To-Do Opsional';
    protected static ?string $modelLabel = 'To-Do List Opsional';
    protected static ?string $pluralModelLabel = 'To-Do List Opsional';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi (Opsional)'),
                        Forms\Components\Select::make('target_roles')
                            ->label('Target Peran (Roles)')
                            ->multiple()
                            ->options(Role::whereNotIn('name', ['superadmin', 'yayasan', 'siswa', 'orang_tua'])->pluck('name', 'name'))
                            ->required()
                            ->helperText('Peran apa saja yang diizinkan untuk mengambil tugas opsional ini?'),
                        Forms\Components\TextInput::make('reward_points')
                            ->label('Reward Poin')
                            ->numeric()
                            ->nullable()
                            ->helperText('Poin tambahan/apresiasi (jika ada).'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Rakit Form (Builder)')
                    ->description('Rancang pertanyaan atau inputan untuk tugas opsional ini.')
                    ->schema([
                        Forms\Components\Repeater::make('form_schema')
                            ->label('Daftar Kolom Input')
                            ->schema([
                                Forms\Components\TextInput::make('field_name')
                                    ->label('Nama Field (Pertanyaan)')
                                    ->required(),
                                Forms\Components\Select::make('field_type')
                                    ->label('Tipe Input')
                                    ->options([
                                        'checkbox' => 'Ceklis (Ya/Tidak)',
                                        'text' => 'Teks Singkat',
                                        'textarea' => 'Paragraf Panjang',
                                        'file' => 'Upload Bukti (File/Foto)',
                                    ])
                                    ->required()
                                    ->default('text'),
                                Forms\Components\Toggle::make('is_required')
                                    ->label('Wajib Diisi')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['field_name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Tugas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_roles')
                    ->label('Target Roles')
                    ->badge(),
                Tables\Columns\TextColumn::make('reward_points')
                    ->label('Poin')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Respon')
                    ->counts('submissions'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOptionalTasks::route('/'),
            'create' => Pages\CreateOptionalTask::route('/create'),
            'edit' => Pages\EditOptionalTask::route('/{record}/edit'),
        ];
    }
}
PHP;
file_put_contents($file, $content);
echo "OptionalTaskResource configured.\n";
