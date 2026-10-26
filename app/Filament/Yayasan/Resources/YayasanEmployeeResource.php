<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\YayasanEmployeeResource\Pages;
use App\Models\YayasanEmployee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class YayasanEmployeeResource extends Resource
{
    protected static ?string $model = YayasanEmployee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Karyawan Yayasan';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $modelLabel = 'Karyawan';
    protected static ?string $pluralModelLabel = 'Karyawan Yayasan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('education')
                            ->label('Pendidikan Terakhir')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Rumah')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Pekerjaan')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Jabatan')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\DatePicker::make('join_date')
                            ->label('Tanggal Masuk')
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ])->columns(2),

                Forms\Components\Section::make('Status Kepegawaian')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Karyawan Aktif?')
                            ->default(true)
                            ->live(),
                        Forms\Components\DatePicker::make('resign_date')
                            ->label('Tanggal Resign')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->hidden(fn (Forms\Get $get) => $get('is_active')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('join_date')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progres To-Do List')
                    ->getStateUsing(function ($record) {
                        $total = $record->tasks()->count();
                        if ($total === 0) return '0%';
                        $completed = $record->tasks()->where('is_completed', true)->count();
                        return round(($completed / $total) * 100) . '%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === '100%' ? 'success' : 'warning'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Kepegawaian')
                    ->placeholder('Semua Karyawan')
                    ->trueLabel('Hanya Karyawan Aktif')
                    ->falseLabel('Hanya Karyawan Resign'),
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
            \App\Filament\Yayasan\Resources\YayasanEmployeeResource\RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYayasanEmployees::route('/'),
            'create' => Pages\CreateYayasanEmployee::route('/create'),
            'edit' => Pages\EditYayasanEmployee::route('/{record}/edit'),
        ];
    }
}