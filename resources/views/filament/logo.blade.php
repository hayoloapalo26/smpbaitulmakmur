<style>
    /* Base styles (Login Page) */
    .custom-logo-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 0.85rem;
    }
    
    .custom-logo-badge {
        background: #ffffff;
        padding: 0.5rem 1.15rem;
        border-radius: 0.85rem;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .custom-logo-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -4px rgba(255, 130, 0, 0.2), 0 0 0 1px rgba(255, 130, 0, 0.25);
    }
    .dark .custom-logo-badge {
        background: #ffffff;
        box-shadow: 0 6px 20px -3px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.15);
    }

    .custom-logo-img {
        height: 5.25rem;
        max-width: 100%;
        object-fit: contain;
    }
    
    /* Typography for the text */
    .custom-logo-text {
        font-family: "Plus Jakarta Sans", "Inter", ui-sans-serif, system-ui, sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        text-align: center;
        background: linear-gradient(135deg, #D84315 0%, #FF8200 50%, #FFA040 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 1.15rem;
        line-height: 1.35;
        filter: drop-shadow(0 2px 4px rgba(216, 67, 21, 0.18));
    }

    /* Sidebar styles */
    .fi-sidebar-header .custom-logo-wrapper {
        padding: 0;
        gap: 0.25rem;
    }
    .fi-sidebar-header .custom-logo-badge {
        background: transparent !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    .fi-sidebar-header .custom-logo-img {
        height: auto;
        width: 100%;
        max-height: 2.25rem;
        object-fit: contain;
    }
    .fi-sidebar-header .custom-logo-text {
        font-size: 0.6rem;
        line-height: 1.1;
    }
</style>
<div class="custom-logo-wrapper">
    <div class="custom-logo-text">Sistem Informasi<br>Manajemen Sekolah</div>
    <div class="custom-logo-badge">
        <img src="{{ asset('images/logo-bmos-new.jpg') }}" alt="Logo LPI Baitul Makmur" class="custom-logo-img" />
    </div>
</div>
