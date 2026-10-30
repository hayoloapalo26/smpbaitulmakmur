<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Filament\Staff\Resources\DutySwapRequestResource;
use App\Filament\Staff\Widgets\DutyScheduleFormationWidget;
use App\Models\DutySchedule;
use App\Models\DutySwapRequest;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDutySchedules extends ListRecords
{
    protected static string $resource = DutyScheduleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DutyScheduleFormationWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Jadwal Piket')
                ->icon('heroicon-o-plus')
                ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),

            Actions\Action::make('ajukan_tukar_piket')
                ->label('🔄 Ajukan Tukar Piket')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->modalHeading('🔄 Pengajuan Tukar Jadwal Piket')
                ->modalDescription('Ajukan permohonan penukaran jadwal piket dengan rekan guru lainnya. Permohonan akan aktif otomatis setelah disetujui.')
                ->modalSubmitActionLabel('Kirim Permohonan Tukar')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\DatePicker::make('original_date')
                        ->label('Tanggal Piket Asli Anda')
                        ->required()
                        ->default(now()->toDateString())
                        ->native(false)
                        ->displayFormat('d F Y')
                        ->helperText('Tanggal jadwal piket Anda yang ingin digantikan.'),

                    Forms\Components\Select::make('substitute_teacher_id')
                        ->label('Pilih Guru Pengganti')
                        ->options(fn () => User::where('id', '!=', Auth::id())->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Pilih rekan guru yang bersedia menggantikan jadwal Anda.'),

                    Forms\Components\DatePicker::make('new_date')
                        ->label('Tanggal Anda Menggantikan Guru Pengganti')
                        ->required()
                        ->default(now()->addDays(2)->toDateString())
                        ->native(false)
                        ->displayFormat('d F Y')
                        ->helperText('Tanggal di mana Anda akan bertugas menggantikan rekan tersebut.'),
                ])
                ->action(function (array $data) {
                    DutySwapRequest::create([
                        'requester_id' => Auth::id(),
                        'original_date' => $data['original_date'],
                        'substitute_teacher_id' => $data['substitute_teacher_id'],
                        'new_date' => $data['new_date'],
                        'status' => 'diajukan',
                        'institution_id' => Auth::user()?->institution_id,
                    ]);

                    Notification::make()
                        ->title('Permohonan Tukar Piket Terkirim!')
                        ->body('Permohonan tukar jadwal piket berhasil diajukan. Rekan guru pengganti atau Waka Kesiswaan dapat menyetujui permohonan ini.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('kelola_tukar_piket')
                ->label('📋 Persetujuan Tukar Piket')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->badge(function () {
                    $user = Auth::user();
                    if (!$user) return null;

                    $query = DutySwapRequest::where('status', 'diajukan');
                    if (!$user->hasAnyRole(['waka_kesiswaan', 'superadmin'])) {
                        $query->where('substitute_teacher_id', $user->id);
                    }
                    $count = $query->count();
                    return $count > 0 ? (string) $count : null;
                })
                ->url(fn () => DutySwapRequestResource::getUrl()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Hari'),
            'senin' => Tab::make('Senin')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Senin')),
            'selasa' => Tab::make('Selasa')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Selasa')),
            'rabu' => Tab::make('Rabu')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Rabu')),
            'kamis' => Tab::make('Kamis')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Kamis')),
            'jumat' => Tab::make('Jumat')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Jumat')),
            'sabtu' => Tab::make('Sabtu')->modifyQueryUsing(fn (Builder $q) => $q->where('hari', 'Sabtu')),
        ];
    }
}
