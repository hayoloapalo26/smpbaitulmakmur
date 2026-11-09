<?php

namespace App\Filament\Staff\Resources\ScheduleResource\Pages;

use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use App\Models\Schedule;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageSchedules extends ManageRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('setting_guru_mapel')
                ->label('Setting Guru Mapel')
                ->icon('heroicon-o-link')
                ->color('info')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) ?? false)
                ->url(fn () => TeacherSubjectClassResource::getUrl()),
            Actions\CreateAction::make()
                ->modalWidth('3xl')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false)
                ->before(function (Actions\CreateAction $action, array $data) {
                    $days = is_array($data['hari'] ?? []) ? $data['hari'] : (!empty($data['hari']) ? [$data['hari']] : []);
                    $jams = is_array($data['jam_ke'] ?? []) ? $data['jam_ke'] : (isset($data['jam_ke']) && $data['jam_ke'] !== '' ? [$data['jam_ke']] : []);

                    $clashes = Schedule::checkClashes($data['classroom_id'] ?? null, $data['teacher_id'] ?? null, $days, $jams);
                    if (!empty($clashes)) {
                        Notification::make()
                            ->title('Gagal Menyimpan: Bentrok Jadwal Terdeteksi!')
                            ->body(implode("\n", $clashes))
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                })
                ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                    $days = is_array($data['hari'] ?? []) ? $data['hari'] : (!empty($data['hari']) ? [$data['hari']] : []);
                    $jams = is_array($data['jam_ke'] ?? []) ? $data['jam_ke'] : (isset($data['jam_ke']) && $data['jam_ke'] !== '' ? [$data['jam_ke']] : []);

                    $clashes = Schedule::checkClashes($data['classroom_id'] ?? null, $data['teacher_id'] ?? null, $days, $jams);
                    if (!empty($clashes)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'jam_ke' => $clashes,
                        ]);
                    }

                    $institutionId = auth()->user()?->institution_id ?? 2;
                    if (!empty($data['classroom_id'])) {
                        $classroom = \App\Models\Classroom::withoutGlobalScopes()->find($data['classroom_id']);
                        if ($classroom && $classroom->institution_id) {
                            $institutionId = $classroom->institution_id;
                        }
                    }

                    $created = [];
                    foreach ($days as $day) {
                        foreach ($jams as $jam) {
                            $created[] = Schedule::withoutGlobalScopes()->firstOrCreate([
                                'classroom_id' => $data['classroom_id'],
                                'subject_id' => $data['subject_id'],
                                'teacher_id' => $data['teacher_id'],
                                'hari' => $day,
                                'jam_ke' => (int) $jam,
                            ], [
                                'institution_id' => $institutionId,
                            ]);
                        }
                    }

                    $total = count($created);
                    Notification::make()
                        ->title('Jadwal Pelajaran Berhasil Disimpan')
                        ->body("Total {$total} JP (Jam Pelajaran) berhasil dijadwalkan untuk guru bersangkutan.")
                        ->success()
                        ->send();

                    return $created[0] ?? new Schedule();
                }),
        ];
    }
}
