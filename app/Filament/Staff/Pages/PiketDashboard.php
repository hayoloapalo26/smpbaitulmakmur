<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\DutySchedule;
use Carbon\Carbon;

class PiketDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.staff.pages.piket-dashboard';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $title = 'Dasbor Piket Harian';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }
}
