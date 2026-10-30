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
                // 1. INFORMASI UMUM & BERANDA (Urutan Pertama)
                'Menu Utama',
                'Informasi Umum & Beranda',
                'Pengumuman & Informasi',
                'Visi & Misi Sekolah',

                // 2. TUGAS GURU (Ditaruh pertama tepat setelah Informasi Umum)
                'Tugas & Kewajiban Harian Guru',
                'Tugas & Kewajiban',
                'Tugas Guru Mapel',
                'Akademik & Kurikulum',
                'Penilaian & Kompetensi',
                'Tugas Guru Al-Qur\'an',
                'Fitur Guru Al-Qur\'an',
                'Tahfidz & Tahsin',
                'Tugas Wali Kelas',
                'Fitur Wali Kelas',
                'Tugas Guru Piket',
                'Piket',

                // 3. TUGAS JABATAN (Ditaruh setelah Tugas Guru)
                'Tugas Kepala Sekolah',
                'Manajemen Sekolah',
                'Kepala Sekolah',
                'Tugas Waka Kurikulum',
                'Program Kerja Waka Kurikulum',
                'Perencanaan Administrasi Pembelajaran',
                'Tugas Waka Kesiswaan',
                'Kesiswaan & Karakter',
                'Kesiswaan',
                'Karakter & Evaluasi',
                'Tugas Waka Sarpras',
                'Sarpras',
                'Perencanaan dan Pengadaan',
                'Inventarisasi & Administrasi',
                'Pemeliharaan',
                'Tugas Waka Humas',
                'Humas',
                'Hubungan & Kerja Sama',
                'Tugas Tata Usaha',
                'Tata Usaha',
                'Administrasi & Data Induk',
                'Perizinan',
                'Lintas Divisi',
                'Manajemen Administrasi',
                'Manajemen Tugas',
                'Tugas Koordinator Madin',
                'Madrasah Diniyah (Madin)',

                // 4. LAINNYA / PENGATURAN SISTEM
                'Pengaturan Sistem',
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
                            flex-direction: column !important;
                            align-items: stretch !important;
                            justify-content: center !important;
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

                        .tupoksi-group-header {
                            display: flex !important;
                            flex-direction: column !important;
                            width: 100% !important;
                            min-width: 0 !important;
                        }

                        .tupoksi-group-row {
                            display: flex !important;
                            align-items: center !important;
                            justify-content: space-between !important;
                            width: 100% !important;
                            min-width: 0 !important;
                            gap: 4px !important;
                        }

                        .tupoksi-keterangan {
                            display: block !important;
                            font-size: 9.5px !important;
                            font-weight: 500 !important;
                            line-height: 1.3 !important;
                            text-transform: none !important;
                            letter-spacing: normal !important;
                            color: #64748b !important;
                            opacity: 0.9 !important;
                        }
                        .dark .tupoksi-keterangan {
                            color: #94a3b8 !important;
                        }

                        /* Piket Aktif Special Glowing Badge */
                        .tupoksi-badge-piket-aktif {
                            background: #fef2f2 !important;
                            color: #dc2626 !important;
                            border: 1px solid #fca5a5 !important;
                            box-shadow: 0 0 8px rgba(239, 68, 68, 0.35) !important;
                        }
                        .dark .tupoksi-badge-piket-aktif {
                            background: rgba(220, 38, 38, 0.22) !important;
                            color: #f87171 !important;
                            border: 1px solid rgba(248, 113, 113, 0.45) !important;
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
                        /* Fix User Menu & Logout Dropdown positioning */
                        .fi-user-menu,
                        .fi-user-menu button {
                            position: relative !important;
                        }
                        
                        .fi-user-menu .fi-dropdown-panel,
                        body > .fi-dropdown-panel {
                            z-index: 999999 !important;
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
                        /* Fix User Menu & Logout Dropdown positioning */
                        .fi-user-menu,
                        .fi-user-menu button {
                            position: relative !important;
                        }
                        
                        .fi-user-menu .fi-dropdown-panel,
                        body > .fi-dropdown-panel {
                            z-index: 999999 !important;
                        }
                    </style>
                ')
            )
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Beralih ke Portal Yayasan')
                    ->url('/yayasan')
                    ->icon('heroicon-o-building-library')
                    ->group('Yayasan')
                    ->sort(999)
                    ->visible(fn () => auth()->check() && auth()->user()->hasAnyRole([
                        'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',
                        'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan',
                        'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan'
                    ])),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("filament.common.portal-switcher-topbar") @include("filament.common.whatsapp-topbar-button")')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("filament.common.mobile-bottom-nav")')
            );
    }
}


