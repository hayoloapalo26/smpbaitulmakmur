<?php

namespace App\Filament\Staff\Widgets;

use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Models\Schedule;
use App\Models\TeachingJournal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class DailyTeachingScheduleWidget extends BaseWidget
{
    protected static ?string $heading = 'Jadwal Mengajar & Status Presensi Hari Ini';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(array_merge(['guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function table(Table $table): Table
    {
        $today = Carbon::today()->format('Y-m-d');
        $hari = Schedule::getIndonesianDayName($today);

        return $table
            ->heading("Jadwal Mengajar & Presensi Hari Ini ({$hari}, " . Carbon::today()->translatedFormat('d F Y') . ")")
            ->description('Presensi kehadiran Anda diambil secara otomatis saat Anda mengisi Jurnal Mengajar pada masing-masing jam pelajaran.')
            ->query(
                Schedule::query()
                    ->where('teacher_id', auth()->id())
                    ->where('hari', $hari)
                    ->orderBy('jam_ke', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Pelajaran & Waktu')
                    ->badge()
                    ->formatStateUsing(fn ($state) => \App\Models\Schedule::getJamLabelWithTime($state))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_presensi')
                    ->label('Status Jurnal & Presensi')
                    ->state(function (Schedule $record) use ($today): string {
                        // Cek apakah guru sedang izin yang telah di-ACC pada hari & jam ini
                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($record->teacher_id, $today, $record->jam_ke)) {
                            $leave = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($record->teacher_id, $today, $record->jam_ke);
                            return 'Izin ACC (' . ($leave?->waktu_izin_label ?? 'Izin') . ')';
                        }

                        $filled = TeachingJournal::where('teacher_id', $record->teacher_id)
                            ->whereDate('tanggal', $today)
                            ->where('jam_ke', $record->jam_ke)
                            ->exists();

                        return $filled ? 'Hadir (Jurnal Terisi)' : 'Belum Isi Jurnal';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match(true) {
                        str_contains($state, 'Hadir') => 'success',
                        str_contains($state, 'Izin') => 'info',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): string => match(true) {
                        str_contains($state, 'Hadir') => 'heroicon-m-check-circle',
                        str_contains($state, 'Izin') => 'heroicon-m-calendar-days',
                        default => 'heroicon-m-clock',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('isi_jurnal')
                    ->label('Isi Jurnal Mengajar')
                    ->icon('heroicon-m-pencil-square')
                    ->button()
                    ->color('primary')
                    ->url(fn () => TeachingJournalResource::getUrl('index'))
                    ->visible(function (Schedule $record) use ($today): bool {
                        // Kunci jika guru sedang izin di jam/tanggal ini
                        if (\App\Models\TeacherLeaveRequest::isTeacherOnLeave($record->teacher_id, $today, $record->jam_ke)) {
                            return false;
                        }

                        return !TeachingJournal::where('teacher_id', $record->teacher_id)
                            ->whereDate('tanggal', $today)
                            ->where('jam_ke', $record->jam_ke)
                            ->exists();
                    }),
            ])
            ->paginated(false);
    }
}
