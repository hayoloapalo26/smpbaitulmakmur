<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\WorkProgramResource\Pages;
use App\Models\WorkProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class WorkProgramResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = WorkProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $modelLabel = 'Program Kerja';
    protected static ?string $recordTitleAttribute = 'nama_program';

    protected static ?string $pluralModelLabel = 'Program Kerja';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('kepala_sekolah')) {
            return $query;
        }

        $divisi = null;
        if ($user->hasRole('waka_kurikulum')) $divisi = 'waka_kurikulum';
        elseif ($user->hasRole('waka_kesiswaan')) $divisi = 'waka_kesiswaan';
        elseif ($user->hasRole('sarpras')) $divisi = 'sarpras';
        elseif ($user->hasRole('humas')) $divisi = 'humas';

        if ($divisi) {
            $query->where('divisi', $divisi);
        }

        return $query;
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Program')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('divisi')
                            ->options([
                                'kepala_sekolah' => 'Kepala Sekolah',
                                'waka_kurikulum' => 'Waka Kurikulum',
                                'waka_kesiswaan' => 'Waka Kesiswaan',
                                'sarpras' => 'Sarana Prasarana',
                                'humas' => 'Humas',
                            ])
                            ->required()
                            ->default(function () {
                                $user = auth()->user();
                                if ($user->hasRole('waka_kurikulum')) return 'waka_kurikulum';
                                if ($user->hasRole('waka_kesiswaan')) return 'waka_kesiswaan';
                                if ($user->hasRole('sarpras')) return 'sarpras';
                                if ($user->hasRole('humas')) return 'humas';
                                return 'kepala_sekolah';
                            }),
                        Forms\Components\Select::make('penanggung_jawab_id')
                            ->relationship('penanggungJawab', 'name')
                            ->required()
                            ->default(auth()->id()),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->required(),
                        Forms\Components\Textarea::make('target_pencapaian')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Dokumen')
                    ->schema([
                        Forms\Components\Select::make('status_pengajuan')
                            ->options([
                                'draft' => 'Draft',
                                'diajukan' => 'Diajukan',
                                'disetujui_ks' => 'Disetujui KS',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\Select::make('status_pelaksanaan')
                            ->options([
                                'mendatang' => 'Mendatang',
                                'berjalan' => 'Berjalan',
                                'selesai' => 'Selesai',
                            ])
                            ->default('mendatang')
                            ->required(),
                        Forms\Components\FileUpload::make('file_proposal')
                            ->label('File Proposal')
                            ->directory('work_programs/proposal'),
                        Forms\Components\FileUpload::make('file_lpj')
                            ->label('File LPJ (Jika Selesai)')
                            ->directory('work_programs/lpj'),
                    ])->columns(2),

                Forms\Components\Section::make('Penilaian Kepala Sekolah')
                    ->schema([
                        Forms\Components\TextInput::make('skor_penilaian')
                            ->label('Skor Penilaian (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->disabled(fn () => !auth()->user()->hasRole('kepala_sekolah')),
                        Forms\Components\Select::make('laporan_yayasan')
                            ->options([
                                'tidak' => 'Tidak Dilaporkan',
                                'mingguan' => 'Laporan Mingguan',
                                'bulanan' => 'Laporan Bulanan',
                                'tahunan' => 'Laporan Tahunan',
                            ])
                            ->default('tidak')
                            ->disabled(fn () => !auth()->user()->hasRole('kepala_sekolah')),
                        Forms\Components\Textarea::make('catatan_ks')
                            ->label('Catatan Kepala Sekolah')
                            ->disabled(fn () => !auth()->user()->hasRole('kepala_sekolah'))
                            ->columnSpanFull(),
                    ])->columns(2)->visible(fn ($get) => $get('status_pengajuan') === 'disetujui_ks' || auth()->user()->hasRole('kepala_sekolah')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')->searchable(),
                Tables\Columns\TextColumn::make('divisi')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->badge(),
                Tables\Columns\TextColumn::make('tanggal_mulai')->date()->sortable(),
                Tables\Columns\TextColumn::make('status_pengajuan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui_ks' => 'success',
                        'ditolak' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mendatang' => 'gray',
                        'berjalan' => 'primary',
                        'selesai' => 'success',
                    }),
                Tables\Columns\TextColumn::make('skor_penilaian')
                    ->numeric()
                    ->sortable()
                    ->label('Skor'),
                Tables\Columns\TextColumn::make('laporan_yayasan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tidak' => 'gray',
                        'mingguan' => 'info',
                        'bulanan' => 'warning',
                        'tahunan' => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('divisi')
                    ->options([
                        'kepala_sekolah' => 'Kepala Sekolah',
                        'waka_kurikulum' => 'Waka Kurikulum',
                        'waka_kesiswaan' => 'Waka Kesiswaan',
                        'sarpras' => 'Sarana Prasarana',
                        'humas' => 'Humas',
                    ])
                    ->visible(fn () => auth()->user()->hasRole('kepala_sekolah')),
                Tables\Filters\SelectFilter::make('status_pelaksanaan')
                    ->options([
                        'mendatang' => 'Mendatang',
                        'berjalan' => 'Berjalan',
                        'selesai' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Aksi Khusus KS: Setujui
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WorkProgram $record) => auth()->user()->hasRole('kepala_sekolah') && $record->status_pengajuan === 'diajukan')
                    ->action(fn (WorkProgram $record) => $record->update(['status_pengajuan' => 'disetujui_ks']))
                    ->requiresConfirmation(),
                    
                // Aksi Khusus KS: Tolak
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WorkProgram $record) => auth()->user()->hasRole('kepala_sekolah') && $record->status_pengajuan === 'diajukan')
                    ->form([
                        Textarea::make('catatan_ks')->label('Alasan Penolakan')->required()
                    ])
                    ->action(function (WorkProgram $record, array $data) {
                        $record->update([
                            'status_pengajuan' => 'ditolak',
                            'catatan_ks' => $data['catatan_ks']
                        ]);
                    })
                    ->requiresConfirmation(),

                // Aksi Khusus KS: Beri Penilaian & Laporan
                Action::make('penilaian')
                    ->label('Nilai & Lapor')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (WorkProgram $record) => auth()->user()->hasRole('kepala_sekolah') && $record->status_pelaksanaan === 'selesai')
                    ->form([
                        TextInput::make('skor_penilaian')->numeric()->minValue(0)->maxValue(100)->required()->default(fn (WorkProgram $record) => $record->skor_penilaian),
                        Select::make('laporan_yayasan')
                            ->options([
                                'tidak' => 'Tidak Dilaporkan',
                                'mingguan' => 'Laporan Mingguan',
                                'bulanan' => 'Laporan Bulanan',
                                'tahunan' => 'Laporan Tahunan',
                            ])
                            ->required()
                            ->default(fn (WorkProgram $record) => $record->laporan_yayasan),
                        Textarea::make('catatan_ks')->label('Catatan')->default(fn (WorkProgram $record) => $record->catatan_ks),
                    ])
                    ->action(function (WorkProgram $record, array $data) {
                        $record->update([
                            'skor_penilaian' => $data['skor_penilaian'],
                            'laporan_yayasan' => $data['laporan_yayasan'],
                            'catatan_ks' => $data['catatan_ks'],
                        ]);
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkPrograms::route('/'),
            'create' => Pages\CreateWorkProgram::route('/create'),
            'view' => Pages\ViewWorkProgram::route('/{record}'),
            'edit' => Pages\EditWorkProgram::route('/{record}/edit'),
        ];
    }
}
