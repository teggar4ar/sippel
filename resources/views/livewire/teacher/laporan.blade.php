<div class="space-y-3 overflow-x-hidden">
    {{-- Header --}}
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Laporan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Lihat dan cetak laporan siswa kelas perwalian Anda</p>
    </div>

    @if(!$this->hasKelasWali)
        {{-- No homeroom class assigned --}}
        <div class="p-4 text-center bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
            <flux:icon name="exclamation-triangle" class="mx-auto w-10 h-10 text-amber-500" />
            <p class="mt-2 text-sm font-medium text-amber-800 dark:text-amber-200">Belum Ada Kelas Perwalian</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                Anda belum ditugaskan sebagai wali kelas. Fitur laporan hanya tersedia untuk wali kelas.
            </p>
        </div>
    @else
        {{-- Report Type Selection --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Jenis Laporan</span>
            </div>
            <div class="p-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" wire:model.live="reportType" value="student" class="peer hidden" />
                        <div class="p-3 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600">
                            <div class="flex items-center gap-3">
                                <flux:icon name="user" class="w-5 h-5 text-blue-500" />
                                <div>
                                    <div class="font-medium text-sm text-slate-900 dark:text-white">Laporan Siswa</div>
                                    <div class="text-xs text-slate-500">Laporan individu per siswa</div>
                                </div>
                            </div>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" wire:model.live="reportType" value="class" class="peer hidden" />
                        <div class="p-3 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600">
                            <div class="flex items-center gap-3">
                                <flux:icon name="user-group" class="w-5 h-5 text-emerald-500" />
                                <div>
                                    <div class="font-medium text-sm text-slate-900 dark:text-white">Laporan Kelas</div>
                                    <div class="text-xs text-slate-500">Rekap seluruh siswa per mata pelajaran</div>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Filter Laporan</span>
            </div>
            <div class="p-3">
                @if($reportType === 'student')
                    {{-- Student filter: single horizontal row --}}
                    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="kelasId"
                                label="Kelas Perwalian"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer"
                            >
                                <option value="">Pilih Kelas</option>
                                @foreach($this->kelasWali as $kelas)
                                    <option value="{{ $kelas->id }}">
                                        {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}
                                        ({{ $kelas->tahunAjaran?->nama_tahun }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="siswaId"
                                label="Siswa"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                :disabled="!$kelasId"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer"
                            >
                                <option value="">{{ $kelasId ? 'Pilih Siswa' : 'Pilih kelas dulu' }}</option>
                                @foreach($this->siswaList as $siswa)
                                    <option value="{{ $siswa->id }}">
                                        {{ $siswa->user?->name }} ({{ $siswa->nis }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                        <button
                            wire:click="generatePreview"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors shrink-0 cursor-pointer"
                        >
                            <flux:icon wire:loading.remove wire:target="generatePreview" name="user" class="w-4 h-4" />
                            <flux:icon wire:loading wire:target="generatePreview" name="arrow-path" class="w-4 h-4 animate-spin" />
                            <span>Lihat Pratinjau</span>
                        </button>
                    </div>
                @else
                    {{-- Class filter: single horizontal row --}}
                    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="kelasId"
                                label="Kelas Perwalian"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer"
                            >
                                <option value="">Pilih Kelas</option>
                                @foreach($this->kelasWali as $kelas)
                                    <option value="{{ $kelas->id }}">
                                        {{ $kelas->tingkat_kelas }}-{{ $kelas->grup_kelas }}
                                        ({{ $kelas->tahunAjaran?->nama_tahun }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="mataPelajaranId"
                                label="Mata Pelajaran"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                :disabled="!$kelasId"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer"
                            >
                                <option value="">{{ $kelasId ? 'Pilih Mata Pelajaran' : 'Pilih kelas dulu' }}</option>
                                @foreach($this->mataPelajaranList as $mapel)
                                    <option value="{{ $mapel->id }}">
                                        {{ $mapel->nama_mapel }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:select
                                wire:model.live="sortBy"
                                label="Urut Berdasarkan"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0 cursor-pointer"
                            >
                                <option value="kehadiran">Kehadiran (Tertinggi)</option>
                                <option value="nama">Nama (A-Z)</option>
                            </flux:select>
                        </div>
                        <button
                            wire:click="generatePreview"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="flex items-center justify-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors shrink-0 cursor-pointer"
                        >
                            <flux:icon wire:loading.remove wire:target="generatePreview" name="user-group" class="w-4 h-4" />
                            <flux:icon wire:loading wire:target="generatePreview" name="arrow-path" class="w-4 h-4 animate-spin" />
                            <span>Lihat Pratinjau</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Preview Section --}}
        @if($showPreview)
            @if($reportType === 'student')
                {{-- ============================================================ --}}
                {{-- Student Report Preview                                        --}}
                {{-- ============================================================ --}}
                @if($this->selectedSiswa && $this->contextTahunAjaran)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                        {{-- Header: title + export --}}
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pratinjau Laporan Siswa</h3>
                                @if($this->studentActivityData->total() > 0)
                                    <div class="shrink-0">
                                        <button
                                            wire:click="downloadStudentPdf"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-rose-200 dark:border-rose-700 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors cursor-pointer"
                                        >
                                            <flux:icon name="document-text" class="w-3.5 h-3.5" />
                                            <span>Ekspor PDF</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-4 space-y-4">
                            {{-- Metadata (view-aktivitas style) --}}
                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-700/60 p-3 sm:p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Nama</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white break-words min-w-0">{{ $this->selectedSiswa->user?->name }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">NIS</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white font-mono">{{ $this->selectedSiswa->nis }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Kelas</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->selectedSiswa->kelas?->tingkat_kelas }}-{{ $this->selectedSiswa->kelas?->grup_kelas }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Tahun Ajaran</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->contextTahunAjaran->nama_tahun }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($this->studentActivityData->total() > 0)
                                {{-- Summary Cards (dashboard style) --}}
                                @php
                                    $totalActivities = $this->studentActivityData->total();
                                    $avgKehadiranPct = $this->studentReportData->isNotEmpty() ? $this->studentReportData->avg('rata_kehadiran') : 0;
                                    $avgPartisipasi = $this->studentReportData->isNotEmpty() ? $this->studentReportData->avg('rata_partisipasi') : 0;
                                    $partisipasiAvgLabel = match(true) {
                                        $avgPartisipasi >= 3.5 => ['Sangat Aktif', 'text-emerald-600 dark:text-emerald-400'],
                                        $avgPartisipasi >= 2.5 => ['Aktif', 'text-blue-600 dark:text-blue-400'],
                                        $avgPartisipasi >= 1.5 => ['Cukup', 'text-amber-600 dark:text-amber-400'],
                                        default => ['Pasif', 'text-rose-600 dark:text-rose-400'],
                                    };
                                @endphp
                                <div class="grid grid-cols-3 gap-1.5 sm:gap-3">
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="hand-raised" class="w-4.5 h-4.5 text-emerald-500/70 dark:text-emerald-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-500/80 dark:text-emerald-400/70 leading-tight">
                                            <span class="sm:hidden">Kehadiran</span>
                                            <span class="hidden sm:inline">Rata-rata Kehadiran</span>
                                        </p>
                                        <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ number_format($avgKehadiranPct, 1) }}<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">%</span></p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="clipboard-document-check" class="w-4.5 h-4.5 text-blue-500/70 dark:text-blue-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-blue-500/80 dark:text-blue-400/70 leading-tight">
                                            <span class="sm:hidden">Pertemuan</span>
                                            <span class="hidden sm:inline">Total Pertemuan</span>
                                        </p>
                                        <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $totalActivities }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="star" class="w-4.5 h-4.5 text-amber-500/70 dark:text-amber-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-amber-500/80 dark:text-amber-400/70 leading-tight">Partisipasi</p>
                                        <p class="text-sm sm:text-xl font-bold {{ $partisipasiAvgLabel[1] }} mt-0.5 sm:mt-1">{{ $partisipasiAvgLabel[0] }}</p>
                                    </div>
                                </div>

                                {{-- Riwayat Aktivitas Table --}}
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Riwayat Aktivitas</p>
                                    <flux:table :paginate="$this->studentActivityData">
                                        <flux:table.columns sticky class="bg-white dark:bg-slate-800">
                                            <flux:table.column>Tanggal</flux:table.column>
                                            <flux:table.column>Mata Pelajaran</flux:table.column>
                                            <flux:table.column>Kehadiran</flux:table.column>
                                            <flux:table.column>Partisipasi</flux:table.column>
                                            <flux:table.column>Catatan Guru</flux:table.column>
                                        </flux:table.columns>
                                        <flux:table.rows>
                                            @foreach($this->studentActivityData as $detail)
                                                @php
                                                    $kehadiranBadge = match($detail->kehadiran) {
                                                        \App\Enums\KehadiranStatus::Hadir => ['Hadir', 'green'],
                                                        \App\Enums\KehadiranStatus::Izin  => ['Izin', 'amber'],
                                                        \App\Enums\KehadiranStatus::Sakit => ['Sakit', 'amber'],
                                                        \App\Enums\KehadiranStatus::Alpa  => ['Alpa', 'red'],
                                                        default => ['-', 'zinc'],
                                                    };
                                                @endphp
                                                <flux:table.row :key="$detail->id">
                                                    <flux:table.cell class="whitespace-nowrap text-xs tabular-nums text-slate-600 dark:text-slate-300">
                                                        {{ $detail->aktivitasPembelajaran->tanggal->format('d/m/Y') }}
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $detail->aktivitasPembelajaran->mataPelajaran?->nama_mapel }}</span>
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        <flux:badge color="{{ $kehadiranBadge[1] }}" size="sm" inset="top bottom">{{ $kehadiranBadge[0] }}</flux:badge>
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        @if($detail->kehadiran === \App\Enums\KehadiranStatus::Hadir && $detail->partisipasi)
                                                            @php
                                                                $partBadge = match((int) $detail->partisipasi) {
                                                                    4 => ['Sangat Aktif', 'green'],
                                                                    3 => ['Aktif', 'blue'],
                                                                    2 => ['Cukup', 'amber'],
                                                                    1 => ['Pasif', 'red'],
                                                                    default => ['-', 'zinc'],
                                                                };
                                                            @endphp
                                                            <flux:badge color="{{ $partBadge[1] }}" size="sm" inset="top bottom">{{ $partBadge[0] }}</flux:badge>
                                                        @else
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </flux:table.cell>
                                                    <flux:table.cell>
                                                        @if($detail->catatan)
                                                            <span class="text-xs text-slate-600 dark:text-slate-300" title="{{ $detail->catatan }}">{{ Str::limit($detail->catatan, 40) }}</span>
                                                        @else
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </flux:table.cell>
                                                </flux:table.row>
                                            @endforeach
                                        </flux:table.rows>
                                    </flux:table>
                                </div>

                                {{-- Activity count --}}
                                <div class="text-xs text-slate-400 dark:text-slate-500 text-right">
                                    Total: <strong class="text-slate-600 dark:text-slate-300">{{ $totalActivities }}</strong> pertemuan
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <flux:icon name="document-magnifying-glass" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-2 text-sm text-slate-500">Belum ada data riwayat aktivitas untuk siswa ini</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                {{-- ============================================================ --}}
                {{-- Class Report Preview                                          --}}
                {{-- ============================================================ --}}
                @if($this->selectedKelas && $this->selectedMataPelajaran && $this->contextTahunAjaran)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                        {{-- Header: title + export buttons --}}
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Pratinjau Laporan Kelas</h3>
                                @if($this->classReportData->isNotEmpty())
                                    <div class="flex gap-2 shrink-0">
                                        <button
                                            wire:click="downloadClassPdf"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-rose-200 dark:border-rose-700 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors cursor-pointer"
                                        >
                                            <flux:icon name="document-text" class="w-3.5 h-3.5" />
                                            <span>Ekspor PDF</span>
                                        </button>
                                        <button
                                            wire:click="exportClassExcel"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border border-emerald-200 dark:border-emerald-700 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 rounded-lg transition-colors cursor-pointer"
                                        >
                                            <flux:icon name="table-cells" class="w-3.5 h-3.5" />
                                            <span>Ekspor Xlsx</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="p-4 space-y-4">
                            {{-- Metadata (view-aktivitas style) --}}
                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-700/60 p-3 sm:p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Kelas</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->selectedKelas->tingkat_kelas }}-{{ $this->selectedKelas->grup_kelas }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Mata Pelajaran</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->selectedMataPelajaran->nama_mapel }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Guru Pengampu</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->selectedMataPelajaran->guru?->name ?? '-' }}</span>
                                        </div>
                                        <div class="flex">
                                            <span class="text-slate-500 dark:text-slate-400 w-28 sm:w-32 shrink-0">Tahun Ajaran</span>
                                            <span class="text-slate-400 mr-2">:</span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $this->contextTahunAjaran->nama_tahun }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($this->classReportData->isNotEmpty())
                                {{-- Summary Cards (dashboard style) --}}
                                @php
                                    $avgKehadiran = $this->classReportData->avg('rata_kehadiran');
                                    $avgPartisipasi = $this->classReportData->avg('rata_partisipasi');
                                    $totalPertemuan = $this->classReportData->max('total_kehadiran') ?? 0;
                                    $partisipasiAvgLabel = match(true) {
                                        $avgPartisipasi >= 3.5 => ['Sangat Aktif', 'text-emerald-600 dark:text-emerald-400'],
                                        $avgPartisipasi >= 2.5 => ['Aktif', 'text-blue-600 dark:text-blue-400'],
                                        $avgPartisipasi >= 1.5 => ['Cukup', 'text-amber-600 dark:text-amber-400'],
                                        default => ['Pasif', 'text-rose-600 dark:text-rose-400'],
                                    };
                                @endphp
                                <div class="grid grid-cols-3 gap-1.5 sm:gap-3">
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="hand-raised" class="w-4.5 h-4.5 text-emerald-500/70 dark:text-emerald-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-500/80 dark:text-emerald-400/70 leading-tight">
                                            <span class="sm:hidden">Kehadiran</span>
                                            <span class="hidden sm:inline">Rata-rata Kehadiran</span>
                                        </p>
                                        <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ number_format($avgKehadiran, 1) }}<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">%</span></p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="clipboard-document-check" class="w-4.5 h-4.5 text-blue-500/70 dark:text-blue-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-blue-500/80 dark:text-blue-400/70 leading-tight">
                                            <span class="sm:hidden">Pertemuan</span>
                                            <span class="hidden sm:inline">Total Pertemuan</span>
                                        </p>
                                        <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $totalPertemuan }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
                                        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg items-center justify-center">
                                            <flux:icon name="star" class="w-4.5 h-4.5 text-amber-500/70 dark:text-amber-400/60" />
                                        </div>
                                        <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-amber-500/80 dark:text-amber-400/70 leading-tight">Partisipasi</p>
                                        <p class="text-sm sm:text-xl font-bold {{ $partisipasiAvgLabel[1] }} mt-0.5 sm:mt-1">{{ $partisipasiAvgLabel[0] }}</p>
                                    </div>
                                </div>

                                {{-- Flux UI Table --}}
                                <flux:table container:class="max-h-[55vh]">
                                    <flux:table.columns sticky class="bg-white dark:bg-slate-800">
                                        <flux:table.column class="w-10">No</flux:table.column>
                                        <flux:table.column>Siswa (Nama & NIS)</flux:table.column>
                                        <flux:table.column>Kehadiran</flux:table.column>
                                        <flux:table.column>Rata-Rata Partisipasi</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach($this->classReportData as $index => $laporan)
                                            @php
                                                $partisipasiLabel = match(true) {
                                                    $laporan->rata_partisipasi >= 4 => ['Sangat Aktif', 'green'],
                                                    $laporan->rata_partisipasi >= 3 => ['Aktif', 'blue'],
                                                    $laporan->rata_partisipasi >= 2 => ['Cukup', 'amber'],
                                                    default => ['Pasif', 'red'],
                                                };
                                                $kehadiranPct = $laporan->rata_kehadiran;
                                                $kehadiranColor = match(true) {
                                                    $kehadiranPct >= 90 => 'bg-emerald-500',
                                                    $kehadiranPct >= 75 => 'bg-blue-500',
                                                    $kehadiranPct >= 60 => 'bg-amber-500',
                                                    default => 'bg-rose-500',
                                                };
                                            @endphp
                                            <flux:table.row :key="$laporan->id">
                                                <flux:table.cell class="text-slate-400 text-xs tabular-nums">{{ $index + 1 }}</flux:table.cell>
                                                <flux:table.cell>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $laporan->siswa?->user?->name }}</p>
                                                        <p class="text-[11px] text-slate-400 font-mono">{{ $laporan->siswa?->nis }}</p>
                                                    </div>
                                                </flux:table.cell>
                                                <flux:table.cell>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full {{ $kehadiranColor }}" style="width: {{ min($kehadiranPct, 100) }}%"></div>
                                                        </div>
                                                        <span class="text-xs font-medium tabular-nums text-slate-700 dark:text-slate-300">{{ number_format($kehadiranPct, 1) }}%</span>
                                                    </div>
                                                </flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:badge color="{{ $partisipasiLabel[1] }}" size="sm" inset="top bottom">{{ $partisipasiLabel[0] }}</flux:badge>
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>

                                {{-- Student count --}}
                                <div class="text-xs text-slate-400 dark:text-slate-500 text-right">
                                    Total: <strong class="text-slate-600 dark:text-slate-300">{{ $this->classReportData->count() }}</strong> siswa
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <flux:icon name="document-magnifying-glass" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-2 text-sm text-slate-500">Belum ada data laporan untuk kelas dan mata pelajaran ini</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        @endif
    @endif
</div>
