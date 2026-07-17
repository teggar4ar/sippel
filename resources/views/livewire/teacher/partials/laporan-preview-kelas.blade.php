                {{-- ============================================================ --}}
                {{-- Class Report Preview                                          --}}
                {{-- ============================================================ --}}
                @if ($this->selectedKelas && $this->selectedMataPelajaran && $this->contextTahunAjaran)
                    <x-ui.card variant="teacher" title="Pratinjau Laporan Kelas" flush>
                        <x-slot:actions>
                            @if ($this->classReportData->isNotEmpty())
                                <div class="flex gap-2 shrink-0">
                                    <button wire:click="downloadClassPdf" wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-rose-200 dark:border-rose-700 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors cursor-pointer">
                                        <flux:icon name="document-text" variant="outline" class="w-3.5 h-3.5" />
                                        <span>Ekspor PDF</span>
                                    </button>
                                    <form action="{{ route('reports.class.export') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="kelas_id" value="{{ $this->selectedKelas->id }}">
                                        <input type="hidden" name="mata_pelajaran_id"
                                            value="{{ $this->selectedMataPelajaran->id }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-emerald-200 dark:border-emerald-700 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 rounded-lg transition-colors cursor-pointer">
                                            <flux:icon name="table-cells" variant="outline" class="w-3.5 h-3.5" />
                                            <span>Ekspor Xlsx</span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </x-slot:actions>

                        <div class="p-4 space-y-4">
                            {{-- Metadata (view-aktivitas style) --}}
                            <div
                                class="bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-700/60 p-3 sm:p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span
                                                class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Kelas</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white">{{ $this->selectedKelas->tingkat_kelas }}-{{ $this->selectedKelas->grup_kelas }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Mata
                                                Pelajaran</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white">{{ $this->selectedMataPelajaran->nama_mapel }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Guru
                                                Pengampu</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white">{{ $this->selectedMataPelajaran->guru?->name ?? '-' }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Tahun
                                                Ajaran</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white">{{ $this->contextTahunAjaran->nama_tahun }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($this->classReportData->isNotEmpty())
                                {{-- Summary Cards (dashboard style) --}}
                                @php
                                    $avgKehadiran = $this->classReportData->avg('rata_kehadiran');
                                    $keaktifanWeights = $this->classReportData
                                        ->pluck('rata_keaktifan')
                                        ->filter()
                                        ->map->weight();
                                    $avgKeaktifan = $keaktifanWeights->isNotEmpty() ? $keaktifanWeights->avg() : 0;
                                    $totalPertemuan = $this->classReportData->max('total_kehadiran') ?? 0;
                                    $keaktifanAvgLabel = match (true) {
                                        $avgKeaktifan >= 3.5 => [
                                            'Sangat Aktif',
                                            'text-emerald-600 dark:text-emerald-400',
                                        ],
                                        $avgKeaktifan >= 2.5 => ['Aktif', 'text-blue-600 dark:text-blue-400'],
                                        $avgKeaktifan >= 1.5 => ['Cukup', 'text-amber-600 dark:text-amber-400'],
                                        default => ['Pasif', 'text-rose-600 dark:text-rose-400'],
                                    };
                                @endphp
                                <div class="grid grid-cols-3 gap-1.5 sm:gap-3">
                                    <div
                                        class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div
                                            class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="hand-raised"
                                                class="w-4.5 h-4.5 text-emerald-500/70 dark:text-emerald-400/60" />
                                        </div>
                                        <p
                                            class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-500/80 dark:text-emerald-400/70 leading-tight">
                                            <span class="sm:hidden">Kehadiran</span>
                                            <span class="hidden sm:inline">Rata-rata Kehadiran</span>
                                        </p>
                                        <p
                                            class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">
                                            {{ number_format($avgKehadiran, 1) }}<span
                                                class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">%</span>
                                        </p>
                                    </div>
                                    <div
                                        class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div
                                            class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="clipboard-document-check"
                                                class="w-4.5 h-4.5 text-blue-500/70 dark:text-blue-400/60" />
                                        </div>
                                        <p
                                            class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-blue-500/80 dark:text-blue-400/70 leading-tight">
                                            <span class="sm:hidden">Pertemuan</span>
                                            <span class="hidden sm:inline">Total Pertemuan</span>
                                        </p>
                                        <p
                                            class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">
                                            {{ $totalPertemuan }}</p>
                                    </div>
                                    <div
                                        class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div
                                            class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="star"
                                                class="w-4.5 h-4.5 text-amber-500/70 dark:text-amber-400/60" />
                                        </div>
                                        <p
                                            class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-amber-500/80 dark:text-amber-400/70 leading-tight">
                                            Keaktifan</p>
                                        <p
                                            class="text-sm sm:text-xl font-bold {{ $keaktifanAvgLabel[1] }} mt-0.5 sm:mt-1">
                                            {{ $keaktifanAvgLabel[0] }}</p>
                                    </div>
                                </div>

                                {{-- Flux UI Table --}}
                                <div class="overflow-x-auto">
                                    <flux:table container:class="max-h-[55vh]">
                                        <flux:table.columns sticky class="bg-white dark:bg-slate-800">
                                            <flux:table.column class="w-10">No</flux:table.column>
                                            <flux:table.column>Siswa (Nama & NIS)</flux:table.column>
                                            <flux:table.column>Kehadiran</flux:table.column>
                                            <flux:table.column>Rata-Rata Keaktifan</flux:table.column>
                                        </flux:table.columns>
                                        <flux:table.rows>
                                            @foreach ($this->classReportData as $index => $laporan)
                                                @php
                                                    $keaktifanLabel = match ($laporan->rata_keaktifan) {
                                                        \App\Enums\Keaktifan::SangatAktif => ['Sangat Aktif', 'green'],
                                                        \App\Enums\Keaktifan::Aktif => ['Aktif', 'blue'],
                                                        \App\Enums\Keaktifan::Cukup => ['Cukup', 'amber'],
                                                        \App\Enums\Keaktifan::Pasif => ['Pasif', 'red'],
                                                        null => ['-', 'zinc'],
                                                    };
                                                    $kehadiranPct = $laporan->rata_kehadiran;
                                                    $kehadiranColor = match (true) {
                                                        $kehadiranPct >= 90 => 'bg-emerald-500',
                                                        $kehadiranPct >= 75 => 'bg-blue-500',
                                                        $kehadiranPct >= 60 => 'bg-amber-500',
                                                        default => 'bg-rose-500',
                                                    };
                                                @endphp
                                                <flux:table.row :key="$laporan->id">
                                                    <flux:table.cell class="text-slate-400 text-xs tabular-nums">
                                                        {{ $index + 1 }}</flux:table.cell>
                                                    <flux:table.cell>
                                                        <div>
                                                            <p
                                                                class="text-sm font-medium text-slate-900 dark:text-white">
                                                                {{ $laporan->siswa?->user?->name }}</p>
                                                            <p class="text-[11px] text-slate-400 font-mono">
                                                                {{ $laporan->siswa?->nis }}</p>
                                                        </div>
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        <div class="flex items-center gap-2">
                                                            <div
                                                                class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full {{ $kehadiranColor }}"
                                                                    style="width: {{ min($kehadiranPct, 100) }}%">
                                                                </div>
                                                            </div>
                                                            <span
                                                                class="text-xs font-medium tabular-nums text-slate-700 dark:text-slate-300">{{ number_format($kehadiranPct, 1) }}%</span>
                                                        </div>
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        <flux:badge color="{{ $keaktifanLabel[1] }}" size="sm"
                                                            inset="top bottom">{{ $keaktifanLabel[0] }}</flux:badge>
                                                    </flux:table.cell>
                                                </flux:table.row>
                                            @endforeach
                                        </flux:table.rows>
                                    </flux:table>
                                </div>

                                {{-- Student count --}}
                                <div class="text-xs text-slate-400 dark:text-slate-500 text-right">
                                    Total: <strong
                                        class="text-slate-600 dark:text-slate-300">{{ $this->classReportData->count() }}</strong>
                                    siswa
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <flux:icon name="document-magnifying-glass"
                                        class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-2 text-sm text-slate-500">Belum ada data laporan untuk kelas dan mata
                                        pelajaran ini</p>
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                @endif
