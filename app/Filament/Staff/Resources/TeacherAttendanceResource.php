<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherAttendanceResource\Pages;
use App\Models\TeacherAttendance;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TeacherAttendanceResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'tata_usaha', 'waka_kurikulum', 'kepala_sekolah', 'superadmin',
        ]) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'tata_usaha', 'waka_kurikulum', 'kepala_sekolah', 'superadmin',
            'guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas'
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    protected static ?string $model = TeacherAttendance::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $modelLabel = 'Rekap Presensi & Izin Guru';
    protected static ?string $pluralModelLabel = 'Rekap Presensi & Izin Guru';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Manajemen Sekolah'
            : 'Akademik & Kurikulum';
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 10 : 5;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScope('institution');
        $user = Auth::user();

        if ($user->hasAnyRole(['tata_usaha', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('teacher_id', $user->id)
              ->orWhere('substitute_teacher_id', $user->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info_presensi')
                    ->label('Sistem Pelaporan & Rekapitulasi Presensi Khusus Guru')
                    ->content('Fitur ini khusus difokuskan untuk pelaporan jam kehadiran mengajar guru (JP), beban mengajar efektif, perizinan guru, serta jam tugas guru pengganti (inval). Untuk rekapitulasi presensi siswa dikelola secara mandiri pada menu terpisah "Presensi & Izin Harian Siswa".')
                    ->columnSpanFull(),

                Forms\Components\Select::make('teacher_id')
                    ->label('Guru Utama (Yang Berhalangan / Mengajar)')
                    ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) {
                        $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum']));
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(Carbon::today()->format('Y-m-d'))
                    ->live()
                    ->required(),

                Forms\Components\Select::make('schedule_id')
                    ->label('Jadwal Mengajar Terkait')
                    ->options(function (Forms\Get $get) {
                        $teacherId = $get('teacher_id');
                        $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                        if (!$teacherId) return [];

                        $hari = Schedule::getIndonesianDayName($tanggal);
                        return Schedule::with(['classroom', 'subject'])
                            ->where('teacher_id', $teacherId)
                            ->where('hari', $hari)
                            ->orderBy('jam_ke')
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => Schedule::getJamLabelWithTime($s->jam_ke) . " | Kelas {$s->classroom?->name} — {$s->subject?->name}"
                            ])
                            ->toArray();
                    })
                    ->placeholder('Pilih jadwal mengajar atau kosongkan untuk izin harian...')
                    ->helperText('Pilih jam mengajar spesifik atau kosongkan jika izin berlaku seharian penuh tanpa jadwal tertentu.')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if (!$state) {
                            $set('classroom_id', null);
                            $set('subject_id', null);
                            $set('jam_ke', null);
                            return;
                        }
                        $schedule = Schedule::find($state);
                        if ($schedule) {
                            $set('classroom_id', $schedule->classroom_id);
                            $set('subject_id', $schedule->subject_id);
                            $set('jam_ke', $schedule->jam_ke);
                        }
                    }),

                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran / Presensi')
                    ->options([
                        'hadir' => 'Hadir Mengajar',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa (Tanpa Keterangan)',
                        'dinas_luar' => 'Dinas Luar / Tugas',
                    ])
                    ->default('izin')
                    ->required()
                    ->live(),

                Forms\Components\Select::make('substitute_teacher_id')
                    ->label('Guru Pengganti (Inval)')
                    ->relationship('substituteTeacher', 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                        \App\Models\User::ensureGuruPiketUser();

                        $user = Auth::user();
                        if ($user->institution_id && !$user->hasRole('superadmin')) {
                            $query->where(function ($q) use ($user) {
                                $q->where('institution_id', $user->institution_id)
                                  ->orWhere('institution_id', 1)
                                  ->orWhereNull('institution_id');
                            });
                        }
                        $query->where(function ($q) {
                            $q->whereHas('roles', fn ($r) => $r->whereIn('name', [
                                'guru_mapel',
                                'guru_alquran',
                                'wali_kelas',
                                'guru_kelas',
                                'waka_kurikulum',
                                'guru_piket',
                            ]))
                            ->orWhere('name', 'Guru Piket')
                            ->orWhere('username', 'gurupiket');
                        });
                        if ($get('teacher_id')) {
                            $query->where('id', '!=', $get('teacher_id'));
                        }
                        $query->orderByRaw("CASE WHEN name = 'Guru Piket' THEN 0 WHEN name LIKE '%Piket%' THEN 1 ELSE 2 END, name ASC");
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih guru pengganti atau Guru Piket...')
                    ->helperText('Pilih guru pengganti atau opsi Guru Piket jika tidak menemukan guru pengganti.'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Alasan / Catatan Izin')
                    ->placeholder('Contoh: Sakit flu, urusan keluarga, dinas luar workshop MGMP, dll...')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('attachment')
                    ->label('Surat Keterangan / Foto Bukti Izin (Opsional)')
                    ->directory('teacher-leave-letters')
                    ->image()
                    ->maxSize(5120)
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('classroom_id'),
                Forms\Components\Hidden::make('subject_id'),
                Forms\Components\Hidden::make('jam_ke'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Rincian Presensi & Mengajar')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Tanggal & Hari')
                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('l, d F Y')),
                        Infolists\Components\TextEntry::make('jam_ke')
                            ->label('Jam Pelajaran')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn ($state) => ($state !== null && $state !== '') ? Schedule::getJamLabelWithTime($state) : 'Harian Penuh'),
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('Guru Utama')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Kehadiran')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'hadir' => 'success',
                                'izin' => 'warning',
                                'sakit', 'alpa' => 'danger',
                                'dinas_luar' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state, TeacherAttendance $record) => match ($state) {
                                'hadir' => $record->teaching_journal_id ? 'Hadir (Sesuai Jurnal Mengajar)' : 'Hadir',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alpa' => 'Alpa',
                                'dinas_luar' => 'Dinas Luar',
                                default => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('classroom.name')
                            ->label('Kelas')
                            ->badge()
                            ->color('info')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('subject.name')
                            ->label('Mata Pelajaran')
                            ->placeholder('-'),
                    ])->columns(2),

                Infolists\Components\Section::make('Guru Pengganti & Surat Bukti Perizinan')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('substituteTeacher.name')
                            ->label('Guru Pengganti (Inval)')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-user-plus')
                            ->placeholder('Tidak ada guru pengganti'),
                        Infolists\Components\TextEntry::make('leaveRequest.status')
                            ->label('Status Persetujuan (ACC)')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'success',
                                'diajukan' => 'warning',
                                'ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => '✅ Sudah di-ACC (Waka Kurikulum)',
                                'diajukan' => '⏳ Menunggu ACC',
                                'ditolak' => '❌ Ditolak',
                                default => '-',
                            })
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Alasan / Catatan Perizinan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan khusus.'),
                        Infolists\Components\ImageEntry::make('attachment')
                            ->label('Foto Surat / Bukti Lampiran')
                            ->placeholder('Tidak ada lampiran surat/foto.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal & Hari')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('d M Y') . ' (' . Schedule::getIndonesianDayName($state) . ')')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Mengajar (JP)')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => ($state !== null && $state !== '') ? Schedule::getJamLabelWithTime($state) . ' (1 JP)' : 'Izin Harian (Dinas/Sakit)')
                    ->description(fn ($record) => $record->jam_ke !== null ? 'Beban: 1 JP Mengajar' : 'Presensi Harian')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->label('Guru Utama')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, TeacherAttendance $record) => match ($state) {
                        'hadir' => $record->teaching_journal_id ? 'Hadir (Jurnal)' : 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                        'dinas_luar' => 'Dinas Luar',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'warning',
                        'sakit', 'alpa' => 'danger',
                        'dinas_luar' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('substituteTeacher.name')
                    ->label('Guru Pengganti (Inval)')
                    ->badge()
                    ->color(fn ($state) => str_contains(strtolower($state ?? ''), 'piket') ? 'warning' : ($state ? 'info' : 'gray'))
                    ->icon('heroicon-m-user-plus')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Alasan / Keterangan')
                    ->limit(28)
                    ->placeholder('-')
                    ->tooltip(fn ($record) => $record->keterangan),
                Tables\Columns\TextColumn::make('leaveRequest.status')
                    ->label('Status ACC')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'success',
                        'diajukan' => 'warning',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'ACC',
                        'diajukan' => 'Menunggu ACC',
                        'ditolak' => 'Ditolak',
                        default => '-',
                    })
                    ->placeholder('-'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru Utama')
                    ->options(fn () => \App\Models\User::withoutGlobalScopes()
                        ->where(function ($q) {
                            $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum', 'guru_piket']))
                              ->orWhereHas('schedules')
                              ->orWhereHas('teacherAttendances');
                        })
                        ->where('email', 'not like', '%@smp.com')
                        ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('substitute_teacher_id')
                    ->label('Guru Pengganti')
                    ->options(fn () => \App\Models\User::withoutGlobalScopes()
                        ->where(function ($q) {
                            $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum', 'guru_piket']))
                              ->orWhere('name', 'Guru Piket')
                              ->orWhereHas('schedules')
                              ->orWhereHas('teacherAttendances');
                        })
                        ->where(function ($q) {
                            $q->where('email', 'not like', '%@smp.com')
                              ->orWhere('name', 'Guru Piket');
                        })
                        ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32])
                        ->orderByRaw("CASE WHEN name = 'Guru Piket' THEN 0 WHEN name LIKE '%Piket%' THEN 1 ELSE 2 END, name ASC")
                        ->pluck('name', 'id')
                        ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                        'dinas_luar' => 'Dinas Luar',
                    ]),
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Periode Bulan')
                    ->options([
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereMonth('tanggal', $data['value']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('pengganti_status')
                    ->label('Status Guru Pengganti')
                    ->options([
                        'ada' => 'Ada Guru Pengganti',
                        'belum' => 'Belum Ada Pengganti',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'ada') {
                            $query->whereNotNull('substitute_teacher_id');
                        } elseif ($data['value'] === 'belum') {
                            $query->whereIn('status', ['izin', 'sakit', 'alpa', 'dinas_luar'])
                                  ->whereNull('substitute_teacher_id');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail Rekap')
                    ->icon('heroicon-o-eye')
                    ->modalWidth('3xl'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn () => Auth::user()?->hasRole('superadmin') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeacherAttendances::route('/')];
    }
}
