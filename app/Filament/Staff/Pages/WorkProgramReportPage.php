<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\ProgramKepsek;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramSarpras;
use App\Models\ProgramHumas;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class WorkProgramReportPage extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Monitoring Pelaksanaan Program';
    protected static ?string $title = 'Monitoring Pelaksanaan Program Sekolah';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.staff.pages.work-program-report-page';

    // State untuk Modal Input / Edit Link Google Drive
    public bool $showDriveModal = false;
    public ?string $modalModelType = null;
    public ?int $modalModelId = null;
    public string $modalProgramName = '';
    public string $modalDriveUrl = '';

    public function openDriveModal(string $modelType, int $modelId, string $programName, ?string $currentUrl = ''): void
    {
        $this->modalModelType = $modelType;
        $this->modalModelId = $modelId;
        $this->modalProgramName = $programName;
        $this->modalDriveUrl = $currentUrl ?? '';
        $this->showDriveModal = true;
    }

    public function closeDriveModal(): void
    {
        $this->showDriveModal = false;
        $this->modalModelType = null;
        $this->modalModelId = null;
        $this->modalProgramName = '';
        $this->modalDriveUrl = '';
    }

    public function saveDriveModal(): void
    {
        if (!$this->modalModelType || !$this->modalModelId) {
            return;
        }

        $url = trim($this->modalDriveUrl);

        try {
            $class = $this->modalModelType;
            if (class_exists($class)) {
                $record = $class::find($this->modalModelId);
                if ($record) {
                    $table = $record->getTable();
                    $updates = [];
                    if (Schema::hasColumn($table, 'link_drive_dokumen')) {
                        $updates['link_drive_dokumen'] = $url ?: null;
                    }
                    if (Schema::hasColumn($table, 'dokumen_terkait')) {
                        $updates['dokumen_terkait'] = $url ?: null;
                    }
                    if (Schema::hasColumn($table, 'dokumen_pendukung')) {
                        $updates['dokumen_pendukung'] = $url ?: null;
                    }

                    if (!empty($updates)) {
                        $record->update($updates);
                    }

                    Notification::make()
                        ->title('Link Drive Berhasil Disimpan')
                        ->body("Tautan Google Drive untuk '{$this->modalProgramName}' telah berhasil diperbarui.")
                        ->success()
                        ->send();
                }
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menyimpan Link')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->closeDriveModal();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('buka_evaluasi')
                ->label('Buka Evaluasi Program')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->url(\App\Filament\Staff\Resources\ProgramEvaluationResource::getUrl('index')),
        ];
    }

    public function getViewData(): array
    {
        // 1. Kumpulkan semua data dari berbagai program
        $user = auth()->user();
        $kepsek = ProgramKepsek::all();
        $kurikulum = ProgramKurikulum::all();
        $kesiswaan = ProgramKesiswaan::all();
        $sarpras = ProgramSarpras::all();
        
        $humasQuery = ProgramHumas::query();
        if ($user && !$user->hasRole('superadmin') && $user->institution_id) {
            $humasQuery->whereHas('user', fn($q) => $q->where('institution_id', $user->institution_id));
        }
        $humas = $humasQuery->get();
        $bk = BkWorkProgram::all();
        $kelas = ClassProgram::with('classroom')->get();

        // 2. Format rekapitulasi jumlah total per divisi
        $rekapDivisi = [
            'Kepala Sekolah' => $kepsek->count(),
            'Kurikulum' => $kurikulum->count(),
            'Kesiswaan' => $kesiswaan->count(),
            'Sarpras' => $sarpras->count(),
            'Humas' => $humas->count(),
            'Bimbingan Konseling' => $bk->count(),
            'Wali Kelas' => $kelas->count(),
        ];

        // 3. Rekap status
        $allStatus = [];
        foreach($kepsek as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($kurikulum as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($kesiswaan as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($sarpras as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($humas as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($bk as $item) $allStatus[] = strtolower($item->status ?? 'belum');
        foreach($kelas as $item) $allStatus[] = strtolower($item->status ?? 'belum');

        $rekapStatus = [
            'Belum Dimulai' => 0,
            'Sedang Berjalan' => 0,
            'Selesai' => 0,
        ];

        foreach ($allStatus as $s) {
            if (str_contains($s, 'belum') || str_contains($s, 'draft') || str_contains($s, 'rencana')) {
                $rekapStatus['Belum Dimulai']++;
            } elseif (str_contains($s, 'jalan') || str_contains($s, 'proses')) {
                $rekapStatus['Sedang Berjalan']++;
            } elseif (str_contains($s, 'selesai') || str_contains($s, 'terlaksana')) {
                $rekapStatus['Selesai']++;
            } else {
                $rekapStatus['Belum Dimulai']++; // default
            }
        }

        // 4. Daftar program kerja (flatten untuk monitoring tabel)
        $daftarProgram = collect();
        foreach($kepsek as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ProgramKepsek::class,
            'divisi' => 'Kepala Sekolah',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\ProgramKepsekResource::getUrl('index', ['tableSearch' => $item->nama_program]),
        ]);
        foreach($kurikulum as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ProgramKurikulum::class,
            'divisi' => 'Kurikulum',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\ProgramKurikulumResource::getUrl('view', ['record' => $item->id]),
        ]);
        foreach($kesiswaan as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ProgramKesiswaan::class,
            'divisi' => 'Kesiswaan',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\ProgramKesiswaanResource::getUrl('view', ['record' => $item->id]),
        ]);
        foreach($sarpras as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ProgramSarpras::class,
            'divisi' => 'Sarpras',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\ProgramSarprasResource::getUrl('index', ['tableSearch' => $item->nama_program]),
        ]);
        foreach($humas as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ProgramHumas::class,
            'divisi' => 'Humas',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\ProgramHumasResource::getUrl('index', ['tableSearch' => $item->nama_program]),
        ]);
        foreach($bk as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => BkWorkProgram::class,
            'divisi' => 'Bimbingan Konseling',
            'nama' => $item->judul_program ?? 'Program BK',
            'status' => $item->status ?? 'Belum',
            'kategori' => 'BK',
            'tipe' => $item->jenis_layanan ?? '-',
            'waktu' => $item->tanggal ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_terkait ?? null,
            'url' => \App\Filament\Staff\Resources\BkWorkProgramResource::getUrl('view', ['record' => $item->id]),
        ]);
        foreach($kelas as $item) $daftarProgram->push((object)[
            'id' => $item->id,
            'model_type' => ClassProgram::class,
            'divisi' => 'Wali Kelas (' . ($item->classroom?->name ?? '-') . ')',
            'nama' => $item->nama_program,
            'status' => ucfirst($item->status ?? 'Rencana'),
            'kategori' => 'Kelas',
            'tipe' => $item->jenis_program ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_pendukung ?? null,
            'link_drive' => $item->link_drive_dokumen ?? $item->dokumen_pendukung ?? null,
            'url' => \App\Filament\Staff\Resources\ClassProgramResource::getUrl('index', ['tableSearch' => $item->nama_program]),
        ]);

        $totalProg = $daftarProgram->count();
        $persenSelesai = $totalProg > 0 ? round(($rekapStatus['Selesai'] / $totalProg) * 100) : 0;

        return [
            'rekapDivisi' => $rekapDivisi,
            'rekapStatus' => $rekapStatus,
            'totalProgram' => $totalProg,
            'persenSelesai' => $persenSelesai,
            'daftarProgram' => $daftarProgram,
        ];
    }
}