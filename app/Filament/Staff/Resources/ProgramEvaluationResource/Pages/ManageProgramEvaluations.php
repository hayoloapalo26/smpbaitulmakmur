<?php

namespace App\Filament\Staff\Resources\ProgramEvaluationResource\Pages;

use App\Filament\Staff\Resources\ProgramEvaluationResource;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use App\Models\ProgramEvaluation;
use App\Models\ProgramHumas;
use App\Models\ProgramKepsek;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramEvaluations extends ManageRecords
{
    protected static string $resource = ProgramEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Evaluasi Program')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('syncFromPrograms')
                ->label('Tarik dari Program Kerja')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Data Program Kerja')
                ->modalDescription('Fitur ini akan menarik seluruh program kerja yang ada (Kepsek, Kurikulum, Kesiswaan, Sarpras, Humas, BK, Wali Kelas) yang belum terdaftar di evaluasi ini secara otomatis.')
                ->action(function () {
                    $user = auth()->user();
                    $institutionId = $user->institution_id;
                    $imported = 0;

                    $sources = [
                        ['divisi' => 'Kepala Sekolah', 'items' => ProgramKepsek::all()],
                        ['divisi' => 'Kurikulum', 'items' => ProgramKurikulum::all()],
                        ['divisi' => 'Kesiswaan', 'items' => ProgramKesiswaan::all()],
                        ['divisi' => 'Sarpras', 'items' => ProgramSarpras::all()],
                        ['divisi' => 'Humas', 'items' => ProgramHumas::all()],
                        ['divisi' => 'Bimbingan Konseling', 'items' => BkWorkProgram::all()],
                        ['divisi' => 'Wali Kelas', 'items' => ClassProgram::all()],
                    ];

                    foreach ($sources as $source) {
                        $divisi = $source['divisi'];
                        foreach ($source['items'] as $item) {
                            $name = $item->nama_program ?? $item->judul_program ?? null;
                            if (!$name) continue;

                            $exists = ProgramEvaluation::where('nama_program', $name)
                                ->where('divisi', $divisi)
                                ->when($institutionId, fn($q) => $q->where('institution_id', $institutionId))
                                ->exists();

                            if (!$exists) {
                                $rawStatus = strtolower($item->status_pelaksanaan ?? $item->status ?? 'belum');
                                $pct = 0;
                                $predikat = 'Cukup';
                                if (str_contains($rawStatus, 'selesai') || str_contains($rawStatus, 'terlaksana')) {
                                    $pct = 100;
                                    $predikat = 'Sangat Baik';
                                } elseif (str_contains($rawStatus, 'jalan') || str_contains($rawStatus, 'proses')) {
                                    $pct = 60;
                                    $predikat = 'Baik';
                                }

                                ProgramEvaluation::create([
                                    'institution_id' => $institutionId,
                                    'user_id' => $user->id,
                                    'divisi' => $divisi,
                                    'nama_program' => $name,
                                    'tahun_ajaran' => $item->tahun_ajaran ?? '2026/2027',
                                    'semester' => 'Semester Ganjil',
                                    'persentase_ketercapaian' => $pct,
                                    'predikat' => $predikat,
                                    'realisasi_kegiatan' => 'Status pelaksanaan program: ' . ucfirst($rawStatus),
                                    'kendala' => null,
                                    'solusi_tindak_lanjut' => 'Evaluasi berkala pelaksanaan program kerja.',
                                    'catatan_kepsek' => null,
                                    'status' => 'Final',
                                ]);
                                $imported++;
                            }
                        }
                    }

                    if ($imported > 0) {
                        Notification::make()
                            ->title("Berhasil Menarik $imported Program")
                            ->body("Data program kerja berhasil ditambahkan ke daftar evaluasi.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Semua Program Sudah Terdaftar')
                            ->body('Tidak ada program kerja baru yang perlu disinkronkan.')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }
}
