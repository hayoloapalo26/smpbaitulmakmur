<?php

namespace App\Filament\Staff\Resources\ExtracurricularAttendanceResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularAttendanceResource;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Student;
use App\Models\Classroom;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageExtracurricularAttendances extends ManageRecords
{
    protected static string $resource = ExtracurricularAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('batch_input')
                ->label('⚡ Input Presensi Rombongan')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->modalHeading('Input Presensi Kehadiran Ekstrakurikuler Rombongan')
                ->modalDescription('Catat presensi kehadiran santri/siswa satu rombel/kelas sekaligus.')
                ->modalWidth('3xl')
                ->modalSubmitActionLabel('Simpan Seluruh Presensi')
                ->form([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('extracurricular_id')
                            ->label('Ekstrakurikuler')
                            ->options(function () {
                                $user = auth()->user();
                                $query = Extracurricular::where('is_active', true);
                                if ($user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
                                    $query->where('pembina_id', $user->id);
                                }
                                $opts = $query->orderBy('name')->pluck('name', 'id');
                                if ($opts->isEmpty()) {
                                    return Extracurricular::where('is_active', true)->orderBy('name')->pluck('name', 'id');
                                }
                                return $opts;
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Kegiatan')
                            ->default(now()->toDateString())
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y'),
                    ]),

                    Forms\Components\Select::make('classroom_id')
                        ->label('Saring Berdasarkan Kelas / Rombel (Opsional)')
                        ->options(fn () => Classroom::reguler()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('student_ids', [])),

                    Forms\Components\CheckboxList::make('student_ids')
                        ->label('Pilih Santri / Siswa Peserta')
                        ->options(function (Forms\Get $get) {
                            $query = Student::query();
                            $clsId = $get('classroom_id');
                            if ($clsId) {
                                $query->where('classroom_id', $clsId);
                            }
                            return $query->orderBy('name')->get()->mapWithKeys(function ($s) {
                                $kelas = $s->classroom ? " [{$s->classroom->name}]" : '';
                                return [$s->id => "{$s->name}{$kelas}"];
                            });
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2)
                        ->required(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Kehadiran untuk Siswa Terpilih')
                            ->options([
                                'hadir' => '✅ Hadir',
                                'izin'  => '📩 Izin',
                                'sakit' => '🩺 Sakit',
                                'alpa'  => '❌ Alpa',
                            ])
                            ->default('hadir')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('catatan')
                            ->label('Materi / Catatan Latihan (Opsional)')
                            ->placeholder('Contoh: Pertemuan rutin, evaluasi gerakan, dll.'),
                    ]),
                ])
                ->action(function (array $data) {
                    $studentIds = $data['student_ids'] ?? [];
                    if (empty($studentIds)) {
                        Notification::make()->title('Pilih minimal satu siswa')->warning()->send();
                        return;
                    }

                    $count = 0;
                    foreach ($studentIds as $sid) {
                        ExtracurricularAttendance::updateOrCreate(
                            [
                                'extracurricular_id' => $data['extracurricular_id'],
                                'student_id'         => $sid,
                                'tanggal'            => $data['tanggal'],
                            ],
                            [
                                'status'             => $data['status'],
                                'catatan'            => $data['catatan'] ?? null,
                                'pembina_id'         => auth()->id(),
                                'institution_id'     => auth()->user()?->institution_id,
                            ]
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title("Presensi Berhasil Disimpan")
                        ->body("Sebanyak {$count} siswa berhasil dicatat presensinya.")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('cetak_rekap')
                ->label('🖨️ Cetak Rekap Presensi (PDF)')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->modalHeading('Cetak Rekapitulasi Presensi Ekstrakurikuler')
                ->modalDescription('Pilih ekstrakurikuler dan periode bulan/tahun yang ingin dicetak laporannya.')
                ->modalWidth('md')
                ->modalSubmitActionLabel('Unduh PDF')
                ->form([
                    Forms\Components\Select::make('extracurricular_id')
                        ->label('Ekstrakurikuler')
                        ->options(function () {
                            $user = auth()->user();
                            $query = Extracurricular::where('is_active', true);
                            if ($user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
                                $query->where('pembina_id', $user->id);
                            }
                            return $query->orderBy('name')->pluck('name', 'id');
                        })
                        ->placeholder('Semua Ekstrakurikuler')
                        ->searchable(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                                '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                                '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                            ])
                            ->default((string) now()->month),

                        Forms\Components\TextInput::make('year')
                            ->label('Tahun')
                            ->default(now()->year)
                            ->numeric()
                            ->required(),
                    ]),
                ])
                ->action(function (array $data) {
                    $url = route('report.extracurricular-attendance', [
                        'extracurricular_id' => $data['extracurricular_id'] ?? null,
                        'month' => $data['month'] ?? null,
                        'year' => $data['year'] ?? now()->year,
                    ]);

                    return redirect()->away($url);
                }),

            Actions\CreateAction::make()
                ->label('+ Input Presensi Siswa')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
