<?php

namespace App\Filament\Staff\Resources;

use App\Models\ProgramHumas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramHumasResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ProgramHumas::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Humas';
    protected static ?string $modelLabel = 'Program Kerja Humas';
    protected static ?string $pluralModelLabel = 'Program Kerja Humas';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            $count = static::getModel()::where('status_persetujuan', 'Menunggu Persetujuan')->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && !$user->hasRole('superadmin') && $user->institution_id) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id);
            });
        }

        return $query;
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
                            ->options(['Publikasi' => 'Publikasi', 'Kemitraan' => 'Kemitraan', 'Event' => 'Event', 'Penerimaan Siswa' => 'Penerimaan Siswa', 'Lainnya' => 'Lainnya'])
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
                            ->label('Link Google Drive Dokumentasi & Dokumen Pendukung')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://docs.google.com/...')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen atau foto kegiatan untuk dokumentasi & arsip.')
                            ->maxLength(2048),
                            
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
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->catatan_kepsek),
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
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('kategori')
                    ->options(['Publikasi' => 'Publikasi', 'Kemitraan' => 'Kemitraan', 'Event' => 'Event', 'Penerimaan Siswa' => 'Penerimaan Siswa', 'Lainnya' => 'Lainnya']),
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
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja Humas'))
                    ->modalWidth('4xl'),

                // Aksi Kepala Sekolah: ACC / Setujui
                Tables\Actions\Action::make('setuju')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('Persetujuan Program Kerja Humas')
                    ->modalDescription('Apakah Anda yakin menyetujui program kerja ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan / Disposisi (Opsional)')
                            ->placeholder('Berikan catatan jika ada...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'catatan_kepsek' => $data['catatan_kepsek'] ?? $record->catatan_kepsek,
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Program Kerja Disetujui')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Minta Perbaikan
                Tables\Actions\Action::make('perbaiki')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan / Revisi')
                            ->required()
                            ->placeholder('Tuliskan poin-poin yang perlu diperbaiki oleh Humas...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Catatan Perbaikan Dikirim')
                            ->warning()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Tolak
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
                            'approved_by' => auth()->id(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Program Kerja Ditolak')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !auth()->user()?->hasRole('kepala_sekolah')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !auth()->user()?->hasRole('kepala_sekolah')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => !auth()->user()?->hasRole('kepala_sekolah')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\ProgramHumasResource\Pages\ManageProgramHumas::route('/'),
        ];
    }
}
