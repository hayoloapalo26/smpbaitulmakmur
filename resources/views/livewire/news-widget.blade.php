<x-filament-widgets::widget>
    <x-filament::section class="!p-0 overflow-hidden" style="border: 1px solid rgba(255, 130, 0, 0.2); box-shadow: 0 10px 15px -3px rgba(255, 130, 0, 0.1);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #FF8200 0%, #D84315 100%); padding: 1.5rem 2rem; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 1rem;">
                    <svg style="width: 1.75rem; height: 1.75rem; color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                </div>
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; margin: 0; letter-spacing: 0.02em;">Berita & Pengumuman Terbaru</h2>
                    <p style="font-size: 0.875rem; opacity: 0.9; margin: 0; margin-top: 0.25rem;">Informasi resmi dari Yayasan Baitul Makmur</p>
                </div>
            </div>
            <div style="font-size: 0.875rem; font-weight: 600; background: rgba(255,255,255,0.15); padding: 0.5rem 1rem; border-radius: 9999px;">
                {{ count($this->news) }} Berita
            </div>
        </div>

        <!-- News List -->
        <div style="background: #ffffff; padding: 1rem;" class="dark:bg-gray-900">
            @if(count($this->news) > 0)
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($this->news as $item)
                        <div style="display: flex; gap: 1.5rem; padding: 1.5rem; border-radius: 1rem; border: 1px solid #f3f4f6; transition: all 0.3s ease;" class="hover:shadow-md hover:border-orange-200 dark:border-gray-800 dark:hover:border-orange-900 group">
                            
                            @if($item->image)
                                <div style="width: 8rem; height: 8rem; flex-shrink: 0; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                    <img src="{{ Storage::url($item->image) }}" alt="News Image" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" class="group-hover:scale-110">
                                </div>
                            @else
                                <div style="width: 8rem; height: 8rem; flex-shrink: 0; border-radius: 0.75rem; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,130,0,0.2);">
                                    <svg style="width: 3rem; height: 3rem; color: #FF8200; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                                </div>
                            @endif

                            <div style="flex: 1; display: flex; flex-direction: column;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 700; color: #6b7280; letter-spacing: 0.05em; text-transform: uppercase;">
                                    <span style="color: #FF8200; display: flex; align-items: center; gap: 0.25rem;">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        {{ $item->created_at->format('d M Y') }}
                                    </span>
                                    <span>&bull;</span>
                                    <span style="display: flex; align-items: center; gap: 0.25rem;">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $item->author->name ?? 'Admin' }}
                                    </span>
                                </div>
                                
                                <h3 style="font-size: 1.25rem; font-weight: 800; color: #1f2937; margin: 0 0 0.75rem 0; line-height: 1.3;" class="dark:text-white group-hover:text-[#D84315] transition-colors">
                                    {{ $item->title }}
                                </h3>
                                
                                <!-- Content preview -->
                                <div style="font-size: 0.875rem; color: #4b5563; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" class="dark:text-gray-400">
                                    {!! strip_tags($item->content) !!}
                                </div>
                                
                                <div style="margin-top: auto; padding-top: 1rem;">
                                    <button wire:click="openNews({{ $item->id }})" style="background: none; border: none; padding: 0; color: #FF8200; font-weight: 700; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" class="hover:text-[#D84315]">
                                        Baca Selengkapnya
                                        <svg style="width: 1rem; height: 1rem; transition: transform 0.2s;" class="group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding: 4rem 2rem; text-align: center; color: #6b7280;">
                    <svg style="width: 4rem; height: 4rem; margin: 0 auto 1rem auto; color: #d1d5db;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    <p style="font-size: 1.125rem; font-weight: 600; color: #374151;" class="dark:text-gray-300">Belum ada pengumuman</p>
                    <p style="font-size: 0.875rem; margin-top: 0.25rem;">Berita dan pengumuman terbaru akan muncul di sini.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
    
    <!-- Alpine JS Modal for Full News -->
    <div x-data="{ open: false, title: '', content: '', author: '', date: '', image: '' }" 
         @open-news-modal.window="open = true; title = $event.detail.title; content = $event.detail.content; author = $event.detail.author; date = $event.detail.date; image = $event.detail.image;"
         x-show="open" 
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="open = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full"
                 style="border: 1px solid rgba(255, 130, 0, 0.2);">
                
                <div class="bg-white dark:bg-gray-800 p-0">
                    <!-- Image Header -->
                    <div x-show="image" class="w-full h-64 bg-gray-100 dark:bg-gray-900 overflow-hidden relative">
                        <img :src="image" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    </div>
                    
                    <div class="px-8 pt-8 pb-4">
                        <div class="flex items-center gap-4 text-sm font-bold text-orange-500 mb-4 uppercase tracking-wider">
                            <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <span x-text="date"></span></span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> <span x-text="author"></span></span>
                        </div>
                        <h3 class="text-3xl leading-tight font-black text-gray-900 dark:text-white mb-6" x-text="title"></h3>
                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed" x-html="content"></div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-4 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="open = false" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 text-base font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm" style="background: linear-gradient(135deg, #FF8200 0%, #D84315 100%);">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openNewsModal(id) {
            // Fetch news data dynamically via a Livewire call or a simple JS array mapped from blade
            // To make it simple, we can emit an event containing the data since we already have $this->news
            // But we can't easily pass the complex HTML content without Alpine getting messy if there are quotes.
            // Let's create a JS array dictionary of all news on the page.
        }
    </script>
</x-filament-widgets::widget>


