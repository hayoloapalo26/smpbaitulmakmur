<?php

namespace App\Filament\Staff\Resources;

use App\Models\ProgramKepsek;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramKepsekResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ProgramKepsek::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Proker Sekolah';
    protected static ?string $modelLabel = 'Proker Sekolah';
    protected static ?string $pluralModelLabel = 'Proker Sekolah';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
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
                            ->options(['Manajemen' => 'Manajemen', 'Evaluasi Kinerja' => 'Evaluasi Kinerja', 'Pengembangan Sekolah' => 'Pengembangan Sekolah', 'Lainnya' => 'Lainnya'])
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
                            ->placeholder('Contoh: Seluruh Guru / Siswa Kelas X'),
                            
                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Waktu Pelaksanaan')
                            ->placeholder('Contoh: Setiap Semester / Juli 2026'),
                            
                        Forms\Components\TextInput::make('dokumen_terkait')
                            ->label('Dokumen Terkait')
                            ->placeholder('Contoh: SK, Proposal'),
                            
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
                    ->options(['Manajemen' => 'Manajemen', 'Evaluasi Kinerja' => 'Evaluasi Kinerja', 'Pengembangan Sekolah' => 'Pengembangan Sekolah', 'Lainnya' => 'Lainnya']),
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
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja Kepala Sekolah'))
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

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\ProgramKepsekResource\Pages\ManageProgramKepseks::route('/'),
        ];
    }
}
