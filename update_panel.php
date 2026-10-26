<?php
$file = 'app/Providers/Filament/StaffPanelProvider.php';
$content = file_get_contents($file);

// 1. Ensure Navigation Groups order
$navGroups = <<<'PHP'
            ->navigationGroups([
                'Tugas & Kewajiban',
                'Administrasi & Data Induk',
                'Akademik & Kurikulum',
                'Kesiswaan & Karakter',
                'Manajemen Sekolah',
            ])
            ->sidebarCollapsibleOnDesktop()
PHP;

if (strpos($content, '->sidebarCollapsibleOnDesktop()') === false) {
    // Insert after ->discoverPages(...) or ->colors(...)
    $content = preg_replace('/->colors\(\[.*?\]\)/s', "$0\n" . $navGroups, $content);
}

// 2. Add Aesthetic CSS via Render Hook
$hookCode = <<<'PHP'
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        /* Aesthetic Sidebar Styling */
                        aside.fi-sidebar {
                            border-right: 1px solid rgba(249, 115, 22, 0.15) !important;
                            box-shadow: 4px 0 20px -5px rgba(0, 0, 0, 0.05);
                        }
                        /* Active Menu Item Styling */
                        .fi-sidebar-item-active > a {
                            border-left: 4px solid #f97316 !important;
                            border-radius: 0 8px 8px 0 !important;
                            background: linear-gradient(90deg, rgba(249,115,22,0.1) 0%, transparent 100%);
                            transition: all 0.3s ease;
                        }
                        /* Group Header Styling */
                        .fi-sidebar-group-label {
                            border-bottom: 1px dashed rgba(156, 163, 175, 0.4);
                            padding-bottom: 6px;
                            margin-bottom: 6px;
                            color: #f97316 !important;
                            font-weight: 700;
                            letter-spacing: 0.5px;
                        }
                        /* Hover effects */
                        .fi-sidebar-item > a:hover {
                            background-color: rgba(249,115,22,0.05);
                        }
                    </style>
                ')
            )
PHP;

if (strpos($content, 'PanelsRenderHook::HEAD_END') === false) {
    $content = preg_replace('/->middleware\(\[/s', $hookCode . "\n            ->middleware([", $content);
}

file_put_contents($file, $content);
echo "StaffPanelProvider updated with styles and groups.\n";
