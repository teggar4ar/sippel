<div class="space-y-4">
    {{-- Inline feedback for same-page actions (delete). Redirect-based flashes are shown by the layout. --}}
    @if ($inlineSuccess)
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-200 rounded-lg text-sm">
            {{ $inlineSuccess }}
        </div>
    @endif
    @if ($inlineError)
        <div class="p-3 bg-red-50 dark:bg-red-900/50 text-red-700 dark:text-red-200 rounded-lg text-sm">
            {{ $inlineError }}
        </div>
    @endif

    {{-- ═══ Header ═══ --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white">Aktivitas Pembelajaran</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola catatan aktivitas dan observasi kelas harian Anda</p>
        </div>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm cursor-pointer">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>

    {{-- ═══ Quick Filters + Search/Filter Bar ═══ --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        {{-- Quick filter pills --}}
        <div class="px-3 pt-3 flex flex-wrap gap-1.5">
            <button wire:click="setQuickFilter('today')"
                    class="px-3 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer {{ $filterPeriode === 'today' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Hari Ini
            </button>
            <button wire:click="setQuickFilter('week')"
                    class="px-3 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer {{ $filterPeriode === 'week' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Minggu Ini
            </button>
            <button wire:click="setQuickFilter('month')"
                    class="px-3 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer {{ $filterPeriode === 'month' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                Bulan Ini
            </button>
        </div>

        {{-- Filter form row --}}
        <div class="p-3">
            <div class="flex flex-col sm:flex-row gap-2">
                {{-- Search --}}
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
                {{-- Mapel dropdown --}}
                <div class="sm:w-44">
                    <flux:select
                        wire:model.live="filterMapel"
                        class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    >
                        <option value="">Semua Mapel</option>
                        @foreach($this->mataPelajaran as $mapel)
                            <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }} ({{ $mapel->kelas?->tingkat_kelas }}-{{ $mapel->kelas?->grup_kelas }})</option>
                        @endforeach
                    </flux:select>
                </div>
                {{-- Reset button --}}
                @if($filterMapel || $filterPeriode || $search)
                    <button wire:click="clearFilters"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors shrink-0 cursor-pointer">
                        <flux:icon name="arrow-path" class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Reset</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ Activity List ═══ --}}
    @if($this->aktivitas->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                <h2 class="font-semibold text-slate-900 dark:text-white">Riwayat Aktivitas Pembelajaran</h2>
            </div>

            {{-- ═══ Desktop Table (lg+) ═══ --}}
            <div class="hidden lg:block px-6">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Kelas</flux:table.column>
                        <flux:table.column>Mata Pelajaran</flux:table.column>
                        <flux:table.column>Topik Pembelajaran</flux:table.column>
                        <flux:table.column>Kehadiran</flux:table.column>
                        <flux:table.column>Keaktifan</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->aktivitas as $aktivitas)
                            @php
                                $details = $aktivitas->detailAktivitas;
                                $totalSiswa = $details->count();
                                $hadirCount = $details->filter(fn($d) => $d->kehadiran === \App\Enums\KehadiranStatus::Hadir)->count();
                                $kehadiranPct = $totalSiswa > 0 ? round(($hadirCount / $totalSiswa) * 100) : 0;

                                $hadirDetails = $details->filter(fn($d) => $d->kehadiran === \App\Enums\KehadiranStatus::Hadir);
                                $avgPart = $hadirDetails->isNotEmpty() ? $hadirDetails->avg('partisipasi') : 0;
                                $partLabel = match(true) {
                                    $avgPart >= 3.5 => ['Sangat Aktif', 'green'],
                                    $avgPart >= 2.5 => ['Aktif', 'blue'],
                                    $avgPart >= 1.5 => ['Cukup', 'amber'],
                                    $avgPart >= 0.1 => ['Pasif', 'red'],
                                    default => ['-', 'zinc'],
                                };
                            @endphp
                            <flux:table.row :key="$aktivitas->id">
                                <flux:table.cell class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    <div class="text-xs tabular-nums">{{ $aktivitas->tanggal->translatedFormat('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 tabular-nums">{{ $aktivitas->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-xs font-medium px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded">
                                        {{ $aktivitas->kelas->tingkat_kelas }}-{{ $aktivitas->kelas->grup_kelas }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-slate-900 dark:text-white">{{ $aktivitas->mataPelajaran->nama_mapel }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white" title="{{ $aktivitas->topik }}">{{ Str::limit($aktivitas->topik, 30) }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-col gap-1 min-w-[3.5rem]">
                                        <span class="text-sm tabular-nums font-semibold {{ $kehadiranPct >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($kehadiranPct >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $kehadiranPct }}%
                                        </span>
                                        <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $kehadiranPct >= 80 ? 'bg-emerald-500' : ($kehadiranPct >= 60 ? 'bg-amber-500' : 'bg-red-500') }}" x-data="{ w: @js(min($kehadiranPct, 100)) }" x-bind:style="`width: ${w}%`"></div>
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="{{ $partLabel[1] }}" size="sm" inset="top bottom">{{ $partLabel[0] }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-0.5">
                                        <a href="{{ route('teacher.aktivitas.view', $aktivitas->id) }}" wire:navigate
                                           class="p-1.5 text-blue-500 dark:text-blue-400 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors"
                                           title="Lihat">
                                            <flux:icon name="eye" class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
                                           class="p-1.5 text-amber-500 dark:text-amber-400 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded transition-colors"
                                           title="Edit">
                                            <flux:icon name="pencil" class="w-4 h-4" />
                                        </a>
                                        <button
                                            x-on:click="$wire.deleteId = {{ $aktivitas->id }}; $wire.deleteTopik = '{{ addslashes($aktivitas->topik) }}'; $wire.showDeleteModal = true"
                                            class="p-1.5 text-red-500 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors cursor-pointer"
                                            title="Hapus">
                                            <flux:icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            {{-- ═══ Mobile Cards (< lg) ═══ --}}
            <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($this->aktivitas as $aktivitas)
                    @php
                        $details = $aktivitas->detailAktivitas;
                        $totalSiswa = $details->count();
                        $hadirCount = $details->filter(fn($d) => $d->kehadiran === \App\Enums\KehadiranStatus::Hadir)->count();
                        $kehadiranPct = $totalSiswa > 0 ? round(($hadirCount / $totalSiswa) * 100) : 0;

                        $hadirDetails = $details->filter(fn($d) => $d->kehadiran === \App\Enums\KehadiranStatus::Hadir);
                        $avgPart = $hadirDetails->isNotEmpty() ? $hadirDetails->avg('partisipasi') : 0;
                        $partLabel = match(true) {
                            $avgPart >= 3.5 => ['Sangat Aktif', 'green'],
                            $avgPart >= 2.5 => ['Aktif', 'blue'],
                            $avgPart >= 1.5 => ['Cukup', 'amber'],
                            $avgPart >= 0.1 => ['Pasif', 'red'],
                            default => ['-', 'zinc'],
                        };
                    @endphp

                    <div class="p-3" wire:key="mobile-{{ $aktivitas->id }}">
                        {{-- Top row: date + kelas + actions --}}
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs font-bold tabular-nums text-blue-600 dark:text-blue-400">
                                    {{ $aktivitas->tanggal->translatedFormat('d M') }}
                                </span>
                                <span class="text-[10px] tabular-nums text-slate-400 dark:text-slate-500">
                                    {{ $aktivitas->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                                </span>
                                <span class="text-[10px] font-medium px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded">
                                    {{ $aktivitas->kelas->tingkat_kelas }}-{{ $aktivitas->kelas->grup_kelas }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    {{ $aktivitas->mataPelajaran->nama_mapel }}
                                </span>
                            </div>
                            <div class="flex items-center gap-0 shrink-0">
                                <a href="{{ route('teacher.aktivitas.view', $aktivitas->id) }}" wire:navigate
                                   class="p-1 text-blue-500 dark:text-blue-400 rounded transition-colors">
                                    <flux:icon name="eye" class="w-3.5 h-3.5" />
                                </a>
                                <a href="{{ route('teacher.aktivitas.edit', $aktivitas->id) }}" wire:navigate
                                   class="p-1 text-amber-500 dark:text-amber-400 rounded transition-colors">
                                    <flux:icon name="pencil" class="w-3.5 h-3.5" />
                                </a>
                                <button
                                    x-on:click="$wire.deleteId = {{ $aktivitas->id }}; $wire.deleteTopik = '{{ addslashes($aktivitas->topik) }}'; $wire.showDeleteModal = true"
                                    class="p-1 text-red-500 dark:text-red-400 rounded transition-colors cursor-pointer">
                                    <flux:icon name="trash" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        {{-- Topic --}}
                        <p class="text-sm font-semibold text-slate-900 dark:text-white leading-snug mb-1.5">
                            {{ $aktivitas->topik }}
                        </p>

                        {{-- Stats row: kehadiran + partisipasi --}}
                                <div class="flex items-center gap-3">
                                    <span class="text-xs tabular-nums {{ $kehadiranPct >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($kehadiranPct >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }} font-semibold">
                                        <flux:icon name="users" class="w-3 h-3 inline -mt-0.5" /> {{ $kehadiranPct }}%
                                    </span>
                                    <flux:badge color="{{ $partLabel[1] }}" size="sm">{{ $partLabel[0] }}</flux:badge>
                                </div>
                    </div>
                @endforeach
            </div>

            {{-- ═══ Pagination Footer ═══ --}}
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    {{-- Left: data info --}}
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan <strong class="text-slate-700 dark:text-slate-200">{{ $this->aktivitas->firstItem() }}-{{ $this->aktivitas->lastItem() }}</strong> dari <strong class="text-slate-700 dark:text-slate-200">{{ $this->aktivitas->total() }}</strong> aktivitas
                    </p>

                    {{-- Center: page links --}}
                    <div class="flex-1 flex justify-center">
                        {{ $this->aktivitas->links() }}
                    </div>

                    {{-- Right: per page --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Per halaman:</span>
                        <flux:select
                            wire:model.live="perPage"
                            size="xs"
                            class="text-xs border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
            @if($filterMapel || $filterPeriode || $search)
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                    <flux:icon name="magnifying-glass" class="w-6 h-6 text-slate-400" />
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white mb-1">Tidak ada hasil</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Coba ubah filter atau reset</p>
                <button wire:click="clearFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors cursor-pointer">
                    Reset Filter
                </button>
            @else
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                    <flux:icon name="clipboard-document-list" class="w-6 h-6 text-slate-400" />
                </div>
                <h3 class="font-medium text-slate-900 dark:text-white mb-1">Belum Ada Aktivitas</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Mulai dengan membuat aktivitas pertama</p>
                <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer">
                    <flux:icon name="plus" class="w-4 h-4" />
                    Buat Aktivitas
                </a>
            @endif
        </div>
    @endif

    {{-- ═══ Delete Confirmation Modal (Flux UI) ═══ --}}
    <flux:modal wire:model.self="showDeleteModal" class="min-w-[22rem] max-w-sm">
        <div class="space-y-5">
            {{-- Icon --}}
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-50 dark:bg-red-950/50 rounded-full ring-4 ring-red-100 dark:ring-red-900/30">
                <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>

            {{-- Content --}}
            <div class="text-center space-y-2">
                <flux:heading size="lg">Hapus Aktivitas ini?</flux:heading>
                <!-- <flux:text class="font-medium line-clamp-2">"{{ $deleteTopik }}"</flux:text> -->
                <flux:text class="!text-xs !text-slate-500 dark:!text-slate-400">
                    Data kehadiran & nilai akan terhapus permanen
                </flux:text>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                </flux:modal.close>
                <flux:button
                    wire:click="deleteAktivitas"
                    wire:loading.attr="disabled"
                    variant="danger"
                    class="cursor-pointer"
                >
                    <span wire:loading.remove wire:target="deleteAktivitas">Hapus</span>
                    <span wire:loading wire:target="deleteAktivitas">Menghapus...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
