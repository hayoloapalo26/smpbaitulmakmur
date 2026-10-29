<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherCertificateResource\Pages;
use App\Models\TeacherCertificate;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeacherCertificateResource extends Resource
{
    protected static ?string $model = TeacherCertificate::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Sertifikat Guru';
    protected static ?string $modelLabel = 'Sertifikat Guru';
    protected static ?string $pluralModelLabel = 'Sertifikat Guru';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return 'Manajemen Administrasi';
        }

        return 'Administrasi & Data Induk';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge([
            'guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas',
            'guru_piket', 'bk', 'waka_kurikulum', 'waka_kesiswaan',
            'kepala_sekolah', 'tata_usaha', 'superadmin', 'koordinator_madin'
        ], \App\Models\User::QURAN_TEACHER_ROLES)) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        if (!$user) {
            return $query;
        }

        // Kepala Sekolah, Waka Kurikulum, Tata Usaha, atau Superadmin dapat memantau seluruh guru di lembaganya
        if ($user->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'tata_usaha', 'superadmin'])) {
            return $query;
        }

        // Guru biasa hanya dapat melihat sertifikat miliknya sendiri
        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Guru')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('Nama Guru')
                            ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) {
                                $user = Auth::user();
                                if ($user && $user->institution_id && !$user->hasRole('superadmin')) {
                                    $query->where('institution_id', $user->institution_id);
                                }
                                $query->whereHas('roles', fn ($q) => $q->whereIn('name', array_merge([
                                    'guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_piket', 'bk', 'waka_kurikulum', 'waka_kesiswaan', 'koordinator_madin'
                                ], \App\Models\User::QURAN_TEACHER_ROLES)));
                            })
                            ->default(fn () => Auth::id())
                            ->searchable()
                            ->preload()
                            ->visible(fn () => Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin']))
                            ->dehydrated(fn () => Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin']))
                            ->required(fn () => Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin'])),

                        Forms\Components\Hidden::make('teacher_id')
                            ->default(fn () => Auth::id())
                            ->dehydrated(fn () => !Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin']))
                            ->visible(fn () => !Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin'])),

                        Forms\Components\Placeholder::make('nama_guru_info')
                            ->label('Nama Guru')
                            ->content(fn () => Auth::user()?->name . ' (' . (Auth::user()?->institution?->name ?? 'Lembaga') . ')')
                            ->visible(fn () => !Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin'])),
                    ]),

                Forms\Components\Section::make('Rincian Kegiatan & Pelatihan')
                    ->schema([
                        Forms\Components\TextInput::make('nama_kegiatan')
                            ->label('Nama Kegiatan / Pelatihan / Webinar')
                            ->placeholder('Contoh: Pelatihan Pemanfaatan AI untuk Pembelajaran Interaktif')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('penyelenggara')
                            ->label('Penyelenggara Kegiatan')
                            ->placeholder('Contoh: Kemendikbudristek / Balai Guru Penggerak / MGMP')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Kegiatan')
                            ->options(TeacherCertificate::getKategoriOptions())
                            ->default('Pelatihan / Bimtek')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan / Penerbitan Sertifikat')
                            ->default(now()->toDateString())
                            ->required(),

                        Forms\Components\TextInput::make('nomor_sertifikat')
                            ->label('Nomor Sertifikat (Opsional)')
                            ->placeholder('Contoh: 1245/BGP/Diklat/2026')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('jumlah_jam')
                            ->label('Jumlah Jam Pelajaran (JP)')
                            ->numeric()
                            ->placeholder('Contoh: 32')
                            ->suffix('JP')
                            ->helperText('Kosongkan bila tidak terdapat keterangan JP pada sertifikat'),
                    ])->columns(2),

                Forms\Components\Section::make('Tautan / Link Google Drive Sertifikat')
                    ->description('Cantumkan link Google Drive sertifikat Anda agar tidak membebani penyimpanan database lokal.')
                    ->schema([
                        Forms\Components\TextInput::make('drive_url')
                            ->label('Link Google Drive Sertifikat')
                            ->placeholder('https://drive.google.com/file/d/... atau https://drive.google.com/open?id=...')
                            ->required()
                            ->dehydrateStateUsing(function (?string $state): ?string {
                                if (!$state) return null;
                                $state = trim($state);
                                if (!preg_match('#^https?://#i', $state)) {
                                    $state = 'https://' . $state;
                                }
                                return $state;
                            })
                            ->columnSpanFull()
                            ->helperText('💡 Pastikan izin akses link diatur ke "Siapa saja yang memiliki link dapat melihat (Viewer)" agar dapat dilihat oleh Kepala Sekolah dan Yayasan.'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Materi Pokok yang Dipelajari (Opsional)')
                            ->placeholder('Ringkasan materi, kompetensi yang dicapai, atau catatan penting pelatihan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Sertifikat Guru')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('Nama Guru')
                            ->icon('heroicon-o-user')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga')
                            ->badge()
                            ->color('gray')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('nama_kegiatan')
                            ->label('Nama Kegiatan / Pelatihan')
                            ->columnSpanFull()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('penyelenggara')
                            ->label('Penyelenggara')
                            ->icon('heroicon-o-building-office-2'),

                        Infolists\Components\TextEntry::make('kategori')
                            ->label('Kategori')
                            ->badge()
                            ->color(fn (string $state): string => TeacherCertificate::getKategoriBadgeColor($state)),

                        Infolists\Components\TextEntry::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan / Terbit')
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
                            ->label('Tautan Google Drive')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color('primary')
                            ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true)
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Catatan / Materi yang Dipelajari')
                            ->placeholder('Tidak ada catatan materi tambahan.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin', 'tata_usaha'])),

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
                    ->label('Beban (JP)')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} JP" : '-')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('drive_url')
                    ->label('Sertifikat')
                    ->formatStateUsing(fn () => 'Buka Drive')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->color('primary')
                    ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true),
            ])
            ->defaultSort('tanggal_pelaksanaan', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori Kegiatan')
                    ->options(TeacherCertificate::getKategoriOptions()),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Filter Guru')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin', 'tata_usaha'])),
            ])
            ->actions([
                Tables\Actions\Action::make('buka_drive')
                    ->label('Buka Drive')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record) => $record->drive_url, shouldOpenInNewTab: true),

                Tables\Actions\ViewAction::make()
                    ->modalWidth('3xl'),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->teacher_id === Auth::id() || Auth::user()?->hasRole('superadmin'))
                    ->modalWidth('2xl'),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->teacher_id === Auth::id() || Auth::user()?->hasRole('superadmin')),
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
            'index' => Pages\ManageTeacherCertificates::route('/'),
        ];
    }
}
