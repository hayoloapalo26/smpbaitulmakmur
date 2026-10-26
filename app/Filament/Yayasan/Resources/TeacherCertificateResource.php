<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\TeacherCertificateResource\Pages;
use App\Models\Institution;
use App\Models\TeacherCertificate;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeacherCertificateResource extends Resource
{
    protected static ?string $model = TeacherCertificate::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Sertifikat Guru';
    protected static ?string $modelLabel = 'Sertifikat Guru';
    protected static ?string $pluralModelLabel = 'Sertifikat Guru Seluruh Lembaga';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
        ]) ?? false;
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
            ->with(['teacher', 'institution'])
            ->orderBy('tanggal_pelaksanaan', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Guru & Lembaga')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga')
                            ->relationship('institution', 'name', modifyQueryUsing: fn ($q) => $q->where('id', '!=', 1))
                            ->required(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Nama Guru')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Rincian Sertifikat & Pelatihan')
                    ->schema([
                        Forms\Components\TextInput::make('nama_kegiatan')
                            ->label('Nama Kegiatan / Pelatihan')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('penyelenggara')
                            ->label('Penyelenggara')
                            ->required(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options(TeacherCertificate::getKategoriOptions())
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->required(),

                        Forms\Components\TextInput::make('nomor_sertifikat')
                            ->label('Nomor Sertifikat'),

                        Forms\Components\TextInput::make('jumlah_jam')
                            ->label('Jumlah Jam (JP)')
                            ->numeric()
                            ->suffix('JP'),
                    ])->columns(2),

                Forms\Components\Section::make('Tautan Google Drive')
                    ->schema([
                        Forms\Components\TextInput::make('drive_url')
                            ->label('Link Google Drive')
                            ->url()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Materi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Sertifikat & Pelatihan Guru')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga')
                            ->badge()
                            ->color('info')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('Nama Guru')
                            ->icon('heroicon-o-user')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('nama_kegiatan')
                            ->label('Nama Kegiatan / Pelatihan / Webinar')
                            ->columnSpanFull()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('penyelenggara')
                            ->label('Penyelenggara Kegiatan')
                            ->icon('heroicon-o-building-office-2'),

                        Infolists\Components\TextEntry::make('kategori')
                            ->label('Kategori')
                            ->badge()
                            ->color(fn (string $state): string => TeacherCertificate::getKategoriBadgeColor($state)),

                        Infolists\Components\TextEntry::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan / Penerbitan')
                            ->date('l, d F Y')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('nomor_sertifikat')
                            ->label('Nomor Sertifikat')
                            ->placeholder('-')
                            ->icon('heroicon-o-hashtag'),

                        Infolists\Components\TextEntry::make('jumlah_jam')
                            ->label('Beban Jam Pelajaran (JP)')
                            ->formatStateUsing(fn ($state) => $state ? "{$state} JP" : '-')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('drive_url')
                            ->label('Tautan Google Drive Sertifikat')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color('primary')
                            ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true)
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Catatan Materi / Kompetensi yang Dicapai')
                            ->placeholder('Tidak ada catatan materi tambahan.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nama_kegiatan')
                    ->label('Nama Kegiatan / Pelatihan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('penyelenggara')
                    ->label('Penyelenggara')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => TeacherCertificate::getKategoriBadgeColor($state)),

                Tables\Columns\TextColumn::make('tanggal_pelaksanaan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_jam')
                    ->label('Beban')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} JP" : '-')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('drive_url')
                    ->label('Sertifikat')
                    ->formatStateUsing(fn () => 'Buka Drive')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(Tables\Columns\IconPosition::After)
                    ->color('primary')
                    ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true),
            ])
            ->defaultSort('tanggal_pelaksanaan', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Filter Lembaga')
                    ->relationship('institution', 'name', fn ($q) => $q->where('id', '!=', 1))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori Kegiatan')
                    ->options(TeacherCertificate::getKategoriOptions()),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Filter Guru')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('buka_drive')
                    ->label('Buka Drive')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true),

                Tables\Actions\ViewAction::make()
                    ->modalWidth('3xl'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeacherCertificates::route('/'),
        ];
    }
}
