<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use App\Models\CapaianPembelajaran;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRppm extends EditRecord
{
    protected static string $resource = RppmResource::class;

    public function getTitle(): string
    {
        return 'Ubah Perangkat Ajar';
    }

    public bool $isDraft = false;

    protected function authorizeAccess(): void
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            abort(403, 'Kepala Sekolah hanya memiliki akses untuk melihat (view), memberikan catatan, dan menyetujui (ACC) RPPM.');
        }
        parent::authorizeAccess();
    }

    public function saveDraft(): void
    {
        $this->isDraft = true;
        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $data = $this->data ?? [];

            $data = $this->mutateFormDataBeforeSave($data);

            $this->handleRecordUpdate($this->getRecord(), $data);

            $this->commitDatabaseTransaction();

            $this->getSavedNotification()?->send();

            $this->redirect($this->getRedirectUrl());
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();
            throw $exception;
        }
    }

    public function publish(): void
    {
        $this->isDraft = false;
        $data = $this->data ?? [];

        // 1. Validasi Mata Pelajaran
        if (empty($data['mata_pelajaran'])) {
            \Filament\Notifications\Notification::make()
                ->title('Mata Pelajaran Wajib Dipilih')
                ->body('Untuk mempublikasikan perangkat ajar, silakan pilih Mata Pelajaran yang Diampu.')
                ->danger()
                ->send();
            return;
        }

        // 2. Validasi Pilihan Fase
        if (empty($data['fases'])) {
            \Filament\Notifications\Notification::make()
                ->title('Pilihan Fase Wajib Dipilih')
                ->body('Untuk mempublikasikan perangkat ajar, silakan pilih minimal 1 Fase Kurikulum.')
                ->danger()
                ->send();
            return;
        }

        // 3. Validasi Topik / Judul
        if (empty(trim((string) ($data['topik'] ?? '')))) {
            \Filament\Notifications\Notification::make()
                ->title('Topik / Judul Modul Ajar Wajib Diisi')
                ->body('Untuk mempublikasikan perangkat ajar, silakan isi Topik atau Judul Modul Ajar.')
                ->danger()
                ->send();
            return;
        }

        // 4. Validasi Capaian Pembelajaran (CP)
        $hasCp = !empty($data['capaian_pembelajaran_id'])
            || (!empty($data['new_cp_deskripsi']) && !empty($data['new_cp_elemen']));

        if (!$hasCp) {
            \Filament\Notifications\Notification::make()
                ->title('Capaian Pembelajaran (CP) Wajib Diisi')
                ->body('Untuk mempublikasikan perangkat ajar, silakan pilih CP dari Bank CP atau simpan CP baru terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        // 5. Validasi Tujuan Pembelajaran (TP)
        $hasTp = !empty($data['selected_tp_ids'])
            || !empty($data['single_new_tp_deskripsi'])
            || (!empty($data['new_tps']) && count(array_filter($data['new_tps'], fn($t) => !empty($t['deskripsi_tp']))) > 0);

        if (!$hasTp) {
            \Filament\Notifications\Notification::make()
                ->title('Tujuan Pembelajaran (TP) Wajib Dipilih / Diisi')
                ->body('Untuk mempublikasikan perangkat ajar, minimal harus ada 1 Tujuan Pembelajaran (TP) yang dipilih atau diinput.')
                ->danger()
                ->send();
            return;
        }

        // 6. Validasi Tautan Google Drive
        $linkDrive = trim((string) ($data['link_drive'] ?? ''));
        if (empty($linkDrive)) {
            \Filament\Notifications\Notification::make()
                ->title('Tautan Google Drive Wajib Diisi')
                ->body('Untuk mempublikasikan perangkat ajar, tautan Google Drive dokumen perangkat ajar wajib disertakan.')
                ->danger()
                ->send();
            return;
        }

        if (!filter_var($linkDrive, FILTER_VALIDATE_URL)) {
            \Filament\Notifications\Notification::make()
                ->title('Tautan Google Drive Tidak Valid')
                ->body('Format tautan Google Drive tidak valid. Pastikan format URL benar (diawali dengan https://).')
                ->danger()
                ->send();
            return;
        }

        $this->save();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_generator')
                ->label('🚀 Bantuan Generator Pembuatan RPPM')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url('https://gemini.google.com/share/d/1e2rmqWovfw8uUldDF8Ql-uBCv6_Z1_Nv?usp=sharing', shouldOpenInNewTab: true),
            Actions\Action::make('cetak_pdf')
                ->label('🖨️ Cetak PDF Modul Ajar')
                ->color('danger')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('academic.rppm.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('saveDraftHeader')
                ->label('💾 Simpan Sementara (Draft)')
                ->color('warning')
                ->icon('heroicon-o-bookmark')
                ->extraAttributes(['formnovalidate' => 'formnovalidate'])
                ->action('saveDraft'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus Perangkat Ajar')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->modalHeading('Hapus Dokumen Perangkat Ajar')
                ->modalDescription('Apakah Anda yakin ingin menghapus dokumen Perangkat Ajar ini? Data yang telah dihapus tidak dapat dikembalikan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successNotificationTitle('Dokumen Perangkat Ajar Berhasil Dihapus')
                ->visible(fn (): bool => !auth()->user()?->hasRole('kepala_sekolah') && (auth()->id() === $this->record->guru_id || auth()->user()?->hasAnyRole(['superadmin', 'waka_kurikulum'])))
                ->successRedirectUrl(RppmResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('saveDraft')
                ->label('💾 Simpan Sementara (Draft)')
                ->color('warning')
                ->icon('heroicon-o-bookmark')
                ->extraAttributes(['formnovalidate' => 'formnovalidate'])
                ->action('saveDraft'),
            Actions\Action::make('publish')
                ->label('🚀 Simpan & Publikasikan (Selesai)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('publish'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        if ($this->isDraft) {
            return \Filament\Notifications\Notification::make()
                ->title('Draft RPPM Berhasil Disimpan Sementara')
                ->body('Perubahan berhasil disimpan sebagai draft. Anda dapat melanjutkannya kembali kapan saja.')
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['capaian_pembelajaran_id'])) {
            $data['content']['capaian_pembelajaran_id'] = $data['capaian_pembelajaran_id'];
        }
        if (!empty($data['selected_tp_ids'])) {
            $data['content']['selected_tp_dropdown'] = $data['selected_tp_ids'];
        }
        $data['input_perangkat_mode'] = 'pilih_ada';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        $fases = !empty($data['fases']) ? (array) $data['fases'] : ['D'];
        $faseStr = collect($fases)->map(fn ($f) => "Fase {$f}")->implode(', ');

        // 2. Simpan CP baru jika diinputkan langsung di form
        if (($data['cp_mode'] ?? null) === 'baru' && !empty($data['new_cp_deskripsi'])) {
            $cp = CapaianPembelajaran::create([
                'institution_id' => auth()->user()?->institution_id,
                'guru_id' => auth()->id(),
                'subject_id' => $subjectId,
                'kode_cp' => !empty($data['new_cp_kode']) ? (string) $data['new_cp_kode'] : null,
                'fase' => $faseStr ?: 'Fase D',
                'fases' => $fases,
                'tingkat' => $tingkat,
                'tahun_ajaran' => '2026/2027',
                'semester' => '1',
                'masa_berlaku' => $data['new_cp_masa_berlaku'] ?? '1 Tahun',
                'elemen' => $data['new_cp_elemen'] ?? 'Materi Inti',
                'deskripsi_cp' => $data['new_cp_deskripsi'],
                'status' => $this->isDraft ? 'draft' : 'published',
            ]);

            $data['capaian_pembelajaran_id'] = $cp->id;
            $data['content']['capaian_pembelajaran_id'] = $cp->id;
            $data['content']['capaian_pembelajaran'] = $cp->deskripsi_cp;
        } elseif (!empty($data['capaian_pembelajaran_id'])) {
            $cp = CapaianPembelajaran::find($data['capaian_pembelajaran_id']);
            if ($cp) {
                $data['content']['capaian_pembelajaran_id'] = $cp->id;
                $data['content']['capaian_pembelajaran'] = $cp->deskripsi_cp;
            }
        }

        // 3. Simpan TP baru jika diinputkan langsung di form (baik melalui single input maupun repeater)
        $selectedTpIds = $data['selected_tp_ids'] ?? [];
        $targetCpId = $data['capaian_pembelajaran_id'] ?? CapaianPembelajaran::first()?->id;

        if (!empty($data['enable_new_tps']) && !empty($data['single_new_tp_deskripsi'])) {
            $rekomendasi = !empty($data['single_new_tp_kelas']) ? (array) $data['single_new_tp_kelas'] : [$tingkat];
            $autoKode = !empty($data['single_new_tp_kode']) ? (string) $data['single_new_tp_kode'] : null;

            $tp = TujuanPembelajaran::create([
                'institution_id' => auth()->user()?->institution_id,
                'guru_id' => auth()->id(),
                'capaian_pembelajaran_id' => $targetCpId,
                'subject_id' => $subjectId,
                'kode_tp' => $autoKode,
                'fases' => $fases,
                'rekomendasi_kelas' => $rekomendasi,
                'materi' => $data['single_new_tp_materi'] ?? null,
                'deskripsi_tp' => $data['single_new_tp_deskripsi'],
                'kelas_tingkat' => (string) ($rekomendasi[0] ?? $tingkat),
                'semester' => 'Ganjil',
                'status' => $this->isDraft ? 'draft' : 'published',
            ]);
            $selectedTpIds[] = $tp->id;
        }

        if (!empty($data['enable_new_tps']) && !empty($data['new_tps']) && is_array($data['new_tps'])) {
            foreach ($data['new_tps'] as $tpIndex => $tpItem) {
                if (!empty($tpItem['deskripsi_tp'])) {
                    $rekomendasi = !empty($tpItem['kelas_rekomendasi']) ? (array) $tpItem['kelas_rekomendasi'] : [$tingkat];
                    $autoKode = !empty($tpItem['kode_tp']) ? (string) $tpItem['kode_tp'] : null;

                    $tp = TujuanPembelajaran::create([
                        'institution_id' => auth()->user()?->institution_id,
                        'guru_id' => auth()->id(),
                        'capaian_pembelajaran_id' => $targetCpId,
                        'subject_id' => $subjectId,
                        'kode_tp' => $autoKode,
                        'fases' => $fases,
                        'rekomendasi_kelas' => $rekomendasi,
                        'materi' => $tpItem['materi'] ?? null,
                        'deskripsi_tp' => $tpItem['deskripsi_tp'],
                        'kelas_tingkat' => (string) ($rekomendasi[0] ?? $tingkat),
                        'semester' => 'Ganjil',
                        'status' => $this->isDraft ? 'draft' : 'published',
                    ]);
                    $selectedTpIds[] = $tp->id;
                }
            }
        }
        $data['selected_tp_ids'] = array_values(array_unique($selectedTpIds));

        // 4. Pastikan content.tujuan_pembelajaran terisi
        if (!empty($data['selected_tp_ids'])) {
            $tps = TujuanPembelajaran::whereIn('id', $data['selected_tp_ids'])->get()->sort(function ($a, $b) {
                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
            });
            $tpItems = [];
            foreach ($tps as $tp) {
                $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                $tpItems[] = ['rumusan' => "[{$tp->kode_tp}] {$prefix}{$tp->deskripsi_tp}"];
            }
            $data['content']['tujuan_pembelajaran'] = $tpItems;
            $data['content']['selected_tp_dropdown'] = $data['selected_tp_ids'];
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
            $data['cp_mode'],
            $data['new_cp_elemen'],
            $data['new_cp_kode'],
            $data['new_cp_masa_berlaku'],
            $data['new_cp_deskripsi'],
            $data['enable_new_tps'],
            $data['single_new_tp_kode'],
            $data['single_new_tp_materi'],
            $data['single_new_tp_kelas'],
            $data['single_new_tp_deskripsi'],
            $data['new_tps'],
            $data['input_perangkat_mode']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if ($record && $record->status === 'published') {
            if (!empty($record->capaian_pembelajaran_id)) {
                CapaianPembelajaran::where('id', $record->capaian_pembelajaran_id)
                    ->where('status', 'draft')
                    ->update(['status' => 'published']);
            }
            if (!empty($record->selected_tp_ids)) {
                TujuanPembelajaran::whereIn('id', (array) $record->selected_tp_ids)
                    ->where('status', 'draft')
                    ->update(['status' => 'published']);
            }
        }
    }
}