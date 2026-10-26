<x-filament-widgets::widget>
    <x-filament::section class="!p-0 overflow-hidden" style="border: none; box-shadow: none; background: transparent;">
        
        @php
            $getPercentageColor = function($percentage) {
                if ($percentage <= 50) {
                    $p = $percentage / 50;
                    $r = round(239 + (245 - 239) * $p);
                    $g = round(68 + (158 - 68) * $p);
                    $b = round(68 + (11 - 68) * $p);
                } else {
                    $p = ($percentage - 50) / 50;
                    $r = round(245 + (16 - 245) * $p);
                    $g = round(158 + (185 - 158) * $p);
                    $b = round(11 + (129 - 11) * $p);
                }
                return $r . ', ' . $g . ', ' . $b;
            };
        @endphp

        <div x-data="{ expanded: false }">
            <!-- Top Banner (Vibrant Sunset Orange Gradient) -->
            <div style="position: relative; overflow: hidden; border-radius: 1.5rem; padding: 2.5rem; color: #ffffff; box-shadow: 0 20px 25px -5px rgba(230, 81, 0, 0.4); margin-bottom: 2rem; background: linear-gradient(135deg, #FF8C00 0%, #E65100 100%); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 2rem;">
                
                <!-- Background Decoration -->
                <div style="position: absolute; right: -5rem; top: -5rem; opacity: 0.1;">
                    <svg width="300" height="300" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="#ffffff"/></svg>
                </div>
                <div style="position: absolute; left: -2.5rem; bottom: -2.5rem; opacity: 0.1;">
                    <svg width="200" height="200" viewBox="0 0 100 100"><rect width="80" height="80" x="10" y="10" fill="#ffffff" rx="20" transform="rotate(45 50 50)"/></svg>
                </div>
                
                <div style="position: relative; z-index: 10;">
                    @php
                        $hour = now()->timezone('Asia/Jakarta')->format('H');
                        if ($hour < 11) {
                            $greeting = 'Selamat Pagi';
                        } elseif ($hour < 15) {
                            $greeting = 'Selamat Siang';
                        } elseif ($hour < 18) {
                            $greeting = 'Selamat Sore';
                        } else {
                            $greeting = 'Selamat Malam';
                        }
                    @endphp
                    <h1 style="font-size: 2.5rem; font-weight: 900; margin: 0; letter-spacing: -0.02em; text-shadow: 0 2px 10px rgba(0,0,0,0.15);">
                        {{ $greeting }}, {{ ucwords(str_replace('_', ' ', auth()->user()->roles->first()->name ?? 'Pengurus Yayasan')) }}
                    </h1>
                </div>
                
                
                    @php
                        $overallPercentage = $this->overallInstitutionProgress;
                        $circumference = 251.32;
                        $dashoffset = $circumference - ($overallPercentage / 100) * $circumference;
                        $rgbColor = $getPercentageColor($overallPercentage);
                    @endphp
                    <button type="button" @click="expanded = !expanded" style="cursor: pointer; background: transparent; border: none; padding: 0; outline: none; transition: transform 0.3s ease; width: 100%; max-width: 32rem;" class="hover:scale-105">
                        
                        <!-- Premium Crisp White Box for Speedometer -->
                        <div style="background: #ffffff; border-radius: 2rem; padding: 2rem 2rem 1.5rem 2rem; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); border: 1px solid rgba(255,255,255,0.8); position: relative;">
                            
                            <div style="position: relative; width: 24rem; height: 12rem; margin: 0 auto;">
                                <svg style="width: 100%; height: 100%; overflow: visible;" viewBox="0 0 200 100">
                                    <!-- Background arc -->
                                    <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#f3f4f6" stroke-width="16" stroke-linecap="round" />
                                    <!-- Progress arc -->
                                    <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="rgb({{ $rgbColor }})" stroke-width="16" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashoffset }}" style="transition: all 1.5s ease-out;" />
                                </svg>
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; padding-bottom: 0.5rem;">
                                    <span style="font-size: 5rem; font-weight: 900; color: #1f2937; line-height: 1;">{{ $overallPercentage }}%</span>
                                    <span style="font-size: 0.875rem; font-weight: 800; color: #E65100; letter-spacing: 0.15em; margin-top: 1rem;">KETERCAPAIAN GLOBAL</span>
                                </div>
                            </div>

                        </div>
                        
                        <div style="margin-top: 1.5rem; font-size: 0.875rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; justify-content: center; gap: 0.5rem; opacity: 0.9;">
                            <span x-text="expanded ? 'Sembunyikan detail' : 'Klik untuk melihat detail lembaga'"></span>
                            <svg x-show="!expanded" style="width: 1.2rem; height: 1.2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            <svg x-show="expanded" style="width: 1.2rem; height: 1.2rem; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                        </div>
                    </button>
                
            </div>

            <div x-show="expanded" x-transition.duration.500ms>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    @foreach($this->institutions as $institution)
                        @php
                            $percentage = $institution['percentage'];
                            $circ = 2 * pi() * 32;
                            $dashoffset = $circ - ($percentage / 100) * $circ;
                            $rgbColor = $getPercentageColor($percentage);
                            $baseHex = 'rgb(' . $rgbColor . ')';
                        @endphp

                        <a href="{{ url('/yayasan/progress/' . $institution['id']) }}" class="yayasan-card group">
                            <!-- Progress Circle -->
                            <div class="yayasan-circle group-hover:scale-105">
                                <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 80 80">
                                    <circle cx="40" cy="40" r="32" stroke="#f3f4f6" stroke-width="8" fill="none" />
                                    <circle cx="40" cy="40" r="32" stroke="{{$baseHex}}" stroke-width="8" fill="none" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $dashoffset }}" stroke-linecap="round" style="transition: all 1s ease-out;" />
                                </svg>
                                <span class="yayasan-percentage" style="color: {{$baseHex}}">{{ $percentage }}%</span>
                            </div>

                            <!-- Details -->
                            <div style="flex: 1; z-index: 10; display: flex; flex-direction: column; justify-content: center;">
                                <div style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 800; letter-spacing: 0.05em; color: #ffffff; margin-bottom: 0.5rem; width: max-content; background: linear-gradient(135deg, #FF8C00 0%, #E65100 100%);">
                                    LEMBAGA
                                </div>
                                <h3 class="yayasan-title">{{ $institution['name'] }}</h3>
                                
                                <div style="display: flex; gap: 1rem; margin-bottom: 0.75rem; margin-top: 0.25rem;">
                                    <div class="yayasan-stats">
                                        <svg style="width: 1rem; height: 1rem; color: #FF8C00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        {{ number_format($institution['student_count'] ?? 0) }} Siswa
                                    </div>
                                    <div class="yayasan-stats">
                                        <svg style="width: 1rem; height: 1rem; color: #FF8C00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        {{ number_format($institution['teacher_count'] ?? 0) }} Guru
                                    </div>
                                </div>
                                <p class="yayasan-subtitle group-hover:text-[#E65100]">
                                    Lihat Detail 9 Pilar 
                                    <svg style="width: 1rem; height: 1rem; transition: transform 0.3s ease;" class="group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin-top: 3rem; margin-bottom: 1.5rem;" class="dark:text-white">Progres Karyawan Yayasan</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    @php
                        $summary = $this->employeeSummary;
                        $percentage = $summary['percentage'];
                        $circ = 2 * pi() * 32;
                        $dashoffset = $circ - ($percentage / 100) * $circ;
                        $rgbColor = $getPercentageColor($percentage);
                        $baseHex = 'rgb(' . $rgbColor . ')';
                    @endphp
                    <a href="{{ url('/yayasan/yayasan-employees') }}" class="yayasan-card group">
                        <div class="yayasan-circle group-hover:scale-105">
                            <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 80 80">
                                <circle cx="40" cy="40" r="32" stroke="#f3f4f6" stroke-width="8" fill="none" />
                                <circle cx="40" cy="40" r="32" stroke="{{$baseHex}}" stroke-width="8" fill="none" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $dashoffset }}" stroke-linecap="round" style="transition: all 1s ease-out;" />
                            </svg>
                            <span class="yayasan-percentage" style="color: {{$baseHex}}">{{ $percentage }}%</span>
                        </div>
                        <div style="flex: 1; z-index: 10; display: flex; flex-direction: column; justify-content: center;">
                            <div style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 800; letter-spacing: 0.05em; color: #ffffff; margin-bottom: 0.5rem; width: max-content; background: linear-gradient(135deg, #FF8C00 0%, #E65100 100%);">
                                TIM YAYASAN
                            </div>
                            <h3 class="yayasan-title">{{ $summary['name'] }}</h3>
                            <div style="font-size: 0.875rem; font-weight: 600; color: #6b7280; margin-top: 0.25rem;" class="dark:text-gray-400">
                                Total: {{ $summary['employee_count'] }} Karyawan
                            </div>
                        </div>
                    </a>
                </div>

                <h2 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin-top: 3rem; margin-bottom: 1.5rem;" class="dark:text-white">Progres Fasilitas Yayasan</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    @php
                        $library = $this->library;
                        $percentage = $library['percentage'];
                        $circ = 2 * pi() * 32;
                        $dashoffset = $circ - ($percentage / 100) * $circ;
                        $rgbColor = $getPercentageColor($percentage);
                        $baseHex = 'rgb(' . $rgbColor . ')';
                    @endphp
                    <a href="{{ url('/yayasan/library-assessments') }}" class="yayasan-card group">
                        <div class="yayasan-circle group-hover:scale-105">
                            <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 80 80">
                                <circle cx="40" cy="40" r="32" stroke="#f3f4f6" stroke-width="8" fill="none" />
                                <circle cx="40" cy="40" r="32" stroke="{{$baseHex}}" stroke-width="8" fill="none" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $dashoffset }}" stroke-linecap="round" style="transition: all 1s ease-out;" />
                            </svg>
                            <span class="yayasan-percentage" style="color: {{$baseHex}}">{{ $percentage }}%</span>
                        </div>
                        <div style="flex: 1; z-index: 10; display: flex; flex-direction: column; justify-content: center;">
                            <div style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.625rem; font-weight: 800; letter-spacing: 0.05em; color: #ffffff; margin-bottom: 0.5rem; width: max-content; background: linear-gradient(135deg, #FF8C00 0%, #E65100 100%);">
                                FASILITAS
                            </div>
                            <h3 class="yayasan-title">{{ $library['name'] }}</h3>
                            <div style="font-size: 0.875rem; font-weight: 600; color: #6b7280; margin-top: 0.25rem;" class="dark:text-gray-400">
                                Penilaian & Evaluasi
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>
        <style>
            .yayasan-card {
                text-decoration: none;
                display: flex;
                gap: 1.5rem;
                align-items: center;
                position: relative;
                overflow: hidden;
                padding: 1.5rem;
                border-radius: 1.25rem;
                background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                border: 1px solid rgba(255, 140, 0, 0.1);
                transition: all 0.3s ease;
            }
            .yayasan-card:hover {
                transform: translateY(-4px);
                border-color: rgba(255, 140, 0, 0.4);
                box-shadow: 0 10px 15px -3px rgba(255, 140, 0, 0.15), 0 4px 6px -2px rgba(255, 140, 0, 0.05);
            }
            .yayasan-circle {
                position: relative;
                width: 6rem;
                height: 6rem;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #ffffff;
                border-radius: 50%;
                box-shadow: 0 4px 8px -2px rgba(0,0,0,0.05), inset 0 2px 4px rgba(0,0,0,0.02);
                z-index: 10;
                transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .yayasan-percentage {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                font-weight: 900;
            }
            .yayasan-title {
                font-weight: 800;
                color: #1f2937;
                line-height: 1.2;
                font-size: 1.25rem;
                margin: 0 0 0.25rem 0;
            }
            .yayasan-subtitle {
                font-size: 0.875rem;
                font-weight: 600;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 0.35rem;
                margin: 0;
                transition: all 0.2s ease;
            }
            .yayasan-stats {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                font-size: 0.8rem;
                font-weight: 600;
                color: #4b5563;
            }

            /* DARK MODE OVERRIDES */
            .dark .yayasan-card {
                background: #1f2937;
                border-color: rgba(255, 255, 255, 0.05);
            }
            .dark .yayasan-circle {
                background-color: #111827;
            }
            .dark .yayasan-title {
                color: #f9fafb;
            }
            .dark .yayasan-subtitle, .dark .yayasan-stats {
                color: #9ca3af;
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>



