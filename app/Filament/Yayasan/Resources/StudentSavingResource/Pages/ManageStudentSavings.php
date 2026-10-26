<?php

namespace App\Filament\Yayasan\Resources\StudentSavingResource\Pages;

use App\Filament\Yayasan\Resources\StudentSavingResource;
use App\Models\Student;
use App\Models\StudentSaving;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentSavings extends ManageRecords
{
    protected static string $resource = StudentSavingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Tabungan Siswa')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Form Input Tabungan Siswa')
                ->mutateFormDataUsing(function (array $data): array {
                    if (empty($data['petugas_id'])) {
                        $data['petugas_id'] = auth()->id();
                    }
                    return $data;
                }),

            Actions\Action::make('buku_tabungan')
                ->label('Buku Tabungan Per Siswa')
                ->icon('heroicon-o-book-open')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('student_id')
                        ->label('Pilih Siswa')
                        ->options(function () {
                            return Student::withoutGlobalScopes()
                                ->with(['classroom' => fn ($q) => $q->withoutGlobalScopes(), 'institution'])
                                ->get()
                                ->mapWithKeys(function ($s) {
                                    $className = $s->classroom?->name ?? '-';
                                    $instName = $s->institution?->name ?? '-';
                                    return [$s->id => "{$s->name} ({$s->nis}) - [{$className} / {$instName}]"];
                                });
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data, \Filament\Actions\Action $action) {
                    // Redirect to passbook or open modal
                })
                ->modalHeading('Cari & Cetak Buku Tabungan Siswa')
                ->modalSubmitActionLabel('Lihat Buku Tabungan')
                ->modalContent(function (array $data) {
                    $studentId = $data['student_id'] ?? null;
                    if (!$studentId) return null;
                    $student = Student::withoutGlobalScopes()->find($studentId);
                    $savings = StudentSaving::where('student_id', $studentId)->orderBy('tanggal', 'asc')->orderBy('id', 'asc')->get();
                    return view('filament.yayasan.modals.buku-tabungan', [
                        'student' => $student,
                        'savings' => $savings,
                    ]);
                }),
        ];
    }
}
