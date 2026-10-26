<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PresensiIzinSiswaResource extends Resource
{
    protected static ?string $model = DailyAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Presensi & Izin Harian Siswa';
    protected static ?string $modelLabel = 'Presensi / Izin Siswa';
    protected static ?string $pluralModelLabel = 'Presensi & Izin Harian Siswa';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user?->hasRole('kepala_sekolah')) {
            return 'Bab 2: Perencanaan Administrasi Pembelajaran';
        }
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'superadmin'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return '- Fitur Rekap Presensi Siswa';
        }
        return 'Presensi & Izin Harian Siswa';
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationSort(): ?int
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return 4;
        }
        return 4;
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole([
            'wali_kelas', 'guru_kelas', 'guru_piket', 'tata_usaha', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Pastikan selain superadmin / yayasan, data selalu terfilter sesuai institution_id masing-masing lembaga (SD ke SD, SMP ke SMP, dst.)
        if (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $query->where('institution_id', $user->institution_id);
        }

        if ($user->hasAnyRole(['kepala_sekolah', 'waka_kesiswaan', 'tata_usaha', 'guru_piket', 'superadmin'])) {
            return $query;
        }

        if ($user->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereHas('student', function ($q) use ($classroomIds) {
                $q->whereIn('classroom_id', $classroomIds);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isWaliKelasOnly = $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kesiswaan', 'tata_usaha', 'guru_piket']);

        $studentIds = [];
        if ($isWaliKelasOnly) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id')->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(Auth::id()),

                Forms\Components\Select::make('student_id')
                    ->label('Nama Siswa')
                    ->options(function () use ($isWaliKelasOnly, $studentIds) {
                        if ($isWaliKelasOnly) {
                            return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                        }
                        return Student::orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(Carbon::today())
                    ->required(),

                Forms\Components\Select::make('tipe_absen')
                    ->label('Kategori Absensi')
                    ->options([
                        'masuk'         => '🏫 Kehadiran Harian (Masuk)',
                        'sholat_dhuha'  => '🕌 Sholat Dhuha',
                        'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                        'sholat_ashar'  => '🕌 Sholat Ashar',
                        'pulang'        => '🏠 Absen Pulang',
                    ])
                    ->default('masuk')
                    ->required()
                    ->live(),

                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran / Izin')
                    ->options([
                        'hadir'    => '✅ Hadir',
                        'sakit'    => '🤒 Sakit',
                        'izin'     => '📝 Izin',
                        'alpa'     => '❌ Alpha / Tanpa Keterangan',
                        'halangan' => '🌸 Halangan (Khusus Sholat Siswi)',
                    ])
                    ->default('sakit')
                    ->required()
                    ->live(),

                Forms\Components\TimePicker::make('waktu_kedatangan')
                    ->label('Jam Kedatangan (Jika Hadir Masuk)')
                    ->default('07:00')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'hadir' && $get('tipe_absen') === 'masuk'),

                Forms\Components\Toggle::make('is_late')
                    ->label('Terlambat?')
                    ->default(false)
                    ->visible(fn (Forms\Get $get) => $get('status') === 'hadir' && $get('tipe_absen') === 'masuk'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Alasan / Keterangan Izin / Sakit / Alpha')
                    ->placeholder('Contoh: Sakit demam tinggi, izin periksa dokter 2 hari / Menghadiri acara keluarga...')
                    ->rows(3)
                    ->required(fn (Forms\Get $get) => in_array($get('status'), ['sakit', 'izin']))
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('bukti_izin')
                    ->label('Foto / Bukti Surat Izin / Surat Dokter')
                    ->directory('attendance/surat_izin')
                    ->image()
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (DailyAttendance $record) => ($record->student->classroom->name ?? '-') . ' • ' . ($record->institution->name ?? '')),

                Tables\Columns\TextColumn::make('tipe_absen')
                    ->label('Jenis Absensi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'masuk'         => '🏫 Harian (Masuk)',
                        'sholat_dhuha'  => '🕌 Sholat Dhuha',
                        'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                        'sholat_ashar'  => '🕌 Sholat Ashar',
                        'pulang'        => '🏠 Pulang',
                        default         => ucfirst((string) $state),
                    })
                    ->color(fn (?string $state): string => str_starts_with((string) $state, 'sholat_') ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('waktu_kedatangan')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir'    => 'success',
                        'izin'     => 'info',
                        'sakit'    => 'warning',
                        'alpa'     => 'danger',
                        'halangan' => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hadir'    => '✅ Hadir',
                        'izin'     => '📝 Izin',
                        'sakit'    => '🤒 Sakit',
                        'alpa'     => '❌ Alpha',
                        'halangan' => '🌸 Halangan',
                        default    => ucfirst($state),
                    }),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Terlambat')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan / Alasan')
                    ->wrap()
                    ->placeholder('-'),

                Tables\Columns\ImageColumn::make('bukti_izin')
                    ->label('Surat/Bukti')
                    ->circular()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Filter Kelas')
                    ->options(fn () => Classroom::orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $classroomId) => $q->whereHas('student', fn ($sq) => $sq->where('classroom_id', $classroomId))
                        );
                    }),
                Tables\Filters\SelectFilter::make('tipe_absen')
                    ->label('Jenis Absensi')
                    ->options([
                        'masuk'         => 'Kehadiran Harian (Masuk)',
                        'sholat_dhuha'  => 'Sholat Dhuha',
                        'sholat_dhuhur' => 'Sholat Dhuhur',
                        'sholat_ashar'  => 'Sholat Ashar',
                        'pulang'        => 'Absen Pulang',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir'    => 'Hadir',
                        'sakit'    => 'Sakit',
                        'izin'     => 'Izin',
                        'alpa'     => 'Alpha',
                        'halangan' => 'Halangan',
                    ]),
                Tables\Filters\Filter::make('hari_ini')
                    ->label('Hanya Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('tanggal', Carbon::today())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('kirim_wa')
                    ->label('Kirim WA Ortu')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (DailyAttendance $record) =>
                        $record->status !== 'hadir' && !empty($record->student->no_telepon_orang_tua)
                    )
                    ->url(function (DailyAttendance $record) {
                        $phone = preg_replace('/[^0-9]/', '', $record->student->no_telepon_orang_tua);
                        $msg = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
                             . "Bapak/Ibu Wali Murid dari ananda *{$record->student->name}*,\n"
                             . "Kami menginformasikan bahwa data kehadiran ananda pada hari " . Carbon::parse($record->tanggal)->isoFormat('dddd, D MMMM Y') . " tercatat dengan status *" . strtoupper($record->status) . "*.\n"
                             . ($record->keterangan ? "Keterangan: {$record->keterangan}\n\n" : "\n")
                             . "Terima kasih atas perhatian dan kerja samanya.\n"
                             . "Wali Kelas / Waka Kesiswaan.";
                        return "https://wa.me/{$phone}?text=" . urlencode($msg);
                    })
                    ->openUrlInNewTab(),
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
            'index' => Pages\ManagePresensiIzinSiswa::route('/'),
        ];
    }
}
