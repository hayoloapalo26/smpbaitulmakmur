<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\StudentResource\Pages;
use App\Models\Institution;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Data Siswa';
    protected static ?string $modelLabel = 'Data Siswa';
    protected static ?string $pluralModelLabel = 'Data Siswa Seluruh Lembaga';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->hasAnyRole([
            'superadmin',
            'yayasan',
            'pembina_yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'keuangan_yayasan',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'tim_pengembang_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'penjamin_mutu_yayasan',
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['institution', 'classroom', 'guruQuran']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Utama Siswa')
                    ->schema([
                        Forms\Components\TextInput::make('nis')
                            ->label('NIS')
                            ->required(),
                        Forms\Components\TextInput::make('nisn')
                            ->label('NISN'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Unit')
                            ->options(Institution::where('id', '>', 1)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name'),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        Forms\Components\Select::make('status_siswa')
                            ->label('Status')
                            ->options([
                                'aktif' => 'Siswa Aktif',
                                'alumni' => 'Alumni',
                            ])
                            ->default('aktif'),
                    ])->columns(2),

                Forms\Components\Section::make('Data Kontak & Orang Tua')
                    ->schema([
                        Forms\Components\TextInput::make('nama_ayah')
                            ->label('Nama Ayah'),
                        Forms\Components\TextInput::make('no_hp_ayah')
                            ->label('No. HP / WA Ayah'),
                        Forms\Components\TextInput::make('nama_ibu')
                            ->label('Nama Ibu'),
                        Forms\Components\TextInput::make('no_hp_ibu')
                            ->label('No. HP / WA Ibu'),
                        Forms\Components\Textarea::make('alamat_siswa')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Profil & Data Induk Siswa')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama Lengkap Siswa')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('nis')
                            ->label('NIS'),
                        Infolists\Components\TextEntry::make('nisn')
                            ->label('NISN')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga / Unit')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('classroom.name')
                            ->label('Kelas Akademik')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn ($state) => $state === 'L' ? 'Laki-laki' : ($state === 'P' ? 'Perempuan' : '-')),
                        Infolists\Components\TextEntry::make('status_siswa')
                            ->label('Status Siswa')
                            ->badge()
                            ->color(fn ($state) => $state === 'aktif' ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => $state === 'aktif' ? 'Siswa Aktif' : 'Alumni'),
                        Infolists\Components\TextEntry::make('kelas_quran_label')
                            ->label('Kelas Al-Qur\'an')
                            ->badge()
                            ->color('emerald')
                            ->placeholder('Belum Ditentukan'),
                        Infolists\Components\TextEntry::make('guruQuran.name')
                            ->label('Guru Pengampu Al-Qur\'an')
                            ->placeholder('-'),
                    ])->columns(3),

                Infolists\Components\Section::make('Data Keluarga & Kontak Orang Tua')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('no_hp_ayah')
                            ->label('No. WhatsApp Ayah')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('pekerjaan_ayah')
                            ->label('Pekerjaan Ayah')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('nama_ibu')
                            ->label('Nama Ibu')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('no_hp_ibu')
                            ->label('No. WhatsApp Ibu')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('pekerjaan_ibu')
                            ->label('Pekerjaan Ibu')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('alamat_siswa')
                            ->label('Alamat Domisili')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga / Unit')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->badge()
                    ->color(fn ($state) => $state === 'P' ? 'pink' : 'blue'),

                Tables\Columns\TextColumn::make('kelas_quran_label')
                    ->label('Kelas Al-Qur\'an')
                    ->badge()
                    ->color('emerald')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status_siswa')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'aktif' ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state === 'aktif' ? 'Aktif' : 'Alumni'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Unit / Lembaga')
                    ->relationship('institution', 'name'),

                Tables\Filters\SelectFilter::make('status_siswa')
                    ->label('Status Siswa')
                    ->options([
                        'aktif' => 'Siswa Aktif',
                        'alumni' => 'Alumni',
                    ]),

                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (Student $record) => "Detail Siswa: {$record->name}"),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudents::route('/'),
        ];
    }
}
