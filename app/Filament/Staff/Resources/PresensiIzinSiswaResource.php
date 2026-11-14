<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PresensiIzinSiswaResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Presensi & Izin Harian Siswa';
    protected static ?string $modelLabel = 'Siswa';
    protected static ?string $pluralModelLabel = 'Presensi & Izin Harian Siswa';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user?->hasRole('kepala_sekolah')) {
            return 'Perencanaan Administrasi Pembelajaran';
        }
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return 'Rekap Presensi Siswa';
        }
        return 'Presensi & Izin Harian Siswa';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isPureQuranTeacher()) return false;
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isPureQuranTeacher()) return false;
        return $user->hasAnyRole([
            'wali_kelas', 'guru_kelas', 'guru_piket', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin',
        ]);
    }

    public static function getActiveDate($livewire = null): string
    {
        if ($livewire && isset($livewire->filterTanggal) && !empty($livewire->filterTanggal)) {
            return $livewire->filterTanggal;
        }
        return Carbon::today()->toDateString();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where('status_siswa', 'aktif')
            ->with([
                'classroom',
                'dailyAttendances' => function ($q) {
                    $q->withoutGlobalScopes();
                },
            ]);

        $user = Auth::user();

        // Pastikan selain superadmin / yayasan, data terfilter sesuai institution_id masing-masing lembaga
        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $instId = $user->institution_id;
            $query->where(function ($q) use ($instId) {
                $q->where('institution_id', $instId)
                  ->orWhereNull('institution_id');
                // Jika staf SMP (2), izinkan juga membaca siswa SMP yang tercatat di institution_id 1
                if ($instId == 2) {
                    $q->orWhere('institution_id', 1);
                }
            });
        }

        if ($user && ($user->hasAnyRole(['kepala_sekolah', 'waka_kesiswaan', 'guru_piket', 'superadmin']) || $user->isPiketToday())) {
            return $query;
        }

        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->whereIn('classroom_id', $classroomIds);
        }

        return $query;
    }

    /**
     * Skema Form utama (mempertahankan bukti_izin sebagai TextInput URL Google Drive untuk testing dan modal).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Nama Siswa')
                    ->options(fn () => Student::withoutGlobalScopes()->where('status_siswa', 'aktif')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Presensi')
                    ->default(Carbon::today())
                    ->required(),

                Forms\Components\Select::make('tipe_absen')
                    ->label('Kategori Sesi')
                    ->options([
                        'masuk'         => '🏫 Kehadiran di Sekolah (Masuk Pagi)',
                        'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                        'sholat_ashar'  => '🕌 Sholat Ashar & Pulang',
                        'pulang'        => '🏠 Jam Pulang',
                    ])
                    ->default('masuk')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran / Izin')
                    ->options([
                        'hadir'    => '✅ Hadir',
                        'sakit'    => '🤒 Sakit',
                        'izin'     => '📋 Izin',
                        'alpa'     => '❌ Alpa / Tanpa Keterangan',
                        'halangan' => '🌸 Halangan (Khusus Sholat Siswi)',
                    ])
                    ->default('sakit')
                    ->required(),

                Forms\Components\TimePicker::make('waktu_kedatangan')
                    ->label('Jam Scan / Masuk')
                    ->default('06:40'),

                Forms\Components\Toggle::make('is_late')
                    ->label('Terlambat?')
                    ->default(false),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Alasan / Keterangan')
                    ->placeholder('Contoh: Sakit demam berobat ke dokter / Izin acara keluarga...')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('bukti_izin')
                    ->label('Link Google Drive Bukti Surat Izin / Sakit / Foto')
                    ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                    ->url()
                    ->prefixIcon('heroicon-o-link')
                    ->helperText('Tautkan link Google Drive bukti surat izin/sakit siswa untuk menghemat ruang penyimpanan hosting.')
                    ->maxLength(2048)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS / Pin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Student $record) => ($state ?: '-') . ' / ' . ($record->fingerspot_pin ?: '-')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Student $record) => ($record->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') . ' • Kelas ' . ($record->classroom?->name ?? '-')),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->placeholder('-'),

                // 1. Kedatangan Waktu di Sekolah (Finger)
                Tables\Columns\TextColumn::make('kedatangan_finger')
                    ->label('1. Kedatangan (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, 'masuk');
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $isLate = $att->is_late || ($att->waktu_kedatangan && Carbon::parse($att->waktu_kedatangan)->format('H:i') > '06:50');

                            if ($isLate) {
                                return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    <span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">Terlambat</span>
                                </div>';
                            }

                            return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">Tepat Waktu</span>
                            </div>';
                        }

                        if ($excused) {
                            $badgeColor = match ($excused->status) {
                                'sakit' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                'izin' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
                                default => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                            };
                            return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border ' . $badgeColor . '">' . ucfirst($excused->status) . '</span>';
                        }

                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Belum Scan
                        </span>';
                    }),

                // 2. Sholat Dhuhur (Finger)
                Tables\Columns\TextColumn::make('dhuhur_finger')
                    ->label('2. Sholat Dhuhur (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, 'sholat_dhuhur');
                        $isHalangan = $record->jenis_kelamin === 'P' && (StudentHalangan::isHalangan($record->id, $tanggal) || ($att && $att->status === 'halangan'));
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        if ($isHalangan) {
                            return '<div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800">
                                <span class="text-[10px] font-bold bg-purple-200 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-1.5 py-0.5 rounded">Halangan / Udzur</span>
                            </div>';
                        }

                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $isLate = $att->is_late || ($att->waktu_kedatangan && Carbon::parse($att->waktu_kedatangan)->format('H:i') > '12:15');

                            if ($isLate) {
                                return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    <span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">Terlambat</span>
                                </div>';
                            }

                            return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">Tepat Waktu</span>
                            </div>';
                        }

                        if ($excused) {
                            return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Izin Harian</span>';
                        }

                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Belum Scan
                        </span>';
                    }),

                // 3. Sholat Ashar (Finger)
                Tables\Columns\TextColumn::make('ashar_finger')
                    ->label('3. Sholat Ashar (Finger)')
                    ->html()
                    ->state(function (Student $record, $livewire): string {
                        $tanggal = static::getActiveDate($livewire);
                        $att = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $isHalangan = $record->jenis_kelamin === 'P' && (StudentHalangan::isHalangan($record->id, $tanggal) || ($att && $att->status === 'halangan'));
                        $excused = $record->getGeneralExcusedOnDate($tanggal);

                        if ($isHalangan) {
                            return '<div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800">
                                <span class="text-[10px] font-bold bg-purple-200 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-1.5 py-0.5 rounded">Halangan / Udzur</span>
                            </div>';
                        }

                        if ($att && $att->status === 'hadir') {
                            $jam = $att->waktu_kedatangan ? Carbon::parse($att->waktu_kedatangan)->format('H:i') : '-';
                            $isLate = $att->is_late || ($att->waktu_kedatangan && Carbon::parse($att->waktu_kedatangan)->format('H:i') > '15:15');

                            if ($isLate) {
                                return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800">
                                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>' . $jam . ' WIB</span>
                                    <span class="text-[10px] bg-rose-200 text-rose-800 dark:bg-rose-900 dark:text-rose-200 px-1.5 py-0.5 rounded font-bold">Terlambat</span>
                                </div>';
                            }

                            return '<div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>' . $jam . ' WIB</span>
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-1.5 py-0.5 rounded font-bold">Tepat Waktu</span>
                            </div>';
                        }

                        if ($excused) {
                            return '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Izin Harian</span>';
                        }

                        return '<span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Belum Scan
                        </span>';
                    }),

                // Ringkasan Status Hari Ini
                Tables\Columns\TextColumn::make('status_harian')
                    ->label('Status Hari Ini')
                    ->badge()
                    ->state(function (Student $record, $livewire): string {
                        $tanggal = static::getActiveDate($livewire);
                        $excused = $record->getGeneralExcusedOnDate($tanggal);
                        if ($excused) {
                            return match ($excused->status) {
                                'sakit' => '🤒 Sakit',
                                'izin' => '📋 Izin',
                                default => '❌ Alpa',
                            };
                        }

                        $attMasuk = $record->getAttendanceOnDate($tanggal, 'masuk');
                        $attDhuhur = $record->getAttendanceOnDate($tanggal, 'sholat_dhuhur');
                        $attAshar = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $isHalangan = $record->jenis_kelamin === 'P' && StudentHalangan::isHalangan($record->id, $tanggal);

                        $dhuhurOk = ($attDhuhur && $attDhuhur->status === 'hadir') || $isHalangan;
                        $asharOk = ($attAshar && $attAshar->status === 'hadir') || $isHalangan;

                        if ($attMasuk && $attMasuk->status === 'hadir') {
                            if ($dhuhurOk && $asharOk) {
                                return '✅ Lengkap (3/3)';
                            }
                            if ($dhuhurOk) {
                                return '🔹 Masuk & Dhuhur (2/3)';
                            }
                            return '🔸 Masuk Saja (1/3)';
                        }

                        return 'Belum Ada Data';
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Lengkap') => 'success',
                        str_contains($state, '2/3')     => 'info',
                        str_contains($state, '1/3')     => 'warning',
                        str_contains($state, 'Sakit')   => 'warning',
                        str_contains($state, 'Izin')    => 'info',
                        str_contains($state, 'Alpa')    => 'danger',
                        default                         => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Filter Kelas / Rombel')
                    ->options(fn () => Classroom::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
            ])
            ->actions([
                // 1. Catat Izin / Sakit / Alpa
                Tables\Actions\Action::make('catat_izin')
                    ->label('Catat Izin / Sakit')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->modalHeading(fn (Student $record) => "Catat Izin / Sakit: {$record->name}")
                    ->modalDescription('Catatan izin, sakit, atau alpa akan otomatis disinkronkan ke seluruh sesi kehadiran dan sholat hari tersebut.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'sakit' => '🤒 Sakit',
                                'izin'  => '📋 Izin',
                                'alpa'  => '❌ Alpa / Tanpa Keterangan',
                            ])
                            ->default('sakit')
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan / Keterangan')
                            ->placeholder('Contoh: Sakit demam tinggi, rawat inap di RS...')
                            ->rows(3)
                            ->required(),

                        Forms\Components\TextInput::make('bukti_izin')
                            ->label('Link Google Drive Bukti Surat Izin / Sakit')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan bukti surat izin atau resep dokter via link Google Drive publik.')
                            ->maxLength(2048),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = $data['tanggal'];
                        $sesiList = ['masuk', 'sholat_dhuhur', 'sholat_ashar'];

                        foreach ($sesiList as $sesi) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $record->id,
                                    'tanggal'    => $tanggal,
                                    'tipe_absen' => $sesi,
                                ],
                                [
                                    'institution_id'   => $record->institution_id ?: 2,
                                    'teacher_id'       => Auth::id(),
                                    'status'           => $data['status'],
                                    'metode_absen'     => 'manual',
                                    'keterangan'       => $data['keterangan'] ?? null,
                                    'bukti_izin'       => $data['bukti_izin'] ?? null,
                                    'waktu_kedatangan' => null,
                                    'is_late'          => false,
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Status Berhasil Dicatat')
                            ->body("Status {$data['status']} untuk {$record->name} pada {$tanggal} berhasil disimpan.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 2. Input Manual / Koreksi Jam
                Tables\Actions\Action::make('input_manual_jam')
                    ->label('Koreksi Jam')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading(fn (Student $record) => "Input / Koreksi Jam Scan: {$record->name}")
                    ->modalDescription('Gunakan fitur ini jika siswa lupa scan jari pada mesin fingerspot atau saat mesin offline.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\Select::make('tipe_absen')
                            ->label('Sesi Presensi')
                            ->options([
                                'masuk'         => '🏫 Kedatangan Sekolah (Masuk Pagi)',
                                'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                                'sholat_ashar'  => '🕌 Sholat Ashar',
                            ])
                            ->default('masuk')
                            ->required(),

                        Forms\Components\TimePicker::make('waktu_kedatangan')
                            ->label('Jam Kehadiran / Sholat')
                            ->default(now()->format('H:i'))
                            ->required(),

                        Forms\Components\Toggle::make('is_late')
                            ->label('Tandai Terlambat')
                            ->helperText('Otomatis: Masuk > 06:50, Dhuhur > 12:15, Ashar > 15:15')
                            ->default(false),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Catatan Petugas Piket')
                            ->placeholder('Contoh: Hadir di sekolah, lupa scan jari di pintu piket')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        $tanggal = $data['tanggal'];
                        $sesi = $data['tipe_absen'];
                        $jam = $data['waktu_kedatangan'];

                        // Auto-check keterlambatan jika belum dicentang
                        $isLate = (bool) $data['is_late'];
                        if (!$isLate && $jam) {
                            if ($sesi === 'masuk' && $jam > '06:50') $isLate = true;
                            if ($sesi === 'sholat_dhuhur' && $jam > '12:15') $isLate = true;
                            if ($sesi === 'sholat_ashar' && $jam > '15:15') $isLate = true;
                        }

                        DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                            [
                                'student_id' => $record->id,
                                'tanggal'    => $tanggal,
                                'tipe_absen' => $sesi,
                            ],
                            [
                                'institution_id'   => $record->institution_id ?: 2,
                                'teacher_id'       => Auth::id(),
                                'status'           => 'hadir',
                                'metode_absen'     => 'manual',
                                'waktu_kedatangan' => $jam,
                                'is_late'          => $isLate,
                                'keterangan'       => $data['keterangan'] ?? 'Diinput manual oleh petugas piket',
                            ]
                        );

                        Notification::make()
                            ->title('Jam Scan Berhasil Disimpan')
                            ->body("Data sesi {$sesi} untuk {$record->name} jam {$jam} WIB tercatat.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 3. Catat Halangan (Khusus Siswi Putri)
                Tables\Actions\Action::make('catat_halangan')
                    ->label('Catat Halangan')
                    ->icon('heroicon-o-heart')
                    ->color('purple')
                    ->visible(fn (Student $record) => $record->jenis_kelamin === 'P')
                    ->modalHeading(fn (Student $record) => "Catat Halangan / Udzur: {$record->name}")
                    ->modalDescription('Mencatat siswi yang sedang udzur syar\'i (haid) sehingga tidak dihitung alpa pada sholat Dhuhur & Ashar.')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(fn ($livewire) => static::getActiveDate($livewire))
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai (Estimasi 7 Hari)')
                            ->default(fn ($livewire) => Carbon::parse(static::getActiveDate($livewire))->addDays(6)->toDateString())
                            ->required(),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->default('Udzur syar\'i (haid)')
                            ->maxLength(255),
                    ])
                    ->action(function (Student $record, array $data, $livewire): void {
                        StudentHalangan::create([
                            'student_id'      => $record->id,
                            'tanggal_mulai'   => $data['tanggal_mulai'],
                            'tanggal_selesai' => $data['tanggal_selesai'],
                            'catatan'         => $data['keterangan'] ?? 'Udzur syar\'i',
                            'recorded_by'     => Auth::id() ?: 1,
                        ]);

                        // Catat juga di daily_attendances untuk tanggal aktif
                        $currDate = $data['tanggal_mulai'];
                        foreach (['sholat_dhuhur', 'sholat_ashar'] as $sesi) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $record->id,
                                    'tanggal'    => $currDate,
                                    'tipe_absen' => $sesi,
                                ],
                                [
                                    'institution_id' => $record->institution_id ?: 2,
                                    'teacher_id'     => Auth::id(),
                                    'status'         => 'halangan',
                                    'metode_absen'   => 'manual',
                                    'keterangan'     => $data['keterangan'] ?? 'Udzur syar\'i',
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Udzur Siswi Berhasil Dicatat')
                            ->body("Data halangan {$record->name} dari {$data['tanggal_mulai']} s.d {$data['tanggal_selesai']} berhasil dicatat.")
                            ->success()
                            ->send();

                        $livewire->dispatch('refresh-presensi-table');
                    }),

                // 4. Kirim WA Laporan ke Orang Tua
                Tables\Actions\Action::make('kirim_wa')
                    ->label('Kirim WA Ortu')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (Student $record, $livewire): ?string {
                        $phone = $record->telepon_ayah ?: $record->telepon_ibu ?: $record->telepon_wali;
                        if (!$phone) {
                            return null;
                        }
                        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }

                        $tanggal = static::getActiveDate($livewire);
                        $tglFmt = Carbon::parse($tanggal)->translatedFormat('l, d F Y');

                        $attMasuk = $record->getAttendanceOnDate($tanggal, 'masuk');
                        $attDhuhur = $record->getAttendanceOnDate($tanggal, 'sholat_dhuhur');
                        $attAshar = $record->getAttendanceOnDate($tanggal, ['sholat_ashar', 'pulang']);
                        $excused = $record->getGeneralExcusedOnDate($tanggal);
                        $isHalangan = $record->jenis_kelamin === 'P' && StudentHalangan::isHalangan($record->id, $tanggal);

                        $masukText = $attMasuk && $attMasuk->status === 'hadir'
                            ? ($attMasuk->waktu_kedatangan ? Carbon::parse($attMasuk->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attMasuk->is_late ? ' (Terlambat)' : ' (Tepat Waktu)')
                            : ($excused ? ucfirst($excused->status) : 'Belum Scan Masuk');

                        $dhuhurText = $isHalangan ? 'Halangan / Udzur Syar\'i' : ($attDhuhur && $attDhuhur->status === 'hadir'
                            ? ($attDhuhur->waktu_kedatangan ? Carbon::parse($attDhuhur->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attDhuhur->is_late ? ' (Terlambat)' : ' (Tepat Waktu)')
                            : ($excused ? 'Izin Harian' : 'Belum Sholat / Scan'));

                        $asharText = $isHalangan ? 'Halangan / Udzur Syar\'i' : ($attAshar && $attAshar->status === 'hadir'
                            ? ($attAshar->waktu_kedatangan ? Carbon::parse($attAshar->waktu_kedatangan)->format('H:i') . ' WIB' : 'Hadir') . ($attAshar->is_late ? ' (Terlambat)' : ' (Tepat Waktu)')
                            : ($excused ? 'Izin Harian' : 'Belum Sholat / Scan'));

                        $kelas = $record->classroom?->name ?? '-';
                        $msg = "Assalamu'alaikum Wr. Wb.\n\n"
                            . "Yth. Bapak/Ibu Wali dari ananda *{$record->name}* (Kelas {$kelas}).\n"
                            . "Berikut ini kami sampaikan laporan kedatangan dan pelaksanaan sholat ananda pada hari *{$tglFmt}*:\n\n"
                            . "1. 🏫 *Kedatangan di Sekolah*: {$masukText}\n"
                            . "2. 🕌 *Sholat Dhuhur*: {$dhuhurText}\n"
                            . "3. 🕌 *Sholat Ashar*: {$asharText}\n\n"
                            . "Terima kasih atas perhatian dan kerjasamanya.\nWassalamu'alaikum Wr. Wb.\n"
                            . "_Tim Kesiswaan & Guru Piket SMP Islam Baitul Makmur_";

                        return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($msg);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Student $record) => (bool) ($record->telepon_ayah ?: $record->telepon_ibu ?: $record->telepon_wali)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePresensiIzinSiswa::route('/'),
        ];
    }
}
