<?php
$file = 'resources/views/filament/staff/pages/dashboard.blade.php';
$content = file_get_contents($file);

$newHeaderAndWidgets = <<<'BLADE'
        {{-- 1. HEADER & LEGEND CARD --}}
        <div class="relative overflow-hidden rounded-3xl bmos-dashboard-header p-8 text-white shadow-xl">
            <!-- Decorative SVG -->
            <div class="absolute -right-20 -top-20 opacity-20">
                <svg width="300" height="300" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="10"/><circle cx="50" cy="50" r="20" fill="currentColor"/></svg>
            </div>
            <div class="absolute -left-10 -bottom-10 opacity-10">
                <svg width="200" height="200" viewBox="0 0 100 100"><rect width="80" height="80" x="10" y="10" fill="currentColor" rx="20" transform="rotate(45 50 50)"/></svg>
            </div>
            
            <div class="relative z-10 flex flex-col lg:flex-row gap-8 justify-between">
                <div class="flex-1">
                    <h2 class="text-3xl font-black mb-2 drop-shadow-md" style="color: #FCFBF8;">Dashboard Manajemen Sekolah</h2>
                    <p class="mb-6 font-medium" style="color: #F4EEDC;">Selamat datang, <b>{{ auth()->user()->name }}</b>. Berikut adalah ringkasan progres sekolah kita.</p>
                    
                    {{-- TOTAL SISWA & GURU --}}
                    <div class="flex flex-col sm:flex-row gap-4 mb-6">
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 flex items-center gap-4 flex-1 shadow-sm">
                            <div class="p-3 rounded-xl shadow-inner" style="background-color: #D8C68D; color: #4A100B;">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wider font-extrabold opacity-90" style="color: #F4EEDC;">Total Siswa Aktif</p>
                                <p class="text-3xl font-black drop-shadow-sm" style="color: #FCFBF8;">{{ ->studentCount }}</p>
                            </div>
                        </div>
                        
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 flex items-center gap-4 flex-1 shadow-sm">
                            <div class="p-3 rounded-xl shadow-inner" style="background-color: #D8C68D; color: #4A100B;">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wider font-extrabold opacity-90" style="color: #F4EEDC;">Total Guru Pengajar</p>
                                <p class="text-3xl font-black drop-shadow-sm" style="color: #FCFBF8;">{{ ->teacherCount }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- HIGHLIGHT PENGUMUMAN YAYASAN --}}
                    <div class="bg-gradient-to-r from-amber-600/30 to-orange-500/10 backdrop-blur-md rounded-2xl p-4 border border-amber-400/40 mb-6 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-xl mt-0.5 bg-gradient-to-br from-amber-300 to-orange-400 text-amber-900 shadow-md border border-amber-200">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.536l9-1.8V12a4.369 4.369 0 00-1-.114c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path></svg>
                            </div>
                            <div>
                                <p class="font-bold text-xs mb-1.5 uppercase tracking-wider text-amber-200 drop-shadow-md">Info & Pengumuman Terbaru</p>
                                <div class="text-sm text-white font-medium leading-relaxed prose prose-invert prose-sm max-w-none [&>p]:mb-1 last:[&>p]:mb-0">
                                    {!! str()->markdown(->latestNews) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 inline-block shadow-inner mt-2">
                        <p class="font-bold text-sm mb-3 flex items-center gap-2 drop-shadow-sm" style="color: #FCFBF8;">
                            <svg class="w-5 h-5" style="color: #D8C68D;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            Kode Penanggung Jawab (PIC)
                        </p>
                        <div class="flex flex-wrap gap-2 text-xs font-semibold" style="color: #34332E;">
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #D8C68D;">KS: Kepala Sekolah</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #F4EEDC;">WKr: Waka Kurikulum</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #D8C68D;">WKs: Waka Kesiswaan</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #F4EEDC;">SP: Sarpras</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #D8C68D;">Hm: Humas</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #F4EEDC;">BK: Guru BK</span>
                            <span class="px-2.5 py-1 rounded-lg shadow-sm" style="background-color: #D8C68D;">TU: Tata Usaha</span>
                        </div>
                    </div>
                </div>

                <!-- OVERALL STATS (ENLARGED) -->
                @php
                     = collect(->pillarsData)->pluck('tasks')->flatten();
                     = collect(->pillarsData)->sum('selesai');
                     = collect(->pillarsData)->sum('total');
                     =  > 0 ? round(( / ) * 100) : 0;
                    
                     = 65;
                     = 2 * pi() * ;
                     =  - ( / 100) * ;
                @endphp
                <div class="flex flex-col items-center justify-center bg-white/10 backdrop-blur-xl p-8 rounded-3xl border border-white/20 shadow-2xl self-start md:sticky md:top-8" style="min-width: 320px;">
                    <p class="text-xl font-bold mb-4 tracking-wide uppercase text-center drop-shadow-md" style="color: #FCFBF8;">Ketercapaian<br>Global</p>
                    <div class="relative w-48 h-48 flex items-center justify-center drop-shadow-2xl my-2">
                        <!-- Background Circle -->
                        <svg class="absolute inset-0 w-full h-full transform -rotate-90" viewBox="0 0 160 160">
                            <circle cx="80" cy="80" r="65" stroke="rgba(255,255,255,0.15)" stroke-width="14" fill="none" />
                            <!-- Progress Circle -->
                            <circle cx="80" cy="80" r="65" stroke="#D8C68D" stroke-width="14" fill="none" stroke-dasharray="{{  }}" stroke-dashoffset="{{  }}" stroke-linecap="round" class="transition-all duration-1000 ease-out drop-shadow-lg" />
                        </svg>
                        <span class="text-6xl font-black drop-shadow-lg" style="color: #FCFBF8;">{{  }}%</span>
                    </div>
                    <p class="mt-6 text-base font-bold bg-black/20 px-6 py-2 rounded-full shadow-inner" style="color: #FCFBF8;">{{  }} / {{  }} Tugas Tuntas</p>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasRole('yayasan'))
            <div class="mb-6">
                @livewire(\App\Filament\Yayasan\Widgets\InstitutionOverviewWidget::class)
            </div>
        @endif

        {{-- WIDGETS STATISTIK (Good News, Bad News, Top Guru) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Widget Good News --}}
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-6 shadow-md border border-green-400/50 text-white flex flex-col justify-between hover:shadow-xl hover:-translate-y-1 transition-all">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black tracking-wide drop-shadow-sm">Ringkasan Good News</h3>
                    </div>
                    <div class="text-sm leading-relaxed text-green-50 font-medium prose prose-invert prose-sm max-w-none [&>p]:mb-3 last:[&>p]:mb-0">
                        {!! str()->markdown(->goodNews) !!}
                    </div>
                </div>
            </div>

            {{-- Widget Bad News --}}
            <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-6 shadow-md border border-red-400/50 text-white flex flex-col justify-between hover:shadow-xl hover:-translate-y-1 transition-all">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border border-white/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black tracking-wide drop-shadow-sm">Ringkasan Bad News</h3>
                    </div>
                    <div class="text-sm leading-relaxed text-red-50 font-medium prose prose-invert prose-sm max-w-none [&>p]:mb-3 last:[&>p]:mb-0">
                        {!! str()->markdown(->badNews) !!}
                    </div>
                </div>
            </div>

            {{-- Widget Peringkat Guru --}}
BLADE;

$pattern = '/\{\{-- 1\. HEADER & LEGEND CARD --\}\}.*?\{\{-- Widget Peringkat Guru --\}\}/s';
$content = preg_replace($pattern, $newHeaderAndWidgets, $content);

file_put_contents($file, $content);
echo "Dashboard updated successfully.\n";
?>
