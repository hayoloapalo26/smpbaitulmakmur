<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeachingJournalResource\Pages;
use App\Models\TeachingJournal;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TeachingJournalResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'guru_kelas', 'guru_mapel', 'guru_alquran', 'wali_kelas',
            'waka_kurikulum', 'kepala_sekolah', 'superadmin'
        ]);
    }

    public static function canCreate(): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    protected static ?string $model = TeachingJournal::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Jurnal Mengajar & Presensi';

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Bab 2: Perencanaan Administrasi Pembelajaran';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return '- Fitur Input Mengajar';
        }
        return 'Jurnal Mengajar & Presensi';
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 1;
        }
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isAdminOrWaka = $user->hasAnyRole(['waka_kurikulum', 'superadmin']);

        return $form
            ->schema([
                Forms\Components\Section::make('Jadwal & Jam Mengajar (Otomatis Mencatat Presensi Guru)')
                    ->description('Pilih tanggal dan jam mengajar sesuai Jadwal Pelajaran yang telah diatur oleh Waka Kurikulum. Input jurnal ini otomatis menjadi bukti Presensi Kehadiran Anda pada jam pelajaran tersebut.')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru Pengajar')
                            ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) use ($user) {
                                if ($user->institution_id && !$user->hasRole('superadmin')) {
                                    $query->where('institution_id', $user->institution_id);
                                }
                                $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']));
                            })
                            ->default(Auth::id())
                            ->visible($isAdminOrWaka)
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('student_attendances', []);
                                $set('catatan_karakter', []);
                            })
                            ->required(),

                        Forms\Components\Hidden::make('teacher_id')
                            ->default(Auth::id())
                            ->visible(!$isAdminOrWaka),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Mengajar')
                            ->default(Carbon::today()->format('Y-m-d'))
                            ->helperText(fn (Forms\Get $get) => 'Hari: ' . Schedule::getIndonesianDayName($get('tanggal') ?: Carbon::today()))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('schedule_id', null);
                                $set('classroom_id', null);
                                $set('subject_id', null);
                                $set('jam_ke', null);
                                $set('student_attendances', []);
                                $set('catatan_karakter', []);
                            })
                            ->required(),

                        Forms\Components\Select::make('schedule_id')
                            ->label('Pilih Jadwal Mengajar (Sesuai Hari & Jam Ke-)')
                            ->options(function (Forms\Get $get, ?TeachingJournal $record) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);

                                // Jadwal reguler guru
                                $regularSchedules = Schedule::with(['classroom', 'subject', 'teacher'])
                                    ->where('teacher_id', $teacherId)
                                    ->where('hari', $hari)
                                    ->get();

                                // Jadwal di mana guru ini ditugaskan sebagai Guru Pengganti (Inval) pada tanggal tersebut
                                $substituteScheduleIds = \App\Models\TeacherAttendance::withoutGlobalScopes()
                                    ->where('substitute_teacher_id', $teacherId)
                                    ->whereDate('tanggal', $tanggal)
                                    ->whereNotNull('schedule_id')
                                    ->pluck('schedule_id')
                                    ->toArray();

                                $substituteSchedules = !empty($substituteScheduleIds)
                                    ? Schedule::with(['classroom', 'subject', 'teacher'])
                                        ->whereIn('id', $substituteScheduleIds)
                                        ->where('hari', $hari)
                                        ->get()
                                    : collect();

                                $schedules = $regularSchedules->merge($substituteSchedules)->sortBy('jam_ke');

                                $options = [];
                                foreach ($schedules as $s) {
                                    $isSubstituteForThis = ($s->teacher_id != $teacherId);
                                    $alreadyFilled = TeachingJournal::where('schedule_id', $s->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->exists();

                                    $statusText = '';
                                    if ($alreadyFilled) {
                                        $statusText = ' ✅ [Sudah Diisi]';
                                    }

                                    if (!$isSubstituteForThis) {
                                        // Cek apakah guru sedang izin yang telah di-ACC pada jam pelajaran ini
                                        $onLeave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $s->jam_ke);
                                        if ($onLeave) {
                                            $statusText = " 🚫 [Izin ACC: {$onLeave->waktu_izin_label}]";
                                        }
                                    } else {
                                        $statusText = " 🔄 [TUGAS INVAL / PENGGANTI dari {$s->teacher?->name}]" . $statusText;
                                    }

                                    $jamText = \App\Models\Schedule::getJamLabelWithTime($s->jam_ke);
                                    $options[$s->id] = "{$jamText} | Kelas {$s->classroom?->name} — {$s->subject?->name}{$statusText}";
                                }

                                return $options;
                            })
                            ->disableOptionWhen(function (string $value, Forms\Get $get, ?TeachingJournal $record) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $schedule = Schedule::find($value);
                                if (!$schedule) return false;

                                $isSubstituteForThis = ($schedule->teacher_id != $teacherId);

                                // 1. Kunci opsi jika guru reguler memiliki izin yang telah di-ACC pada jam ini (Kecuali guru pengganti)
                                if (!$isSubstituteForThis && \App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $schedule->jam_ke)) {
                                    return true;
                                }

                                // 2. Kunci opsi jika sudah diisi sebelumnya
                                return TeachingJournal::where('schedule_id', $schedule->id)
                                    ->whereDate('tanggal', $tanggal)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->exists();
                            })
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $teacherId = $get('teacher_id') ?: Auth::id();
                                        $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                        $schedule = Schedule::find($value);
                                        if ($schedule && $schedule->teacher_id == $teacherId && \App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $schedule->jam_ke)) {
                                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $schedule->jam_ke);
                                            $fail("Tidak dapat mengisi jurnal mengajar! Anda tercatat memiliki perizinan yang telah disetujui (ACC) pada tanggal {$tanggal} (" . ($leave?->waktu_izin_label ?? "Jam Ke-{$schedule->jam_ke}") . ").");
                                        }
                                    };
                                }
                            ])
                            ->helperText(function (Forms\Get $get) {
                                $teacherId = $get('teacher_id') ?: Auth::id();
                                $tanggal = $get('tanggal') ?: Carbon::today()->format('Y-m-d');
                                $hari = Schedule::getIndonesianDayName($tanggal);

                                $leaveToday = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal);
                                if ($leaveToday) {
                                    return "⛔ PERHATIAN: Terdapat perizinan guru yang telah di-ACC pada tanggal ini ({$leaveToday->waktu_izin_label} — Alasan: {$leaveToday->alasan}). Jadwal jam pelajaran yang bertepatan dengan izin otomatis terkunci dan tidak dapat diisi jurnal.";
                                }

                                $count = Schedule::where('teacher_id', $teacherId)->where('hari', $hari)->count();
                                if ($count === 0) {
                                    return "⚠️ Anda tidak memiliki jadwal mengajar pada hari {$hari} berdasarkan Jadwal Pelajaran Waka Kurikulum.";
                                }
                                return "Terdapat {$count} jam mengajar terjadwal untuk Anda pada hari {$hari}.";
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (!$state) {
                                    $set('classroom_id', null);
                                    $set('subject_id', null);
                                    $set('jam_ke', null);
                                    $set('student_attendances', []);
                                    $set('catatan_karakter', []);
                                    return;
                                }

                                $schedule = Schedule::find($state);
                                if (!$schedule) return;

                                $set('classroom_id', $schedule->classroom_id);
                                $set('subject_id', $schedule->subject_id);
                                $set('jam_ke', $schedule->jam_ke);

                                $tanggal = $get('tanggal') ?? Carbon::today()->format('Y-m-d');
                                $students = \App\Models\Student::where('classroom_id', $schedule->classroom_id)->orderBy('name')->get();
                                $attendanceData = [];
                                foreach ($students as $student) {
                                    $morning = \App\Models\DailyAttendance::where('student_id', $student->id)
                                        ->whereDate('tanggal', $tanggal)
                                        ->where('tipe_absen', 'masuk')
                                        ->first();
                                    if ($morning && in_array($morning->status, ['sakit', 'izin', 'alpa'])) {
                                        $attendanceData[$student->id] = $morning->status;
                                    } else {
                                        $attendanceData[$student->id] = 'hadir';
                                    }
                                }
                                $set('student_attendances', $attendanceData);
                            })
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('jam_ke')
                            ->label('Jam Pelajaran & Waktu')
                            ->options(Schedule::getJamOptions())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('To Do List Kegiatan Rutin')
                    ->description('Dengan melaksanakan to do list ini, anda berkontribusi terhadap pengembangan kualitas murid')
                    ->schema([
                        Forms\Components\CheckboxList::make('kegiatan_rutin')
                            ->label('')
                            ->options([
                                'Do\'a (Karakter)' => 'Do\'a (Karakter)',
                                'Menanyakan Kabar (Karakter)' => 'Menanyakan Kabar (Karakter)',
                                'Absensi (Akademik)' => 'Absensi (Akademik)',
                                'Review Materi Sebelumnya (Akademik)' => 'Review Materi Sebelumnya (Akademik)',
                                'Pembelajaran Harian (Akademik)' => 'Pembelajaran Harian (Akademik)',
                                'Mengingatkan Tentang Sampah (Karakter)' => 'Mengingatkan Tentang Sampah (Karakter)',
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Jurnal Pembelajaran')
                    ->schema([
                        Forms\Components\TextInput::make('rencana_kegiatan_guru')
                            ->label('Rencana Kegiatan Guru')
                            ->placeholder('Contoh: Guru menyampaikan Ceramah')
                            ->required(),
                        Forms\Components\TextInput::make('kegiatan_siswa')
                            ->label('Kegiatan Siswa')
                            ->placeholder('Contoh: Siswa Melakukan Presentasi')
                            ->required(),
                        Forms\Components\Textarea::make('refleksi_guru')
                            ->label('Refleksi Guru')
                            ->placeholder('Apa yang berjalan baik? Apa yang perlu ditingkatkan?')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('tindak_lanjut')
                            ->label('Tindak Lanjut')
                            ->placeholder('Rencana tindak lanjut / remedial / pengayaan...')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Hidden::make('catatan_karakter')
                    ->default([])
                    ->dehydrated()
                    ->afterStateHydrated(function (Forms\Components\Hidden $component, ?TeachingJournal $record) {
                        if ($record) {
                            $component->state($record->catatan_karakter ?? []);
                        }
                    })
                    ->saveRelationshipsUsing(function (TeachingJournal $record, $state) {
                        $charEntries = is_array($state) ? $state : ($record->catatan_karakter ?? []);

                        // Hapus log karakter lama dari jurnal ini agar sinkron dan tidak dobel saat diedit
                        $prefix = "[Jurnal #{$record->id}]";
                        \App\Models\CharacterScoreLog::withoutGlobalScopes()
                            ->where('description', 'like', "{$prefix}%")
                            ->get()
                            ->each(fn ($log) => $log->delete());

                        if (is_array($charEntries)) {
                            foreach ($charEntries as $item) {
                                $studentId = $item['student_id'] ?? null;
                                $catatan = trim($item['catatan'] ?? '');
                                $kategori = trim($item['kategori'] ?? 'Karakter Mapel');
                                $type = $item['type'] ?? 'baik';

                                if ($studentId && ($catatan !== '' || $kategori !== '')) {
                                    $student = \App\Models\Student::find($studentId);
                                    if ($student) {
                                        $isBaik = ($type === 'baik');
                                        $subjectName = $record->subject?->name ?? 'Mapel';
                                        $descriptionText = "{$prefix} [{$subjectName}] " . ($catatan ?: $kategori);

                                        \App\Models\CharacterScoreLog::create([
                                            'student_id'   => $student->id,
                                            'source'       => 'guru_kelas',
                                            'category'     => $kategori ?: ($isBaik ? 'Karakter Baik' : 'Karakter Kurang Baik'),
                                            'description'  => $descriptionText,
                                            'point_change' => $isBaik ? 1 : -1,
                                            'tanggal'      => $record->tanggal,
                                            'submitted_by' => Auth::id(),
                                            'institution_id' => $student->institution_id,
                                        ]);
                                    }
                                }
                            }
                        }
                    }),

                Forms\Components\ViewField::make('student_attendances')
                    ->label('Presensi & Penilaian Karakter Siswa Per Mapel')
                    ->view('filament.staff.forms.components.student-attendance-selector')
                    ->viewData(function (Forms\Get $get, ?TeachingJournal $record, Forms\Components\ViewField $component) {
                        $classroomId = $get('classroom_id') ?? $record?->classroom_id;
                        $students = $classroomId
                            ? \App\Models\Student::where('classroom_id', $classroomId)->orderBy('name')->get()
                            : collect();

                        return [
                            'students' => $students,
                            'classroomId' => $classroomId,
                            'charStatePath' => str_replace('student_attendances', 'catatan_karakter', $component->getStatePath()),
                        ];
                    })
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\ViewField $component, ?TeachingJournal $record) {
                        if ($record) {
                            $existing = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)
                                ->pluck('status', 'student_id')
                                ->toArray();
                            $students = \App\Models\Student::where('classroom_id', $record->classroom_id)->orderBy('name')->get();
                            $attendanceData = [];
                            foreach ($students as $student) {
                                $attendanceData[$student->id] = $existing[$student->id] ?? 'hadir';
                            }
                            $component->state($attendanceData);
                        }
                    })
                    ->saveRelationshipsUsing(function (TeachingJournal $record, $state) {
                        $students = \App\Models\Student::where('classroom_id', $record->classroom_id)->get();
                        $state = is_array($state) ? $state : [];

                        foreach ($students as $student) {
                            $status = $state[$student->id] ?? $state[(string) $student->id] ?? 'hadir';
                            if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpa'])) {
                                $status = 'hadir';
                            }

                            \App\Models\SubjectAttendance::updateOrCreate(
                                [
                                    'teaching_journal_id' => $record->id,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'institution_id' => $student->institution_id,
                                    'status' => $status,
                                ]
                            );

                            $morning = \App\Models\DailyAttendance::where('student_id', $student->id)
                                ->whereDate('tanggal', $record->tanggal)
                                ->where('tipe_absen', 'masuk')
                                ->first();

                            // Jika belum ada rekap presensi harian masuk pada hari tersebut, sinkronkan ke DailyAttendance agar masuk ke Waka Kesiswaan lembaga terkait
                            if (!$morning) {
                                \App\Models\DailyAttendance::create([
                                    'institution_id' => $student->institution_id,
                                    'student_id' => $student->id,
                                    'teacher_id' => $record->teacher_id ?: Auth::id(),
                                    'tanggal' => Carbon::parse($record->tanggal)->toDateString(),
                                    'tipe_absen' => 'masuk',
                                    'metode_absen' => 'jurnal_mengajar',
                                    'status' => $status,
                                    'is_late' => false,
                                    'keterangan' => 'Tercatat dari Jurnal Mengajar (' . ($record->subject->name ?? 'Mapel') . ')',
                                ]);
                            } elseif ($morning->metode_absen === 'jurnal_mengajar') {
                                $morning->update(['status' => $status]);
                            }
                        }
                    })
                    ->columnSpanFull(),
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
                    ->label('Jam Pelajaran')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => Schedule::getJamLabelWithTime($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->label('Guru'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('presensi_status')
                    ->label('Presensi Guru')
                    ->state(fn () => 'Hadir (Dari Jurnal)')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle'),
                Tables\Columns\TextColumn::make('student_attendance_summary')
                    ->label('Presensi Siswa')
                    ->state(function (TeachingJournal $record) {
                        $attendances = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)->get();
                        if ($attendances->isEmpty()) return '-';
                        $hadir = $attendances->where('status', 'hadir')->count();
                        $sakit = $attendances->where('status', 'sakit')->count();
                        $izin = $attendances->where('status', 'izin')->count();
                        $alpa = $attendances->where('status', 'alpa')->count();

                        $parts = [];
                        if ($hadir > 0) $parts[] = "H: {$hadir}";
                        if ($sakit > 0) $parts[] = "S: {$sakit}";
                        if ($izin > 0) $parts[] = "I: {$izin}";
                        if ($alpa > 0) $parts[] = "A: {$alpa}";

                        return implode(' • ', $parts);
                    })
                    ->badge()
                    ->color(function (TeachingJournal $record) {
                        $hasAbsence = \App\Models\SubjectAttendance::where('teaching_journal_id', $record->id)
                            ->whereIn('status', ['sakit', 'izin', 'alpa'])
                            ->exists();
                        return $hasAbsence ? 'warning' : 'success';
                    }),
                Tables\Columns\TextColumn::make('catatan_karakter')
                    ->label('Karakter Siswa')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state) || count($state) === 0) {
                            return '-';
                        }
                        $baik = collect($state)->where('type', 'baik')->count();
                        $kurang = collect($state)->where('type', 'kurang_baik')->count();
                        $badges = [];
                        if ($baik > 0) $badges[] = "⭐ {$baik} Baik";
                        if ($kurang > 0) $badges[] = "⚠️ {$kurang} Kurang";
                        return implode(' • ', $badges);
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (empty($state) || !is_array($state) || count($state) === 0) return 'gray';
                        $hasKurang = collect($state)->where('type', 'kurang_baik')->count() > 0;
                        return $hasKurang ? 'danger' : 'success';
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('5xl')
                    ->before(function (Tables\Actions\EditAction $action, TeachingJournal $record, array $data) {
                        $teacherId = $data['teacher_id'] ?? $record->teacher_id;
                        $tanggal = $data['tanggal'] ?? $record->tanggal;
                        $schedule = isset($data['schedule_id']) ? Schedule::find($data['schedule_id']) : null;
                        $jamKe = $schedule ? $schedule->jam_ke : ($data['jam_ke'] ?? $record->jam_ke);

                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $jamKe)) {
                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $jamKe);
                            \Filament\Notifications\Notification::make()
                                ->title('Perubahan Jurnal Ditolak')
                                ->body('Jurnal ini tidak dapat diubah karena guru bersangkutan memiliki perizinan yang telah di-ACC pada waktu tersebut (' . ($leave?->waktu_izin_label ?? "Jam Ke-{$jamKe}") . ').')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeachingJournals::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
