                {{-- ============================================================ --}}
                {{-- Student Report Preview                                        --}}
                {{-- ============================================================ --}}
                @if ($this->selectedSiswa && $this->contextTahunAjaran)
                    <x-ui.card variant="teacher" title="Pratinjau Laporan Siswa" flush>
                        <x-slot:actions>
                            @if ($this->studentActivityData->total() > 0)
                                <div class="shrink-0">
                                    <button wire:click="downloadStudentPdf" wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-rose-200 dark:border-rose-700 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors cursor-pointer">
                                        <flux:icon name="document-text" variant="outline" class="w-3.5 h-3.5" />
                                        <span>Ekspor PDF</span>
                                    </button>
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
                                                class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Nama</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white break-words min-w-0">{{ $this->selectedSiswa->user?->name }}</span>
                                        </div>
                                        <div class="flex">
                                            <span
                                                class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">NIS</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white font-mono">{{ $this->selectedSiswa->nis }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span
                                                class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Kelas</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white">{{ $this->selectedSiswa->kelas?->tingkat_kelas }}-{{ $this->selectedSiswa->kelas?->grup_kelas }}</span>
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

                            @if ($this->studentActivityData->total() > 0)
                                {{-- Summary Cards (dashboard style) --}}
                                @php
                                    $totalActivities = $this->studentActivityData->total();
                                    $avgKehadiranPct = $this->studentReportData->isNotEmpty()
                                        ? $this->studentReportData->avg('rata_kehadiran')
                                        : 0;
                                    $keaktifanWeights = $this->studentReportData
                                        ->pluck('rata_keaktifan')
                                        ->filter()
                                        ->map->weight();
                                    $avgKeaktifan = $keaktifanWeights->isNotEmpty() ? $keaktifanWeights->avg() : 0;
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
                                            {{ number_format($avgKehadiranPct, 1) }}<span
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
                                            {{ $totalActivities }}</p>
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

                                {{-- Riwayat Aktivitas Table --}}
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Riwayat
                                        Aktivitas</p>
                                    <div class="overflow-x-auto">
                                        <flux:table :paginate="$this->studentActivityData">
                                            <flux:table.columns sticky class="bg-white dark:bg-slate-800">
                                                <flux:table.column>Tanggal</flux:table.column>
                                                <flux:table.column>Mata Pelajaran</flux:table.column>
                                                <flux:table.column>Kehadiran</flux:table.column>
                                                <flux:table.column>Keaktifan</flux:table.column>
                                                <flux:table.column>Catatan Guru</flux:table.column>
                                            </flux:table.columns>
                                            <flux:table.rows>
                                                @foreach ($this->studentActivityData as $detail)
                                                    @php
                                                        $kehadiranBadge = match ($detail->kehadiran) {
                                                            \App\Enums\KehadiranStatus::Hadir => ['Hadir', 'green'],
                                                            \App\Enums\KehadiranStatus::Izin => ['Izin', 'amber'],
                                                            \App\Enums\KehadiranStatus::Sakit => ['Sakit', 'amber'],
                                                            \App\Enums\KehadiranStatus::Alpa => ['Alpa', 'red'],
                                                            default => ['-', 'zinc'],
                                                        };
                                                    @endphp
                                                    <flux:table.row :key="$detail->id">
                                                        <flux:table.cell
                                                            class="whitespace-nowrap text-xs tabular-nums text-slate-600 dark:text-slate-300">
                                                            {{ $detail->aktivitasPembelajaran->tanggal->format('d/m/Y') }}
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            <span
                                                                class="text-sm font-medium text-slate-900 dark:text-white">{{ $detail->aktivitasPembelajaran->mataPelajaran?->nama_mapel }}</span>
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            <flux:badge color="{{ $kehadiranBadge[1] }}" size="sm"
                                                                inset="top bottom">{{ $kehadiranBadge[0] }}
                                                            </flux:badge>
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            @if ($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir && $detail->keaktifan)
                                                                @php
                                                                    $keaktifanBadge = match ($detail->keaktifan) {
                                                                        \App\Enums\Keaktifan::SangatAktif => [
                                                                            'Sangat Aktif',
                                                                            'green',
                                                                        ],
                                                                        \App\Enums\Keaktifan::Aktif => [
                                                                            'Aktif',
                                                                            'blue',
                                                                        ],
                                                                        \App\Enums\Keaktifan::Cukup => [
                                                                            'Cukup',
                                                                            'amber',
                                                                        ],
                                                                        \App\Enums\Keaktifan::Pasif => [
                                                                            'Pasif',
                                                                            'red',
                                                                        ],
                                                                    };
                                                                @endphp
                                                                <flux:badge color="{{ $keaktifanBadge[1] }}"
                                                                    size="sm" inset="top bottom">
                                                                    {{ $keaktifanBadge[0] }}</flux:badge>
                                                            @else
                                                                <span class="text-xs text-slate-400">-</span>
                                                            @endif
                                                        </flux:table.cell>
                                                        <flux:table.cell>
                                                            @if ($detail->catatan)
                                                                <span class="text-xs text-slate-600 dark:text-slate-300"
                                                                    title="{{ $detail->catatan }}">{{ Str::limit($detail->catatan, 40) }}</span>
                                                            @else
                                                                <span class="text-xs text-slate-400">-</span>
                                                            @endif
                                                        </flux:table.cell>
                                                    </flux:table.row>
                                                @endforeach
                                            </flux:table.rows>
                                        </flux:table>
                                    </div>
                                </div>

                                {{-- Activity count --}}
                                <div class="text-xs text-slate-400 dark:text-slate-500 text-right">
                                    Total: <strong
                                        class="text-slate-600 dark:text-slate-300">{{ $totalActivities }}</strong>
                                    pertemuan
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <flux:icon name="document-magnifying-glass"
                                        class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-2 text-sm text-slate-500">Belum ada data riwayat aktivitas untuk siswa
                                        ini</p>
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                @endif
