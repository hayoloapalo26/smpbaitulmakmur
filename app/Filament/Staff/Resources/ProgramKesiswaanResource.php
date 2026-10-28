<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ProgramKesiswaanResource\Pages;
use App\Models\ProgramKesiswaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramKesiswaanResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ProgramKesiswaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Program Kerja Kesiswaan';
    protected static ?string $pluralModelLabel = 'Program Kerja Kesiswaan';
    protected static ?int $navigationSort = 1;

    

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                
                Forms\Components\Section::make('Informasi Program')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program / Kegiatan')
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Program')
                            ->options([
                                'PPDB (Penerimaan Peserta Didik Baru)' => 'PPDB (Penerimaan Peserta Didik Baru)',
                                'Pembinaan Karakter & Siswa' => 'Pembinaan Karakter & Siswa',
                                'Kelulusan' => 'Kelulusan',
                                'PHBI (Peringatan Hari Besar Islam)' => 'PHBI (Peringatan Hari Besar Islam)',
                                'PHBN (Peringatan Hari Besar Nasional)' => 'PHBN (Peringatan Hari Besar Nasional)',
                                'Program Keberlanjutan & Penunjang' => 'Program Keberlanjutan & Penunjang',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('tipe_pelaksanaan')
                            ->label('Tipe / Frekuensi')
                            ->options([
                                'Harian' => 'Harian',
                                'Mingguan' => 'Mingguan',
                                'Bulanan' => 'Bulanan',
                                'Semesteran' => 'Semesteran',
                                'Tahunan' => 'Tahunan',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('sifat')
                            ->label('Sifat Kegiatan')
                            ->options([
                                'Wajib' => 'Wajib',
                                'Kontinu' => 'Kontinu',
                                'Terjadwal' => 'Terjadwal',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Rencana' => 'Rencana',
                                'Sedang Berjalan' => 'Sedang Berjalan',
                                'Selesai' => 'Selesai',
                                'Batal' => 'Batal',
                            ])
                            ->default('Rencana')
                            ->required(),

                        Forms\Components\TextInput::make('sasaran')
                            ->label('Sasaran Peserta')
                            ->placeholder('Contoh: Seluruh Siswa / Kelas X / Tim Ekstrakurikuler'),
                            
                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Waktu Pelaksanaan')
                            ->placeholder('Contoh: Setiap Senin / Juli 2026'),
                            
                        Forms\Components\TextInput::make('dokumen_terkait')
                            ->label('Dokumen Terkait')
                            ->placeholder('Contoh: SK, Proposal, Jadwal'),
                            
                        Forms\Components\TextInput::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Opsional (Ketik angka saja)'),
                            
                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail & Pelaksanaan')
                    ->schema([
                        Forms\Components\Textarea::make('tujuan')
                            ->label('Tujuan Program')
                            ->rows(3),
                        Forms\Components\RichEditor::make('teknis_pelaksanaan')
                            ->label('Teknis Pelaksanaan')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                    ])->columns(1),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('summary_table')
                    ->view('filament.staff.components.program-kerja-summary-table')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('tipe_pelaksanaan')
                    ->label('Tipe')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sifat')
                    ->label('Sifat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Wajib' => 'danger',
                        'Kontinu' => 'info',
                        'Terjadwal' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waktu_pelaksanaan')
                    ->label('Waktu')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rencana' => 'gray',
                        'Sedang Berjalan' => 'warning',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'PPDB (Penerimaan Peserta Didik Baru)' => 'PPDB',
                        'Pembinaan Karakter & Siswa' => 'Pembinaan Karakter',
                        'Kelulusan' => 'Kelulusan',
                        'PHBI (Peringatan Hari Besar Islam)' => 'PHBI',
                        'PHBN (Peringatan Hari Besar Nasional)' => 'PHBN',
                        'Program Keberlanjutan & Penunjang' => 'Penunjang',
                    ]),
                Tables\Filters\SelectFilter::make('tipe_pelaksanaan')
                    ->options([
                        'Harian' => 'Harian',
                        'Mingguan' => 'Mingguan',
                        'Bulanan' => 'Bulanan',
                        'Semesteran' => 'Semesteran',
                        'Tahunan' => 'Tahunan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja Kesiswaan'))
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramKesiswaans::route('/'),
            'create' => Pages\CreateProgramKesiswaan::route('/create'),
            'view' => Pages\ViewProgramKesiswaan::route('/{record}'),
            'edit' => Pages\EditProgramKesiswaan::route('/{record}/edit'),
        ];
    }
}
