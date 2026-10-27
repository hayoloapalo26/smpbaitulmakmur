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
                'Pengumuman & Informasi',
                'Fitur Guru Al-Qur\'an',
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
                        
                        /* --- TUPOKSI SUB-JUDUL SIDEBAR (GURU, JABATAN, UMUM & INFORMASI) --- */
                        
                        /* Group Header Layout */
                        aside.fi-sidebar .fi-sidebar-group-label {
                            display: flex !important;
                            align-items: center !important;
                            justify-content: space-between !important;
                            width: 100% !important;
                            padding-top: 5px !important;
                            padding-bottom: 6px !important;
                            margin-bottom: 6px !important;
                            font-weight: 700 !important;
                            font-size: 0.72rem !important;
                            letter-spacing: 0.6px !important;
                            text-transform: uppercase !important;
                            transition: all 0.2s ease;
                        }

                        aside.fi-sidebar .fi-sidebar-group-label .fi-sidebar-group-label-text {
                            flex: 1;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }

                        /* Mini Pill Badge */
                        .tupoksi-badge {
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            font-size: 8.5px !important;
                            line-height: 1 !important;
                            padding: 2.5px 6px !important;
                            border-radius: 9999px !important;
                            font-weight: 800 !important;
                            letter-spacing: 0.4px !important;
                            text-transform: uppercase !important;
                            white-space: nowrap !important;
                            margin-left: 6px !important;
                            flex-shrink: 0 !important;
                        }

                        /* 1. TUGAS GURU (EMERALD GREEN / HIJAU EDUKASI) */
                        aside.fi-sidebar .fi-sidebar-group[data-tupoksi="guru"] .fi-sidebar-group-label,
                        aside.fi-sidebar .fi-sidebar-group-tupoksi-guru .fi-sidebar-group-label {
                            color: #059669 !important;
                            border-left: 3.5px solid #10b981 !important;
                            padding-left: 8px !important;
                            border-bottom: 1.5px dashed rgba(16, 185, 129, 0.4) !important;
                        }
                        .dark aside.fi-sidebar .fi-sidebar-group[data-tupoksi="guru"] .fi-sidebar-group-label,
                        .dark aside.fi-sidebar .fi-sidebar-group-tupoksi-guru .fi-sidebar-group-label {
                            color: #34d399 !important;
                            border-left: 3.5px solid #34d399 !important;
                            border-bottom: 1.5px dashed rgba(52, 211, 153, 0.4) !important;
                        }
                        .tupoksi-badge-guru {
                            background: #ecfdf5 !important;
                            color: #047857 !important;
                            border: 1px solid #a7f3d0 !important;
                        }
                        .dark .tupoksi-badge-guru {
                            background: rgba(16, 185, 129, 0.18) !important;
                            color: #6ee7b7 !important;
                            border: 1px solid rgba(52, 211, 153, 0.4) !important;
                        }

                        /* 2. TUGAS JABATAN (INDIGO ROYAL / UNGU STRUKTURAL) */
                        aside.fi-sidebar .fi-sidebar-group[data-tupoksi="jabatan"] .fi-sidebar-group-label,
                        aside.fi-sidebar .fi-sidebar-group-tupoksi-jabatan .fi-sidebar-group-label {
                            color: #4f46e5 !important;
                            border-left: 3.5px solid #6366f1 !important;
                            padding-left: 8px !important;
                            border-bottom: 1.5px dashed rgba(99, 102, 241, 0.4) !important;
                        }
                        .dark aside.fi-sidebar .fi-sidebar-group[data-tupoksi="jabatan"] .fi-sidebar-group-label,
                        .dark aside.fi-sidebar .fi-sidebar-group-tupoksi-jabatan .fi-sidebar-group-label {
                            color: #818cf8 !important;
                            border-left: 3.5px solid #818cf8 !important;
                            border-bottom: 1.5px dashed rgba(129, 140, 248, 0.4) !important;
                        }
                        .tupoksi-badge-jabatan {
                            background: #eef2ff !important;
                            color: #4338ca !important;
                            border: 1px solid #c7d2fe !important;
                        }
                        .dark .tupoksi-badge-jabatan {
                            background: rgba(99, 102, 241, 0.18) !important;
                            color: #a5b4fc !important;
                            border: 1px solid rgba(129, 140, 248, 0.4) !important;
                        }

                        /* 3. FITUR UMUM & INFORMASI (SKY BLUE / CYAN INFORMASI) */
                        aside.fi-sidebar .fi-sidebar-group[data-tupoksi="umum"] .fi-sidebar-group-label,
                        aside.fi-sidebar .fi-sidebar-group-tupoksi-umum .fi-sidebar-group-label {
                            color: #0284c7 !important;
                            border-left: 3.5px solid #0ea5e9 !important;
                            padding-left: 8px !important;
                            border-bottom: 1.5px dashed rgba(14, 165, 233, 0.4) !important;
                        }
                        .dark aside.fi-sidebar .fi-sidebar-group[data-tupoksi="umum"] .fi-sidebar-group-label,
                        .dark aside.fi-sidebar .fi-sidebar-group-tupoksi-umum .fi-sidebar-group-label {
                            color: #38bdf8 !important;
                            border-left: 3.5px solid #38bdf8 !important;
                            border-bottom: 1.5px dashed rgba(56, 189, 248, 0.4) !important;
                        }
                        .tupoksi-badge-umum {
                            background: #f0f9ff !important;
                            color: #0369a1 !important;
                            border: 1px solid #bae6fd !important;
                        }
                        .dark .tupoksi-badge-umum {
                            background: rgba(14, 165, 233, 0.18) !important;
                            color: #7dd3fc !important;
                            border: 1px solid rgba(56, 189, 248, 0.4) !important;
                        }

                        /* Hover Micro-Interactions */
                        aside.fi-sidebar .fi-sidebar-group:hover .fi-sidebar-group-label {
                            border-bottom-style: solid !important;
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
                    @include("filament.common.mobile-head")
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
                        document.addEventListener("livewire:init", () => {
                            Livewire.hook("request", ({ fail }) => {
                                fail(({ status, preventDefault }) => {
                                    if (status === 419) {
                                        preventDefault();
                                        window.location.reload();
                                    }
                                });
                            });
                        });
                    </script>
                    <style>
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
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("filament.common.mobile-bottom-nav")')
            );
    }
}


