<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Riwayat Kehadiran</h1>
        <p class="text-sm text-teal-600 dark:text-teal-400 mt-0.5">Lihat rekap kehadiran kamu</p>
    </div>

    {{-- Summary stats - Inline compact --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
        <div class="flex">
            <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['hadir'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400">Hadir</div>
                <div class="text-[10px] text-teal-500 dark:text-teal-500">{{ $stats['hadir_pct'] }}%</div>
            </div>
            <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $stats['izin'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400">Izin</div>
                <div class="text-[10px] text-teal-500 dark:text-teal-500">{{ $stats['izin_pct'] }}%</div>
            </div>
            <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $stats['sakit'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400">Sakit</div>
                <div class="text-[10px] text-teal-500 dark:text-teal-500">{{ $stats['sakit_pct'] }}%</div>
            </div>
            <div class="flex-1 py-3 text-center">
                <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $stats['alpa'] }}</div>
                <div class="text-[10px] text-teal-600 dark:text-teal-400">Alpa</div>
                <div class="text-[10px] text-teal-500 dark:text-teal-500">{{ $stats['alpa_pct'] }}%</div>
            </div>
        </div>
    </div>

    {{-- Filters - Compact --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden p-3">
        <div class="grid grid-cols-2 gap-2">
            <div>
                <flux:select
                    wire:model.live="filterMapel"
                    label="Mata Pelajaran"
                    size="sm"
                    class="border-teal-200 dark:border-teal-700 focus:border-teal-500 focus:outline-none focus:ring-0"
                >
                    <option value="">Semua</option>
                    @foreach($mataPelajaran as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select
                    wire:model.live="filterStatus"
                    label="Status"
                    size="sm"
                    class="border-teal-200 dark:border-teal-700 focus:border-teal-500 focus:outline-none focus:ring-0"
                >
                    <option value="">Semua</option>
                    <option value="Hadir">Hadir</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Alpa">Alpa</option>
                </flux:select>
            </div>
            <div>
                <flux:input
                    wire:model.live="filterDariTanggal"
                    type="date"
                    label="Dari"
                    size="sm"
                    class:input="border-teal-200 dark:border-teal-700 focus:border-teal-500 focus:outline-none focus:ring-0"
                />
            </div>
            <div>
                <flux:input
                    wire:model.live="filterSampaiTanggal"
                    type="date"
                    label="Sampai"
                    size="sm"
                    class:input="border-teal-200 dark:border-teal-700 focus:border-teal-500 focus:outline-none focus:ring-0"
                />
            </div>
        </div>
    </div>

    {{-- Attendance list --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
        <div class="divide-y divide-teal-50 dark:divide-teal-800">
            @forelse($riwayat as $detail)
                <div class="p-3">
                    {{-- Row 1: Date + Badge --}}
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs text-teal-500 dark:text-teal-400">{{ $detail->aktivitasPembelajaran->tanggal->format('d M Y') }}</span>
                        @php
                            $badgeColors = [
                                'Hadir' => 'bg-emerald-500',
                                'Izin' => 'bg-blue-500',
                                'Sakit' => 'bg-amber-500',
                                'Alpa' => 'bg-red-500',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-[10px] font-medium text-white rounded flex-shrink-0 {{ $badgeColors[$detail->kehadiran] ?? 'bg-teal-500' }}">
                            {{ $detail->kehadiran }}
                        </span>
                    </div>
                    {{-- Row 2: Topic + Subject --}}
                    <p class="text-sm font-medium text-teal-900 dark:text-white truncate">{{ $detail->aktivitasPembelajaran->topik }}</p>
                    <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}</p>
                    @if($detail->catatan)
                        <p class="mt-1 text-[10px] text-teal-500 dark:text-teal-500 truncate italic">{{ $detail->catatan }}</p>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center">
                    <flux:icon name="calendar" class="w-10 h-10 mx-auto text-teal-300 dark:text-teal-600 mb-2" />
                    <p class="text-sm text-teal-500 dark:text-teal-400">Tidak ada data kehadiran</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($riwayat instanceof \Illuminate\Pagination\LengthAwarePaginator && $riwayat->total() > 0)
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden p-3">
            <div class="flex items-center justify-between gap-2">
                {{-- Info --}}
                <span class="text-xs text-teal-600 dark:text-teal-400">
                    {{ $riwayat->firstItem() }}-{{ $riwayat->lastItem() }} dari {{ $riwayat->total() }}
                </span>
                {{-- Navigation --}}
                <div class="flex items-center gap-1">
                    @if($riwayat->onFirstPage())
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800/50 text-teal-400 dark:text-teal-600 cursor-not-allowed">
                            <flux:icon name="chevron-left" class="w-4 h-4" />
                        </span>
                    @else
                        <button wire:click="previousPage" class="w-8 h-8 flex items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800 text-teal-700 dark:text-teal-300 hover:bg-teal-200 dark:hover:bg-teal-700 transition-colors">
                            <flux:icon name="chevron-left" class="w-4 h-4" />
                        </button>
                    @endif
                    <span class="px-3 py-1 text-xs font-medium text-teal-900 dark:text-white bg-teal-50 dark:bg-teal-800 rounded-lg">
                        {{ $riwayat->currentPage() }} / {{ $riwayat->lastPage() }}
                    </span>
                    @if($riwayat->hasMorePages())
                        <button wire:click="nextPage" class="w-8 h-8 flex items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800 text-teal-700 dark:text-teal-300 hover:bg-teal-200 dark:hover:bg-teal-700 transition-colors">
                            <flux:icon name="chevron-right" class="w-4 h-4" />
                        </button>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-800/50 text-teal-400 dark:text-teal-600 cursor-not-allowed">
                            <flux:icon name="chevron-right" class="w-4 h-4" />
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
