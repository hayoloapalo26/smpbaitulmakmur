<?php

namespace App\Observers;

use App\Models\News;

class NewsObserver
{
    public function created(News $news): void
    {
        if ($news->is_published) {
            $news->broadcastNotification();
        }
    }

    public function updated(News $news): void
    {
        if ($news->is_published) {
            if (
                $news->wasChanged('is_published') ||
                $news->wasChanged('title') ||
                $news->wasChanged('content') ||
                $news->wasChanged('target_institutions') ||
                $news->wasChanged('target_roles')
            ) {
                $news->broadcastNotification();
            }
        }
    }
}
