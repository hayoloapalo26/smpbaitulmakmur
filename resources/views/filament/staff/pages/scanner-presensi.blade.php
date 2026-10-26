<x-filament-panels::page>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <div 
        x-data="{
            isCameraOpen: false,
            html5QrCode: null,
            audioCtx: null,
            init() {
                this.focusInput();
                document.addEventListener('click', (e) => {
                    if(!e.target.closest('#qr-reader') && !e.target.closest('button')) {
                        this.focusInput();
                    }
                });
                
                // Initialize audio context on first user interaction to bypass browser autoplay blocks
                document.addEventListener('click', () => {
                    if(!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }, { once: true });
            },
            playBeep(status) {
                if(!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                const osc = this.audioCtx.createOscillator();
                const gainNode = this.audioCtx.createGain();
                
                osc.connect(gainNode);
                gainNode.connect(this.audioCtx.destination);
                
                if (status === 'success') {
                    // High pitch short beep (Checkout counter sound)
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, this.audioCtx.currentTime); // A5
                    gainNode.gain.setValueAtTime(0.1, this.audioCtx.currentTime);
                    osc.start();
                    osc.stop(this.audioCtx.currentTime + 0.15);
                } else if (status === 'error') {
                    // Low pitch long buzz
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(200, this.audioCtx.currentTime);
                    gainNode.gain.setValueAtTime(0.1, this.audioCtx.currentTime);
                    osc.start();
                    osc.stop(this.audioCtx.currentTime + 0.4);
                } else if (status === 'warning') {
                    // Two quick mid-pitch beeps
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(440, this.audioCtx.currentTime);
                    gainNode.gain.setValueAtTime(0.05, this.audioCtx.currentTime);
                    
                    gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime + 0.1);
                    gainNode.gain.setValueAtTime(0.05, this.audioCtx.currentTime + 0.15);
                    gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime + 0.25);
                    
                    osc.start();
                    osc.stop(this.audioCtx.currentTime + 0.3);
                }
            },
            focusInput() {
                setTimeout(() => {
                    if(this.$refs.barcodeInput && !this.isCameraOpen) {
                        this.$refs.barcodeInput.focus();
                    }
                }, 100);
            },
            startCamera() {
                this.isCameraOpen = true;
                this.html5QrCode = new Html5Qrcode('qr-reader');
                
                this.html5QrCode.start(
                    { facingMode: 'environment' }, 
                    {
                        fps: 15, // slightly faster
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        // Success callback
                        $wire.set('nis', decodedText);
                        $wire.handleScan();
                        
                        // Pause briefly so it doesn't scan the same QR 100 times
                        this.html5QrCode.pause();
                        setTimeout(() => this.html5QrCode.resume(), 1500);
                    },
                    (errorMessage) => {
                        // ignore errors
                    }
                ).catch((err) => {
                    alert('Gagal membuka kamera: ' + err);
                    this.isCameraOpen = false;
                });
            },
            stopCamera() {
                if(this.html5QrCode) {
                    this.html5QrCode.stop().then(() => {
                        this.html5QrCode.clear();
                        this.isCameraOpen = false;
                        this.focusInput();
                    }).catch(err => {
                        console.error(err);
                    });
                }
            }
        }"
        @scan-result.window="playBeep($event.detail.status)"
        class="flex flex-col items-center justify-start min-h-[70vh] space-y-6 relative"
    >
        <!-- Loading Overlay -->
        <div wire:loading wire:target="handleScan" class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-3xl">
            <div class="flex flex-col items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 animate-pulse">
                <x-heroicon-o-arrow-path class="w-12 h-12 text-primary-500 animate-spin mb-4" />
                <p class="text-lg font-bold text-gray-900 dark:text-white">Memproses Data...</p>
            </div>
        </div>

        <!-- Tipe Absen Indicator (Auto-detect dari jam) -->
        <div class="w-full max-w-6xl">
            @php
                $nowJam = now()->hour * 60 + now()->minute;
                $activeType = match(true) {
                    $nowJam < 12 * 60  => 'masuk',
                    $nowJam < 14 * 60  => 'sholat_dhuhur',
                    $nowJam < 16 * 60  => 'sholat_ashar',
                    default            => 'pulang',
                };
                $absenSlots = [
                    ['key' => 'masuk',         'label' => 'Absen Masuk',   'waktu' => 'Sebelum 07:15', 'emoji' => '🏫'],
                    ['key' => 'sholat_dhuhur', 'label' => 'Sholat Dhuhur', 'waktu' => '12:00 – 13:59', 'emoji' => '🕌'],
                    ['key' => 'sholat_ashar',  'label' => 'Sholat Ashar',  'waktu' => '14:00 – 15:59', 'emoji' => '🌅'],
                    ['key' => 'pulang',        'label' => 'Absen Pulang',  'waktu' => 'Mulai 16:00',   'emoji' => '🏠'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($absenSlots as $slot)
                <div class="rounded-xl p-4 text-center border-2 transition-all {{ $activeType === $slot['key'] ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 shadow-md' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 opacity-50' }}">
                    <div class="text-2xl mb-1">{{ $slot['emoji'] }}</div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">{{ $slot['label'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $slot['waktu'] }}</p>
                    @if($activeType === $slot['key'])
                        <span class="mt-2 inline-block text-xs bg-emerald-500 text-white px-2 py-0.5 rounded-full font-bold animate-pulse">✓ AKTIF SEKARANG</span>
                    @endif
                </div>
                @endforeach
            </div>
            <p class="text-center text-xs text-gray-400 mt-2">Tipe absen dideteksi otomatis dari jam — <strong>{{ now()->format('H:i') }} WIB</strong></p>
        </div>

        <input 
            x-ref="barcodeInput"
            type="text" 
            wire:model="nis" 
            wire:keydown.enter="handleScan"
            class="opacity-0 absolute -z-50 h-0 w-0"
            autocomplete="off"
            autofocus
        >

        <!-- Main Scanner Area -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-6xl">
            
            <!-- Left: Camera & Hardware Scanner Instructions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center">
                <div class="inline-flex items-center justify-center p-4 bg-primary-50 rounded-full text-primary-600 mb-4">
                    <x-heroicon-o-camera class="w-10 h-10" />
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">Scan Kamera / Scanner Fisik</h2>
                
                <!-- Camera Viewfinder -->
                <div id="qr-reader" class="w-full max-w-sm rounded-lg overflow-hidden border-2 border-gray-100 dark:border-gray-700 bg-black min-h-[300px] flex items-center justify-center relative mb-4">
                    <div x-show="!isCameraOpen" class="text-center text-gray-400 absolute inset-0 flex flex-col items-center justify-center p-4">
                        <x-heroicon-o-video-camera-slash class="w-12 h-12 mb-2 opacity-50" />
                        <p class="text-sm">Kamera belum diaktifkan</p>
                    </div>
                </div>

                <div class="flex gap-4 w-full max-w-sm">
                    <button x-show="!isCameraOpen" @click="startCamera()" type="button" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2">
                        <x-heroicon-s-play class="w-5 h-5" /> Mulai Kamera Web
                    </button>
                    <button x-show="isCameraOpen" @click="stopCamera()" type="button" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2">
                        <x-heroicon-s-stop class="w-5 h-5" /> Matikan Kamera
                    </button>
                </div>
                
                <p class="text-xs text-gray-400 mt-6 text-center">
                    Gunakan tombol di atas untuk scan menggunakan kamera Laptop/HP.<br>Atau gunakan alat Scanner Fisik (USB/Bluetooth) secara langsung.
                </p>
            </div>

            <!-- Right: Result Area -->
            <div class="flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 min-h-[400px]">
                @if($scanStatus)
                    <div class="w-full transition-all transform duration-300 scale-100 opacity-100">
                        <div class="rounded-2xl border shadow-lg overflow-hidden bg-white dark:bg-gray-800 
                            @if($scanStatus === 'success') border-green-500 ring-1 ring-green-500
                            @elseif($scanStatus === 'warning') border-amber-500 ring-1 ring-amber-500
                            @elseif($scanStatus === 'halangan') border-pink-400 ring-1 ring-pink-400
                            @else border-red-500 ring-1 ring-red-500 @endif
                        ">
                            <div class="px-6 py-4 flex items-center justify-between text-white
                                @if($scanStatus === 'success') bg-green-500
                                @elseif($scanStatus === 'warning') bg-amber-500
                                @elseif($scanStatus === 'halangan') bg-pink-400
                                @else bg-red-500 @endif
                            ">
                                <div class="flex items-center gap-3">
                                    @if($scanStatus === 'success')
                                        <x-heroicon-o-check-circle class="w-8 h-8" />
                                    @elseif($scanStatus === 'halangan')
                                        <span class="text-2xl">🌸</span>
                                    @elseif($scanStatus === 'warning')
                                        <x-heroicon-o-exclamation-triangle class="w-8 h-8" />
                                    @else
                                        <x-heroicon-o-x-circle class="w-8 h-8" />
                                    @endif
                                    <div>
                                        <h3 class="text-lg font-bold">{{ $scanMessage }}</h3>
                                        @if($scanStatus === 'halangan')
                                            <p class="text-xs opacity-80">Siswi tidak wajib sholat — sudah tercatat otomatis</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-sm font-mono opacity-90">{{ $scanTime }}</span>
                            </div>

                            @if($scannedStudent)
                            <div class="p-6 flex flex-col items-center text-center space-y-4">
                                @if(!empty($scannedStudent['foto']))
                                    <img src="{{ Storage::url($scannedStudent['foto']) }}" alt="Foto" class="w-32 h-32 rounded-full object-cover shadow-md border-4 border-gray-50 dark:border-gray-700">
                                @else
                                    <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shadow-md border-4 border-gray-50 dark:border-gray-700 text-gray-400">
                                        <x-heroicon-s-user class="w-16 h-16" />
                                    </div>
                                @endif
                                
                                <div>
                                    <h4 class="text-2xl font-black text-gray-900 dark:text-white mb-4">{{ $scannedStudent['name'] }}</h4>
                                    
                                    <div class="text-left w-full bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-600">
                                        <table class="w-full text-sm md:text-base">
                                            <tr>
                                                <td class="py-1.5 text-gray-500 dark:text-gray-400 font-medium w-1/3">Nama Lengkap</td>
                                                <td class="py-1.5 font-bold text-gray-900 dark:text-white">: {{ $scannedStudent['name'] }}</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1.5 text-gray-500 dark:text-gray-400 font-medium">NIS</td>
                                                <td class="py-1.5 font-bold text-gray-900 dark:text-white">: {{ $scannedStudent['nis'] }}</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1.5 text-gray-500 dark:text-gray-400 font-medium">Kelas</td>
                                                <td class="py-1.5 font-bold text-gray-900 dark:text-white">: Kelas {{ $scannedStudent['classroom'] }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center text-gray-400">
                        <x-heroicon-o-identification class="w-20 h-20 mx-auto mb-4 opacity-20" />
                        <p class="font-medium text-lg text-gray-500 dark:text-gray-400">Menunggu Scan...</p>
                        <p class="text-sm mt-2">Hasil scan akan muncul di sini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
