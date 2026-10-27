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
            Actions\Action::make('routing_guru_mapel')
                ->label('Routing Guru Mapel')
                ->icon('heroicon-o-link')
                ->color('info')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) ?? false)
                ->url(fn () => TeacherSubjectClassResource::getUrl()),
            Actions\CreateAction::make()
                ->modalWidth('3xl')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'tata_usaha', 'kepala_sekolah', 'superadmin']) ?? false)
                ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                    $days = is_array($data['hari']) ? $data['hari'] : [$data['hari']];
                    $jams = is_array($data['jam_ke']) ? $data['jam_ke'] : [$data['jam_ke']];

                    $institutionId = auth()->user()?->institution_id ?? 2;
                    if (!empty($data['classroom_id'])) {
                        $classroom = \App\Models\Classroom::find($data['classroom_id']);
                        if ($classroom && $classroom->institution_id) {
                            $institutionId = $classroom->institution_id;
                        }
                    }

                    $created = [];
                    foreach ($days as $day) {
                        foreach ($jams as $jam) {
                            $created[] = Schedule::firstOrCreate([
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
                        ->body("{$total} slot jadwal pelajaran berhasil ditambahkan.")
                        ->success()
                        ->send();

                    return $created[0] ?? new Schedule();
                }),
        ];
    }
}
