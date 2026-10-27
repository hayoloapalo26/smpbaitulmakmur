<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gray-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Portal BM">
    <meta name="theme-color" content="#059669">
    <title>{{ $title ?? 'Portal SMPI Baitul Makmur' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style> 
        body { 
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        } 
        @media (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="text-gray-800 pt-[env(safe-area-inset-top,0px)] pb-[env(safe-area-inset-bottom,0px)]">
    <!-- Navbar (Touch & Mobile Responsive) -->
    <nav class="bg-white/95 backdrop-blur-md shadow-xs border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8">
            <div class="flex justify-between h-15 sm:h-16 items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-emerald-600 text-white rounded-xl flex items-center justify-center font-black text-lg sm:text-xl shadow-md">
                        BM
                    </div>
                    <span class="font-extrabold text-base sm:text-xl tracking-tight text-gray-900">Portal Siswa & Wali</span>
                </div>
                
                @auth
                <div class="flex items-center gap-2 sm:gap-4">
                    <span class="text-xs sm:text-sm font-bold text-gray-700 bg-gray-100 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full max-w-[130px] sm:max-w-none truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('filament.staff.auth.logout') }}">
                        @csrf
                        <button type="submit" class="text-xs sm:text-sm font-bold text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl transition-all active:scale-95">
                            Keluar
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen px-2.5 sm:px-4 py-4 sm:py-6">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-10 sm:mt-12 py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs sm:text-sm text-gray-500">
            &copy; {{ date('Y') }} Sistem Informasi Yayasan Baitul Makmur. Hak Cipta Dilindungi.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
