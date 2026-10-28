<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\BkWorkProgramResource\Pages;
use App\Models\BkWorkProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BkWorkProgramResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['bk', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = BkWorkProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Bimbingan Konseling';
    protected static ?string $modelLabel = 'Bimbingan Konseling';
    protected static ?string $pluralModelLabel = 'Bimbingan Konseling';
    protected static ?int $navigationSort = 2;

    

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                
                Forms\Components\Section::make('Informasi Program')
                    ->schema([
                        Forms\Components\TextInput::make('judul_program')
                            ->label('Nama Program / Kegiatan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('bidang_layanan')
                            ->label('Bidang / Kategori Program')
                            ->options([
                                'Pribadi' => 'Bimbingan Pribadi',
                                'Sosial' => 'Bimbingan Sosial',
                                'Belajar' => 'Bimbingan Belajar',
                                'Karir' => 'Bimbingan Karir',
                                'Lainnya' => 'Lainnya',
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
                            ->required()
                            ->default('Tahunan'),
                            
                        Forms\Components\Select::make('sifat')
                            ->label('Sifat Kegiatan')
                            ->options([
                                'Wajib' => 'Wajib',
                                'Kontinu' => 'Kontinu',
                                'Terjadwal' => 'Terjadwal',
                            ])
                            ->required()
                            ->default('Wajib'),
                            
                        Forms\Components\Select::make('status')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Rencana' => 'Rencana',
                                'Sedang Berjalan' => 'Sedang Berjalan',
                                'Terlaksana' => 'Terlaksana',
                                'Tidak Terlaksana' => 'Tidak Terlaksana',
                            ])
                            ->default('Rencana')
                            ->required(),

                        Forms\Components\TextInput::make('sasaran')
                            ->label('Sasaran Peserta')
                            ->required()
                            ->placeholder('Contoh: Siswa Kelas 7 / Kasus Khusus'),
                            
                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Waktu Pelaksanaan')
                            ->required()
                            ->placeholder('Contoh: Semester Ganjil / Setiap Senin'),
                            
                        Forms\Components\TextInput::make('dokumen_terkait')
                            ->label('Dokumen Terkait')
                            ->placeholder('Contoh: Jurnal Konseling, Surat Panggilan'),
                            
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
                            ->required()
                            ->rows(3),
                        Forms\Components\RichEditor::make('teknis_pelaksanaan')
                            ->label('Teknis Pelaksanaan')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan Tambahan (Opsional)')
                            ->rows(2),
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
                Tables\Columns\TextColumn::make('judul_program')
                    ->label('Program Kerja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('bidang_layanan')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pribadi' => 'success',
                        'Sosial' => 'info',
                        'Belajar' => 'warning',
                        'Karir' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tipe_pelaksanaan')
                    ->label('Tipe')
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_pelaksanaan')
                    ->label('Waktu'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rencana' => 'gray',
                        'Sedang Berjalan' => 'primary',
                        'Terlaksana' => 'success',
                        'Tidak Terlaksana' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Penyusun')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin'])),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Rencana' => 'Rencana',
                        'Sedang Berjalan' => 'Sedang Berjalan',
                        'Terlaksana' => 'Terlaksana',
                        'Tidak Terlaksana' => 'Tidak Terlaksana',
                    ]),
                Tables\Filters\SelectFilter::make('bidang_layanan')
                    ->options([
                        'Pribadi' => 'Pribadi',
                        'Sosial' => 'Sosial',
                        'Belajar' => 'Belajar',
                        'Karir' => 'Karir',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->judul_program ?? 'Program Kerja BK'))
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
            'index' => Pages\ListBkWorkPrograms::route('/'),
            'create' => Pages\CreateBkWorkProgram::route('/create'),
            'view' => Pages\ViewBkWorkProgram::route('/{record}'),
            'edit' => Pages\EditBkWorkProgram::route('/{record}/edit'),
        ];
    }
}
