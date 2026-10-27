@props([
    'active' => false,
    'collapsible' => true,
    'icon' => null,
    'items' => [],
    'label' => null,
    'sidebarCollapsible' => true,
    'subNavigation' => false,
])

@php
    $sidebarCollapsible = $sidebarCollapsible && (filament()?->isSidebarCollapsibleOnDesktop() ?? false);
    $hasDropdown = filled($label) && filled($icon) && $sidebarCollapsible;
    $isKepsek = auth()->check() && auth()->user()->hasRole('kepala_sekolah');

    // Identifikasi Kategori Tupoksi Sidebar Group
    $labelLower = strtolower(trim((string) $label));
    $tupoksiType = null;
    $tupoksiBadge = null;

    if (filled($label)) {
        // 1. Fitur Umum & Informasi
        if (
            str_contains($labelLower, 'menu utama') ||
            str_contains($labelLower, 'pengumuman') ||
            str_contains($labelLower, 'informasi') ||
            str_contains($labelLower, 'visi &') ||
            str_contains($labelLower, 'visi,') ||
            str_contains($labelLower, 'visi misi') ||
            str_contains($labelLower, 'visi-misi') ||
            str_contains($labelLower, 'data induk') ||
            str_contains($labelLower, 'pengaturan')
        ) {
            $tupoksiType = 'umum';
            $tupoksiBadge = 'UMUM / INFO';
        }
        // 2. Tugas Guru
        elseif (
            str_contains($labelLower, 'akademik') ||
            str_contains($labelLower, 'pembelajaran') ||
            str_contains($labelLower, 'penilaian') ||
            str_contains($labelLower, 'kompetensi') ||
            str_contains($labelLower, 'tahfidz') ||
            str_contains($labelLower, 'tahsin') ||
            str_contains($labelLower, 'guru') ||
            str_contains($labelLower, 'wali kelas') ||
            str_contains($labelLower, 'kewajiban') ||
            str_contains($labelLower, 'manajemen tugas') ||
            str_contains($labelLower, 'piket')
        ) {
            $tupoksiType = 'guru';
            $tupoksiBadge = 'TUGAS GURU';
        }
        // 3. Tugas Jabatan (Struktural, Waka, Kepsek, Kesiswaan, Sarpras, Humas, TU, Lintas Divisi, dll.)
        else {
            $tupoksiType = 'jabatan';
            $tupoksiBadge = 'TUGAS JABATAN';
        }
    }
@endphp

<li
    x-data="{ label: @js($subNavigation ? "sub_navigation_{$label}" : $label) }"
    data-group-label="{{ $subNavigation ? "sub_navigation_{$label}" : $label }}"
    @if ($tupoksiType)
        data-tupoksi="{{ $tupoksiType }}"
    @endif
    {{
        $attributes->class([
            'fi-sidebar-group flex flex-col gap-y-1',
            'fi-active' => $active,
            "fi-sidebar-group-tupoksi-{$tupoksiType}" => (bool) $tupoksiType,
        ])
    }}
