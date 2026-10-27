{{-- Mobile Meta & Responsive Optimization for Android & iOS --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Baitul Makmur">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#FF8200">

<style>
    /* ========================================================
       GLOBAL MOBILE & TOUCH OPTIMIZATION (ANDROID & IOS)
       ======================================================== */
    :root {
        --sat: env(safe-area-inset-top, 0px);
        --sab: env(safe-area-inset-bottom, 0px);
        --sal: env(safe-area-inset-left, 0px);
        --sar: env(safe-area-inset-right, 0px);
    }

    html, body {
        -webkit-tap-highlight-color: transparent !important;
        touch-action: manipulation;
        scroll-behavior: smooth;
    }

    /* Topbar Safe Area (iPhone Notch / Dynamic Island / Android Cutout) */
    .fi-topbar {
        padding-top: var(--sat) !important;
        height: calc(4rem + var(--sat)) !important;
    }

    /* Sleek Custom Scrollbar for Touch & Desktop */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255, 130, 0, 0.35); border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(216, 67, 21, 0.65); }

    /* ========================================================
       MOBILE PHONE SPECIFIC RULES (< 768px)
       ======================================================== */
    @media (max-width: 768px) {
        /* 1. Prevent iOS Safari auto-zoom on input focus */
        input[type="text"],
        input[type="search"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            font-size: 16px !important;
            border-radius: 0.75rem !important;
        }

        /* 2. Bottom Spacing so content never gets obscured by mobile bottom bar */
        .fi-main {
            padding-bottom: calc(5.5rem + var(--sab)) !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        /* 3. Touch Friendly Button Sizing */
        .fi-btn {
            min-height: 42px !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            touch-action: manipulation;
        }
        .fi-icon-btn {
            min-width: 40px !important;
            min-height: 40px !important;
            touch-action: manipulation;
        }

        /* 4. Filament Table Mobile Layout */
        .fi-ta-header-toolbar {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }
        .fi-ta-search-field, .fi-ta-search-field input {
            width: 100% !important;
        }
        .fi-ta-actions {
            flex-wrap: wrap !important;
            gap: 0.375rem !important;
        }
        .fi-ta-pagination {
            flex-direction: column !important;
            gap: 0.75rem !important;
            align-items: center !important;
        }

        /* 5. Mobile Dialogs & Modals */
        .fi-modal-window {
            width: 95vw !important;
            max-width: 95vw !important;
            margin: 0.5rem auto !important;
            border-radius: 1.25rem !important;
            max-height: 90vh !important;
        }
        .fi-modal-footer {
            padding-bottom: calc(0.75rem + var(--sab)) !important;
        }

        /* 6. Tabs Scrollable on Mobile */
        .fi-tabs {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            scrollbar-width: none !important;
        }
        .fi-tabs::-webkit-scrollbar {
            display: none !important;
        }
    }

    /* ========================================================
       MOBILE DRAWER SIDEBAR (< 1024px)
       ======================================================== */
    @media (max-width: 1024px) {
        aside.fi-sidebar {
            width: min(85vw, 320px) !important;
            border-radius: 0 1.5rem 1.5rem 0 !important;
            box-shadow: 12px 0 35px rgba(0, 0, 0, 0.18) !important;
            padding-top: var(--sat) !important;
            padding-bottom: calc(1rem + var(--sab)) !important;
        }
        .fi-sidebar-close-overlay {
            backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important;
            background-color: rgba(0, 0, 0, 0.4) !important;
        }
    }
</style>

<script>
    document.addEventListener('livewire:init', () => {
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        window.location.reload();
                    }
                });
            });
        }
    });
</script>
