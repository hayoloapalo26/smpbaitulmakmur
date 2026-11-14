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
                    $schedule = isset($data['schedule_id']) ? Schedule::find($data['schedule_id']) : null;
                    $jamKe = $schedule ? $schedule->jam_ke : ($data['jam_ke'] ?? null);

                    // 1. Validasi jadwal harus sesuai dengan hari pada tanggal yang dipilih
                    if ($schedule) {
                        $hariJadwal = Schedule::getIndonesianDayName($tanggal);
                        if ($schedule->hari !== $hariJadwal) {
                            \Filament\Notifications\Notification::make()
                                ->title('Jadwal Tidak Sesuai Hari')
                                ->body("Jadwal yang dipilih merupakan jadwal hari {$schedule->hari}, sedangkan tanggal yang dipilih adalah hari {$hariJadwal}.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }

                    // 2. Cek izin mengajar guru
                    if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($teacherId, $tanggal, $jamKe)) {
                        $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($teacherId, $tanggal, $jamKe);
                        \Filament\Notifications\Notification::make()
                            ->title('Pengisian Jurnal Ditolak')
                            ->body('Anda tidak dapat mengisi jurnal mengajar karena sedang dalam status perizinan yang telah disetujui (ACC) pada waktu tersebut (' . ($leave?->waktu_izin_label ?? "Jam Ke-{$jamKe}") . ').')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                })
                ->after(function (TeachingJournal $record) {
                    if ($record->is_late) {
                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Tercatat Terlambat')
                            ->body('Jurnal berhasil disimpan dan tercatat terlambat (melewati 23:59 tanggal mengajar). Jurnal ini masuk ke antrean Persetujuan Waka Kurikulum terlebih dahulu sebelum masuk ke laporan Kepala Sekolah.')
                            ->warning()
                            ->duration(8000)
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Jurnal Berhasil Disimpan')
                            ->body('Jurnal pembelajaran tepat waktu berhasil dicatat dan langsung diteruskan ke Kepala Sekolah untuk pemantauan.')
                            ->success()
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

        $teachers = User::role(array_merge(['guru_mapel', 'guru_kelas', 'wali_kelas'], User::QURAN_TEACHER_ROLES))
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
                'attendance_status' => $attendance?->status ?? ($isFilled ? 'hadir' : ($leaveOnDate ? 'izin' : 'belum_absen')),
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
