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
            'divisi' => 'Kepala Sekolah',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
        ]);
        foreach($kurikulum as $item) $daftarProgram->push((object)[
            'divisi' => 'Kurikulum',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
        ]);
        foreach($kesiswaan as $item) $daftarProgram->push((object)[
            'divisi' => 'Kesiswaan',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
        ]);
        foreach($sarpras as $item) $daftarProgram->push((object)[
            'divisi' => 'Sarpras',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
        ]);
        foreach($humas as $item) $daftarProgram->push((object)[
            'divisi' => 'Humas',
            'nama' => $item->nama_program,
            'status' => $item->status_pelaksanaan ?? 'Belum',
            'kategori' => $item->kategori ?? '-',
            'tipe' => $item->tipe_pelaksanaan ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => $item->dokumen_terkait ?? null,
        ]);
        foreach($bk as $item) $daftarProgram->push((object)[
            'divisi' => 'Bimbingan Konseling',
            'nama' => $item->judul_program ?? 'Program BK',
            'status' => $item->status ?? 'Belum',
            'kategori' => 'BK',
            'tipe' => $item->jenis_layanan ?? '-',
            'waktu' => $item->tanggal ?? '-',
            'dokumen' => null,
        ]);
        foreach($kelas as $item) $daftarProgram->push((object)[
            'divisi' => 'Wali Kelas (' . ($item->classroom?->name ?? '-') . ')',
            'nama' => $item->nama_program,
            'status' => ucfirst($item->status ?? 'Rencana'),
            'kategori' => 'Kelas',
            'tipe' => $item->jenis_program ?? '-',
            'waktu' => $item->waktu_pelaksanaan ?? '-',
            'dokumen' => null,
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