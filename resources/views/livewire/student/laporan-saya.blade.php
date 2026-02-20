<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-teal-900 dark:text-white">Laporan Saya</h1>
        <p class="text-sm text-teal-600 dark:text-teal-400 mt-0.5">Lihat dan unduh laporan akademik</p>
    </div>

    @if($this->siswa)
        {{-- Filter Section - Compact --}}
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden p-3">
            <flux:select
                wire:model.live="mataPelajaranId"
                label="Mata Pelajaran"
                size="sm"
                class="border-teal-200 dark:border-teal-700 focus:border-teal-500 focus:outline-none focus:ring-0"
            >
                <option value="">Semua Mata Pelajaran</option>
                @foreach($this->mataPelajaranList as $mapel)
                    <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                @endforeach
            </flux:select>
        </div>

        {{-- Summary Stats - Inline compact --}}
        @if($this->laporanData->isNotEmpty())
            @php $stats = $this->summaryStats; @endphp
            <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
                <div class="flex">
                    <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['avgKehadiran'], 0) }}%</div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400">Kehadiran</div>
                    </div>
                    <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['avgNilai'], 1) }}</div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400">Nilai</div>
                    </div>
                    <div class="flex-1 py-3 text-center border-r border-teal-100 dark:border-teal-800">
                        <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($stats['avgPartisipasi'], 1) }}</div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400">Partisipasi</div>
                    </div>
                    <div class="flex-1 py-3 text-center">
                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $stats['totalMapel'] }}</div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400">Mapel</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Report Data - Compact list --}}
        <div class="bg-white dark:bg-teal-900/30 rounded-xl shadow-sm border border-teal-200 dark:border-teal-800 overflow-hidden">
            <div class="px-3 py-2 border-b border-teal-100 dark:border-teal-800">
                <span class="text-sm font-semibold text-teal-900 dark:text-white">Detail per Mapel</span>
            </div>

            @if($this->laporanData->isNotEmpty())
                <div class="divide-y divide-teal-50 dark:divide-teal-800 max-h-[50vh] overflow-y-auto">
                    @foreach($this->laporanData as $laporan)
                        <div class="p-3">
                            {{-- Subject name + Performance badge --}}
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <p class="text-sm font-medium text-teal-900 dark:text-white truncate">
                                    {{ $laporan->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran' }}
                                </p>
                                @php
                                    $performance = match(true) {
                                        $laporan->rata_nilai >= 85 => ['label' => 'A', 'class' => 'bg-emerald-500'],
                                        $laporan->rata_nilai >= 70 => ['label' => 'B', 'class' => 'bg-blue-500'],
                                        $laporan->rata_nilai >= 55 => ['label' => 'C', 'class' => 'bg-amber-500'],
                                        default => ['label' => 'D', 'class' => 'bg-red-500'],
                                    };
                                @endphp
                                <span class="w-6 h-6 flex items-center justify-center text-[10px] font-bold text-white rounded {{ $performance['class'] }} flex-shrink-0">
                                    {{ $performance['label'] }}
                                </span>
                            </div>

                            {{-- Stats row --}}
                            <div class="flex gap-4 text-xs">
                                <div>
                                    <span class="text-teal-500 dark:text-teal-400">Kehadiran</span>
                                    <p class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($laporan->rata_kehadiran, 0) }}%</p>
                                </div>
                                <div>
                                    <span class="text-teal-500 dark:text-teal-400">Nilai</span>
                                    @php
                                        $nilaiColor = match(true) {
                                            $laporan->rata_nilai >= 85 => 'text-emerald-600 dark:text-emerald-400',
                                            $laporan->rata_nilai >= 70 => 'text-blue-600 dark:text-blue-400',
                                            $laporan->rata_nilai >= 55 => 'text-amber-600 dark:text-amber-400',
                                            default => 'text-red-600 dark:text-red-400',
                                        };
                                    @endphp
                                    <p class="font-bold {{ $nilaiColor }}">{{ number_format($laporan->rata_nilai, 1) }}</p>
                                </div>
                                <div>
                                    <span class="text-teal-500 dark:text-teal-400">Partisipasi</span>
                                    <p class="font-bold text-amber-600 dark:text-amber-400">{{ $laporan->rata_partisipasi }}/5</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <flux:icon name="document-magnifying-glass" class="w-10 h-10 mx-auto text-teal-300 dark:text-teal-600 mb-2" />
                    <p class="text-sm text-teal-500 dark:text-teal-400">Belum ada data laporan</p>
                    <p class="text-xs text-teal-400 dark:text-teal-500 mt-1">Laporan tersedia setelah dihitung oleh sistem</p>
                </div>
            @endif
        </div>

        {{-- Download Button --}}
        @if($this->laporanData->isNotEmpty())
            <button
                wire:click="downloadPdf"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="w-full flex items-center justify-center gap-2 py-3 bg-teal-600 text-white font-medium text-sm rounded-xl hover:bg-teal-700 transition-colors"
            >
                <flux:icon name="arrow-down-tray" class="w-4 h-4" wire:loading.remove wire:target="downloadPdf" />
                <flux:icon name="arrow-path" class="w-4 h-4 animate-spin" wire:loading wire:target="downloadPdf" />
                <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                <span wire:loading wire:target="downloadPdf">Menyiapkan...</span>
            </button>
        @endif

    @else
        {{-- No student data --}}
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Data Tidak Ditemukan</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">Hubungi administrator untuk menghubungkan akun.</p>
                </div>
            </div>
        </div>
    @endif
</div>
