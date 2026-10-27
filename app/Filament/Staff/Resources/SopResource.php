<?php
namespace App\Filament\Staff\Resources;

use App\Models\Sop;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SopResource extends Resource
{
    protected static ?string $model = Sop::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'SOP Sekolah';
    protected static ?string $pluralModelLabel = 'Standar Operasional Prosedur (SOP)';
    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras', 'bk']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('judul_sop')
                            ->label('Judul SOP')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record && $record->status === 'disetujui' && !Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                        
                        Forms\Components\DatePicker::make('tanggal_pembuatan')
                            ->label('Tanggal Pembuatan')
                            ->default(now())
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'disetujui' && !Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                        
                        Forms\Components\TextInput::make('link_dokumen')
                            ->label('Link Dokumen (Google Drive)')
                            ->url()
                            ->required()
                            ->placeholder('https://drive.google.com/...')
                            ->disabled(fn ($record) => $record && $record->status === 'disetujui' && !Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                        
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                    ])->columnSpan(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Pengajuan')
                            ->options([
                                'draft' => 'Draft',
                                'belum_diperiksa' => 'Belum Diperiksa (Diajukan)',
                                'dalam_pemeriksaan' => 'Dalam Pemeriksaan',
                                'perbaikan' => 'Butuh Perbaikan',
                                'disetujui' => 'Disetujui',
                            ])
                            ->default('draft')
                            ->disabled(fn () => !Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                        
                        Forms\Components\Textarea::make('keterangan_ks')
                            ->label('Keterangan / Catatan Kepala Sekolah')
                            ->rows(4)
                            ->disabled(fn () => !Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                    ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul_sop')->label('Judul SOP')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Dibuat Oleh'),
                Tables\Columns\TextColumn::make('user.roles.name')
                    ->label('Jabatan')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', strtoupper($state))),
                Tables\Columns\TextColumn::make('tanggal_pembuatan')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('link_dokumen')
                    ->label('Dokumen')
                    ->formatStateUsing(fn ($state) => 'Buka Link Drive')
                    ->url(fn ($record) => $record->link_dokumen, true)
                    ->color('primary'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'belum_diperiksa',
                        'primary' => 'dalam_pemeriksaan',
                        'danger' => 'perbaikan',
                        'success' => 'disetujui',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', strtoupper($state))),
                Tables\Columns\TextColumn::make('keterangan_ks')->label('Catatan KS')->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'belum_diperiksa' => 'Belum Diperiksa',
                    'dalam_pemeriksaan' => 'Dalam Pemeriksaan',
                    'perbaikan' => 'Perbaikan',
                    'disetujui' => 'Disetujui',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('ajukan')
                    ->label('Ajukan ke KS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan SOP ke Kepala Sekolah')
                    ->modalDescription('Apakah Anda yakin ingin mengajukan SOP ini untuk diperiksa Kepala Sekolah?')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'perbaikan']) && !Auth::user()->hasRole('kepala_sekolah'))
                    ->action(fn ($record) => $record->update(['status' => 'belum_diperiksa'])),

                Tables\Actions\Action::make('mulai_periksa')
                    ->label('Periksa')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'belum_diperiksa' && Auth::user()->hasRole(['superadmin', 'kepala_sekolah']))
                    ->action(fn ($record) => $record->update(['status' => 'dalam_pemeriksaan'])),

                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'dalam_pemeriksaan' && Auth::user()->hasRole(['superadmin', 'kepala_sekolah']))
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                    ])
                    ->action(function ($record, array $data) {
                        $updateData = ['status' => 'disetujui'];
                        if (!empty($data['catatan'])) {
                            $updateData['keterangan_ks'] = $data['catatan'];
                        }
                        $record->update($updateData);
                    }),
                    
                Tables\Actions\Action::make('revisi')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['belum_diperiksa', 'dalam_pemeriksaan']) && Auth::user()->hasRole(['superadmin', 'kepala_sekolah']))
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Revisi / Perbaikan')
                            ->required()
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'perbaikan',
                            'keterangan_ks' => $data['catatan'],
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\SopResource\Pages\ListSops::route('/'),
            'create' => \App\Filament\Staff\Resources\SopResource\Pages\CreateSop::route('/create'),
            'edit' => \App\Filament\Staff\Resources\SopResource\Pages\EditSop::route('/{record}/edit'),
        ];
    }
}