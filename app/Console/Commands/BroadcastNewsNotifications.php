<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class BroadcastNewsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:broadcast-notifications {--force : Paksa kirim ulang notifikasi meskipun sudah pernah dikirimkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengumuman (News) yang aktif ke lonceng notifikasi pojok kanan atas pengguna';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $publishedNews = News::where('is_published', true)->orderBy('created_at', 'asc')->get();

        if ($publishedNews->isEmpty()) {
            $this->info('Tidak ada pengumuman dengan status terbit (is_published = true).');
            return Command::SUCCESS;
        }

        $this->info("Memproses {$publishedNews->count()} pengumuman terbit...");

        $totalSent = 0;
        foreach ($publishedNews as $news) {
            $sent = $news->broadcastNotification($force);
            $totalSent += $sent;
            $this->line(" - [ID {$news->id}] {$news->title}: {$sent} notifikasi disiarkan.");
        }

        $this->info("Selesai! Total {$totalSent} notifikasi berhasil disiarkan ke lonceng pengguna.");
        return Command::SUCCESS;
    }
}
