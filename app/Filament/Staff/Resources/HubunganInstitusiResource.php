<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\HubunganInstitusiResource\Pages;
use App\Models\HubunganInstitusi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HubunganInstitusiResource extends Resource
{
    protected static ?string $model = HubunganInstitusi::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Hubungan & Kerja Sama';
    protected static ?string $navigationLabel = 'Hubungan Institusi';
    protected static ?string $modelLabel = 'Hubungan Institusi';
    protected static ?string $pluralModelLabel = 'Hubungan Institusi';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Humas'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 3 : static::$navigationSort;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']) ?? false;
    }

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

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['humas', 'superadmin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Informasi Kegiatan Hubungan Institusi')
                    ->description('Detail kegiatan kemitraan, kerja sama, atau kunjungan antar institusi/lembaga (contoh: Penandatanganan MoU, Kunjungan Dinas, Studi Banding)')
                    ->schema([
                        Forms\Components\TextInput::make('nama_kegiatan')
                            ->label('Nama Kegiatan')
                            ->placeholder('Contoh: Penandatanganan Nota Kesepahaman (MoU) dengan Universitas Mitra / Studi Banding')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('target_peserta')
                            ->label('Target Institusi / Peserta')
                            ->placeholder('Contoh: Dinas Pendidikan, Perguruan Tinggi Mitra, Puskesmas, Kepolisian, DUDI')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required(),

                        Forms\Components\TextInput::make('lokasi_kegiatan')
                            ->label('Lokasi Kegiatan')
                            ->placeholder('Contoh: Gedung Rektorat Kampus Mitra, Aula Sekolah, Kantor Dinas')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Rencana' => 'Rencana',
                                'Terjadwal' => 'Terjadwal',
                                'Sedang Berjalan' => 'Sedang Berjalan',
                                'Selesai' => 'Selesai',
                                'Batal' => 'Batal',
                            ])
                            ->default('Rencana')
                            ->required(),

                        Forms\Components\TextInput::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung (Link Google Drive)')
                            ->placeholder('https://drive.google.com/drive/folders/... atau https://docs.google.com/...')
                            ->url()
                            ->helperText('Sistem hanya menampung tautan/link Google Drive dari dokumen pendukung (Naskah MoU/PKS, Proposal Kerja Sama, Surat Tugas, Notula, LPJ, dll). Pastikan hak akses link sudah dapat dibuka oleh Kepala Sekolah.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->tooltip('Buka link di tab baru')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => filled($state))
                            )
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('tujuan_kegiatan')
                            ->label('Tujuan Kegiatan / Kerja Sama')
                            ->placeholder('Jelaskan tujuan strategis, ruang lingkup, dan manfaat kemitraan/kegiatan ini bagi sekolah...')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Catatan Persetujuan Kepala Sekolah')
                    ->description('Bagian ini memuat riwayat persetujuan resmi dari Kepala Sekolah')
                    ->schema([
                        Forms\Components\TextInput::make('status_persetujuan')
                            ->label('Status Persetujuan')
                            ->default('Menunggu Persetujuan')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('tanggal_persetujuan')
                            ->label('Tanggal Respon / ACC')
                            ->disabled()
                            ->placeholder('Belum ada respon'),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->disabled()
                            ->placeholder('Belum ada catatan dari Kepala Sekolah')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Detail Kegiatan Hubungan Institusi')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_kegiatan')
                            ->label('Nama Kegiatan')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('target_peserta')
                            ->label('Target Institusi / Peserta')
                            ->icon('heroicon-m-building-office-2'),

                        Infolists\Components\TextEntry::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->date('l, d F Y')
                            ->icon('heroicon-m-calendar-days'),

                        Infolists\Components\TextEntry::make('lokasi_kegiatan')
                            ->label('Lokasi Kegiatan')
                            ->icon('heroicon-m-map-pin'),

                        Infolists\Components\TextEntry::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Rencana' => 'gray',
                                'Terjadwal' => 'info',
                                'Sedang Berjalan' => 'warning',
                                'Selesai' => 'success',
                                'Batal' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('link_drive_dokumen')
                            ->label('Dokumen Pendukung Google Drive')
                            ->formatStateUsing(fn ($state) => $state ? 'Buka Dokumen di Google Drive ↗' : 'Tidak ada dokumen terlampir')
                            ->url(fn ($record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color(fn ($state) => $state ? 'info' : 'gray'),

                        Infolists\Components\TextEntry::make('tujuan_kegiatan')
                            ->label('Tujuan Kegiatan / Kerja Sama')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(2),

                Infolists\Components\Section::make('Status Persetujuan Kepala Sekolah')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_persetujuan')
                            ->label('Status ACC')
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

                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Ditinjau / Di-ACC Oleh')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('tanggal_persetujuan')
                            ->label('Waktu Persetujuan')
                            ->dateTime('d F Y, H:i WIB')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah')
                            ->default('Belum ada catatan')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Informasi Pengaju & Lembaga')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Diajukan Oleh (Humas)'),
                        Infolists\Components\TextEntry::make('institution.name')
                            ->label('Lembaga / Sekolah'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Dibuat')
                            ->dateTime('d F Y, H:i WIB'),
                    ])->columns(3)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_kegiatan')
                    ->label('Nama Kegiatan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (HubunganInstitusi $record) => $record->target_peserta),

                Tables\Columns\TextColumn::make('tanggal_pelaksanaan')
                    ->label('Tgl Pelaksanaan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lokasi_kegiatan')
                    ->label('Lokasi')
                    ->searchable()
                    ->icon('heroicon-m-map-pin'),

                Tables\Columns\TextColumn::make('link_drive_dokumen')
                    ->label('Dokumen Drive')
                    ->formatStateUsing(fn ($state) => $state ? 'Buka Drive' : '-')
                    ->url(fn (HubunganInstitusi $record) => $record->link_drive_dokumen, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => $state ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->badge(fn ($state) => (bool) $state),

                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Pelaksanaan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rencana' => 'gray',
                        'Terjadwal' => 'info',
                        'Sedang Berjalan' => 'warning',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status ACC')
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

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Di-ACC Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('status_pelaksanaan')
                    ->label('Status Pelaksanaan')
                    ->options([
                        'Rencana' => 'Rencana',
                        'Terjadwal' => 'Terjadwal',
                        'Sedang Berjalan' => 'Sedang Berjalan',
                        'Selesai' => 'Selesai',
                        'Batal' => 'Batal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye'),

                // Aksi Kepala Sekolah: ACC / Setujui
                Tables\Actions\Action::make('setujui')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (HubunganInstitusi $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('ACC Kegiatan Hubungan Institusi')
                    ->modalDescription('Apakah Anda yakin menyetujui kegiatan hubungan institusi ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Tambahan (Opsional)')
                            ->placeholder('Tuliskan catatan atau instruksi terkait kegiatan/MoU ini jika ada...'),
                    ])
                    ->action(function (HubunganInstitusi $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Disetujui',
                            'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $record->catatan_kepsek,
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Kegiatan Institusi Disetujui (ACC)')
                            ->success()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Minta Perbaikan / Revisi
                Tables\Actions\Action::make('perbaiki')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn (HubunganInstitusi $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan / Revisi')
                            ->required()
                            ->placeholder('Tuliskan poin-poin yang perlu diperbaiki oleh Humas...'),
                    ])
                    ->action(function (HubunganInstitusi $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Catatan Revisi Terkirim')
                            ->warning()
                            ->send();
                    }),

                // Aksi Kepala Sekolah: Tolak
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->button()
                    ->visible(fn (HubunganInstitusi $record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Ditolak')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan kegiatan ini ditolak...'),
                    ])
                    ->action(function (HubunganInstitusi $record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Ditolak',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Kegiatan Institusi Ditolak')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
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
            'index' => Pages\ListHubunganInstitusis::route('/'),
            'create' => Pages\CreateHubunganInstitusi::route('/create'),
            'view' => Pages\ViewHubunganInstitusi::route('/{record}'),
            'edit' => Pages\EditHubunganInstitusi::route('/{record}/edit'),
        ];
    }
}
