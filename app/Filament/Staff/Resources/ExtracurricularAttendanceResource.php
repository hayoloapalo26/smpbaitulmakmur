<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ExtracurricularAttendanceResource\Pages;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Student;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExtracurricularAttendanceResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = ExtracurricularAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Kesiswaan & Karakter';
        }
        if ($user?->hasRole('guru_ekstrakurikuler') && !$user?->hasAnyRole(['superadmin', 'kepala_sekolah'])) {
            return 'Tugas Guru Ekstrakurikuler';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Rekap Presensi Ekstrakurikuler';
        }
        return 'Presensi Ekstrakurikuler';
    }

    public static function getModelLabel(): string
    {
        return 'Presensi Ekstrakurikuler';
    }

    public static function getPluralModelLabel(): string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Rekap Presensi Ekstrakurikuler';
        }
        return 'Presensi Ekstrakurikuler';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student.classroom', 'extracurricular', 'pembina']);
        $user = auth()->user();
        if (!$user) return $query;

        // Waka Kesiswaan, Kepala Sekolah, dan Superadmin dapat melihat rekap seluruh eskul
        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        // Guru Ekstrakurikuler: Hanya melihat presensi pada eskul yang dibina atau dicatat olehnya
        if ($user->hasRole('guru_ekstrakurikuler')) {
            $myEskulIds = Extracurricular::where('pembina_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $myEskulIds) {
                if (!empty($myEskulIds)) {
                    $q->whereIn('extracurricular_id', $myEskulIds);
                }
                $q->orWhere('pembina_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isGuruEskulOnly = $user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);

        return $form
            ->schema([
                Forms\Components\Section::make('Pencatatan Presensi Kehadiran Ekstrakurikuler')
                    ->description('Pilih kegiatan ekstrakurikuler, siswa, serta status kehadiran pada pertemuan kegiatan.')
                    ->schema([
                        Forms\Components\Select::make('extracurricular_id')
                            ->label('Kegiatan Ekstrakurikuler')
                            ->options(function () use ($user, $isGuruEskulOnly) {
                                $query = Extracurricular::where('is_active', true);
                                if ($isGuruEskulOnly) {
                                    $query->where('pembina_id', $user->id);
                                }
                                $opts = $query->orderBy('name')->pluck('name', 'id');
                                if ($opts->isEmpty()) {
                                    return Extracurricular::where('is_active', true)->orderBy('name')->pluck('name', 'id');
                                }
                                return $opts;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pertemuan')
                            ->default(now()->toDateString())
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required(),

                        Forms\Components\Select::make('student_id')
                            ->label('Nama Santri / Siswa')
                            ->options(function () {
                                return Student::with('classroom')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($s) {
                                        $kelas = $s->classroom ? " [{$s->classroom->name}]" : '';
                                        $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                                        return [$s->id => "{$s->name}{$kelas}{$nis}"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status Kehadiran')
                            ->options([
                                'hadir' => '✅ Hadir',
                                'izin'  => '📩 Izin',
                                'sakit' => '🩺 Sakit',
                                'alpa'  => '❌ Alpa / Tanpa Keterangan',
                            ])
                            ->default('hadir')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('catatan')
                            ->label('Materi / Catatan Latihan (Opsional)')
                            ->placeholder('Contoh: Latihan teknik dasar, materi formasi, dsb.')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('pembina_id')
                            ->default(fn () => auth()->id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('extracurricular.name')
                    ->label('Ekstrakurikuler')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Santri / Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Kehadiran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hadir' => 'Hadir',
                        'izin'  => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa'  => 'Alpa',
                        default => ucfirst($state ?? '-'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'hadir' => 'success',
                        'izin'  => 'warning',
                        'sakit' => 'info',
                        'alpa'  => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan Kegiatan')
                    ->placeholder('-')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->catatan),

                Tables\Columns\TextColumn::make('pembina.name')
                    ->label('Pencatat')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('extracurricular_id')
                    ->label('Ekstrakurikuler')
                    ->relationship('extracurricular', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin'  => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa'  => 'Alpa',
                    ]),

                Tables\Filters\Filter::make('bulan_ini')
                    ->label('Presensi Bulan Ini')
                    ->query(fn ($query) => $query->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageExtracurricularAttendances::route('/'),
        ];
    }
}
