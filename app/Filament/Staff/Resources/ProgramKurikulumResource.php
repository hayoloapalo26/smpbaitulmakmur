<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ProgramKurikulumResource\Pages;
use App\Models\ProgramKurikulum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramKurikulumResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ProgramKurikulum::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Program Kerja Kurikulum';
    protected static ?string $pluralModelLabel = 'Program Kerja Kurikulum';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Bab 1: Program Kerja WKr';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return '- Fitur Input Program Kerja WKr';
        }
        return 'Program Kerja Kurikulum';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 1;
        }
        return static::$navigationSort;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin']);
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
                                'Perencanaan Pembelajaran' => 'Perencanaan Pembelajaran',
                                'Pelaksanaan KBM' => 'Pelaksanaan KBM',
                                'Evaluasi & Penilaian' => 'Evaluasi & Penilaian',
                                'Pengembangan SDM/Guru' => 'Pengembangan SDM/Guru',
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
                            ->placeholder('Contoh: SK, Silabus, RPP'),
                            
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

                Forms\Components\Section::make('Status Persetujuan Kepala Sekolah')
                    ->schema([
                        Forms\Components\TextInput::make('status_persetujuan')
                            ->label('Status Persetujuan')
                            ->default('Menunggu Persetujuan')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('tanggal_persetujuan')
                            ->label('Tanggal Respon')
                            ->disabled(),
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Persetujuan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Disetujui' => 'success',
                        'Perlu Perbaikan' => 'warning',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'Disetujui' => 'heroicon-m-check-circle',
                        'Perlu Perbaikan' => 'heroicon-m-exclamation-triangle',
                        'Ditolak' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-clock',
                    }),
                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Status Pelaksanaan')
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
                        'Perencanaan Pembelajaran' => 'Perencanaan Pembelajaran',
                        'Pelaksanaan KBM' => 'Pelaksanaan KBM',
                        'Evaluasi & Penilaian' => 'Evaluasi & Penilaian',
                        'Pengembangan SDM/Guru' => 'Pengembangan SDM/Guru',
                        'Lainnya' => 'Lainnya',
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
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye'),

                Tables\Actions\Action::make('setuju')
                    ->label('Setuju')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('Persetujuan Program Kerja')
                    ->modalDescription('Apakah Anda yakin menyetujui program kerja ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function ($record) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'tanggal_persetujuan' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Program Kerja Disetujui')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('perbaiki')
                    ->label('Perbaiki')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan / Revisi')
                            ->required()
                            ->placeholder('Tuliskan poin-poin yang perlu diperbaiki oleh Waka Kurikulum...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Catatan Perbaikan Dikirim')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Ditolak')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan penolakan program kerja ini...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Ditolak',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Program Kerja Ditolak')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin'])),
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
            'index' => Pages\ListProgramKurikulums::route('/'),
            'create' => Pages\CreateProgramKurikulum::route('/create'),
            'view' => Pages\ViewProgramKurikulum::route('/{record}'),
            'edit' => Pages\EditProgramKurikulum::route('/{record}/edit'),
        ];
    }
}
