<?php

namespace App\Filament\Staff\Resources\ExtracurricularGradeResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularGradeResource;
use App\Models\AcademicYear;
use App\Models\Extracurricular;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularMember;
use App\Models\Student;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExtracurricularGrades extends ListRecords
{
    protected static string $resource = ExtracurricularGradeResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isGuruEskulOrAdmin = $user && ($user->hasRole('guru_ekstrakurikuler') || $user->hasRole('superadmin'));
        $activeYear = AcademicYear::where('is_active', true)->first();

        return [
            Actions\Action::make('batch_input_nilai')
                ->label('⚡ Input Nilai Kolektif')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->visible($isGuruEskulOrAdmin)
                ->modalHeading('Input Nilai Akhir Semester Ekstrakurikuler Kolektif')
                ->modalDescription('Pilih ekstrakurikuler dan tahun ajaran, lalu masukkan nilai seluruh siswa anggota sekaligus.')
                ->modalWidth('4xl')
                ->form([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('extracurricular_id')
                            ->label('Ekstrakurikuler')
                            ->options(function () use ($user) {
                                $query = Extracurricular::where('is_active', true);
                                if ($user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah'])) {
                                    $query->where('pembina_id', $user->id);
                                }
                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state) {
                                if ($state) {
                                    $members = ExtracurricularMember::where('extracurricular_id', $state)
                                        ->where('is_active', true)
                                        ->with('student.classroom')
                                        ->get();

                                    $acadYearId = $get('academic_year_id');
                                    $sem = $get('semester') ?? 'Ganjil';

                                    $items = [];
                                    foreach ($members as $m) {
                                        $existingGrade = ExtracurricularGrade::where([
                                            'extracurricular_id' => $state,
                                            'student_id'         => $m->student_id,
                                            'academic_year_id'   => $acadYearId,
                                            'semester'           => $sem,
                                        ])->first();

                                        $items[] = [
                                            'student_id'   => $m->student_id,
                                            'student_name' => $m->student?->name . ($m->student?->classroom ? " [{$m->student->classroom->name}]" : ''),
                                            'nilai_angka'  => $existingGrade?->nilai_angka ?? 85,
                                            'predikat'     => $existingGrade?->predikat ?? 'A',
                                            'deskripsi'    => $existingGrade?->deskripsi ?? 'Sangat aktif dan menguasai materi ekstrakurikuler dengan baik.',
                                        ];
                                    }
                                    $set('grade_items', $items);
                                }
                            }),

                        Forms\Components\Select::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->options(fn () => AcademicYear::orderBy('id', 'desc')->pluck('name', 'id'))
                            ->default($activeYear?->id)
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Ganjil',
                                'Genap'  => 'Genap',
                            ])
                            ->default($activeYear?->semester ?? 'Ganjil')
                            ->required()
                            ->live(),
                    ]),

                    Forms\Components\Repeater::make('grade_items')
                        ->label('Daftar Penilaian Santri / Siswa Anggota')
                        ->schema([
                            Forms\Components\Hidden::make('student_id'),
                            Forms\Components\TextInput::make('student_name')
                                ->label('Nama Santri / Siswa')
                                ->disabled()
                                ->columnSpan(4),

                            Forms\Components\TextInput::make('nilai_angka')
                                ->label('Nilai Angka')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                    if ($state !== null && is_numeric($state)) {
                                        $score = (float) $state;
                                        if ($score >= 85) {
                                            $set('predikat', 'A');
                                        } elseif ($score >= 75) {
                                            $set('predikat', 'B');
                                        } elseif ($score >= 60) {
                                            $set('predikat', 'C');
                                        } else {
                                            $set('predikat', 'D');
                                        }
                                    }
                                })
                                ->columnSpan(2),

                            Forms\Components\Select::make('predikat')
                                ->label('Predikat')
                                ->options([
                                    'A' => 'A (Sangat Baik)',
                                    'B' => 'B (Baik)',
                                    'C' => 'C (Cukup)',
                                    'D' => 'D (Kurang)',
                                ])
                                ->required()
                                ->native(false)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('deskripsi')
                                ->label('Capaian / Catatan Akhir Semester')
                                ->placeholder('Deskripsi kompetensi santri...')
                                ->columnSpan(4),
                        ])
                        ->columns(12)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data) {
                    $items = $data['grade_items'] ?? [];
                    if (empty($items)) {
                        Notification::make()->title('Tidak ada siswa yang dinilai')->warning()->send();
                        return;
                    }

                    $count = 0;
                    foreach ($items as $item) {
                        ExtracurricularGrade::updateOrCreate(
                            [
                                'extracurricular_id' => $data['extracurricular_id'],
                                'student_id'         => $item['student_id'],
                                'academic_year_id'   => $data['academic_year_id'],
                                'semester'           => $data['semester'],
                            ],
                            [
                                'pembina_id'         => auth()->id(),
                                'nilai_angka'        => $item['nilai_angka'] ?? null,
                                'predikat'           => $item['predikat'] ?? 'A',
                                'deskripsi'          => $item['deskripsi'] ?? null,
                                'institution_id'     => auth()->user()?->institution_id,
                            ]
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title('Nilai Akhir Semester Berhasil Disimpan')
                        ->body("Sebanyak {$count} nilai siswa berhasil diperbarui.")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('➕ Tambah Nilai Siswa')
                ->visible($isGuruEskulOrAdmin),
        ];
    }
}
