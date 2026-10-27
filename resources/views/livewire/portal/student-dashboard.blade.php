<div class="max-w-7xl mx-auto py-4 sm:py-8 px-2.5 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
    
    <!-- Hero / Welcome Section -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-500 rounded-2xl shadow-xl overflow-hidden relative">
        <!-- Abstract Decoration -->
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-10 -mb-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
        
        <div class="p-5 sm:p-10 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left text-white">
                <p class="text-emerald-100 font-medium mb-1 tracking-wide uppercase text-xs sm:text-sm">Dashboard Siswa</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat datang, {{ $student->name }}!</h2>
                <p class="mt-2 text-emerald-50 max-w-xl text-xs sm:text-sm">Pantau perkembangan karakter, progres tahfidz, dan tugas harianmu di sini.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center shadow-inner min-w-[200px]">
                <p class="text-emerald-50 text-sm font-semibold uppercase tracking-wider mb-2">Skor Karakter</p>
                <div class="flex items-center justify-center gap-3">
                    <span class="text-5xl font-black text-white drop-shadow-md">{{ $student->character_score }}</span>
                    <span class="text-2xl text-emerald-200">/ 100</span>
                </div>
                <div class="mt-3">
                    @if($student->character_score >= 80)
                        <span class="inline-flex items-center gap-1 bg-green-400/20 text-green-100 px-3 py-1 rounded-full text-xs font-bold border border-green-400/30 shadow-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Sangat Baik
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-amber-400/20 text-amber-100 px-3 py-1 rounded-full text-xs font-bold border border-amber-400/30 shadow-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Perlu Peningkatan
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- E-Rapor Section -->
    <div class="mt-6">
        @if($student->is_administrasi_lunas)
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-emerald-900">E-Rapor Akademik</h3>
                    <p class="text-sm text-emerald-700">Administrasi telah lunas. Kamu dapat mengunduh E-Rapor.</p>
                </div>
                <a href="/portal/rapor/{{ $student->id }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download E-Rapor
                </a>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-red-900">Pembayaran Administrasi Diperlukan</h3>
                    <p class="text-sm text-red-700 mt-1">Mohon maaf, E-Rapor belum dapat diunduh. Hubungi orang tua/wali untuk menyelesaikan pembayaran administrasi.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- =========================================================== -->
    <!-- FORMULIR DCM (Daftar Cek Masalah) -->
    <!-- =========================================================== -->
    <div class="bg-white rounded-2xl overflow-hidden mt-8 mb-8" style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
        <!-- Header DCM -->
        <div style="background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%); padding: 1.5rem 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; rounded-xl; border-radius: 0.75rem;">
                        <svg style="width: 28px; height: 28px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: white; margin: 0; letter-spacing: -0.025em;">Formulir DCM — Daftar Cek Masalah</h3>
                        <p style="color: #eaddff; font-size: 0.875rem; margin: 0.25rem 0 0 0; font-weight: 500;">Problem Check List (Bimbingan Konseling) · Bersifat Pribadi & Rahasia</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    @if($dcmResponse)
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #a7f3d0; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                            <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Sudah Diisi · {{ \Carbon\Carbon::parse($dcmResponse->submitted_at)->format('d M Y') }}
                        </span>
                    @else
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fde68a; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                            <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Belum Diisi
                        </span>
                    @endif
                    <button wire:click="$toggle('showDcmForm')" style="display: inline-flex; align-items: center; gap: 0.5rem; background: white; color: #6d28d9; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; border: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s;">
                        @if($showDcmForm)
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            Tutup Formulir
                        @else
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ $dcmResponse ? 'Buka Kembali & Perbarui' : 'Isi Formulir Sekarang' }}
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Success -->
        @if(session('dcm_success'))
            <div style="margin: 1rem 1.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.75rem; padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                <svg style="width: 20px; height: 20px; color: #059669;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <p style="color: #065f46; font-weight: 600; font-size: 0.875rem; margin: 0;">{{ session('dcm_success') }}</p>
            </div>
        @endif

        <!-- Form DCM (collapsed/expanded) -->
        @if($showDcmForm)
        <div class="p-6 space-y-6">
            <!-- Petunjuk -->
            <div class="rounded-xl p-5" style="background-color: #faf5ff; border: 1px solid #e9d5ff;">
                <h4 class="font-bold mb-2 flex items-center gap-2" style="color: #4c1d95;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Petunjuk Pengisian
                </h4>
                <ul class="text-sm space-y-1 list-disc list-inside" style="color: #5b21b6;">
                    <li>Di bawah ini ada bermacam-macam kemungkinan masalah.</li>
                    <li>Pilihlah masalah-masalah yang <strong style="color: #4c1d95;">pernah atau sedang</strong> kamu hadapi dengan menekan tombol "Ya".</li>
                    <li>Jawablah dengan sejujur-jujurnya, kamu jangan khawatir atau malu.</li>
                    <li>Hasilnya akan membantu guru BK untuk membantu memecahkan masalahmu demi keberhasilan belajar dan masa depanmu.</li>
                    <li><strong style="color: #4c1d95;">Data ini bersifat rahasia</strong> dan hanya dapat dilihat oleh guru Bimbingan Konseling.</li>
                </ul>
            </div>

            <!-- Kategori pertanyaan -->
            @foreach($dcmCategories as $field => $category)
            <div class="rounded-xl overflow-hidden" x-data="{ open: false }" style="border: 1px solid #e5e7eb; background-color: #ffffff;">
                <!-- Accordion Header -->
                @php 
                    $selectedYa = 0;
                    foreach($dcmState[$field] ?? [] as $ans) {
                        if($ans === 'ya') $selectedYa++;
                    }
                @endphp
                <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-6 py-4 transition-colors group" style="background-color: #ffffff; border-bottom: 1px solid #f3f4f6;">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm" style="background-color: #f3e8ff; color: #7e22ce;">
                            {{ $selectedYa }}
                        </span>
                        <span class="font-semibold text-sm" style="color: #1f2937;">{{ $category['label'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($selectedYa > 0)
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: #f3e8ff; color: #9333ea;">
                                {{ $selectedYa }} dipilih
                            </span>
                        @endif
                        <svg class="w-5 h-5 transition-transform" style="color: #9ca3af;" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <!-- Accordion Content -->
                <div x-show="open" x-transition class="bg-white" style="border-top: 1px solid #f3f4f6;">
                    @foreach($category['questions'] as $no => $pertanyaan)
                    @php $currentState = $dcmState[$field][$no] ?? null; @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between px-5 py-4 gap-4" style="border-bottom: 1px solid #f9fafb;">
                        <div class="flex items-start gap-3 flex-1">
                            <span class="flex-shrink-0 w-7 h-7 rounded-full font-bold text-xs flex items-center justify-center mt-0.5" style="background-color: #f3f4f6; color: #4b5563;">{{ $no }}</span>
                            <p class="text-sm leading-relaxed font-medium mt-1" style="color: #374151;">{{ $pertanyaan }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" 
                                wire:click="setDcmAnswer('{{ $field }}', {{ $no }}, 'ya')"
                                style="{{ $currentState === 'ya' ? 'background-color: #7c3aed; color: white; border-color: #7c3aed; box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.4);' : 'background-color: white; color: #9ca3af; border-color: #e5e7eb;' }}"
                                class="px-5 py-2 rounded-xl border-2 text-sm font-bold transition-all w-24 text-center cursor-pointer">
                                Ya
                            </button>
                            <button type="button" 
                                wire:click="setDcmAnswer('{{ $field }}', {{ $no }}, 'tidak')"
                                style="{{ $currentState === 'tidak' ? 'background-color: #ef4444; color: white; border-color: #ef4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4);' : 'background-color: white; color: #9ca3af; border-color: #e5e7eb;' }}"
                                class="px-5 py-2 rounded-xl border-2 text-sm font-bold transition-all w-24 text-center cursor-pointer">
                                Tidak
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <!-- Uraian Terbuka -->
            <div class="rounded-xl p-5 space-y-5" style="background-color: #f9fafb; border: 1px solid #e5e7eb;">
                <h4 class="font-bold text-sm uppercase tracking-wide" style="color: #1f2937;">Uraikan Dengan Singkat</h4>
                
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: #374151;">
                        1. Masalah apa yang pernah/sedang kamu hadapi yang belum tercantum di atas?
                    </label>
                    <textarea wire:model="dcmUraian1" rows="3"
                        class="w-full rounded-xl text-sm resize-none shadow-sm"
                        style="border: 1px solid #d1d5db; padding: 0.75rem;"
                        placeholder="Tuliskan di sini..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: #374151;">
                        2. Masalah-masalah apa yang sedang kamu hadapi saat ini?
                    </label>
                    <textarea wire:model="dcmUraian2" rows="3"
                        class="w-full rounded-xl text-sm resize-none shadow-sm"
                        style="border: 1px solid #d1d5db; padding: 0.75rem;"
                        placeholder="Tuliskan di sini..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: #374151;">
                        3. Kalau kamu menghadapi masalah yang sulit, dengan siapakah biasanya kamu membicarakannya?
                    </label>
                    <textarea wire:model="dcmUraian3" rows="2"
                        class="w-full rounded-xl text-sm resize-none shadow-sm"
                        style="border: 1px solid #d1d5db; padding: 0.75rem;"
                        placeholder="Tuliskan di sini..."></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-2">
                <button wire:click="$toggle('showDcmForm')" type="button"
                    class="px-5 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer" style="border: 1px solid #d1d5db; color: #374151; background-color: #ffffff;">
                    Batal
                </button>
                <button wire:click="submitDcm" type="button"
                    class="px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-colors shadow-sm flex items-center gap-2 cursor-pointer" style="background-color: #7c3aed; border: none;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Formulir DCM
                </button>
            </div>
        </div>
        @elseif($dcmResponse)
        <!-- Summary jika sudah diisi dan form tertutup -->
        <div class="px-6 py-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($dcmCategories as $field => $category)
                @php $score = count($dcmResponse->$field ?? []); @endphp
                <div class="text-center p-3 rounded-xl border"
                     style="{{ $score > 0 ? 'background-color: #f3e8ff; border-color: #d8b4fe;' : 'background-color: #f9fafb; border-color: #f3f4f6;' }}">
                    <p class="text-2xl font-black" style="{{ $score > 0 ? 'color: #6b21a8;' : 'color: #9ca3af;' }}">{{ $score }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 leading-tight">{{ $category['short'] }}</p>
                </div>
                @endforeach
            </div>
            <p class="text-sm mt-4 text-center" style="color: #6b7280;">Total keseluruhan: <span style="font-weight: 800; color: #4b5563; font-size: 1.1rem;">{{ $dcmResponse->total_score }}</span> masalah yang dialami</p>
        </div>
        @endif
    </div>
    <!-- =========================================================== -->

    <!-- Main Grid -->
    @livewire(\App\Livewire\NewsWidget::class)

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Column: Tasks & Tahfidz -->
        <div class="lg:col-span-7 space-y-8">
            
            <!-- Tahfidz Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full blur-3xl -z-10 -mr-10 -mt-10"></div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </span>
                        Progres Tahfidz
                    </h3>
                    <span class="text-3xl font-black text-blue-600 drop-shadow-sm">{{ number_format($tahfidzScore ?? 0, 1) }}<span class="text-sm font-medium text-gray-400 ml-1">avg</span></span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50/50 border border-gray-100 p-5 rounded-xl text-center hover:bg-gray-50 transition">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Ziyadah</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $quranZiyadah }} <span class="text-sm font-normal text-gray-400">baris</span></p>
                    </div>
                    <div class="bg-gray-50/50 border border-gray-100 p-5 rounded-xl text-center hover:bg-gray-50 transition">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">Muraja'ah</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $quranMurajaah }} <span class="text-sm font-normal text-gray-400">baris</span></p>
                    </div>
                </div>
            </div>

            <!-- To-Do List Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6">
                    <span class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </span>
                    Tugas Harian (To-Do List)
                </h3>
                
                <div class="flex gap-2 mb-6">
                    <input wire:model="todoTask" type="text" wire:keydown.enter="addTodo" class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-4 py-3 bg-gray-50" placeholder="Ketik tugas baru lalu tekan enter...">
                    <button wire:click="addTodo" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors shadow-sm whitespace-nowrap">Tambah</button>
                </div>
                
                @if($todos->isEmpty())
                    <div class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path></svg>
                        <p class="text-gray-500 font-medium">Yeay! Semua tugas sudah selesai.</p>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach($todos as $todo)
                            <li class="group flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-100 hover:shadow-sm transition-all bg-white {{ $todo->is_done ? 'opacity-60 bg-gray-50' : '' }}">
                                <label class="flex items-center gap-4 cursor-pointer flex-1">
                                    <input type="checkbox" wire:click="toggleTodo({{ $todo->id }})" {{ $todo->is_done ? 'checked' : '' }} class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer transition">
                                    <span class="font-medium {{ $todo->is_done ? 'line-through text-gray-400' : 'text-gray-700' }}">{{ $todo->task }}</span>
                                </label>
                                <button wire:click="deleteTodo({{ $todo->id }})" class="text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            
        </div>

        <!-- Right Column: Logs & Reports -->
        <div class="lg:col-span-5 space-y-8">
            
            <!-- Riwayat Karakter -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[400px]">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                    <span class="p-1.5 bg-emerald-100 text-emerald-600 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </span>
                    Riwayat Karakter
                </h3>
                
                <div class="overflow-y-auto pr-2 flex-1 space-y-4 custom-scrollbar">
                    @forelse($scoreLogs as $log)
                        <div class="flex items-start justify-between gap-4">
                            <div class="bg-gray-50 p-2 rounded-lg flex-shrink-0 mt-1 {{ $log->point_change > 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }}">
                                @if($log->point_change > 0)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $log->description }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-500">{{ ucfirst(str_replace('_', ' ', $log->source)) }}</span>
                                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->tanggal)->format('d M Y') }}</span>
                                </div>
                            </div>
                            <div class="font-black text-lg {{ $log->point_change > 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $log->point_change > 0 ? '+'.$log->point_change : $log->point_change }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <p class="text-sm text-gray-400 font-medium">Belum ada catatan karakter.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Laporan BK -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[320px]">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                    <span class="p-1.5 bg-amber-100 text-amber-600 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </span>
                    Catatan Bimbingan (BK)
                </h3>
                
                <div class="overflow-y-auto pr-2 flex-1 space-y-3 custom-scrollbar">
                    @forelse($bkReports as $report)
                        <div class="bg-amber-50/50 border border-amber-100 p-4 rounded-xl relative">
                            <div class="absolute top-4 right-4 w-2 h-2 rounded-full bg-amber-400"></div>
                            <p class="text-xs text-amber-600 font-bold mb-1">{{ \Carbon\Carbon::parse($report->tanggal)->format('d F Y') }}</p>
                            <p class="text-sm font-medium text-gray-800">{{ $report->isi_laporan }}</p>
                        </div>
                    @empty
                        <div class="text-center py-10 flex flex-col items-center justify-center h-full">
                            <svg class="w-10 h-10 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-gray-400 font-medium">Tidak ada catatan khusus dari BK.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
    </style>
</div>

