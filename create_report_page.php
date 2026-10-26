<?php
$file = 'app/Filament/Staff/Pages/WorkProgramReportPage.php';
$content = <<<'PHP'
<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\ProgramKepsek;
use App\Models\ProgramKurikulum;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramSarpras;
use App\Models\ProgramHumas;
use App\Models\BkWorkProgram;

class WorkProgramReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Laporan Program Kerja';
    protected static ?string $title = 'Laporan Keseluruhan Program Kerja Sekolah';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.staff.pages.work-program-report-page';

    public function getViewData(): array
    {
        // 1. Kumpulkan semua data dari berbagai program
        $kepsek = ProgramKepsek::all();
        $kurikulum = ProgramKurikulum::all();
        $kesiswaan = ProgramKesiswaan::all();
        $sarpras = ProgramSarpras::all();
        $humas = ProgramHumas::all();
        $bk = BkWorkProgram::all();

        // 2. Format rekapitulasi jumlah total per divisi
        $rekapDivisi = [
            'Kepala Sekolah' => $kepsek->count(),
            'Kurikulum' => $kurikulum->count(),
            'Kesiswaan' => $kesiswaan->count(),
            'Sarpras' => $sarpras->count(),
            'Humas' => $humas->count(),
            'Bimbingan Konseling' => $bk->count(),
        ];

        // 3. Rekap status
        // Karena ada perbedaan nama field status (e.g., status_pelaksanaan, status), kita normalisasi.
        $allStatus = [];
        foreach($kepsek as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($kurikulum as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($kesiswaan as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($sarpras as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        foreach($humas as $item) $allStatus[] = strtolower($item->status_pelaksanaan ?? 'belum');
        
        // BK usually doesn't have status_pelaksanaan field, or maybe it does? Let's assume it doesn't or we'll count as unknown
        foreach($bk as $item) $allStatus[] = strtolower($item->status ?? 'belum');

        $rekapStatus = [
            'Belum Dimulai' => 0,
            'Sedang Berjalan' => 0,
            'Selesai' => 0,
        ];

        foreach ($allStatus as $s) {
            if (str_contains($s, 'belum') || str_contains($s, 'draft')) {
                $rekapStatus['Belum Dimulai']++;
            } elseif (str_contains($s, 'jalan') || str_contains($s, 'proses')) {
                $rekapStatus['Sedang Berjalan']++;
            } elseif (str_contains($s, 'selesai')) {
                $rekapStatus['Selesai']++;
            } else {
                $rekapStatus['Belum Dimulai']++; // default
            }
        }

        // 4. Daftar program kerja (flatten untuk tabel)
        $daftarProgram = collect();
        foreach($kepsek as $item) $daftarProgram->push((object)['divisi' => 'Kepala Sekolah', 'nama' => $item->nama_program, 'status' => $item->status_pelaksanaan ?? 'Belum']);
        foreach($kurikulum as $item) $daftarProgram->push((object)['divisi' => 'Kurikulum', 'nama' => $item->nama_program, 'status' => $item->status_pelaksanaan ?? 'Belum']);
        foreach($kesiswaan as $item) $daftarProgram->push((object)['divisi' => 'Kesiswaan', 'nama' => $item->nama_program, 'status' => $item->status_pelaksanaan ?? 'Belum']);
        foreach($sarpras as $item) $daftarProgram->push((object)['divisi' => 'Sarpras', 'nama' => $item->nama_program, 'status' => $item->status_pelaksanaan ?? 'Belum']);
        foreach($humas as $item) $daftarProgram->push((object)['divisi' => 'Humas', 'nama' => $item->nama_program, 'status' => $item->status_pelaksanaan ?? 'Belum']);
        foreach($bk as $item) $daftarProgram->push((object)['divisi' => 'Bimbingan Konseling', 'nama' => $item->judul_program ?? 'Program BK', 'status' => $item->status ?? 'Belum']);

        return [
            'rekapDivisi' => $rekapDivisi,
            'rekapStatus' => $rekapStatus,
            'totalProgram' => $daftarProgram->count(),
            'daftarProgram' => $daftarProgram,
        ];
    }
}
PHP;
file_put_contents($file, $content);
echo "Page created.\n";
