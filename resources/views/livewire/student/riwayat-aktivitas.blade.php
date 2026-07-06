<div class="space-y-4 overflow-x-hidden">

    {{-- ═══ Page Header ═══ --}}
    <div>
        <div class="flex justify-between items-center gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Riwayat Aktivitas</h1>
            </div>
            <div>
                <button
                    wire:click="exportPdf"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 border border-rose-200 dark:border-rose-800 rounded-lg transition-colors cursor-pointer shrink-0"
                >
                    <flux:icon wire:loading.remove wire:target="exportPdf" name="arrow-down-tray" class="w-3.5 h-3.5" />
                    <flux:icon wire:loading wire:target="exportPdf" name="arrow-path" class="w-3.5 h-3.5 animate-spin" />
                    <span>Ekspor PDF</span>
                </button>
            </div>
        </div>
        <p class="text-sm text-teal-600 dark:text-teal-400 mt-0.5">Lihat rekap kehadiran dan keaktifan belajarmu</p>
    </div>

    {{-- ═══ Main Card (Search + Filters + Table) ═══ --}}
    <div class="bg-white dark:bg-slate-900/95 rounded-2xl border border-gray-200 dark:border-slate-700/80 shadow-sm overflow-hidden"
         wire:loading.class="opacity-50 pointer-events-none"
         wire:target="search, filterKehadiran, resetFilters, nextPage, previousPage, gotoPage">

        {{-- Search + Mapel Filter --}}
        <div class="px-5 pb-3 mt-4">
            <div class="flex gap-3">
                <div class="relative flex-1">
                    <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Cari mata pelajaran, topik, atau catatan..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition"
                    />
                </div>
                <div class="relative w-48 shrink-0">
                    <flux:icon name="book-open" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10" />
                    <select
                        wire:model.live="filterMapel"
                        class="w-full pl-9 pr-8 py-2.5 text-sm bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 transition cursor-pointer"
                    >
                        <option value="">Semua Mapel</option>
                        @foreach($this->mataPelajaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                </div>
            </div>
        </div>

        {{-- Pill Filters for Kehadiran --}}
        <div class="px-5 pb-4 flex items-center gap-2 flex-wrap">
            <button wire:click="$set('filterKehadiran', '')"
                class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer
                    {{ $filterKehadiran === '' ? 'bg-teal-100 text-teal-800 dark:bg-teal-800/40 dark:text-teal-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                Semua Kehadiran
            </button>
            @foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $val => $label)
                <button wire:click="$set('filterKehadiran', '{{ $val }}')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer
                        {{ $filterKehadiran === $val ? 'bg-teal-100 text-teal-800 dark:bg-teal-800/40 dark:text-teal-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Divider --}}
        <div class="border-t border-gray-100 dark:border-slate-700/80"></div>

        @if($riwayat->count() > 0)

            {{-- ═══ Desktop Table (lg+) ═══ --}}
            <div class="hidden lg:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-slate-700/80">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Tanggal &amp; Waktu</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Mata Pelajaran</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Topik</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Kehadiran</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Keaktifan</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60">
                        @foreach($riwayat as $detail)
                            @php
                                $kVal  = $detail->kehadiran->value;
                                $kLabel = $detail->kehadiran->label();
                                $kClass = match($kVal) {
                                    'hadir' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'izin'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'sakit' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'alpa'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                                $pLabel = $detail->label_keaktifan;
                                $pClass = match($pLabel) {
                                    'Sangat Aktif' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'Aktif'        => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                    'Cukup'        => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'Pasif'        => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                    default        => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <p class="font-medium text-slate-800 dark:text-slate-100">
                                        {{ $detail->aktivitasPembelajaran->tanggal->translatedFormat('d M Y') }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-slate-500 tabular-nums mt-0.5">
                                        {{ $detail->aktivitasPembelajaran->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                                    </p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 max-w-[16rem]">
                                    <span class="block truncate text-slate-600 dark:text-slate-300" title="{{ $detail->aktivitasPembelajaran->topik }}">
                                        {{ $detail->aktivitasPembelajaran->topik }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kClass }}">
                                        {{ $kLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pClass }}">
                                            {{ $pLabel }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 max-w-[10rem]">
                                    @if($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir && $detail->catatan)
                                        <span class="block truncate text-xs text-slate-500 dark:text-slate-400 italic" title="{{ $detail->catatan }}">
                                            {{ $detail->catatan }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Desktop Pagination --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700/80">
                    {{ $riwayat->links() }}
                </div>
            </div>

            {{-- ═══ Mobile Cards (< lg) ═══ --}}
            <div class="lg:hidden divide-y divide-gray-100 dark:divide-slate-700/60">
                @foreach($riwayat as $detail)
                    @php
                        $kVal   = $detail->kehadiran->value;
                        $kLabel = $detail->kehadiran->label();
                        $kClass = match($kVal) {
                            'hadir' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'izin'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'sakit' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'alpa'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default => 'bg-gray-100 text-gray-600',
                        };
                        $pLabel = $detail->label_keaktifan;
                        $pClass = match($pLabel) {
                            'Sangat Aktif' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'Aktif'        => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                            'Cukup'        => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'Pasif'        => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                            default        => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <div class="px-5 py-4" wire:key="mobile-{{ $detail->id }}">
                        {{-- Date + Mapel --}}
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $detail->aktivitasPembelajaran->tanggal->translatedFormat('d M Y') }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-slate-500 tabular-nums">
                                {{ $detail->aktivitasPembelajaran->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                            </span>
                            <span class="text-xs text-gray-500 dark:text-slate-400">
                                &middot; {{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}
                            </span>
                        </div>
                        {{-- Topik --}}
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mb-2 leading-snug">
                            {{ $detail->aktivitasPembelajaran->topik }}
                        </p>
                        {{-- Badges --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kClass }}">{{ $kLabel }}</span>
                            @if($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pClass }}">{{ $pLabel }}</span>
                                @if($detail->catatan)
                                    <span class="text-xs italic text-gray-400 dark:text-slate-500 truncate max-w-[14rem]" title="{{ $detail->catatan }}">{{ $detail->catatan }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Mobile Pagination --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700/80 flex justify-center">
                    {{ $riwayat->links() }}
                </div>
            </div>

        @else
            {{-- Empty State --}}
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                    <flux:icon name="calendar" class="w-7 h-7 text-gray-400 dark:text-slate-500" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Belum ada data riwayat aktivitas</p>
                @if($search || $filterKehadiran)
                    <button wire:click="resetFilters" class="mt-3 text-xs text-teal-600 hover:text-teal-700 dark:text-teal-400 cursor-pointer underline underline-offset-2">
                        Reset filter
                    </button>
                @endif
            </div>
        @endif

    </div>
</div>
