<?php

namespace App\Models;

use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'author_id',
        'target_institutions',
        'target_roles',
        'is_published',
    ];

    protected $casts = [
        'target_institutions' => 'array',
        'target_roles' => 'array',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title) . '-' . time();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Dapatkan seluruh target user yang berhak menerima pengumuman ini.
     */
    public function getTargetUsers(): Collection
    {
        $query = User::query()->where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        });

        $targetInstitutions = array_filter((array) ($this->target_institutions ?? []));
        $targetRoles = array_filter((array) ($this->target_roles ?? []));

        if (!empty($targetInstitutions)) {
            $includeYayasan = in_array('yayasan', $targetInstitutions);
            $institutionIds = array_filter($targetInstitutions, fn ($id) => is_numeric($id));

            $query->where(function ($q) use ($institutionIds, $includeYayasan) {
                $hasCondition = false;
                if (!empty($institutionIds)) {
                    $q->whereIn('institution_id', $institutionIds);
                    $hasCondition = true;
                }
                if ($includeYayasan) {
                    if ($hasCondition) {
                        $q->orWhereHas('roles', fn ($rq) => $rq->whereIn('name', ['yayasan', 'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'pengembang_mutu']));
                    } else {
                        $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['yayasan', 'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'pengembang_mutu']));
                    }
                }
            });
        }

        if (!empty($targetRoles)) {
            $query->whereHas('roles', function ($q) use ($targetRoles) {
                $q->whereIn('name', $targetRoles);
            });
        }

        return $query->get();
    }

    /**
     * Kirim notifikasi database ke lonceng pojok kanan atas seluruh pengguna target.
     */
    public function broadcastNotification(bool $force = false): int
    {
        if (!$this->is_published) {
            return 0;
        }

        $targetUsers = $this->getTargetUsers();
        $sentCount = 0;

        foreach ($targetUsers as $user) {
            if (!$force) {
                $alreadyNotified = DB::table('notifications')
                    ->where('notifiable_type', $user->getMorphClass())
                    ->where('notifiable_id', $user->getKey())
                    ->where('data', 'like', '%"news_id":' . $this->id . '%')
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }
            }

            $targetUrl = $user->hasRole('yayasan') ? '/yayasan/news' : '/staff/news';

            Notification::make()
                ->title('📢 Pengumuman: ' . $this->title)
                ->body(Str::limit(strip_tags($this->content), 120))
                ->icon('heroicon-o-megaphone')
                ->iconColor('primary')
                ->viewData(['news_id' => $this->id])
                ->actions([
                    NotificationAction::make('view')
                        ->label('Buka Pengumuman')
                        ->button()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url($targetUrl)
                        ->markAsRead(),
                    NotificationAction::make('markAsRead')
                        ->label('Tandai Dibaca')
                        ->color('gray')
                        ->icon('heroicon-m-check')
                        ->markAsRead(),
                ])
                ->sendToDatabase($user, isEventDispatched: true);

            $sentCount++;
        }

        return $sentCount;
    }
}
