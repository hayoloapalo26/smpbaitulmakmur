<?php

namespace App\Filament\Staff\Resources\StudentCaseResource\Pages;

use App\Filament\Staff\Pages\RekapitulasiSkorPelanggaranPage;
use App\Filament\Staff\Resources\StudentCaseResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentCases extends ManageRecords
{
    protected static string $resource = StudentCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Laporkan Kasus Baru')
                ->visible(fn () => StudentCaseResource::canCreate())
                ->mutateFormDataUsing(function (array $data): array {
                    $user = auth()->user();
                    if ($user && $user->hasRole('waka_kesiswaan') && !$user->hasRole('superadmin')) {
                        $data['recommended_to_kesiswaan'] = true;
                        $data['ditangani_oleh'] = 'kesiswaan';
                    }
                    if (!empty($data['student_id'])) {
                        $st = Student::with('classroom')->find($data['student_id']);
                        if (empty($data['wali_kelas_id']) && $st?->classroom?->wali_kelas_id) {
                            $data['wali_kelas_id'] = $st->classroom->wali_kelas_id;
                        }
                        if (empty($data['institution_id'])) {
                            $data['institution_id'] = $st?->institution_id ?? $user?->institution_id;
                        }
                    }
                    if (empty($data['wali_kelas_id'])) {
                        $data['wali_kelas_id'] = $user?->id ?? 1;
                    }
                    if (empty($data['poin_pelanggaran'])) {
                        $data['poin_pelanggaran'] = 5;
                    }
                    return $data;
                }),

            Actions\Action::make('antrean_terlambat')
                ->label(function () {
                    $pendingCount = \App\Models\DailyAttendance::withoutGlobalScopes()
                        ->where('tipe_absen', 'masuk')
                        ->where('status', 'hadir')
                        ->where(function ($sub) {
                            $sub->where('is_late', true)
                                ->orWhere('waktu_kedatangan', '>', '06:45:00');
                        })
                        ->where(function ($q) {
                            $q->where('late_approval_status', 'pending')
                              ->orWhereNull('late_approval_status');
                        })
                        ->whereDate('tanggal', \Carbon\Carbon::today())
                        ->count();
                    return $pendingCount > 0
                        ? "⏰ ACC Keterlambatan ({$pendingCount} Menunggu)"
                        : "⏰ ACC Keterlambatan";
                })
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => \Illuminate\Support\Facades\Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin']))
                ->url(fn () => \App\Filament\Staff\Resources\PresensiIzinSiswaResource::getUrl() . '?activeTab=terlambat'),

            Actions\Action::make('rekapitulasi_skor')
                ->label('Rekapitulasi Skor Poin')
                ->icon('heroicon-o-shield-exclamation')
                ->color('success')
                ->url(fn () => RekapitulasiSkorPelanggaranPage::getUrl()),
        ];
    }
}
