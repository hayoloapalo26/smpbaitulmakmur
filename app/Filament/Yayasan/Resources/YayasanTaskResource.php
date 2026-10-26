<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\YayasanTaskResource\Pages;
use App\Models\YayasanTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class YayasanTaskResource extends Resource
{
    protected static ?string $model = YayasanTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Tugas Yayasan';
    protected static ?string $modelLabel = 'Tugas Yayasan';
    protected static ?string $pluralModelLabel = 'Tugas Yayasan';
    protected static ?string $navigationGroup = 'Manajemen Tugas';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['ketua yayasan', 'sekretaris yayasan', 'superadmin', 'yayasan', 'tim_pengembang_penjamin_mutu_yayasan']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tugas')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('task_type')
                            ->label('Jenis Tugas')
                            ->options([
                                'mandatory' => 'Wajib',
                                'optional' => 'Opsional',
                            ])
                            ->required()
                            ->default('mandatory'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif?')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Target Penerima')
                    ->schema([
                        Forms\Components\CheckboxList::make('target_roles')
                            ->label('Pilih Role Target')
                            ->options(function () {
                                return Role::whereNotIn('name', ['siswa', 'orang_tua'])
                                    ->pluck('name', 'name')
                                    ->mapWithKeys(fn ($item, $key) => [$key => ucwords(str_replace('_', ' ', $item))]);
                            })
                            ->required()
                            ->columns(3)
                            ->gridDirection('row'),
                    ]),

                Forms\Components\Section::make('Form Builder (Google Forms Style)')
                    ->schema([
                        Forms\Components\Repeater::make('form_schema')
                            ->label('Daftar Pertanyaan')
                            ->schema([
                                Forms\Components\TextInput::make('field_name')
                                    ->label('Nama Pertanyaan')
                                    ->required(),
                                Forms\Components\Select::make('field_type')
                                    ->label('Tipe Jawaban')
                                    ->options([
                                        'text' => 'Teks Singkat',
                                        'textarea' => 'Teks Panjang',
                                        'checkbox' => 'Checkbox (Ya/Tidak)',
                                        'file_upload' => 'Upload File',
                                    ])
                                    ->required()
                                    ->default('text'),
                                Forms\Components\Toggle::make('is_required')
                                    ->label('Wajib Diisi')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Pertanyaan')
                    ]),
                
                Forms\Components\Hidden::make('creator_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\TextColumn::make('task_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mandatory' => 'danger',
                        'optional' => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state == 'mandatory' ? 'Wajib' : 'Opsional'),
                Tables\Columns\TextColumn::make('target_roles')
                    ->label('Target')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListYayasanTasks::route('/'),
            'create' => Pages\CreateYayasanTask::route('/create'),
            'edit' => Pages\EditYayasanTask::route('/{record}/edit'),
        ];
    }
}
