<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AttributeViolationResource\Pages;
use App\Models\AttributeViolation;
use App\Models\Student;
use App\Models\CharacterScoreLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttributeViolationResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = AttributeViolation::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Pelanggaran Atribut';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')->default(Auth::id()),
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(Student::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')->default(Carbon::today())->required(),
                
                Forms\Components\Radio::make('is_lengkap')
                    ->label('Apakah Atribut Lengkap?')
                    ->options([
                        'Lengkap' => 'Lengkap',
                        'Tidak Lengkap' => 'Tidak Lengkap'
                    ])
                    ->live()
                    ->required(),
                    
                Forms\Components\CheckboxList::make('attribute_type')
                    ->label('Atribut yang Tidak Dipakai')
                    ->options([
                        'Sabuk' => 'Sabuk',
                        'Dasi' => 'Dasi',
                        'Topi' => 'Topi',
                        'Sepatu Hitam' => 'Sepatu Hitam',
                        'Kaos Kaki Putih' => 'Kaos Kaki Putih',
                        'Badge/Lokasi' => 'Badge/Lokasi',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('is_lengkap') === 'Tidak Lengkap')
                    ->required(fn (Forms\Get $get) => $get('is_lengkap') === 'Tidak Lengkap'),
                    
                Forms\Components\Textarea::make('jenis_pelanggaran')
                    ->label('Keterangan Tambahan')
                    ->visible(fn (Forms\Get $get) => $get('is_lengkap') === 'Tidak Lengkap'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa')->searchable(),
                Tables\Columns\TextColumn::make('attribute_type')
                    ->label('Atribut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('jenis_pelanggaran')->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttributeViolations::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
