<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staff')
            ->path('staff')
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->brandName('Sistem Informasi Baitul Makmur')
            ->brandLogo(fn () => view('filament.logo'))
            ->brandLogoHeight('auto')
            ->colors([
                'primary' => '#FF8200', // Sunset Orange
                'success' => '#16a34a', // Green
                'warning' => '#ffbb88', // Peach
                'info' => '#3B82F6', // Sky Blue
                'gray' => Color::Slate,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->font('Plus Jakarta Sans')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\\Filament\\Staff\\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\\Filament\\Staff\\Pages')
            ->navigationGroups([
                'Menu Utama',
                'Bab 1: Program Kerja WKr',
                'Bab 2: Perencanaan Administrasi Pembelajaran',
                'Kesiswaan & Karakter',
                'Sarpras',
                'Humas',
                'Manajemen Sekolah',
                'Tugas & Kewajiban',
                'Administrasi & Data Induk',
                'Visi & Misi Sekolah',
                'Fitur Wali Kelas',
                'Manajemen Tugas',
                'Program Kerja WKr',
                'Perencanaan Administrasi Pembelajaran',
                'Akademik & Kurikulum',
                'Akademik',
                'Penilaian & Kompetensi',
                'Tahfidz & Tahsin',
                'Kesiswaan',
                'Karakter & Evaluasi',
                'Hubungan & Kerja Sama',
                'Perencanaan dan Pengadaan',
                'Inventarisasi & Administrasi',
                'Pemeliharaan',
                'Piket',
                'Tata Usaha',
                'Perizinan',
                'Lintas Divisi',
                'Manajemen Administrasi',
                'Kepala Sekolah',
                'Pengaturan',
            ])
            ->pages([
            ])
            ->discoverWidgets(in: app_path('Filament/Staff/Widgets'), for: 'App\\Filament\\Staff\\Widgets')
            ->widgets([
                \App\Livewire\ParentActiveness::class,
                Widgets\AccountWidget::class, \App\Livewire\NewsWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
                        
                        ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        /* --- CUSTOM AESTHETIC SIDEBAR (CLEAN/WHITE THEME) --- */
                        
                        /* Sidebar container subtle styling */
                        aside.fi-sidebar {
                            border-right: 1px solid rgba(249, 115, 22, 0.15) !important;
                            box-shadow: 4px 0 20px -5px rgba(0, 0, 0, 0.02);
                        }
                        
                        /* Active Item Styling (Aesthetic Left Border) */
                        aside.fi-sidebar .fi-sidebar-item-active > a {
                            border-left: 4px solid #f97316 !important;
                            border-radius: 0 8px 8px 0 !important;
                            background: linear-gradient(90deg, rgba(249,115,22,0.1) 0%, transparent 100%) !important;
                            color: #f97316 !important;
                            box-shadow: none !important;
                            transition: all 0.3s ease;
                        }
                        aside.fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-icon {
                            color: #f97316 !important;
                        }
                        
                        /* Hover effects for inactive items */
                        aside.fi-sidebar .fi-sidebar-item > a:hover {
                            background-color: rgba(249,115,22,0.05) !important;
                            color: #f97316 !important;
                        }
                        aside.fi-sidebar .fi-sidebar-item > a:hover .fi-sidebar-item-icon {
                            color: #f97316 !important;
                        }
                        
                        /* Group Header Styling */
                        aside.fi-sidebar .fi-sidebar-group-label {
                            border-bottom: 1px dashed rgba(156, 163, 175, 0.4);
                            padding-bottom: 6px;
                            margin-bottom: 6px;
                            color: #f97316 !important;
                            font-weight: 700;
                            letter-spacing: 0.5px;
                            text-transform: uppercase !important;
                        }
                    </style>
                ')
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\RedirectPortalUsers::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <script src="https://cdn.tailwindcss.com"></script>
                    <script>
                        tailwind.config = {
                            darkMode: "class",
                            theme: {
                                fontFamily: { sans: [\'"Plus Jakarta Sans"\', "sans-serif"] }
                            }
                        };
                        window.addEventListener("DOMContentLoaded", () => {
                            const nav = document.querySelector(".fi-sidebar-nav");
                            if (nav && window.location.pathname.endsWith("/staff")) {
                                nav.scrollTop = 0;
                            }
                        });
                    </script>
                    <style>
                        /* Sleek Custom Scrollbar */
                        ::-webkit-scrollbar { width: 6px; height: 6px; }
                        ::-webkit-scrollbar-track { background: #FCFBF8; } /* WARM WHITE */
                        ::-webkit-scrollbar-thumb { background: rgba(255, 130, 0, 0.4); border-radius: 10px; } /* SUNSET ORANGE */
                        ::-webkit-scrollbar-thumb:hover { background: rgba(216, 67, 21, 0.7); } /* DARK ORANGE */

                        /* Glassmorphism Topbar Styling */
                        .fi-topbar {
                            background: rgba(252, 251, 248, 0.85) !important; /* WARM WHITE */
                            backdrop-filter: blur(12px) !important;
                            -webkit-backdrop-filter: blur(12px) !important;
                            border-bottom: 1px solid rgba(255, 130, 0, 0.1) !important;
                            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03) !important;
                        }
                        
                        .dark .fi-topbar {
                            background: rgba(52, 51, 46, 0.8) !important; /* CHARCOAL */
                            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
                        }

                        /* Gradient Text for Logo */
                        .fi-logo {
                            background: linear-gradient(90deg, #FF8200, #D84315); /* ORANGE to DARK ORANGE */
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            font-weight: 900 !important;
                            font-size: 1.25rem !important;
                            letter-spacing: -0.5px;
                        }
                        
                        .dark .fi-logo {
                            background: linear-gradient(90deg, #ffbb88, #F4EEDC); /* PEACH to CREAM MUDA */
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        }
                    </style>
                ')
            );
    }
}


