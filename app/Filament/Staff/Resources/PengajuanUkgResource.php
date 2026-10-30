<?php
namespace App\Filament\Staff\Resources;

use App\Models\TeacherCompetencyTest;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class PengajuanUkgResource extends Resource
{
    protected static ?string $model = TeacherCompetencyTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Penilaian & Kompetensi';
    protected static ?string $navigationLabel = 'Penilaian Kinerja Guru (PKG)';
    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Manajemen Sekolah'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 8 : static::$navigationSort;
    }
    protected static ?string $pluralModelLabel = 'Penilaian Kinerja Guru (PKG)';
    protected static ?string $modelLabel = 'Penilaian Kinerja Guru';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasRole('kepala_sekolah')) {
            return $query; // KS sees all in their institution
        }
        return $query->where('teacher_id', Auth::id()); // Teacher sees their own
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(array_merge([
            'superadmin', 'kepala_sekolah', 'guru_mapel', 'wali_kelas'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('tanggal')->default(now()),
                Forms\Components\Textarea::make('catatan')
                    ->label('Alasan / Keterangan Pengajuan PKG')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->label('Nama Guru')->visible(fn() => Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                Tables\Columns\TextColumn::make('tanggal')->label('Tgl Pengajuan')->date('d M Y'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'menunggu',
                        'primary' => 'dijadwalkan',
                        'danger' => 'ditolak',
                        'success' => 'dinilai',
                    ]),
                Tables\Columns\TextColumn::make('jadwal')->label('Jadwal PKG')->dateTime('d M Y, H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (TeacherCompetencyTest $record) => $record->nilai_akhir ?? $record->score)
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : 'Belum Dinilai'),
                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn (TeacherCompetencyTest $record) => TeacherCompetencyTest::getPredicateBadgeColor($record->nilai_akhir ?? $record->score))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('catatan_yayasan')->label('Catatan Yayasan')->limit(30),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Raport PKG PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(fn (TeacherCompetencyTest $record) => route('ukg.report-card.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherCompetencyTest $record) => $record->score !== null || $record->nilai_akhir !== null),

                Tables\Actions\Action::make('print_raport')
                    ->label('Cetak Raport PKG')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn (TeacherCompetencyTest $record) => route('ukg.report-card.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherCompetencyTest $record) => $record->score !== null || $record->nilai_akhir !== null),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\PengajuanUkgResource\Pages\ListPengajuanUkgs::route('/'),
        ];
    }
}