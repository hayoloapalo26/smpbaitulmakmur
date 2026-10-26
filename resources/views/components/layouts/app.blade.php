<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gray-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Portal SMPI Baitul Makmur' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style> body { font-family: 'Inter', sans-serif; } </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="text-gray-800">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-600 text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-md">
                        BM
                    </div>
                    <span class="font-bold text-xl tracking-tight text-gray-900 hidden sm:block">SMPI Baitul Makmur</span>
                </div>
                
                @auth
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full hidden sm:block">
                        {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('filament.staff.auth.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors">
                            Keluar
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Sistem Informasi Yayasan Baitul Makmur. Hak Cipta Dilindungi.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
