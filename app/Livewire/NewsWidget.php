<?php

namespace App\Livewire;

use App\Models\News;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NewsWidget extends Widget
{
    protected static string $view = 'livewire.news-widget';
    protected int | string | array $columnSpan = 'full';

    // The widget will poll every 30s to keep news fresh
    protected static ?string $pollingInterval = '30s';

    public function getNewsProperty()
    {
        $user = Auth::user();
        $userRoles = $user->getRoleNames()->toArray();

        return News::with('author')
            ->where('is_published', true)
            ->latest('created_at')
            ->get()
            ->filter(function ($news) use ($userRoles) {
                if (empty($news->target_roles)) {
                    return true;
                }
                return count(array_intersect($news->target_roles, $userRoles)) > 0;
            })
            ->take(5);
    }
    
    public function openNews($id)
    {
        $news = News::with('author')->find($id);
        if ($news) {
            $this->dispatch('open-news-modal', 
                title: $news->title,
                content: $news->content,
                author: $news->author->name ?? 'Admin',
                date: $news->created_at->format('d M Y'),
                image: $news->image ? Storage::url($news->image) : null
            );
        }
    }
}
