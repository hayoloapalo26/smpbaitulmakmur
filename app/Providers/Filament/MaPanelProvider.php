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
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;
use Filament\View\PanelsRenderHook;

class MaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('ma')
            ->path('ma')
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->brandName('MA Baitul Makmur')
            ->brandLogoHeight('auto')
            ->colors([
                'primary' => '#06b6d4', // Cyan/Sky Blue
                'success' => '#10b981', // Emerald
                'warning' => '#f59e0b', // Amber
                'danger'  => '#f43f5e', // Rose
                'info'    => '#3b82f6', // Blue
                'gray'    => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Ma/Resources'), for: 'App\\Filament\\Ma\\Resources')
            ->discoverPages(in: app_path('Filament/Ma/Pages'), for: 'App\\Filament\\Ma\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Ma/Widgets'), for: 'App\\Filament\\Ma\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>
                        /* --- CUSTOM AESTHETIC SIDEBAR FOR MA --- */
                        
                        /* Sidebar container subtle styling */
                        aside.fi-sidebar {
                            border-right: 1px solid rgba(6, 182, 212, 0.15) !important;
                            box-shadow: 4px 0 20px -5px rgba(0, 0, 0, 0.02);
                        }
                        
                        /* Active Item Styling */
                        aside.fi-sidebar .fi-sidebar-item-active > a {
                            border-left: 4px solid #06b6d4 !important;
                            border-radius: 0 8px 8px 0 !important;
                            background: linear-gradient(90deg, rgba(6, 182, 212, 0.1) 0%, transparent 100%) !important;
                            color: #06b6d4 !important;
                            box-shadow: none !important;
                            transition: all 0.3s ease;
                        }
                        aside.fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-icon {
                            color: #06b6d4 !important;
                        }
                        
                        /* Hover effects for inactive items */
                        aside.fi-sidebar .fi-sidebar-item > a:hover {
                            background-color: rgba(6, 182, 212, 0.05) !important;
                            color: #06b6d4 !important;
                        }
                        aside.fi-sidebar .fi-sidebar-item > a:hover .fi-sidebar-item-icon {
                            color: #06b6d4 !important;
                        }
                        
                        /* Group Header Styling */
                        aside.fi-sidebar .fi-sidebar-group-label {
                            border-bottom: 1px dashed rgba(6, 182, 212, 0.4);
                            padding-bottom: 6px;
                            margin-bottom: 6px;
                            color: #06b6d4 !important;
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
                        }
                    </script>
                    <style>
                        /* Sleek Custom Scrollbar */
                        ::-webkit-scrollbar { width: 6px; height: 6px; }
                        ::-webkit-scrollbar-track { background: #FCFBF8; }
                        ::-webkit-scrollbar-thumb { background: rgba(6, 182, 212, 0.4); border-radius: 10px; }
                        ::-webkit-scrollbar-thumb:hover { background: rgba(6, 182, 212, 0.7); }

                        /* Glassmorphism Topbar Styling */
                        .fi-topbar {
                            background: rgba(252, 251, 248, 0.85) !important;
                            backdrop-filter: blur(12px) !important;
                            -webkit-backdrop-filter: blur(12px) !important;
                            border-bottom: 1px solid rgba(6, 182, 212, 0.1) !important;
                            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03) !important;
                        }
                        
                        .dark .fi-topbar {
                            background: rgba(52, 51, 46, 0.8) !important;
                            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
                        }

                        /* Gradient Text for Logo */
                        .fi-logo {
                            background: linear-gradient(90deg, #06b6d4, #3b82f6); /* CYAN to BLUE */
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                            font-weight: 900 !important;
                            font-size: 1.25rem !important;
                            letter-spacing: -0.5px;
                        }
                    </style>
                ')
            )
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\RedirectPortalUsers::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("filament.common.mobile-bottom-nav")')
            );
    }
}
