<?php

namespace App\Filament\Staff\Resources\TeachingJournalResource\Pages;

use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Models\Schedule;
use App\Models\SubjectAttendance;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubjectClass;
use App\Models\TeachingJournal;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeachingJournals extends ManageRecords
{
    protected static string $resource = TeachingJournalResource::class;

    public ?string $selectedDate = null;
    public string $search = '';
    public string $statusFilter = 'all'; // 'all', 'sudah', 'belum'
    public ?int $selectedTeacherId = null;
    public bool $showDetailModal = false;
    public bool $showFeedbackModal = false;
    public ?int $feedbackJournalId = null;
    public string $feedbackNotes = '';
    public string $feedbackStatus = 'approved';

    public function mount(): void
    {
        parent::mount();
        $this->selectedDate = now()->toDateString();
    }

    public function getView(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'filament.staff.resources.teaching-journal.monitoring-kepsek';
        }

        return parent::getView();
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return [];
        }

        return [
            Actions\Action::make('cetak_rekap_pdf')
                ->label('🖨️ Cetak Rekap Jurnal (PDF)')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('academic.journal.rekap.pdf'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make()
                ->label('Input Jurnal Mengajar Baru')
                ->icon('heroicon-m-plus')
                ->modalWidth('5xl')
                ->before(function (Actions\CreateAction $action, array $data) {
                    $teacherId = $data['teacher_id'] ?? auth()->id();
                    $tanggal = $data['tanggal'] ?? now()->toDateString();
                    $scheduleIds = (array) ($data['schedule_ids'] ?? ($data['schedule_id'] ?? []));
                    $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));

                    if (empty($scheduleIds)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Pilih Jadwal Mengajar')
                            ->body('Silakan pilih minimal satu jadwal mengajar.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }

                    $schedules = Schedule::withoutGlobalScopes()->whereIn('id', $scheduleIds)->get();
                    if ($schedules->count() !== count($scheduleIds)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Jadwal Tidak Ditemukan')
                            ->body('Sebagian jadwal mengajar yang dipilih tidak ditemukan.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }

                    $hariTanggal = Schedule::getIndonesianDayName($tanggal);
                    $firstSchedule = $schedules->first();

                    foreach ($schedules as $schedule) {
                        // 1. Validasi jadwal harus sesuai dengan hari pada tanggal yang dipilih
                        if ($schedule->hari !== $hariTanggal) {
                            \Filament\Notifications\Notification::make()
                                ->title('Jadwal Tidak Sesuai Hari')
                                ->body("Jadwal jam ke-{$schedule->jam_ke} merupakan jadwal hari {$schedule->hari}, sedangkan tanggal yang dipilih adalah hari {$hariTanggal}.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }

                        // 2. Validasi kesamaan kelas dan mapel
                        if ($schedule->classroom_id !== $firstSchedule->classroom_id || $schedule->subject_id !== $firstSchedule->subject_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('Kombinasi Jadwal Tidak Valid')
                                ->body('Semua jadwal yang dipilih bersamaan harus berada pada kelas dan mata pelajaran yang sama.')
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }

                        // 3. Cek izin mengajar guru (Kecuali Dinas Luar)
                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $schedule->jam_ke)) {
                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $schedule->jam_ke);
                            if ($leave && !$leave->isDinasLuar()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Pengisian Jurnal Ditolak')
                                    ->body('Anda tidak dapat mengisi jurnal mengajar karena sedang dalam status perizinan yang telah disetujui (ACC) pada jam ke-' . $schedule->jam_ke . ' (' . ($leave?->waktu_izin_label ?? "Jam Ke-{$schedule->jam_ke}") . ').')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }

                        // 4. Cek apakah sudah pernah diisi hari ini oleh guru ini
                        $alreadyFilled = TeachingJournal::where('schedule_id', $schedule->id)
                            ->where('teacher_id', $teacherId)
                            ->whereDate('tanggal', $tanggal)
                            ->exists();
                        if ($alreadyFilled) {
                            \Filament\Notifications\Notification::make()
                                ->title('Jadwal Sudah Pernah Diisi')
                                ->body("Jadwal jam ke-{$schedule->jam_ke} sudah pernah Anda isi jurnalnya pada tanggal {$tanggal}.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }
                })
                ->using(function (array $data, ?string $model = null): \Illuminate\Database\Eloquent\Model {
                    $scheduleIds = (array) ($data['schedule_ids'] ?? ($data['schedule_id'] ?? []));
                    $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));

                    $schedules = Schedule::withoutGlobalScopes()->whereIn('id', $scheduleIds)->orderBy('jam_ke')->get();
                    if ($schedules->isEmpty()) {
                        throw new \Exception("Jadwal mengajar tidak ditemukan.");
                    }

                    // Ambil data presensi siswa dan catatan karakter
                    $rawState = method_exists($this, 'getMountedActionForm') ? ($this->getMountedActionForm()?->getRawState() ?? []) : [];
                    $studentAttendances = $data['student_attendances'] ?? $rawState['student_attendances'] ?? [];
                    $catatanKarakter = $data['catatan_karakter'] ?? $rawState['catatan_karakter'] ?? [];

                    $firstJournal = null;

                    \Illuminate\Support\Facades\DB::transaction(function () use ($schedules, $data, $studentAttendances, $catatanKarakter, &$firstJournal) {
                        foreach ($schedules as $schedule) {
                            $isKegiatanLain = !empty($data['is_kegiatan_lain']);
                            $namaKegiatanLain = $data['nama_kegiatan_lain'] ?? null;

                            $journalData = [
                                'teacher_id' => $data['teacher_id'] ?? auth()->id(),
                                'schedule_id' => $schedule->id,
                                'classroom_id' => $schedule->classroom_id,
                                'subject_id' => $schedule->subject_id,
                                'jam_ke' => $schedule->jam_ke,
                                'institution_id' => $schedule->institution_id ?? ($data['institution_id'] ?? null),
                                'tanggal' => $data['tanggal'] ?? now()->toDateString(),
                                'is_kegiatan_lain' => $isKegiatanLain,
                                'nama_kegiatan_lain' => $namaKegiatanLain,
                                'materi' => $data['materi'] ?? ($namaKegiatanLain ? "[Kegiatan Siswa] {$namaKegiatanLain}" : null),
                                'rencana_kegiatan_guru' => $data['rencana_kegiatan_guru'] ?? ($namaKegiatanLain ? "Mendampingi & mengawasi siswa dalam pelaksanaan {$namaKegiatanLain}" : null),
                                'kegiatan_siswa' => $data['kegiatan_siswa'] ?? ($namaKegiatanLain ? "Siswa mengikuti dan melaksanakan kegiatan {$namaKegiatanLain} dengan tertib" : null),
                                'refleksi_guru' => $data['refleksi_guru'] ?? null,
                                'tindak_lanjut' => $data['tindak_lanjut'] ?? null,
                                'fase' => $data['fase'] ?? 'D',
                                'capaian_pembelajaran_id' => $data['capaian_pembelajaran_id'] ?? null,
                                'link_drive_rppm' => $data['link_drive_rppm'] ?? null,
                                'rppm_id' => $isKegiatanLain ? null : ($data['rppm_id'] ?? null),
                                'tp_ids' => $isKegiatanLain ? [] : ($data['tp_ids'] ?? []),
                                'kegiatan_rutin' => $data['kegiatan_rutin'] ?? [],
                                'catatan_karakter' => $catatanKarakter,
                            ];

                            // Jika guru pengisi adalah guru dinas luar dan guru inval sudah mengisi presensi siswa sebelumnya
                            $leaveForSchedule = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($journalData['teacher_id'], $journalData['tanggal'], $schedule->jam_ke);
                            if ($leaveForSchedule?->isDinasLuar()) {
                                $invalJournal = TeachingJournal::where('schedule_id', $schedule->id)
                                    ->whereDate('tanggal', $journalData['tanggal'])
                                    ->where('teacher_id', '!=', $journalData['teacher_id'])
                                    ->latest()
                                    ->first();

                                if ($invalJournal) {
                                    $invalAttendances = \App\Models\SubjectAttendance::where('teaching_journal_id', $invalJournal->id)
                                        ->pluck('status', 'student_id')
                                        ->toArray();
                                    if (!empty($invalAttendances)) {
                                        $studentAttendances = $invalAttendances;
                                    }
                                }
                            }

                            $journal = TeachingJournal::create($journalData);

                            // Catat presensi siswa (SubjectAttendance) untuk jurnal ini
                            $students = \App\Models\Student::where('classroom_id', $schedule->classroom_id)->get();
                            foreach ($students as $student) {
                                $status = $studentAttendances[$student->id] ?? $studentAttendances[(string) $student->id] ?? 'hadir';
                                if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpa'])) {
                                    $status = 'hadir';
                                }

                                \App\Models\SubjectAttendance::updateOrCreate(
                                    [
                                        'teaching_journal_id' => $journal->id,
                                        'student_id' => $student->id,
                                    ],
                                    [
                                        'institution_id' => $student->institution_id,
                                        'status' => $status,
                                    ]
                                );
                            }

                            // Jika yang mengisi saat ini adalah Guru Inval, sinkronkan juga presensi siswa ke Jurnal Guru Dinas Luar (jika guru dinas luar sudah membuat jurnal duluan)
                            $isSubstitute = ($schedule->teacher_id != $journalData['teacher_id']);
                            if ($isSubstitute) {
                                $originalTeacherJournal = TeachingJournal::where('schedule_id', $schedule->id)
                                    ->where('teacher_id', $schedule->teacher_id)
                                    ->whereDate('tanggal', $journalData['tanggal'])
                                    ->where('id', '!=', $journal->id)
                                    ->first();

                                if ($originalTeacherJournal) {
                                    foreach ($students as $student) {
                                        $status = $studentAttendances[$student->id] ?? $studentAttendances[(string) $student->id] ?? 'hadir';
                                        if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpa'])) {
                                            $status = 'hadir';
                                        }
                                        \App\Models\SubjectAttendance::updateOrCreate(
                                            [
                                                'teaching_journal_id' => $originalTeacherJournal->id,
                                                'student_id' => $student->id,
                                            ],
                                            [
                                                'institution_id' => $student->institution_id,
                                                'status' => $status,
                                            ]
                                        );
                                    }
                                }
                            }

                            if ($firstJournal === null) {
                                $firstJournal = $journal;
                            }
                        }
                    });

                    return $firstJournal;
                })
                ->after(function (TeachingJournal $record, array $data) {
                    $scheduleIds = (array) ($data['schedule_ids'] ?? ($data['schedule_id'] ?? []));
                    $count = count(array_filter($scheduleIds));
                    $jpText = $count > 1 ? "{$count} Jurnal Mengajar ({$count} JP)" : "Jurnal Mengajar";

                    if ($record->is_late) {
                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Tercatat Terlambat')
                            ->body("Berhasil menyimpan {$jpText} dan tercatat terlambat (melewati 23:59 tanggal mengajar). Jurnal masuk ke antrean Persetujuan Waka Kurikulum.")
                            ->warning()
                            ->duration(8000)
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Berhasil Disimpan')
                            ->body("Berhasil menyimpan {$jpText} tepat waktu dan langsung diteruskan ke Kepala Sekolah untuk pemantauan.")
                            ->success()
                            ->duration(6000)
                            ->send();
                    }
                }),
        ];
    }

    public function openDetail(int $teacherId): void
    {
        $this->selectedTeacherId = $teacherId;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedTeacherId = null;
    }

    public function getMonitoringDataProperty(): array
    {
        $date = $this->selectedDate ?: now()->toDateString();
        $dayName = Schedule::getIndonesianDayName($date);

        $teacherRoleNames = array_merge(['guru_mapel', 'guru_kelas', 'wali_kelas'], User::QURAN_TEACHER_ROLES);
        $teachers = User::whereHas('roles', fn ($q) => $q->whereIn('name', $teacherRoleNames))
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalSudah = 0;
        $totalBelum = 0;

        foreach ($teachers as $teacher) {
            $subjectList = TeacherSubjectClass::where('teacher_id', $teacher->id)
                ->with('subject')
                ->get()
                ->pluck('subject.name')
                ->unique()
                ->filter()
                ->values()
                ->all();

            $classList = TeacherSubjectClass::where('teacher_id', $teacher->id)
                ->with('classroom')
                ->get()
                ->pluck('classroom.name')
                ->unique()
                ->filter()
                ->values()
                ->all();

            // Schedules for this date's day
            $schedulesOnDate = Schedule::where('teacher_id', $teacher->id)
                ->where('hari', $dayName)
                ->with(['classroom', 'subject'])
                ->orderBy('jam_ke')
                ->get();

            // Journals on this date (Khusus yang siap untuk Kepala Sekolah: tepat waktu atau terlambat disetujui Waka)
            $journalsOnDate = TeachingJournal::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $date)
                ->readyForKepsek()
                ->with(['classroom', 'subject', 'wakaApprover', 'kepsekApprover'])
                ->orderBy('jam_ke')
                ->get();

            $isFilled = $journalsOnDate->count() > 0;
            if ($isFilled) {
                $totalSudah++;
            } else {
                $totalBelum++;
            }

            // Filter status
            if ($this->statusFilter === 'sudah' && ! $isFilled) {
                continue;
            }
            if ($this->statusFilter === 'belum' && $isFilled) {
                continue;
            }

            $totalHistory = TeachingJournal::where('teacher_id', $teacher->id)
                ->readyForKepsek()
                ->count();

            // Teacher Attendance status on that date
            $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $date)
                ->first();
            $leaveOnDate = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacher->id, $date);

            $rows[] = [
                'teacher' => $teacher,
                'subjects' => !empty($subjectList) ? implode(', ', $subjectList) : 'Semua Mapel',
                'classes' => !empty($classList) ? implode(', ', $classList) : '-',
                'schedules_count' => $schedulesOnDate->count(),
                'schedules' => $schedulesOnDate,
                'journals_count' => $journalsOnDate->count(),
                'journals' => $journalsOnDate,
                'is_filled' => $isFilled,
                'total_history' => $totalHistory,
                'leave_info' => $leaveOnDate,
                'attendance_status' => $attendance?->status ?? ($isFilled ? 'hadir' : ($leaveOnDate ? ($leaveOnDate->isDinasLuar() ? 'dinas_luar' : 'izin') : 'belum_absen')),
            ];
        }

        return [
            'rows' => $rows,
            'total_guru' => count($teachers),
            'total_sudah' => $totalSudah,
            'total_belum' => $totalBelum,
            'persentase' => count($teachers) > 0 ? round(($totalSudah / count($teachers)) * 100) : 0,
            'date_formatted' => Carbon::parse($date)->translatedFormat('l, d F Y'),
        ];
    }

    public function getSelectedTeacherJournalsProperty(): array
    {
        if (! $this->selectedTeacherId) {
            return [];
        }

        $teacher = User::find($this->selectedTeacherId);
        $journals = TeachingJournal::where('teacher_id', $this->selectedTeacherId)
            ->readyForKepsek()
            ->with(['classroom', 'subject', 'schedule', 'wakaApprover', 'kepsekApprover'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_ke', 'asc')
            ->get();

        // Enhance each journal with student attendance count
        $journalDetails = [];
        foreach ($journals as $j) {
            $attendances = SubjectAttendance::where('teaching_journal_id', $j->id)->get();
            $hadirCount = $attendances->where('status', 'hadir')->count();
            $sakitCount = $attendances->where('status', 'sakit')->count();
            $izinCount = $attendances->where('status', 'izin')->count();
            $alpaCount = $attendances->where('status', 'alpa')->count();

            $journalDetails[] = [
                'journal' => $j,
                'student_attendance' => [
                    'hadir' => $hadirCount,
                    'sakit' => $sakitCount,
                    'izin' => $izinCount,
                    'alpa' => $alpaCount,
                    'total' => $attendances->count(),
                ],
            ];
        }

        return [
            'teacher' => $teacher,
            'entries' => $journalDetails,
            'total' => $journals->count(),
        ];
    }

    public function getFeedbackJournalProperty(): ?TeachingJournal
    {
        if (! $this->feedbackJournalId) {
            return null;
        }

        return TeachingJournal::with(['teacher', 'subject', 'classroom'])->find($this->feedbackJournalId);
    }

    public function openFeedbackModal(int $journalId): void
    {
        $journal = TeachingJournal::find($journalId);
        if (! $journal) {
            return;
        }

        $this->feedbackJournalId = $journalId;
        $this->feedbackNotes = (string) ($journal->catatan_kepala_sekolah ?? '');
        $this->feedbackStatus = $journal->status_acc_kepsek ?: TeachingJournal::ACC_KEPSEK_APPROVED;
        $this->showFeedbackModal = true;
    }

    public function closeFeedbackModal(): void
    {
        $this->showFeedbackModal = false;
        $this->feedbackJournalId = null;
        $this->feedbackNotes = '';
        $this->feedbackStatus = 'approved';
    }

    public function saveFeedbackByKepsek(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])) {
            return;
        }

        if (! $this->feedbackJournalId) {
            return;
        }

        $journal = TeachingJournal::find($this->feedbackJournalId);
        if (! $journal) {
            return;
        }

        $catatan = trim($this->feedbackNotes);
        $status = in_array($this->feedbackStatus, [TeachingJournal::ACC_KEPSEK_APPROVED, TeachingJournal::ACC_KEPSEK_NEED_REVISION, TeachingJournal::ACC_KEPSEK_PENDING])
            ? $this->feedbackStatus
            : TeachingJournal::ACC_KEPSEK_APPROVED;

        $journal->update([
            'status_acc_kepsek'       => $status,
            'catatan_kepala_sekolah'  => !empty($catatan) ? $catatan : ($status === TeachingJournal::ACC_KEPSEK_APPROVED ? 'Pembelajaran telah terlaksana dengan baik.' : null),
            'kepsek_approved_by'      => $user->id,
            'kepsek_approved_at'      => now(),
        ]);

        if ($journal->teacher) {
            $statusLabel = match ($status) {
                TeachingJournal::ACC_KEPSEK_APPROVED => 'Disahkan & Di-ACC',
                TeachingJournal::ACC_KEPSEK_NEED_REVISION => 'Perlu Revisi',
                default => 'Catatan Evaluasi',
            };

            \Filament\Notifications\Notification::make()
                ->title("Umpan Balik Jurnal Mengajar ({$statusLabel})")
                ->body("Kepala Sekolah ({$user->name}) telah memberikan umpan balik pada jurnal Anda (" . Carbon::parse($journal->tanggal)->translatedFormat('d M Y') . "): \"" . ($catatan ?: 'Telah ditinjau.') . "\"")
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->warning($status === TeachingJournal::ACC_KEPSEK_NEED_REVISION)
                ->success($status === TeachingJournal::ACC_KEPSEK_APPROVED)
                ->sendToDatabase($journal->teacher);
        }

        \Filament\Notifications\Notification::make()
            ->title('Umpan Balik Berhasil Disimpan')
            ->body("Catatan evaluasi untuk {$journal->teacher?->name} telah tersimpan dan status diperbarui.")
            ->success()
            ->send();

        $this->closeFeedbackModal();
    }

    public function accJournalByKepsek(int $journalId, ?string $catatan = null): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])) {
            return;
        }

        $journal = TeachingJournal::find($journalId);
        if ($journal && $journal->isReadyForKepsek()) {
            $journal->update([
                'status_acc_kepsek' => TeachingJournal::ACC_KEPSEK_APPROVED,
                'kepsek_approved_by' => $user->id,
                'kepsek_approved_at' => now(),
                'catatan_kepala_sekolah' => $catatan ?: ($journal->catatan_kepala_sekolah ?: 'Disetujui dan disahkan oleh Kepala Sekolah.'),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Jurnal Mengajar Berhasil Di-ACC')
                ->body("Jurnal mengajar {$journal->teacher?->name} tanggal " . Carbon::parse($journal->tanggal)->translatedFormat('d F Y') . " telah berhasil disahkan.")
                ->success()
                ->send();
        }
    }

    public function accAllJournalsForTeacher(int $teacherId): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum'])) {
            return;
        }

        $teacher = User::find($teacherId);
        $journals = TeachingJournal::where('teacher_id', $teacherId)
            ->readyForKepsek()
            ->where('status_acc_kepsek', '!=', TeachingJournal::ACC_KEPSEK_APPROVED)
            ->get();

        $count = $journals->count();
        if ($count === 0) {
            \Filament\Notifications\Notification::make()
                ->title('Informasi')
                ->body('Semua jurnal guru ini sudah dalam status disahkan (ACC).')
                ->info()
                ->send();
            return;
        }

        foreach ($journals as $journal) {
            $journal->update([
                'status_acc_kepsek' => TeachingJournal::ACC_KEPSEK_APPROVED,
                'kepsek_approved_by' => $user->id,
                'kepsek_approved_at' => now(),
                'catatan_kepala_sekolah' => $journal->catatan_kepala_sekolah ?: 'Disetujui dan disahkan oleh Kepala Sekolah.',
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Seluruh Jurnal Berhasil Di-ACC')
            ->body("Sebanyak {$count} jurnal mengajar milik {$teacher?->name} telah berhasil disahkan sekaligus.")
            ->success()
            ->send();
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        if ($user && $user->hasRole('kepala_sekolah')) {
            return [];
        }

        $isWakaOrAdmin = $user && $user->hasAnyRole(['waka_kurikulum', 'superadmin']);

        $pendingQuery = TeachingJournal::pendingWaka();
        if (!$isWakaOrAdmin && $user) {
            $pendingQuery->where('teacher_id', $user->id);
        }
        $pendingCount = $pendingQuery->count();

        return [
            'all' => \Filament\Resources\Components\Tab::make('Semua Jurnal'),
            'on_time' => \Filament\Resources\Components\Tab::make('Tepat Waktu')
                ->modifyQueryUsing(fn ($query) => $query->where('is_late', false)),
            'pending_waka' => \Filament\Resources\Components\Tab::make('⚠️ Menunggu Persetujuan Waka (Terlambat)')
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->pendingWaka()),
            'approved_waka' => \Filament\Resources\Components\Tab::make('✅ Disetujui Waka (Terlambat)')
                ->modifyQueryUsing(fn ($query) => $query->where('late_status', TeachingJournal::LATE_STATUS_APPROVED_WAKA)),
        ];
    }
}
