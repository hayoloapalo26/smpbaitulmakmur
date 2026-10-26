<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\CharacterScoreLog;
use App\Observers\CharacterScoreLogObserver;
use App\Models\StudentCase;
use App\Observers\StudentCaseObserver;
use App\Models\AttributeViolation;
use App\Observers\AttributeViolationObserver;
use App\Models\PrayerAttendance;
use App\Observers\PrayerAttendanceObserver;
use App\Models\MeetingMinute;
use App\Observers\MeetingMinuteObserver;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CharacterScoreLog::observe(CharacterScoreLogObserver::class);
        StudentCase::observe(StudentCaseObserver::class);
        AttributeViolation::observe(AttributeViolationObserver::class);
        PrayerAttendance::observe(PrayerAttendanceObserver::class);
        MeetingMinute::observe(MeetingMinuteObserver::class);

        Gate::before(function ($user, $ability) {
            // 1. Hak Akses Mutlak (Superadmin)
            // Hanya Superadmin yang bisa melakukan APA SAJA (Create, Read, Update, Delete) di semua fitur
            if ($user->hasRole('superadmin')) {
                return true;
            }

            // 2. Hak Akses Pemantauan (Yayasan)
            // Yayasan bertugas melakukan pengawasan (Monitoring). 
            // Maka Yayasan diberi hak akses BACA (view, viewAny) ke seluruh data di sistem,
            // namun TIDAK BISA menambah/mengubah/menghapus data sekolah (kecuali diizinkan spesifik di Policy-nya).
            if ($user->hasAnyRole(['yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan'])) {
                if (in_array($ability, ['view', 'viewAny'])) {
                    return true;
                }
            }
            
            return null;
        });
    }
}
