<?php
$files = [
    'app/Providers/Filament/StaffPanelProvider.php',
    'app/Providers/Filament/YayasanPanelProvider.php'
];

$hookCode = <<<'PHP'
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
PHP;

foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        // Remove old renderHook block entirely
        $content = preg_replace('/->renderHook\(\s*\\\\Filament\\\\View\\\\PanelsRenderHook::HEAD_END.*?\)\s*\)/s', '', $content);
        // Insert new renderHook before ->middleware([
        $content = preg_replace('/->middleware\(\[/s', $hookCode . "\n            ->middleware([", $content);
        file_put_contents($file, $content);
    }
}
echo "Theme reverted to clean white with aesthetic borders.\n";
