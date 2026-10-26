<?php
$file = 'app/Providers/Filament/YayasanPanelProvider.php';
if(file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'PanelsRenderHook::HEAD_END') === false) {
        $hookCode = <<<'PHP'

            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        aside.fi-sidebar {
                            border-right: 1px solid rgba(249, 115, 22, 0.15) !important;
                            box-shadow: 4px 0 20px -5px rgba(0, 0, 0, 0.05);
                        }
                        .fi-sidebar-item-active > a {
                            border-left: 4px solid #f97316 !important;
                            border-radius: 0 8px 8px 0 !important;
                            background: linear-gradient(90deg, rgba(249,115,22,0.1) 0%, transparent 100%);
                            transition: all 0.3s ease;
                        }
                        .fi-sidebar-group-label {
                            border-bottom: 1px dashed rgba(156, 163, 175, 0.4);
                            padding-bottom: 6px;
                            margin-bottom: 6px;
                            color: #f97316 !important;
                            font-weight: 700;
                            letter-spacing: 0.5px;
                        }
                        .fi-sidebar-item > a:hover {
                            background-color: rgba(249,115,22,0.05);
                        }
                    </style>
                ')
            )
PHP;
        $content = preg_replace('/->discoverWidgets\((.*?)\)/s', "$0\n" . $hookCode, $content);
        file_put_contents($file, $content);
    }
}
echo "Yayasan updated.\n";
