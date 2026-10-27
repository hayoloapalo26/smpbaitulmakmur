<x-filament-panels::page>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <div 
        x-data="{
            isCameraOpen: false,
            currentFacingMode: 'environment', // 'environment' (belakang) atau 'user' (depan)
            isTorchOn: false,
            supportsTorch: false,
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
            playFeedback(status) {
                // 1. Haptic Vibration untuk perangkat Android / Mobile
                if (navigator.vibrate) {
                    if (status === 'success') {
                        navigator.vibrate(100);
                    } else if (status === 'warning') {
                        navigator.vibrate([80, 50, 80]);
                    } else if (status === 'error') {
                        navigator.vibrate([150, 80, 150]);
                    }
                }

                // 2. Audio Beep
                if(!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                try {
                    const osc = this.audioCtx.createOscillator();
                    const gainNode = this.audioCtx.createGain();
                    
                    osc.connect(gainNode);
                    gainNode.connect(this.audioCtx.destination);
                    
                    if (status === 'success') {
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, this.audioCtx.currentTime); // A5
                        gainNode.gain.setValueAtTime(0.12, this.audioCtx.currentTime);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.15);
                    } else if (status === 'error') {
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(200, this.audioCtx.currentTime);
                        gainNode.gain.setValueAtTime(0.12, this.audioCtx.currentTime);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.4);
                    } else if (status === 'warning') {
                        osc.type = 'square';
                        osc.frequency.setValueAtTime(440, this.audioCtx.currentTime);
                        gainNode.gain.setValueAtTime(0.06, this.audioCtx.currentTime);
                        
                        gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime + 0.1);
                        gainNode.gain.setValueAtTime(0.06, this.audioCtx.currentTime + 0.15);
                        gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime + 0.25);
                        
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.3);
                    }
                } catch(e) {
                    console.log('Audio feedback error', e);
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
                
                const config = {
                    fps: 20,
                    qrbox: { width: 240, height: 240 },
                    aspectRatio: 1.0,
                    videoConstraints: {
                        facingMode: { ideal: this.currentFacingMode },
                        focusMode: 'continuous'
                    }
                };

                this.html5QrCode.start(
                    { facingMode: this.currentFacingMode }, 
                    config,
                    (decodedText, decodedResult) => {
                        $wire.set('nis', decodedText);
                        $wire.handleScan();
                        
                        // Pause briefly so it doesn't scan the same QR repeatedly
                        this.html5QrCode.pause();
                        setTimeout(() => {
                            if (this.isCameraOpen && this.html5QrCode) {
                                this.html5QrCode.resume();
                            }
                        }, 1600);
                    },
                    (errorMessage) => {
                        // ignore frame scan errors
                    }
                ).then(() => {
                    // Check torch support
                    try {
                        const track = this.html5QrCode.getRunningTrackCameraCapabilities();
                        if (track && track.torchFeature().isSupported()) {
                            this.supportsTorch = true;
                        }
                    } catch(e) {
                        this.supportsTorch = false;
                    }
                }).catch((err) => {
                    alert('Gagal mengaktifkan kamera. Pastikan izin kamera telah diberikan di browser Android / iOS Anda:\n' + err);
                    this.isCameraOpen = false;
                });
            },
            toggleCamera() {
                this.currentFacingMode = (this.currentFacingMode === 'environment' ? 'user' : 'environment');
                if (this.isCameraOpen) {
                    this.stopCamera(() => {
                        setTimeout(() => this.startCamera(), 200);
                    });
                }
            },
            async toggleTorch() {
                if (!this.html5QrCode || !this.supportsTorch) return;
                try {
                    this.isTorchOn = !this.isTorchOn;
                    await this.html5QrCode.applyVideoConstraints({
                        advanced: [{ torch: this.isTorchOn }]
                    });
                } catch(e) {
                    console.log('Torch error:', e);
                }
            },
            stopCamera(callback) {
                if(this.html5QrCode) {
                    this.html5QrCode.stop().then(() => {
                        this.html5QrCode.clear();
                        this.isCameraOpen = false;
                        this.isTorchOn = false;
                        this.focusInput();
                        if (callback) callback();
                    }).catch(err => {
                        console.error(err);
                        this.isCameraOpen = false;
                        if (callback) callback();
                    });
                } else {
                    this.isCameraOpen = false;
                    if (callback) callback();
                }
            }
        }"
        @scan-result.window="playFeedback($event.detail.status)"
        class="flex flex-col items-center justify-start min-h-[70vh] space-y-4 sm:space-y-6 relative touch-manipulation"
    >
        <!-- Loading Overlay -->
        <div wire:loading wire:target="handleScan" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs">
            <div class="flex flex-col items-center p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 animate-pulse max-w-xs mx-4">
                <x-heroicon-o-arrow-path class="w-10 h-10 text-primary-500 animate-spin mb-3" />
                <p class="text-base font-bold text-gray-900 dark:text-white">Memproses Data Siswa...</p>
                <p class="text-xs text-gray-400 mt-1 text-center">Mohon tunggu sebentar</p>
            </div>
        </div>

        <!-- Tipe Absen Indicator (Auto-detect dari jam) -->
        <div class="w-full max-w-5xl px-1">
            @php
                $nowJam = now()->hour * 60 + now()->minute;
                $activeType = match(true) {
                    $nowJam < 12 * 60  => 'masuk',
                    $nowJam < 14 * 60  => 'sholat_dhuhur',
                    $nowJam < 16 * 60  => 'sholat_ashar',
                    default            => 'pulang',
                };
                $absenSlots = [
                    ['key' => 'masuk',         'label' => 'Absen Masuk',   'waktu' => 'Pagi < 07:15', 'emoji' => '🏫'],
                    ['key' => 'sholat_dhuhur', 'label' => 'Dhuhur',        'waktu' => '12:00 – 13:59', 'emoji' => '🕌'],
                    ['key' => 'sholat_ashar',  'label' => 'Ashar',         'waktu' => '14:00 – 15:59', 'emoji' => '🌅'],
                    ['key' => 'pulang',        'label' => 'Absen Pulang',  'waktu' => 'Mulai 16:00',   'emoji' => '🏠'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-3">
                @foreach($absenSlots as $slot)
                <div class="rounded-xl p-2.5 sm:p-4 text-center border-2 transition-all {{ $activeType === $slot['key'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 shadow-sm ring-1 ring-emerald-500/30' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/60 opacity-60' }}">
                    <div class="text-xl sm:text-2xl mb-0.5">{{ $slot['emoji'] }}</div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">{{ $slot['label'] }}</p>
                    <p class="text-[10px] sm:text-xs text-gray-400 mt-0.5">{{ $slot['waktu'] }}</p>
                    @if($activeType === $slot['key'])
                        <span class="mt-1 sm:mt-1.5 inline-block text-[10px] sm:text-xs bg-emerald-600 text-white px-2 py-0.5 rounded-full font-bold animate-pulse">✓ AKTIF</span>
                    @endif
                </div>
                @endforeach
            </div>
            <p class="text-center text-[11px] sm:text-xs text-gray-400 mt-1.5 font-medium">Jam Sekolah: <strong>{{ now()->format('H:i') }} WIB</strong> (Otomatis)</p>
        </div>

        {{-- Hidden Input for Hardware Scanner (USB/Bluetooth Gun) --}}
        <input 
            x-ref="barcodeInput"
            type="text" 
            wire:model="nis" 
            wire:keydown.enter="handleScan"
            class="opacity-0 absolute -z-50 h-0 w-0"
            autocomplete="off"
            autofocus
        >

        <!-- Main Scanner Area: Two Columns on Desktop, Single Clean Stack on Mobile -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 w-full max-w-5xl">
            
            <!-- Left: Camera Viewfinder & Controls -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6 flex flex-col items-center">
                
                {{-- Camera Viewfinder Container (Optimized for Android & iOS) --}}
                <div id="qr-reader" class="w-full max-w-[320px] sm:max-w-sm aspect-square rounded-2xl overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600 bg-black min-h-[280px] sm:min-h-[320px] flex items-center justify-center relative shadow-inner">
                    <div x-show="!isCameraOpen" class="text-center text-gray-400 absolute inset-0 flex flex-col items-center justify-center p-4">
                        <x-heroicon-o-camera class="w-12 h-12 mb-2 opacity-40 text-primary-400 animate-pulse" />
                        <p class="text-sm font-semibold text-gray-300">Kamera Belum Aktif</p>
                        <p class="text-xs text-gray-500 mt-1">Ketuk tombol hijau di bawah untuk membuka kamera</p>
                    </div>
                </div>

                {{-- Action Controls (Touch Friendly) --}}
                <div class="flex flex-col sm:flex-row gap-2.5 w-full max-w-[320px] sm:max-w-sm mt-4">
                    {{-- Start / Stop Camera Button --}}
                    <button x-show="!isCameraOpen" @click="startCamera()" type="button" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-md flex justify-center items-center gap-2 select-none touch-manipulation">
                        <x-heroicon-s-play class="w-5 h-5" /> Buka Kamera HP
                    </button>
                    <button x-show="isCameraOpen" @click="stopCamera()" type="button" class="w-full bg-rose-600 hover:bg-rose-500 active:scale-95 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-md flex justify-center items-center gap-2 select-none touch-manipulation">
                        <x-heroicon-s-stop class="w-5 h-5" /> Tutup Kamera
                    </button>

                    {{-- Mobile Utility Controls: Switch Camera & Flashlight --}}
                    <div x-show="isCameraOpen" class="flex gap-2 w-full">
                        {{-- Switch Camera (Front/Back) --}}
                        <button @click="toggleCamera()" type="button" title="Ganti Kamera Depan/Belakang" class="flex-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2.5 px-3 rounded-xl transition-all text-xs flex justify-center items-center gap-1.5 active:scale-95">
                            <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4 text-primary-500" />
                            <span x-text="currentFacingMode === 'environment' ? 'Ganti Depan' : 'Ganti Belakang'"></span>
                        </button>

                        {{-- Flashlight / Torch (if supported) --}}
                        <button x-show="supportsTorch" @click="toggleTorch()" type="button" class="bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold py-2.5 px-3 rounded-xl transition-all text-xs flex justify-center items-center gap-1 active:scale-95">
                            <x-heroicon-o-bolt class="w-4 h-4" />
                            <span x-text="isTorchOn ? 'Senter Mati' : 'Senter Hidup'"></span>
                        </button>
                    </div>
                </div>
                
                <p class="text-[11px] text-gray-400 mt-4 text-center leading-relaxed">
                    Mendukung kamera HP (Android/iOS) dan scanner barcode USB/Bluetooth.
                </p>
            </div>

            <!-- Right: Student Scan Result Area -->
            <div class="flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 sm:p-6 min-h-[300px]">
                @if($scanStatus)
                    <div class="w-full transition-all transform duration-200 scale-100 opacity-100">
                        <div class="rounded-2xl border shadow-lg overflow-hidden bg-white dark:bg-gray-800 
                            @if($scanStatus === 'success') border-green-500 ring-2 ring-green-500/20
                            @elseif($scanStatus === 'warning') border-amber-500 ring-2 ring-amber-500/20
                            @elseif($scanStatus === 'halangan') border-pink-400 ring-2 ring-pink-400/20
                            @else border-red-500 ring-2 ring-red-500/20 @endif
                        ">
                            {{-- Result Status Header Banner --}}
                            <div class="px-4 sm:px-6 py-3.5 flex items-center justify-between text-white
                                @if($scanStatus === 'success') bg-green-600
                                @elseif($scanStatus === 'warning') bg-amber-600
                                @elseif($scanStatus === 'halangan') bg-pink-500
                                @else bg-red-600 @endif
                            ">
                                <div class="flex items-center gap-2.5">
                                    @if($scanStatus === 'success')
                                        <x-heroicon-o-check-circle class="w-7 h-7 sm:w-8 sm:h-8" />
                                    @elseif($scanStatus === 'halangan')
                                        <span class="text-2xl">🌸</span>
                                    @elseif($scanStatus === 'warning')
                                        <x-heroicon-o-exclamation-triangle class="w-7 h-7 sm:w-8 sm:h-8" />
                                    @else
                                        <x-heroicon-o-x-circle class="w-7 h-7 sm:w-8 sm:h-8" />
                                    @endif
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold leading-tight">{{ $scanMessage }}</h3>
                                        @if($scanStatus === 'halangan')
                                            <p class="text-[11px] opacity-90">Siswi tidak wajib sholat — tercatat otomatis</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs sm:text-sm font-mono opacity-90 font-bold">{{ $scanTime }}</span>
                            </div>

                            @if($scannedStudent)
                            <div class="p-4 sm:p-6 flex flex-col items-center text-center space-y-3 sm:space-y-4">
                                @if(!empty($scannedStudent['foto']))
                                    <img src="{{ Storage::url($scannedStudent['foto']) }}" alt="Foto" class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover shadow-md border-4 border-gray-100 dark:border-gray-700">
                                @else
                                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shadow-md border-4 border-gray-100 dark:border-gray-700 text-gray-400">
                                        <x-heroicon-s-user class="w-12 h-12 sm:w-14 sm:h-14" />
                                    </div>
                                @endif
                                
                                <div class="w-full">
                                    <h4 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mb-2 leading-snug">{{ $scannedStudent['name'] }}</h4>
                                    
                                    <div class="text-left w-full bg-gray-50 dark:bg-gray-700/40 p-3.5 sm:p-4 rounded-xl border border-gray-100 dark:border-gray-700 space-y-1.5 text-xs sm:text-sm">
                                        <div class="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
                                            <span class="text-gray-500 dark:text-gray-400">NIS</span>
                                            <span class="font-bold text-gray-900 dark:text-white font-mono">{{ $scannedStudent['nis'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
                                            <span class="text-gray-500 dark:text-gray-400">Kelas</span>
                                            <span class="font-bold text-gray-900 dark:text-white">{{ $scannedStudent['classroom'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-1">
                                            <span class="text-gray-500 dark:text-gray-400">Status Waktu</span>
                                            <span class="font-bold {{ $scanStatus === 'warning' ? 'text-amber-600' : 'text-emerald-600' }}">
                                                {{ $scanStatus === 'warning' ? 'Terlambat' : 'Tepat Waktu' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center text-gray-400 py-8">
                        <x-heroicon-o-identification class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 opacity-30 text-gray-400" />
                        <p class="font-bold text-base sm:text-lg text-gray-600 dark:text-gray-300">Menunggu Scan Siswa...</p>
                        <p class="text-xs sm:text-sm text-gray-400 mt-1">Arahkan kamera ke kartu QR siswa atau gunakan alat scanner</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
