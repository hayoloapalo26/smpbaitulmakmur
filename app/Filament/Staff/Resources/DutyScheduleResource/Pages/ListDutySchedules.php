<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Filament\Staff\Resources\DutySwapRequestResource;
use App\Filament\Staff\Widgets\DutyScheduleFormationWidget;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\DutyScheduleGeneratorService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
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
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $defaultSemester = ($currentMonth >= 7 && $currentMonth <= 12) ? 'Ganjil' : 'Genap';

        $defaultStart = ($defaultSemester === 'Ganjil') ? "{$currentYear}-07-01" : "{$currentYear}-01-01";
        $defaultEnd = ($defaultSemester === 'Ganjil') ? "{$currentYear}-12-31" : "{$currentYear}-06-30";

        return [
            Actions\Action::make('generate_semester_schedule')
                ->label('Acak & Atur Jadwal Piket (1 Semester)')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('primary')
                ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']))
                ->modalHeading('🎲 Acak & Generate Jadwal Piket 1 Semester')
                ->modalDescription('Atur jadwal guru piket selama 1 semester. Sistem akan mengacak penugasan guru secara merata. Pada semester berikutnya, jalankan kembali fitur ini untuk mengacak ulang jadwal agar formasi guru berganti.')
                ->modalSubmitActionLabel('Mulai Acak & Buat Jadwal')
                ->modalWidth('3xl')
                ->form([
                    Forms\Components\Section::make('Periode Semester & Tahun Ajaran')
                        ->schema([
                            Forms\Components\Select::make('academic_year_id')
                                ->label('Tahun Ajaran')
                                ->options(AcademicYear::pluck('name', 'id'))
                                ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('semester')
                                ->label('Pilihan Semester')
                                ->options([
                                    'Ganjil' => 'Semester Ganjil (Juli - Desember)',
                                    'Genap' => 'Semester Genap (Januari - Juni)',
                                ])
                                ->default($defaultSemester)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) use ($currentYear) {
                                    if ($state === 'Ganjil') {
                                        $set('start_date', "{$currentYear}-07-01");
                                        $set('end_date', "{$currentYear}-12-31");
                                    } else {
                                        $targetYear = ($currentYear);
                                        $set('start_date', "{$targetYear}-01-01");
                                        $set('end_date', "{$targetYear}-06-30");
                                    }
                                }),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Tanggal Mulai Semester')
                                ->default($defaultStart)
                                ->required()
                                ->native(false)
                                ->displayFormat('d F Y'),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Tanggal Selesai Semester')
                                ->default($defaultEnd)
                                ->required()
                                ->native(false)
                                ->displayFormat('d F Y'),
                        ])->columns(2),

                    Forms\Components\Section::make('Konfigurasi Pengacakan & Kuota')
                        ->schema([
                            Forms\Components\Select::make('mode')
                                ->label('Metode Pengacakan Jadwal')
                                ->options([
                                    'weekly_fixed' => 'Formasi Rutin Mingguan Selama 1 Semester (Rekomendasi - Guru piket di hari tetap selama 1 semester, diacak ulang tiap semester baru)',
                                    'round_robin' => 'Rotasi Bergilir Sepanjang Semester (Siklus bergantian secara merata)',
                                ])
                                ->default('weekly_fixed')
                                ->helperText('Untuk sistem sekolah, formasi mingguan tetap selama 1 semester sangat disarankan agar guru memiliki hari rutin dalam satu semester.')
                                ->columnSpanFull()
                                ->required(),

                            Forms\Components\TextInput::make('teachers_per_day')
                                ->label('Jumlah Guru Piket Per Hari')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10)
                                ->default(2)
                                ->required(),

                            Forms\Components\CheckboxList::make('days_of_week')
                                ->label('Hari Sekolah yang Memiliki Jadwal Piket')
                                ->options([
                                    'Senin' => 'Senin',
                                    'Selasa' => 'Selasa',
                                    'Rabu' => 'Rabu',
                                    'Kamis' => 'Kamis',
                                    'Jumat' => 'Jumat',
                                    'Sabtu' => 'Sabtu (Opsional)',
                                ])
                                ->default(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'])
                                ->columns(3)
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\Toggle::make('avoid_previous_semester_day')
                                ->label('Acak Beda Hari dari Semester Lalu')
                                ->default(true)
                                ->helperText('Memastikan guru mendapatkan hari piket yang berbeda dari semester sebelumnya agar adil dan bervariasi.')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Daftar Guru yang Diikutkan Piket')
                        ->schema([
                            Forms\Components\CheckboxList::make('teacher_ids')
                                ->label('Pilih Guru yang Bertugas Piket')
                                ->options(function () {
                                    $instId = Auth::user()?->institution_id;
                                    return DutyScheduleGeneratorService::getEligibleTeachers($instId)->pluck('name', 'id');
                                })
                                ->default(function () {
                                    $instId = Auth::user()?->institution_id;
                                    return DutyScheduleGeneratorService::getEligibleTeachers($instId)->pluck('id')->toArray();
                                })
                                ->columns(2)
                                ->searchable()
                                ->required()
                                ->helperText('Centang guru-guru yang akan masuk ke dalam rotasi pengacakan jadwal piket.'),
                        ]),

                    Forms\Components\Toggle::make('overwrite')
                        ->label('Hapus & Timpa Jadwal Lama di Rentang Tanggal Ini')
                        ->default(true)
                        ->helperText('Jika dicentang, jadwal piket lama pada rentang tanggal semester ini akan digantikan dengan hasil acakan baru.'),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new DutyScheduleGeneratorService();
                        $data['institution_id'] = Auth::user()?->institution_id;
                        $result = $service->generateSemesterSchedule($data);

                        $summaryLines = [];
                        if (!empty($result['formation'])) {
                            foreach ($result['formation'] as $day => $teachers) {
                                $summaryLines[] = "• {$day}: " . implode(', ', $teachers);
                            }
                        }

                        $formationText = !empty($summaryLines)
                            ? "\n\nFormasi Acakan Mingguan:\n" . implode("\n", $summaryLines)
                            : '';

                        Notification::make()
                            ->title('Jadwal Piket 1 Semester Berhasil Diacak & Dibuat!')
                            ->body("Berhasil membuat {$result['count']} penugasan tanggal piket untuk Semester {$result['semester']} (Periode {$result['start_date']} s/d {$result['end_date']}).{$formationText}")
                            ->success()
                            ->duration(12000)
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengacak Jadwal Piket')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Actions\Action::make('tukar_jadwal')
                ->label('Pengajuan Tukar Jadwal Piket')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->url(fn () => DutySwapRequestResource::getUrl()),

            Actions\CreateAction::make()
                ->label('Tambah Jadwal Manual')
                ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
        ];
    }
}
