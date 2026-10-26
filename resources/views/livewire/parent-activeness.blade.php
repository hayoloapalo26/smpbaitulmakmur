<x-filament-widgets::widget>
    <x-filament::card class="relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-6 h-6 text-primary-500" />
                Tren Keaktifan Orang Tua
            </h2>
            <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                <input type="date" wire:model.live="startDate" class="border-none bg-transparent text-sm focus:ring-0 text-gray-600 dark:text-gray-300">
                <span class="text-gray-400">s/d</span>
                <input type="date" wire:model.live="endDate" class="border-none bg-transparent text-sm focus:ring-0 text-gray-600 dark:text-gray-300">
            </div>
        </div>

        <!-- Global Percentage -->
        @php
            $pct = $this->globalPercentage;
            if ($pct >= 80) $color = 'from-green-400 to-emerald-600';
            elseif ($pct >= 50) $color = 'from-yellow-400 to-orange-500';
            else $color = 'from-red-500 to-rose-600';
        @endphp
        <div wire:click="openInstitutionsModal" class="cursor-pointer group flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-primary-500 transition-all">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rata-rata Keaktifan (Semua Lembaga)</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-5xl font-black bg-gradient-to-br {{ $color }} bg-clip-text text-transparent group-hover:scale-105 transition-transform">
                        {{ $pct }}%
                    </span>
                    <span class="text-sm text-gray-400">orang tua aktif dalam rentang waktu terpilih</span>
                </div>
            </div>
            <div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm group-hover:bg-primary-50 transition-colors">
                <x-heroicon-o-arrow-right class="w-6 h-6 text-gray-400 group-hover:text-primary-600" />
            </div>
        </div>

        <!-- MODALS -->

        <!-- Institutions Modal -->
        <x-filament::modal id="institutions-modal" display-classes="block" width="2xl">
            <x-slot name="heading">
                Keaktifan Orang Tua Per Lembaga
            </x-slot>
            <x-slot name="description">
                Rentang: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </x-slot>

            <div class="space-y-4 my-4">
                @foreach($this->institutionsData as $inst)
                    @php
                        $ipct = $inst['percentage'];
                        if ($ipct >= 80) $icolor = 'bg-gradient-to-r from-green-400 to-emerald-500';
                        elseif ($ipct >= 50) $icolor = 'bg-gradient-to-r from-yellow-400 to-orange-400';
                        else $icolor = 'bg-gradient-to-r from-red-500 to-rose-500';
                    @endphp
                    <div wire:click="viewInstitutionDetail({{ $inst['id'] }})" class="cursor-pointer bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-primary-500 hover:shadow-md transition-all">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $inst['name'] }}</span>
                            <span class="font-black {{ $ipct >= 80 ? 'text-green-600' : ($ipct >= 50 ? 'text-orange-500' : 'text-red-500') }}">{{ $ipct }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 mb-2 overflow-hidden">
                            <div class="{{ $icolor }} h-3 rounded-full transition-all duration-1000" style="width: {{ $ipct }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $inst['active_records'] }} Check-in dari total {{ $inst['expected_records'] }} harapan</span>
                            <span>Siswa: {{ $inst['total_students'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="flex justify-end">
                <x-filament::button wire:click="closeModals" color="gray">Tutup</x-filament::button>
            </div>
        </x-filament::modal>

        <!-- Detail Modal -->
        <x-filament::modal id="detail-modal" display-classes="block" width="4xl">
            <x-slot name="heading">
                Detail Tren Keaktifan Orang Tua
            </x-slot>
            <x-slot name="description">
                Total {{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1 }} Hari ({{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }})
            </x-slot>

            <div class="overflow-x-auto my-4 max-h-[60vh] custom-scrollbar">
                @if($this->institutionDetail && count($this->institutionDetail) > 0)
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3">Siswa</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Orang Tua</th>
                                <th class="px-4 py-3 text-center">Keaktifan</th>
                                <th class="px-4 py-3 text-center">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->institutionDetail as $det)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $det['name'] }}</td>
                                    <td class="px-4 py-3">{{ $det['classroom'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $det['parent_name'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-gray-700">{{ $det['active_days'] }}</span> / {{ $det['total_days'] }} Hari
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($det['pct'] >= 80)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $det['pct'] }}% (Sangat Aktif)
                                            </span>
                                        @elseif($det['pct'] >= 40)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                {{ $det['pct'] }}% (Kurang)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $det['pct'] }}% (Pasif)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-10 text-gray-500">
                        Tidak ada data siswa untuk lembaga ini.
                    </div>
                @endif
            </div>

            <div class="flex justify-between mt-4">
                <x-filament::button wire:click="openInstitutionsModal" color="gray" icon="heroicon-o-arrow-left">Kembali</x-filament::button>
                <x-filament::button wire:click="closeModals" color="primary">Tutup</x-filament::button>
            </div>
        </x-filament::modal>

    </x-filament::card>
    
    <div x-data="{ 
        showInst: @entangle('showInstitutionModal'),
        showDet: @entangle('showDetailModal')
    }" 
    x-init="
        $watch('showInst', value => {
            if(value) { $dispatch('open-modal', { id: 'institutions-modal' }) }
            else { $dispatch('close-modal', { id: 'institutions-modal' }) }
        });
        $watch('showDet', value => {
            if(value) { $dispatch('open-modal', { id: 'detail-modal' }) }
            else { $dispatch('close-modal', { id: 'detail-modal' }) }
        });
    "></div>
</x-filament-widgets::widget>