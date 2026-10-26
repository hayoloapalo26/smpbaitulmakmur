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
                        /* --- CUSTOM AESTHETIC SIDEBAR (ORANGE THEME) --- */
                        
                        /* Background Sidebar Orange */
                        aside.fi-sidebar, 
                        aside.fi-sidebar .fi-sidebar-nav {
                            background-color: #f97316 !important; /* Orange 500 */
                            border-right: none !important;
                        }
                        
                        /* Header/Logo section in sidebar */
                        aside.fi-sidebar header.fi-sidebar-header {
                            background-color: #ea580c !important; /* Orange 600 */
                            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
                        }
                        
                        /* Inactive Items Text & Icon to White */
                        aside.fi-sidebar .fi-sidebar-item > a {
                            color: #ffffff !important;
                            font-weight: 500 !important;
                        }
                        aside.fi-sidebar .fi-sidebar-item-icon {
                            color: #ffffff !important;
                            opacity: 0.9;
                        }
                        
                        /* Hover Effect for Inactive Items */
                        aside.fi-sidebar .fi-sidebar-item > a:hover {
                            background-color: rgba(255, 255, 255, 0.1) !important;
                            border-radius: 9999px !important;
                        }
                        
                        /* Active Item Styling (White background, Orange text) */
                        aside.fi-sidebar .fi-sidebar-item-active > a {
                            background: #ffffff !important;
                            color: #f97316 !important;
                            border-radius: 20px !important; /* Rounded aesthetic */
                            border-left: none !important; /* Remove previous border */
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                        }
                        aside.fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-icon {
                            color: #f97316 !important;
                            opacity: 1;
                        }
                        
                        /* Group Header (YAYASAN, INFORMASI, dll) */
                        aside.fi-sidebar .fi-sidebar-group-label {
                            color: rgba(255, 255, 255, 0.7) !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
                            text-transform: uppercase !important;
                            letter-spacing: 1px;
                        }

                        /* Caret/Arrow for groups */
                        aside.fi-sidebar .fi-sidebar-group-collapse-btn {
                            color: rgba(255, 255, 255, 0.7) !important;
                        }
                    </style>
                ')
            )
PHP;

foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        // Remove old renderHook block
        $content = preg_replace('/->renderHook\(\s*\\\\Filament\\\\View\\\\PanelsRenderHook::HEAD_END.*?\)\s*\)/s', '', $content);
        // Insert new renderHook before ->middleware([
        $content = preg_replace('/->middleware\(\[/s', $hookCode . "\n            ->middleware([", $content);
        file_put_contents($file, $content);
    }
}
echo "Theme updated.\n";
