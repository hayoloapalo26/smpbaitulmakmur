<style>
    /* Base styles (Login Page) */
    .custom-logo-wrapper { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; gap: 0.75rem; }
    .custom-logo-img { height: 5.5rem; max-width: 100%; object-fit: contain; transition: all 0.3s ease; }
    
    /* Typography for the text */
    .custom-logo-text {
        font-family: "Inter", ui-sans-serif, system-ui, -apple-system, sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: center;
        background: linear-gradient(135deg, #D84315 0%, #FF8200 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 1.15rem;
        line-height: 1.3;
        filter: drop-shadow(0 2px 2px rgba(216, 67, 21, 0.2));
    }

    /* Sidebar styles */
    .fi-sidebar-header .custom-logo-wrapper { padding: 0; gap: 0.25rem; }
    .fi-sidebar-header .custom-logo-img { height: auto; width: 100%; max-height: 2.25rem; object-fit: contain; filter: none; }
    .fi-sidebar-header .custom-logo-text {
        font-size: 0.6rem;
        line-height: 1.1;
    }

    /* Dark mode support */
    .dark .custom-logo-img { background-color: white; padding: 0.25rem; border-radius: 0.25rem; }
</style>
<div class="custom-logo-wrapper">
    <div class="custom-logo-text">Sistem Informasi<br>Manajemen Sekolah</div>
    <img src="{{ asset('images/logo-bmos-new.jpg') }}" alt="Logo LPI Baitul Makmur" class="custom-logo-img" />
</div>
