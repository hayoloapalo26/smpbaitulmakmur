<?php

namespace App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanSupervisiResource;
use App\Models\Institution;
use App\Models\TeacherSupervision;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPermintaanSupervisis extends ListRecords
{
    protected static string $resource = PermintaanSupervisiResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua Lembaga')
                ->badge(fn () => TeacherSupervision::withoutGlobalScopes()->count()),
        ];

        $institutions = Institution::where('id', '!=', 1)->orderBy('id')->get();
        foreach ($institutions as $inst) {
            $tabs['inst_' . $inst->id] = Tab::make($inst->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('institution_id', $inst->id))
                ->badge(fn () => TeacherSupervision::withoutGlobalScopes()->where('institution_id', $inst->id)->count());
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input / Penilaian Supervisi Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
            Actions\Action::make('jadwalkan_supervisi_penjamin_mutu')
                ->label('Jadwalkan Supervisi Guru')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')

                ->modalHeading('Jalur 1: Penjamin Mutu Menjadwalkan Supervisi Guru')
                ->modalDescription('Pilih lembaga terlebih dahulu agar daftar guru yang muncul sesuai dengan lembaga yang diklik. Jadwal ini akan langsung diinformasikan kepada Kepala Sekolah dan Guru yang bersangkutan.')
                ->fillForm(function (): array {
                    $defaultInstId = null;
                    if (is_string($this->activeTab) && str_starts_with($this->activeTab, 'inst_')) {
                        $defaultInstId = (int) str_replace('inst_', '', $this->activeTab);
                    }
                    return [
                        'institution_id' => $defaultInstId,
                    ];
                })
                ->form([
                    Forms\Components\Select::make('institution_id')
                        ->label('Pilih Lembaga')
                        ->options(fn () => Institution::where('id', '!=', 1)->orderBy('id')->pluck('name', 'id'))
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('teacher_id', null))
                        ->required()
                        ->placeholder('-- Klik / Pilih Lembaga --'),

                    Forms\Components\Select::make('teacher_id')
                        ->label('Pilih Guru (Sesuai Lembaga yang Diklik)')
                        ->options(function (Forms\Get $get) {
                            $instId = $get('institution_id');
                            if (!$instId) {
                                return [];
                            }

                            return User::withoutGlobalScopes()
                                ->where('institution_id', $instId)
                                ->whereHas('roles', fn ($q) => $q->whereIn('name', array_merge([
                                    'guru_kelas', 'guru_mapel', 'wali_kelas',
                                    'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
                                    'sarpras', 'humas',
                                ], \App\Models\User::QURAN_TEACHER_ROLES)))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Forms\Get $get) => empty($get('institution_id')))
                        ->placeholder(fn (Forms\Get $get) => $get('institution_id')
                            ? '-- Pilih Guru dari Lembaga Ini --'
                            : 'Pilih Lembaga di atas terlebih dahulu')
                        ->helperText(function (Forms\Get $get) {
                            $instId = $get('institution_id');
                            if (!$instId) {
                                return 'Daftar guru akan otomatis difilter sesuai lembaga yang Anda klik.';
                            }
                            $instName = Institution::where('id', $instId)->value('name');
                            return "Menampilkan daftar guru khusus lembaga {$instName}.";
                        }),

                    Forms\Components\DateTimePicker::make('jadwal')
                        ->label('Jadwal Pelaksanaan Supervisi')
                        ->default(now()->addDays(1)->setHour(8)->setMinute(0))
                        ->required(),

                    Forms\Components\Textarea::make('catatan_yayasan')
                        ->label('Agenda / Catatan Supervisi (Diinformasikan ke KS & Guru)')
                        ->placeholder('Contoh: Supervisi perangkat ajar & observasi pembelajaran di kelas...')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    TeacherSupervision::create([
                        'institution_id' => $data['institution_id'],
                        'teacher_id' => $data['teacher_id'],
                        'supervisor_id' => auth()->id(),
                        'jalur_supervisi' => 'penjamin_mutu',
                        'tanggal' => now()->toDateString(),
                        'jadwal' => $data['jadwal'],
                        'status' => 'dijadwalkan',
                        'catatan' => 'Dijadwalkan oleh Tim Penjamin Mutu Yayasan (Tanpa Permintaan)',
                        'catatan_yayasan' => $data['catatan_yayasan'],
                        'hasil' => '-',
                    ]);

                    Notification::make()
                        ->title('Supervisi Berhasil Dijadwalkan')
                        ->body('Jadwal supervisi telah disimpan dan otomatis diinformasikan ke Kepala Sekolah serta Guru yang bersangkutan.')
                        ->success()
                        ->send();
                }),
        ];
    }
}