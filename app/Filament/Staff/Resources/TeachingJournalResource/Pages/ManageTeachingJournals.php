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
            Actions\CreateAction::make()
                ->label('Input Jurnal Mengajar Baru')
                ->icon('heroicon-m-plus')
                ->modalWidth('5xl')
                ->before(function (Actions\CreateAction $action, array $data) {
                    $teacherId = $data['teacher_id'] ?? auth()->id();
                    $tanggal = $data['tanggal'] ?? now()->toDateString();
                    $schedule = isset($data['schedule_id']) ? Schedule::find($data['schedule_id']) : null;
                    $jamKe = $schedule ? $schedule->jam_ke : ($data['jam_ke'] ?? null);

                    $isAdminOrWaka = auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']);

                    // Cek batas waktu pengisian 24 jam
                    if (!$isAdminOrWaka && TeachingJournal::isPastDeadline($tanggal, $jamKe)) {
                        $deadline = TeachingJournal::getJournalDeadline($tanggal, $jamKe)->translatedFormat('d M Y, H:i');
                        \Filament\Notifications\Notification::make()
                            ->title('Pengisian Jurnal Ditolak (Kedaluwarsa)')
                            ->body("Batas waktu pengisian jurnal mengajar telah berakhir (maksimal 24 jam setelah jadwal mengajar selesai: {$deadline} WIB). Anda tidak dapat lagi menginputkan jurnal ini.")
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }

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

        $teachers = User::role(['guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas'])
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

            // Journals on this date
            $journalsOnDate = TeachingJournal::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $date)
                ->with(['classroom', 'subject'])
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

            $totalHistory = TeachingJournal::where('teacher_id', $teacher->id)->count();

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
            ->with(['classroom', 'subject', 'schedule'])
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
}
