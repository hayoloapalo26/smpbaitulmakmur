<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ProgramEvaluationResource\Pages;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use App\Models\ProgramEvaluation;
use App\Models\ProgramHumas;
use App\Models\ProgramKepsek;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ProgramEvaluationResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan']) ?? false;
    }

    protected static ?string $model = ProgramEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Hasil Evaluasi Program';
    protected static ?string $modelLabel = 'Hasil Evaluasi Program';
    protected static ?string $pluralModelLabel = 'Hasil Evaluasi Program';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Identitas Program Kerja')
                    ->description('Pilih divisi dan nama program yang dievaluasi')
                    ->schema([
                        Forms\Components\Select::make('divisi')
                            ->label('Divisi / Bidang')
                            ->options([
                                'Kepala Sekolah' => 'Kepala Sekolah (Manajemen)',
                                'Kurikulum' => 'Waka Kurikulum',
                                'Kesiswaan' => 'Waka Kesiswaan',
                                'Sarpras' => 'Sarana & Prasarana',
                                'Humas' => 'Hubungan Masyarakat',
                                'Bimbingan Konseling' => 'Bimbingan Konseling (BK)',
                                'Wali Kelas' => 'Program Wali Kelas',
                                'Umum' => 'Program Umum / Lintas Divisi',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program Kerja')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Periode Evaluasi')
                            ->options([
                                'Semester Ganjil' => 'Semester Ganjil',
                                'Semester Genap' => 'Semester Genap',
                                'Tahunan' => 'Tahunan / Akhir Tahun',
                            ])
                            ->default('Semester Ganjil')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Ketercapaian & Predikat')
                    ->schema([
                        Forms\Components\TextInput::make('persentase_ketercapaian')
                            ->label('Persentase Ketercapaian')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(100)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $pct = (int) $state;
                                if ($pct >= 90) {
                                    $set('predikat', 'Sangat Baik');
                                } elseif ($pct >= 75) {
                                    $set('predikat', 'Baik');
                                } elseif ($pct >= 60) {
                                    $set('predikat', 'Cukup');
                                } else {
                                    $set('predikat', 'Kurang');
                                }
                            }),

                        Forms\Components\Select::make('predikat')
                            ->label('Predikat Capaian')
                            ->options([
                                'Sangat Baik' => 'Sangat Baik',
                                'Baik' => 'Baik',
                                'Cukup' => 'Cukup',
                                'Kurang' => 'Kurang / Perlu Perbaikan',
                            ])
                            ->default('Baik')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status Evaluasi')
                            ->options([
                                'Draft' => 'Draft',
                                'Final' => 'Final',
                                'Disetujui Yayasan' => 'Disetujui Yayasan',
                            ])
                            ->default('Final')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Analisis & Rekomendasi Tindak Lanjut')
                    ->schema([
                        Forms\Components\Textarea::make('realisasi_kegiatan')
                            ->label('Realisasi & Bukti Pelaksanaan')
                            ->placeholder('Jelaskan bagaimana program ini terlaksana secara faktual...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('kendala')
                            ->label('Kendala & Hambatan')
                            ->placeholder('Hambatan, kendala teknis, anggaran, atau sumber daya yang dihadapi...')
                            ->rows(3),

                        Forms\Components\Textarea::make('solusi_tindak_lanjut')
                            ->label('Solusi & Rekomendasi Tindak Lanjut')
                            ->placeholder('Saran perbaikan, kelanjutan, atau modifikasi untuk periode berikutnya...')
                            ->rows(3),

                        Forms\Components\Textarea::make('catatan_kepsek')
                            ->label('Catatan Kepala Sekolah / Evaluator')
                            ->placeholder('Catatan atau arahan pimpinan...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('file_evaluasi')
                            ->label('Link Google Drive LPJ / Laporan Evaluasi (Opsional)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen LPJ / laporan evaluasi untuk menghemat kapasitas hosting.')
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])->columns(2),
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
                    ->weight('bold')
                    ->description(fn (ProgramEvaluation $record) => $record->tahun_ajaran . ' • ' . $record->semester),

                Tables\Columns\TextColumn::make('divisi')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Kepala Sekolah' => 'primary',
                        'Kurikulum' => 'info',
                        'Kesiswaan' => 'success',
                        'Sarpras' => 'warning',
                        'Humas' => 'danger',
                        'Bimbingan Konseling' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('persentase_ketercapaian')
                    ->label('Capaian')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'info',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Baik' => 'success',
                        'Baik' => 'info',
                        'Cukup' => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('solusi_tindak_lanjut')
                    ->label('Tindak Lanjut')
                    ->limit(40)
                    ->tooltip(fn (ProgramEvaluation $record) => $record->solusi_tindak_lanjut ?? '-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Final' => 'success',
                        'Disetujui Yayasan' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('file_evaluasi')
                    ->label('Dokumen LPJ')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka LPJ' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('divisi')
                    ->options([
                        'Kepala Sekolah' => 'Kepala Sekolah',
                        'Kurikulum' => 'Kurikulum',
                        'Kesiswaan' => 'Kesiswaan',
                        'Sarpras' => 'Sarpras',
                        'Humas' => 'Humas',
                        'Bimbingan Konseling' => 'Bimbingan Konseling',
                        'Wali Kelas' => 'Wali Kelas',
                    ]),

                Tables\Filters\SelectFilter::make('predikat')
                    ->options([
                        'Sangat Baik' => 'Sangat Baik',
                        'Baik' => 'Baik',
                        'Cukup' => 'Cukup',
                        'Kurang' => 'Kurang',
                    ]),

                Tables\Filters\SelectFilter::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options([
                        '2026/2027' => '2026/2027',
                        '2025/2026' => '2025/2026',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ManageProgramEvaluations::route('/'),
        ];
    }
}
