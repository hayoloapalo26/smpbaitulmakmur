<?php

namespace App\Filament\Staff\Resources;

use App\Models\TeacherSupervision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PengajuanSupervisiResource extends Resource
{
    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Penilaian & Kompetensi';
    protected static ?string $navigationLabel = 'Supervisi Guru';
    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Manajemen Sekolah'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 7 : static::$navigationSort;
    }
    protected static ?string $modelLabel = 'Supervisi Guru';
    protected static ?string $pluralModelLabel = 'Supervisi Guru';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->orderBy('created_at', 'desc');
        if (Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum'])) {
            return $query; // KS & Waka Kurikulum see all in their institution (handled by BelongsToInstitution trait)
        }
        return $query->where('teacher_id', Auth::id()); // Teacher sees their own (both Jalur 1 & Jalur 2)
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('jalur_supervisi')
                    ->default('pengajuan_guru'),
                Forms\Components\Hidden::make('status')
                    ->default('menunggu'),
                Forms\Components\Hidden::make('hasil')
                    ->default('-'),
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Pengajuan')
                    ->default(now())
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->label('Keterangan / Tujuan Permintaan Supervisi')
                    ->placeholder('Contoh: Mohon supervisi pembelajaran di kelas untuk materi...')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSupervisor = Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum']);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => $isSupervisor),
                Tables\Columns\TextColumn::make('jalur_supervisi')
                    ->label('Jalur Supervisi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'penjamin_mutu' => '1. Dijadwalkan Penjamin Mutu',
                        default => '2. Permintaan Guru',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'penjamin_mutu' => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl Dibuat/Pengajuan')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'menunggu' => 'Menunggu Jadwal',
                        'dijadwalkan' => 'Dijadwalkan',
                        'dinilai' => 'Selesai Dinilai',
                        'ditolak' => 'Ditolak',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'menunggu' => 'gray',
                        'dijadwalkan' => 'primary',
                        'dinilai' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('jadwal')
                    ->label('Jadwal Pelaksanaan')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum Dijadwalkan'),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('Penjamin Mutu / Supervisor')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('catatan_yayasan')
                    ->label('Catatan Penjamin Mutu')
                    ->limit(35)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum Dinilai'),
                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->predikat ?: ($record->score ? TeacherSupervision::determinePredicate((float)$record->score) : '-'))
                    ->badge()
                    ->color(fn (TeacherSupervision $record) => TeacherSupervision::getPredicateBadgeColor($record->score)),
                Tables\Columns\TextColumn::make('hasil')
                    ->label('Hasil Evaluasi')
                    ->limit(35)
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jalur_supervisi')
                    ->label('Jalur Supervisi')
                    ->options([
                        'penjamin_mutu' => '1. Dijadwalkan Penjamin Mutu',
                        'pengajuan_guru' => '2. Permintaan Sendiri oleh Guru',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu Jadwal',
                        'dijadwalkan' => 'Dijadwalkan',
                        'dinilai' => 'Selesai Dinilai',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('nilai_supervisi')
                    ->label('Jadwal & Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn () => $isSupervisor)
                    ->fillForm(fn (TeacherSupervision $record): array => [
                        'status' => $record->status ?? 'dijadwalkan',
                        'jadwal' => $record->jadwal,
                        'score' => $record->score,
                        'hasil' => $record->hasil === '-' ? '' : $record->hasil,
                    ])
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Supervisi')
                            ->options([
                                'menunggu' => 'Menunggu',
                                'dijadwalkan' => 'Dijadwalkan',
                                'dinilai' => 'Sudah Dinilai',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('jadwal')
                            ->label('Jadwal Pelaksanaan Supervisi'),
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Supervisi (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\Textarea::make('hasil')
                            ->label('Hasil Evaluasi / Masukan Supervisor')
                            ->rows(3),
                    ])
                    ->action(function (TeacherSupervision $record, array $data): void {
                        $record->update([
                            'supervisor_id' => $record->supervisor_id ?: Auth::id(),
                            'status' => $data['status'],
                            'jadwal' => $data['jadwal'] ?? $record->jadwal,
                            'score' => $data['score'] ?? $record->score,
                            'hasil' => $data['hasil'] ?? $record->hasil,
                        ]);

                        Notification::make()
                            ->title('Data Supervisi Diperbarui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('report_card')
                    ->label('Report Card')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (TeacherSupervision $record) => route('supervisi.report-card.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherSupervision $record) => $record->status === 'dinilai' || $record->total_skor > 0 || $record->score !== null),
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(fn (TeacherSupervision $record) => route('supervisi.report-card.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherSupervision $record) => $record->status === 'dinilai' || $record->total_skor > 0 || $record->score !== null),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => $isSupervisor),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $isSupervisor),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\PengajuanSupervisiResource\Pages\ListPengajuanSupervisis::route('/'),
        ];
    }
}