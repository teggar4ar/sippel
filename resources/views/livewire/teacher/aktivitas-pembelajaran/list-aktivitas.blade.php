<div class="space-y-4">
    {{-- Header - Compact --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white">Aktivitas Pembelajaran</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola aktivitas harian</p>
        </div>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>

    {{-- Stats Cards - Mobile optimized --}}
    <div class="grid grid-cols-3 gap-2">
        <div class="p-2.5 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center mx-auto mb-1">
                <flux:icon name="calendar-days" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>
            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $this->totalAktivitasBulanIni }}</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400">Bulan Ini</p>
        </div>
        <div class="p-2.5 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center mx-auto mb-1">
                <flux:icon name="user-group" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $this->rataKehadiran }}%</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400">Kehadiran</p>
        </div>
        <div class="p-2.5 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 text-center">
            <div class="w-8 h-8 bg-violet-100 dark:bg-violet-900/50 rounded-lg flex items-center justify-center mx-auto mb-1">
                <flux:icon name="academic-cap" class="w-4 h-4 text-violet-600 dark:text-violet-400" />
            </div>
            @if($this->mapelTeraktif)
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate px-1" title="{{ $this->mapelTeraktif->nama }}">
                    {{ Str::limit($this->mapelTeraktif->nama, 8) }}
                </p>
            @else
                <p class="text-lg font-bold text-slate-900 dark:text-white">-</p>
            @endif
            <p class="text-[10px] text-slate-500 dark:text-slate-400">Teraktif</p>
        </div>
    </div>

    {{-- Quick Filters --}}
    <div class="flex flex-wrap gap-1.5">
        <button wire:click="setQuickFilter('today')"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition-all {{ $filterPeriode === 'today' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600' }}">
            Hari Ini
        </button>
        <button wire:click="setQuickFilter('week')"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition-all {{ $filterPeriode === 'week' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600' }}">
            Minggu Ini
        </button>
        <button wire:click="setQuickFilter('month')"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition-all {{ $filterPeriode === 'month' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600' }}">
            Bulan Ini
        </button>
        @if($filterPeriode)
            <button wire:click="setQuickFilter('')"
                    class="px-2 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                <flux:icon name="x-mark" class="w-4 h-4" />
            </button>
        @endif
    </div>

    {{-- Filters - Mobile optimized --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-3">
            {{-- Search row --}}
            <div class="flex gap-2">
                <div class="flex-1">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        inputmode="search"
                        placeholder="Cari topik..."
                        icon="magnifying-glass"
                        class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    />
                </div>
                @if($filterMapel || $filterTanggal || $filterPeriode || $search)
                    <button wire:click="clearFilters"
                            class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors flex-shrink-0"
                            title="Reset semua filter">
                        <flux:icon name="x-mark" class="w-5 h-5" />
                    </button>
                @endif
            </div>

            {{-- Filter row --}}
            <div class="grid grid-cols-2 gap-2 mt-2">
                <flux:select
                    wire:model.live="filterMapel"
                    class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                >
                    <option value="">Semua Mapel</option>
                    @foreach($this->mataPelajaran as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                    @endforeach
                </flux:select>
                <flux:input
                    wire:model.live="filterTanggal"
                    type="date"
                    :disabled="$filterPeriode"
                    class:input="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 disabled:opacity-50 disabled:cursor-not-allowed"
                />
            </div>
        </div>

        {{-- Results count & per-page --}}
        @if($this->aktivitas->total() > 0)
            <div class="px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $this->aktivitas->firstItem() }}-{{ $this->aktivitas->lastItem() }} dari {{ $this->aktivitas->total() }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">Per halaman:</span>
                    <flux:select
                        wire:model.live="perPage"
                        size="xs"
                        class="text-xs border-transparent bg-transparent text-slate-600 dark:text-slate-300 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 focus:border-transparent"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </flux:select>
                </div>
            </div>
        @endif
    </div>

    {{-- Activity list --}}
    @if($this->aktivitas->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($this->aktivitas as $aktivitas)
                    <div class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                         wire:key="aktivitas-{{ $aktivitas->id }}">
                        {{-- Mobile: Stacked layout --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                {{-- Date and tags --}}
                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                                        {{ $aktivitas->tanggal->format('d M') }}
                                    </span>
                                    <span class="text-xs px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded">
                                        {{ $aktivitas->kelas->nama_lengkap }}
                                    </span>
                                </div>

                                {{-- Topic --}}
                                <h3 class="font-semibold text-slate-900 dark:text-white text-sm leading-snug">
                                    {{ $aktivitas->topik }}
                                </h3>

                                {{-- Subject --}}
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                    {{ $aktivitas->mataPelajaran->nama_mapel }}
                                </p>

                                {{-- Stats --}}
                                @php
                                    $hadir = $aktivitas->detailAktivitas->filter(fn($d) => strtolower($d->kehadiran) === 'hadir')->count();
                                    $total = $aktivitas->detailAktivitas->count();
                                    $percentage = $total > 0 ? round(($hadir / $total) * 100) : 0;
                                @endphp
                                <div class="flex items-center gap-2 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="flex items-center gap-1">
                                        <flux:icon name="users" class="w-3.5 h-3.5" />
                                        {{ $hadir }}/{{ $total }}
                                    </span>
                                    <div class="flex-1 max-w-[80px] bg-slate-200 dark:bg-slate-600 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $percentage >= 80 ? 'bg-emerald-500' : ($percentage >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span>{{ $percentage }}%</span>
                                </div>
                            </div>

                            {{-- Actions - Vertical on mobile --}}
                            <div class="flex flex-col gap-0.5 flex-shrink-0">
                                <a href="{{ route('teacher.aktivitas.view', $aktivitas->id) }}" wire:navigate
                                   class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors">
                                    <flux:icon name="eye" class="w-4 h-4" />
                                </a>
                                <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
                                   class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded transition-colors">
                                    <flux:icon name="pencil" class="w-4 h-4" />
                                </a>
                                <button 
                                    wire:click="confirmDelete({{ $aktivitas->id }}, '{{ addslashes($aktivitas->topik) }}')"
                                    wire:loading.attr="disabled"
                                    class="relative p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors disabled:opacity-75 disabled:cursor-wait">
                                    
                                    {{-- Trash icon - hide when this specific button is loading --}}
                                    <span wire:loading.remove 
                                          wire:target="confirmDelete({{ $aktivitas->id }}, '{{ addslashes($aktivitas->topik) }}')">
                                        <flux:icon name="trash" class="w-4 h-4" />
                                    </span>
                                    
                                    {{-- Loading spinner - show only for this specific button --}}
                                    <svg wire:loading 
                                         wire:target="confirmDelete({{ $aktivitas->id }}, '{{ addslashes($aktivitas->topik) }}')"
                                         class="w-4 h-4 animate-spin text-red-600" 
                                         fill="none" 
                                         viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination / Load More --}}
        <div class="mt-4 space-y-3">
            {{-- Load More Button (Mobile friendly) --}}
            @if($this->hasMorePages)
                <button wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        class="w-full py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="loadMore">Muat Lebih Banyak</span>
                    <span wire:loading wire:target="loadMore" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat...
                    </span>
                </button>
            @endif

            {{-- Standard Pagination (for direct page navigation) --}}
            <div class="hidden sm:block">
                {{ $this->aktivitas->links() }}
            </div>

            {{-- Mobile: Simple prev/next --}}
            <div class="flex sm:hidden gap-2">
                @if($this->aktivitas->onFirstPage())
                    <span class="flex-1 py-2 text-center text-sm text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-lg">
                        ← Sebelumnya
                    </span>
                @else
                    <button wire:click="previousPage"
                            class="flex-1 py-2 text-center text-sm text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                        ← Sebelumnya
                    </button>
                @endif

                @if($this->hasMorePages)
                    <button wire:click="nextPage"
                            class="flex-1 py-2 text-center text-sm text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">
                        Selanjutnya →
                    </button>
                @else
                    <span class="flex-1 py-2 text-center text-sm text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-lg">
                        Selanjutnya →
                    </span>
                @endif
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
            @if($filterMapel || $filterTanggal || $filterPeriode || $search)
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                    <flux:icon name="magnifying-glass" class="w-6 h-6 text-slate-400" />
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white mb-1">Tidak ada hasil</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Coba ubah filter atau reset</p>
                <button wire:click="clearFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Reset Filter
                </button>
            @else
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                    <flux:icon name="clipboard-document-list" class="w-6 h-6 text-slate-400" />
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white mb-1">Belum Ada Aktivitas</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Mulai dengan membuat aktivitas pertama</p>
                <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <flux:icon name="plus" class="w-4 h-4" />
                    Buat Aktivitas
                </a>
            @endif
        </div>
    @endif

    {{-- Delete Confirmation Modal - Custom Implementation --}}
    @if($showDeleteModal)
        <div
            x-data="{ show: @entangle('showDeleteModal') }"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;">

            {{-- Backdrop --}}
            <div
                @click="$wire.closeDeleteModal()"
                class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/75 backdrop-blur-sm">
            </div>

            {{-- Modal Content --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-sm bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xl p-4 sm:p-5 space-y-4"
                @click.stop>

                {{-- Close Button --}}
                <button
                    wire:click="closeDeleteModal"
                    class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                {{-- Icon --}}
                <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 mx-auto bg-red-50 dark:bg-red-950/50 rounded-full ring-4 ring-red-100 dark:ring-red-900/30">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>

                {{-- Content --}}
                <div class="text-center space-y-2 sm:space-y-3">
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white leading-tight">
                        Hapus Aktivitas?
                    </h3>

                    <div class="space-y-1.5">
                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 font-medium line-clamp-2 px-2">
                            "{{ $deleteTopik }}"
                        </p>
                        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                            Data kehadiran & nilai akan terhapus permanen
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-2 pt-1">
                    <button
                        wire:click="closeDeleteModal"
                        type="button"
                        class="flex-1 px-4 py-2 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button
                        wire:click="deleteAktivitas"
                        wire:loading.attr="disabled"
                        type="button"
                        class="flex-1 px-4 py-2 text-xs sm:text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg border-0 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span class="inline-flex items-center justify-center gap-1.5">
                            <svg wire:loading.remove wire:target="deleteAktivitas" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <svg wire:loading wire:target="deleteAktivitas" class="w-3.5 h-3.5 sm:w-4 sm:h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="deleteAktivitas">Hapus</span>
                            <span wire:loading wire:target="deleteAktivitas" class="hidden sm:inline">Menghapus...</span>
                            <span wire:loading wire:target="deleteAktivitas" class="inline sm:hidden">...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
