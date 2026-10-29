<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use App\Models\CapaianPembelajaran;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRppm extends CreateRecord
{
    protected static string $resource = RppmResource::class;

    public bool $isDraft = false;

    public function saveDraft(): void
    {
        $this->isDraft = true;
        $this->create();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveDraftHeader')
                ->label('💾 Simpan Sementara (Draft)')
                ->color('warning')
                ->icon('heroicon-o-bookmark')
                ->action('saveDraft'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('💾 Simpan Sementara (Draft)')
                ->color('warning')
                ->icon('heroicon-o-bookmark')
                ->action('saveDraft'),
            $this->getCreateFormAction()
                ->label('🚀 Simpan & Publikasikan (Selesai)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->isDraft = false;
                    $this->create();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        if ($this->isDraft) {
            return \Filament\Notifications\Notification::make()
                ->title('Draft RPPM Berhasil Disimpan Sementara')
                ->body('Data RPPM telah tersimpan sebagai draft. Anda dapat keluar dan melanjutkannya kembali kapan saja melalui menu RPPM.')
                ->warning();
        }

        return \Filament\Notifications\Notification::make()
            ->title('RPPM Berhasil Dipublikasikan')
            ->body('Dokumen RPPM dan Perangkat Ajar telah selesai dan berhasil dipublikasikan.')
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guru_id'] = auth()->id();
        $data['status'] = $this->isDraft ? 'draft' : 'published';

        $data['kelas_fase'] = !empty($data['kelas_fase']) ? $data['kelas_fase'] : 'Kelas 7 / Fase D';
        $data['mata_pelajaran'] = !empty($data['mata_pelajaran']) ? $data['mata_pelajaran'] : 'Mata Pelajaran (Draft)';
        $data['topik'] = !empty($data['topik']) ? $data['topik'] : 'Draft Topik Pembelajaran';

        // 1. Tentukan subject_id jika memungkinkan
        $subjectId = null;
        if (!empty($data['mata_pelajaran'])) {
            $subj = Subject::where('name', 'like', "%{$data['mata_pelajaran']}%")->first();
            $subjectId = $subj?->id;
        }
        if (!$subjectId) {
            $subjectId = TeacherSubjectClass::where('teacher_id', auth()->id())->value('subject_id')
                ?? Subject::first()?->id;
        }

        $tingkat = '7';
        if (!empty($data['kelas_fase']) && preg_match('/(7|8|9)/', $data['kelas_fase'], $m)) {
            $tingkat = $m[1];
        }

        // 2. Simpan CP baru jika diinputkan langsung
        if (!empty($data['new_cp_deskripsi'])) {
            $cp = CapaianPembelajaran::create([
                'institution_id' => auth()->user()?->institution_id,
                'guru_id' => auth()->id(),
                'subject_id' => $subjectId,
                'fase' => 'Fase D (SMP)',
                'tingkat' => $tingkat,
                'tahun_ajaran' => '2026/2027',
                'semester' => '1',
                'masa_berlaku' => $data['new_cp_masa_berlaku'] ?? '1 Tahun',
                'elemen' => $data['new_cp_elemen'] ?? 'Materi Inti',
                'deskripsi_cp' => $data['new_cp_deskripsi'],
            ]);

            $data['capaian_pembelajaran_id'] = $cp->id;
            $data['content']['capaian_pembelajaran'] = $cp->deskripsi_cp;
        } elseif (!empty($data['capaian_pembelajaran_id'])) {
            $cp = CapaianPembelajaran::find($data['capaian_pembelajaran_id']);
            if ($cp) {
                $data['content']['capaian_pembelajaran'] = $cp->deskripsi_cp;
            }
        }

        // 3. Simpan TP baru jika diinputkan langsung
        $selectedTpIds = $data['selected_tp_ids'] ?? [];
        if (!empty($data['new_tps']) && is_array($data['new_tps'])) {
            $targetCpId = $data['capaian_pembelajaran_id'] ?? CapaianPembelajaran::first()?->id;

            foreach ($data['new_tps'] as $tpItem) {
                if (!empty($tpItem['deskripsi_tp'])) {
                    $tp = TujuanPembelajaran::create([
                        'institution_id' => auth()->user()?->institution_id,
                        'guru_id' => auth()->id(),
                        'capaian_pembelajaran_id' => $targetCpId,
                        'subject_id' => $subjectId,
                        'kode_tp' => $tpItem['kode_tp'] ?? ('TP.' . $tingkat . '.' . rand(1, 9) . '.' . rand(1, 9)),
                        'deskripsi_tp' => $tpItem['deskripsi_tp'],
                        'materi' => $tpItem['materi'] ?? null,
                        'kelas_tingkat' => $tingkat,
                        'semester' => 'Ganjil',
                    ]);
                    $selectedTpIds[] = $tp->id;
                }
            }
            $data['selected_tp_ids'] = array_values(array_unique($selectedTpIds));
        }

        // 4. Pastikan content.tujuan_pembelajaran terisi
        if (!empty($data['selected_tp_ids'])) {
            $tps = TujuanPembelajaran::whereIn('id', $data['selected_tp_ids'])->get();
            $tpItems = [];
            foreach ($tps as $tp) {
                $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                $tpItems[] = ['rumusan' => "{$prefix}{$tp->deskripsi_tp}"];
            }
            $data['content']['tujuan_pembelajaran'] = $tpItems;
        }

        // 5. Otomatis tautkan ATP yang terakumulasi untuk guru dan mapel ini
        if ($subjectId) {
            $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                auth()->id(),
                $subjectId,
                $tingkat
            );
            if ($atp) {
                $data['alur_tujuan_pembelajaran_id'] = $atp->id;
            }
        }

        // Bersihkan variabel form sementara
        unset(
            $data['new_cp_elemen'],
            $data['new_cp_masa_berlaku'],
            $data['new_cp_deskripsi'],
            $data['new_tps'],
            $data['input_perangkat_mode']
        );

        return $data;
    }
}
