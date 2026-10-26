<?php
$file = 'app/Filament/Staff/Resources/MandatoryTaskResource.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\MandatoryTaskResource\Pages;
use App\Models\MandatoryTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class MandatoryTaskResource extends Resource
{
    protected static ?string $model = MandatoryTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Manajemen Tugas';
    protected static ?string $navigationLabel = 'Rakit To-Do List Wajib';
    protected static ?string $modelLabel = 'To-Do List Wajib';
    protected static ?string $pluralModelLabel = 'To-Do List Wajib';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas')
                    ->schema([
                        Forms\Components\Hidden::make('institution_id')
                            ->default(fn () => auth()->user()->institution_id)
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul / Nama Tugas')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi (Opsional)'),
                        Forms\Components\Select::make('target_roles')
                            ->label('Target Peran (Roles)')
                            ->multiple()
                            ->options(Role::whereNotIn('name', ['superadmin', 'yayasan', 'siswa', 'orang_tua'])->pluck('name', 'name'))
                            ->required()
                            ->helperText('Siapa saja yang wajib mengisi form tugas harian ini?'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Rakit Form (Builder)')
                    ->description('Rancang pertanyaan atau ceklis yang harus diisi oleh staf/guru.')
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
                                    ->default('checkbox'),
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
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Respon')
                    ->counts('submissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMandatoryTasks::route('/'),
            'create' => Pages\CreateMandatoryTask::route('/create'),
            'view' => Pages\ViewMandatoryTask::route('/{record}'),
            'edit' => Pages\EditMandatoryTask::route('/{record}/edit'),
        ];
    }
}
PHP;
file_put_contents($file, $content);
echo "MandatoryTaskResource configured.\n";
