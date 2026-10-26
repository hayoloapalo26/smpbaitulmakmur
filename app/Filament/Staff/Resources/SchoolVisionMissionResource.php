<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\SchoolVisionMissionResource\Pages;
use App\Models\SchoolVisionMission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchoolVisionMissionResource extends Resource
{
    protected static ?string $model = SchoolVisionMission::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Visi & Misi Sekolah';
    protected static ?string $modelLabel = 'Visi & Misi Sekolah';
    protected static ?string $pluralModelLabel = 'Visi & Misi Sekolah';
    protected static ?int $navigationSort = 0;

    public static function getNavigationGroup(): ?string
    {
        return 'Manajemen Sekolah';
    }

    public static function getNavigationLabel(): string
    {
        return 'Visi, Misi & Tujuan';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'kepala_sekolah',
            'superadmin',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $active = SchoolVisionMission::getActive(auth()->user()?->institution_id);
            return $active ? $active->periode : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('VisiMisiTabs')
                    ->tabs([
                        // ============================================
                        // FITUR 1: VISI SEKOLAH
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Visi')
                            ->label('1. Visi Sekolah')
                            ->icon('heroicon-o-light-bulb')
                            ->schema([
                                Forms\Components\TextInput::make('slogan')
                                    ->label('Motto / Slogan Sekolah')
                                    ->placeholder('Contoh: Berakhlak Mulia, Cerdas, dan Berprestasi')
                                    ->maxLength(255)
                                    ->helperText('Slogan atau motto singkat yang mencerminkan karakter dan semangat visi sekolah')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('visi')
                                    ->label('Rumusan Pernyataan Visi')
                                    ->placeholder('Tuliskan visi sekolah secara jelas, inspiratif, futuristik, dan terukur...')
                                    ->rows(4)
                                    ->required()
                                    ->helperText('Pernyataan cita-cita jangka panjang dan arah utama satuan pendidikan')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('indikator_visi')
                                    ->label('Indikator Ketercapaian Visi (Opsional)')
                                    ->placeholder("Contoh:\n- Terwujudnya pembiasaan ibadah dan akhlak islami\n- Meningkatnya prestasi akademik dan non-akademik siswa\n- Terwujudnya lulusan yang menguasai literasi teknologi dan Al-Quran")
                                    ->rows(4)
                                    ->helperText('Penjabaran tanda-tanda utama bahwa visi sekolah telah berhasil diwujudkan')
                                    ->columnSpanFull(),
                            ]),

                        // ============================================
                        // FITUR 2: MISI SEKOLAH
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Misi')
                            ->label('2. Misi Sekolah')
                            ->icon('heroicon-o-rocket-launch')
                            ->schema([
                                Forms\Components\Repeater::make('misi')
                                    ->label('Daftar Poin Misi Satuan Pendidikan')
                                    ->schema([
                                        Forms\Components\TextInput::make('poin')
                                            ->label('Isi Pernyataan Misi')
                                            ->placeholder('Contoh: Menyelenggarakan pembelajaran berbasis Al-Quran dan kurikulum mutakhir')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel('+ Tambah Poin Misi')
                                    ->reorderableWithButtons()
                                    ->cloneable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['poin'] ?? 'Poin Misi Baru')
                                    ->defaultItems(3)
                                    ->required()
                                    ->helperText('Langkah-langkah strategis operasional yang dilakukan sekolah untuk mewujudkan visi')
                                    ->columnSpanFull(),
                            ]),

                        // ============================================
                        // FITUR 3: TUJUAN SEKOLAH
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Tujuan')
                            ->label('3. Tujuan Sekolah')
                            ->icon('heroicon-o-trophy')
                            ->schema([
                                Forms\Components\Repeater::make('tujuan')
                                    ->label('Daftar Poin Tujuan Sekolah')
                                    ->schema([
                                        Forms\Components\Select::make('kategori')
                                            ->label('Kategori Tujuan')
                                            ->options([
                                                'Tujuan Umum' => 'Tujuan Umum',
                                                'Jangka Pendek (1 Tahun)' => 'Jangka Pendek (1 Tahun)',
                                                'Jangka Menengah (4 Tahun)' => 'Jangka Menengah (4 Tahun)',
                                                'Jangka Panjang' => 'Jangka Panjang',
                                            ])
                                            ->default('Jangka Menengah (4 Tahun)')
                                            ->required()
                                            ->columnSpan(4),

                                        Forms\Components\TextInput::make('poin')
                                            ->label('Rumusan Tujuan')
                                            ->placeholder('Contoh: Minimal 90% lulusan hafal Juz 30 dan mampu berbahasa Arab/Inggris dasar')
                                            ->required()
                                            ->columnSpan(8),

                                        Forms\Components\TextInput::make('indikator')
                                            ->label('Target / Waktu Capaian (Opsional)')
                                            ->placeholder('Contoh: Target Tahun Ajaran 2026/2027')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(12)
                                    ->addActionLabel('+ Tambah Poin Tujuan')
                                    ->reorderableWithButtons()
                                    ->cloneable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => ($state['kategori'] ?? 'Tujuan') . ': ' . ($state['poin'] ?? 'Baru'))
                                    ->defaultItems(3)
                                    ->required()
                                    ->helperText('Target terukur yang ingin dicapai sekolah dalam rentang waktu pelaksanaan')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('sasaran_strategis')
                                    ->label('Sasaran Strategis & Kebijakan Mutu (Opsional)')
                                    ->placeholder('Catatan kebijakan mutu, program unggulan pendukung, atau strategi pencapaian tujuan sekolah...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        // ============================================
                        // PENGATURAN & PERIODE
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Periode')
                            ->label('4. Periode & Status')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Lembaga / Satuan Pendidikan')
                                    ->relationship('institution', 'name')
                                    ->default(fn () => auth()->user()?->institution_id)
                                    ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                                    ->required(),

                                Forms\Components\TextInput::make('tahun_ajaran')
                                    ->label('Tahun Ajaran')
                                    ->default('2026/2027')
                                    ->required(),

                                Forms\Components\TextInput::make('periode')
                                    ->label('Periode Renstra')
                                    ->placeholder('Contoh: 2024 - 2029')
                                    ->default('2024 - 2029')
                                    ->required()
                                    ->helperText('Rentang periode masa berlaku dokumen visi & misi'),

                                Forms\Components\Select::make('status')
                                    ->label('Status Dokumen')
                                    ->options([
                                        'Aktif' => 'Aktif (Sedang Berlaku)',
                                        'Draft' => 'Draft (Tahap Perumusan)',
                                        'Arsip' => 'Arsip (Periode Lalu)',
                                    ])
                                    ->default('Aktif')
                                    ->required()
                                    ->helperText('Hanya dokumen berstatus Aktif yang ditampilkan di kartu utama sekolah'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'ketua_yayasan', 'pembina_yayasan']) || auth()->user()?->institution_id == 1),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode Renstra')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('visi')
                    ->label('Rumusan Visi Sekolah')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->visi)
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('misi')
                    ->label('Misi')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' Poin Misi' : '1 Poin')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' Poin Tujuan' : '1 Poin')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Draft' => 'warning',
                        'Arsip' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Draft' => 'Draft',
                        'Arsip' => 'Arsip',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('🎯 Visi Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('slogan')
                            ->label('Motto / Slogan')
                            ->badge()
                            ->color('warning')
                            ->visible(fn ($record) => !empty($record->slogan)),

                        Infolists\Components\TextEntry::make('visi')
                            ->label('Rumusan Visi Utama')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('indikator_visi')
                            ->label('Indikator Ketercapaian Visi')
                            ->visible(fn ($record) => !empty($record->indikator_visi)),
                    ]),

                Infolists\Components\Section::make('🚀 Misi Sekolah')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('misi')
                            ->label('Poin-Poin Misi')
                            ->schema([
                                Infolists\Components\TextEntry::make('poin')
                                    ->label('Misi')
                                    ->bulleted(),
                            ]),
                    ]),

                Infolists\Components\Section::make('🏆 Tujuan Sekolah')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('tujuan')
                            ->label('Poin-Poin Tujuan Satuan Pendidikan')
                            ->schema([
                                Infolists\Components\TextEntry::make('kategori')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('poin')
                                    ->label('Rumusan Tujuan'),
                                Infolists\Components\TextEntry::make('indikator')
                                    ->label('Target Waktu')
                                    ->placeholder('-'),
                            ])
                            ->columns(3),

                        Infolists\Components\TextEntry::make('sasaran_strategis')
                            ->label('Sasaran Strategis & Kebijakan Mutu')
                            ->visible(fn ($record) => !empty($record->sasaran_strategis)),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSchoolVisionMissions::route('/'),
        ];
    }
}