>
    @if ($label)
        <div
            @if ($collapsible)
                x-on:click="$store.sidebar.toggleCollapsedGroup(label)"
            @endif
            @if ($sidebarCollapsible)
                x-show="$store.sidebar.isOpen"
                x-transition:enter="delay-100 lg:transition"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            @endif
            @class([
                'fi-sidebar-group-button flex items-center gap-x-3 px-2 py-2',
                'cursor-pointer' => $collapsible,
            ])
        >
            @if ($icon)
                <x-filament::icon
                    :icon="$icon"
                    class="fi-sidebar-group-icon h-6 w-6 text-gray-400 dark:text-gray-500"
                />
            @endif

            <span
                class="fi-sidebar-group-label flex-1 text-sm font-medium leading-6"
            >
                <span class="fi-sidebar-group-label-text">{{ $label }}</span>
                @if ($tupoksiBadge)
                    <span class="tupoksi-badge tupoksi-badge-{{ $tupoksiType }}">{{ $tupoksiBadge }}</span>
                @endif
            </span>

            @if ($collapsible)
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-m-chevron-up"
                    icon-alias="panels::sidebar.group.collapse-button"
                    :label="$label"
                    x-bind:aria-expanded="! $store.sidebar.groupIsCollapsed(label)"
                    x-on:click.stop="$store.sidebar.toggleCollapsedGroup(label)"
                    class="fi-sidebar-group-collapse-button"
                    x-bind:class="{ '-rotate-180': $store.sidebar.groupIsCollapsed(label) }"
                />
            @endif
        </div>
    @endif

    @if ($hasDropdown)
        <x-filament::dropdown
            :placement="(__('filament-panels::layout.direction') === 'rtl') ? 'left-start' : 'right-start'"
            teleport
            x-show="! $store.sidebar.isOpen"
        >
            <x-slot name="trigger">
                <button
                    x-data="{ tooltip: false }"
                    x-effect="
                        tooltip = $store.sidebar.isOpen
                            ? false
                            : {
                                  content: @js($label),
                                  placement: document.dir === 'rtl' ? 'left' : 'right',
                                  theme: $store.theme,
                              }
                    "
                    x-tooltip.html="tooltip"
                    class="relative flex flex-1 items-center justify-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                >
                    <x-filament::icon
                        :icon="$icon"
                        @class([
                            'h-6 w-6',
                            'text-gray-400 dark:text-gray-500' => ! $active,
                            'text-primary-600 dark:text-primary-400' => $active,
                        ])
                    />
                </button>
            </x-slot>

            @php
                $lists = [];

                foreach ($items as $item) {
                    if ($childItems = $item->getChildItems()) {
                        $lists[] = [
                            $item,
                            ...$childItems,
                        ];
                        $lists[] = [];

                        continue;
                    }

                    if (empty($lists)) {
                        $lists[] = [$item];

                        continue;
                    }

                    $lists[count($lists) - 1][] = $item;
                }

                if (empty($lists[count($lists) - 1])) {
                    array_pop($lists);
                }
            @endphp

            @if (filled($label))
                <x-filament::dropdown.header>
                    {{ $label }}
                </x-filament::dropdown.header>
            @endif

            @foreach ($lists as $list)
                <x-filament::dropdown.list>
                    @foreach ($list as $item)
                        @php
                            $itemIsActive = $item->isActive();
                        @endphp

                        <x-filament::dropdown.list.item
                            :badge="$item->getBadge()"
                            :badge-color="$item->getBadgeColor()"
                            :badge-tooltip="$item->getBadgeTooltip()"
                            :color="$itemIsActive ? 'primary' : 'gray'"
                            :href="$item->getUrl()"
                            :icon="$itemIsActive ? ($item->getActiveIcon() ?? $item->getIcon()) : $item->getIcon()"
                            tag="a"
                            :target="$item->shouldOpenUrlInNewTab() ? '_blank' : null"
                        >
                            {{ $item->getLabel() }}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            @endforeach
        </x-filament::dropdown>
    @endif

    <ul
        @if (filled($label))
            @if ($sidebarCollapsible)
                x-show="$store.sidebar.isOpen ? ! $store.sidebar.groupIsCollapsed(label) : ! @js($hasDropdown)"
            @else
                x-show="! $store.sidebar.groupIsCollapsed(label)"
            @endif
            x-collapse.duration.200ms
        @endif
        @if ($sidebarCollapsible)
            x-transition:enter="delay-100 lg:transition"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        @endif
        class="fi-sidebar-group-items flex flex-col gap-y-1"
    >
        @foreach ($items as $item)
            @php
                $itemIcon = $item->getIcon();
                $itemActiveIcon = $item->getActiveIcon();

                if ($icon) {
                    if ($hasDropdown || (blank($itemIcon) && blank($itemActiveIcon))) {
                        $itemIcon = null;
                        $itemActiveIcon = null;
                    } else {
                        throw new \Exception('Navigation group [' . $label . '] has an icon but one or more of its items also have icons. Either the group or its items can have icons, but not both. This is to ensure a proper user experience.');
                    }
                }

                $rawItemLabel = (string) $item->getLabel();
            @endphp

            @if ($isKepsek)
                @if (str_contains($rawItemLabel, 'Fitur Input Mengajar'))
                    <li class="list-none px-2.5 pt-2 pb-1 text-xs font-bold text-orange-600 dark:text-orange-400 flex items-center gap-1.5 tracking-wide">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                        <span>Subbab A. Pelaksanaan Pembelajaran</span>
                    </li>
                @elseif (str_contains($rawItemLabel, 'Fitur Rekap & Input Nilai'))
                    <li class="list-none px-2.5 pt-3 pb-1 text-xs font-bold text-orange-600 dark:text-orange-400 border-t border-dashed border-gray-200 dark:border-gray-700/60 flex items-center gap-1.5 mt-2 tracking-wide">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                        <span>Subbab B. Penilaian Pembelajaran</span>
                    </li>
                @elseif (str_contains($rawItemLabel, 'Fitur Rekap Presensi Siswa'))
                    <li class="list-none px-2.5 pt-3 pb-1 text-xs font-bold text-orange-600 dark:text-orange-400 border-t border-dashed border-gray-200 dark:border-gray-700/60 flex items-center gap-1.5 mt-2 tracking-wide">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                        <span>Subbab C. Kedisiplinan</span>
                    </li>
                @endif
            @endif

            <x-filament-panels::sidebar.item
                :active="$item->isActive()"
                :active-child-items="$item->isChildItemsActive()"
                :active-icon="$itemActiveIcon"
                :badge="$item->getBadge()"
                :badge-color="$item->getBadgeColor()"
                :badge-tooltip="$item->getBadgeTooltip()"
                :child-items="$item->getChildItems()"
                :first="$loop->first"
                :grouped="filled($label)"
                :icon="$itemIcon"
                :last="$loop->last"
                :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                :sidebar-collapsible="$sidebarCollapsible"
                :url="$item->getUrl()"
                @class(['ps-3' => $isKepsek && str_starts_with($rawItemLabel, '- ')])
            >
                {{ $item->getLabel() }}

                @if ($itemIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                    <x-slot name="icon">
                        {{ $itemIcon }}
                    </x-slot>
                @endif

                @if ($itemActiveIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                    <x-slot name="activeIcon">
                        {{ $itemActiveIcon }}
                    </x-slot>
                @endif
            </x-filament-panels::sidebar.item>
        @endforeach
    </ul>
</li>
