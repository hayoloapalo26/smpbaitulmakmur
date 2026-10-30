<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\OsisProgramResource\Pages;
use App\Models\OsisProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OsisProgramResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
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

    protected static ?string $model = OsisProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Program OSIS';
    protected static ?string $recordTitleAttribute = 'nama_program';

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Program OSIS')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')->required(),
                        Forms\Components\DatePicker::make('tanggal')->required(),
                        Forms\Components\Select::make('status')
                            ->options(['direncanakan'=>'Direncanakan','berjalan'=>'Berjalan','selesai'=>'Selesai','batal'=>'Batal'])
                            ->required(),
                        Forms\Components\TextInput::make('proposal_file')
                            ->label('Proposal (Link Google Drive)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen proposal untuk menghemat penyimpanan hosting.')
                            ->maxLength(2048),
                        Forms\Components\TextInput::make('lpj_file')
                            ->label('LPJ (Link Google Drive)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen LPJ untuk menghemat penyimpanan hosting.')
                            ->maxLength(2048),
                    ])->columns(2),

                Forms\Components\Section::make('Verifikasi & Persetujuan Kepala Sekolah (Approval)')
                    ->schema([
                        Forms\Components\Select::make('status_persetujuan')
                            ->label('Status Persetujuan')
                            ->options([
                                'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                                'Disetujui' => 'Disetujui',
                                'Perlu Perbaikan' => 'Perlu Perbaikan',
                            ])
                            ->default('Menunggu Persetujuan')
                            ->required()
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin'])),
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan / Arahan Kepala Sekolah')
                            ->placeholder('Catatan atau arahan kepala sekolah...')
                            ->rows(3)
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin'])),
                        Forms\Components\DateTimePicker::make('tanggal_persetujuan')
                            ->label('Waktu Persetujuan')
                            ->disabled()
                            ->visible(fn ($record) => filled($record?->tanggal_persetujuan)),
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
                Tables\Columns\TextColumn::make('nama_program')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Approval KS')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Disetujui' => 'success',
                        'Perlu Perbaikan' => 'danger',
                        'Menunggu Persetujuan' => 'warning',
                        default => 'gray',
                    })
                    ->default('Menunggu Persetujuan'),

                Tables\Columns\TextColumn::make('catatan_kepsek')
                    ->label('Catatan Kepala Sekolah')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->catatan_kepsek)
                    ->color('warning')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('proposal_file')
                    ->label('Proposal')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka Proposal' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('lpj_file')
                    ->label('LPJ')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka LPJ' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['direncanakan'=>'Direncanakan','berjalan'=>'Berjalan','selesai'=>'Selesai','batal'=>'Batal']),
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan')
                    ->options([
                        'Menunggu Persetujuan' => 'Menunggu Persetujuan',
                        'Disetujui' => 'Disetujui',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja OSIS'))
                    ->modalWidth('4xl'),
                Tables\Actions\Action::make('setuju')
                    ->label('ACC')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $record->status_persetujuan !== 'Disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('Persetujuan Program Kerja OSIS')
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
                            ->title('Program Kerja OSIS Disetujui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('perbaiki')
                    ->label('Revisi')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Perbaikan dari Kepala Sekolah')
                            ->required()
                            ->placeholder('Tuliskan poin-poin yang perlu diperbaiki oleh Pembina OSIS...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_persetujuan' => 'Perlu Perbaikan',
                            'catatan_kepsek' => $data['catatan_kepsek'],
                            'tanggal_persetujuan' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Catatan Revisi Terkirim')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(route('report.osis'))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOsisPrograms::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
