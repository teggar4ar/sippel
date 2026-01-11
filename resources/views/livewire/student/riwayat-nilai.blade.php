<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Riwayat Nilai</h1>
        <p class="text-sm text-teal-600 dark:text-teal-400 mt-0.5">Lihat perkembangan nilai kamu</p>
    </div>

    {{-- Summary by subject - Compact horizontal scroll --}}
    @if($summaryPerMapel->isNotEmpty())
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-teal-800 flex items-center justify-between">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Ringkasan per Mapel</span>
                <span class="text-[10px] text-teal-500 dark:text-teal-400">{{ $summaryPerMapel->count() }} mapel</span>
            </div>
            <div class="flex overflow-x-auto scrollbar-hide">
                @foreach($summaryPerMapel as $mapel)
                    <div class="flex-shrink-0 p-2 border-r border-teal-50 dark:border-teal-800 last:border-r-0 min-w-[100px]">
                        <p class="text-[10px] font-medium text-teal-900 dark:text-white truncate mb-1">{{ $mapel['nama'] }}</p>
                        <div class="flex items-center gap-1.5 text-[10px]">
                            <span class="font-bold text-teal-700 dark:text-teal-300">{{ $mapel['avg'] }}</span>
                            <span class="text-teal-300 dark:text-teal-600">|</span>
                            <span class="text-emerald-600 dark:text-emerald-400">{{ $mapel['max'] }}</span>
                            <span class="text-teal-300 dark:text-teal-600">|</span>
                            <span class="text-red-600 dark:text-red-400">{{ $mapel['min'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-3 py-1 border-t border-teal-50 dark:border-teal-800 bg-teal-50/50 dark:bg-teal-900/50">
                <p class="text-[9px] text-teal-500 dark:text-teal-400 text-center">Rata-rata | Tertinggi | Terendah</p>
            </div>
        </div>
    @endif

    {{-- Filters - Compact --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden p-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <div class="col-span-2 sm:col-span-1">
                <flux:select
                    wire:model.live="filterMapel"
                    label="Mapel"
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

    {{-- Grade list --}}
    <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
        <div class="divide-y divide-teal-50 dark:divide-teal-800">
            @forelse($riwayat as $detail)
                <div class="p-3">
                    {{-- Row 1: Date + Grade badge --}}
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs text-teal-500 dark:text-teal-400">{{ $detail->aktivitasPembelajaran->tanggal->format('d M Y') }}</span>
                        @if($detail->nilai)
                            @php
                                $nilaiColor = match(true) {
                                    $detail->nilai >= 80 => 'bg-emerald-500',
                                    $detail->nilai >= 60 => 'bg-amber-500',
                                    default => 'bg-red-500',
                                };
                            @endphp
                            <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded flex-shrink-0 {{ $nilaiColor }}">
                                {{ $detail->nilai }}
                            </span>
                        @endif
                    </div>
                    {{-- Row 2: Topic + Subject --}}
                    <p class="text-sm font-medium text-teal-900 dark:text-white truncate">{{ $detail->aktivitasPembelajaran->topik }}</p>
                    <p class="text-xs text-teal-600 dark:text-teal-400 truncate">{{ $detail->aktivitasPembelajaran->mataPelajaran->nama_mapel }}</p>
                    {{-- Row 3: Participation + Note --}}
                    <div class="flex items-center gap-2 mt-1.5">
                        @if($detail->partisipasi)
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <flux:icon
                                        name="star"
                                        variant="{{ $i <= $detail->partisipasi ? 'solid' : 'outline' }}"
                                        class="w-3 h-3 {{ $i <= $detail->partisipasi ? 'text-amber-400' : 'text-teal-200 dark:text-teal-700' }}"
                                    />
                                @endfor
                            </div>
                        @endif
                        @if($detail->catatan)
                            <p class="text-[10px] text-teal-500 dark:text-teal-500 truncate flex-1 italic">{{ $detail->catatan }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <flux:icon name="academic-cap" class="w-10 h-10 mx-auto text-teal-300 dark:text-teal-600 mb-2" />
                    <p class="text-sm text-teal-500 dark:text-teal-400">Tidak ada data nilai</p>
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
