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
            <div class="p-3 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Kelas Selection --}}
                    <div>
                        <flux:select
                            wire:model.live="kelasId"
                            label="Kelas Perwalian"
                            label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                            class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
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

                    @if($reportType === 'student')
                        {{-- Siswa Selection (for student report) --}}
                        <div class="sm:col-span-2">
                            <flux:select
                                wire:model.live="siswaId"
                                label="Siswa"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                :disabled="!$kelasId"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                            >
                                <option value="">{{ $kelasId ? 'Pilih Siswa' : 'Pilih kelas terlebih dahulu' }}</option>
                                @foreach($this->siswaList as $siswa)
                                    <option value="{{ $siswa->id }}">
                                        {{ $siswa->user?->name }} ({{ $siswa->nis }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>
                    @else
                        {{-- Mata Pelajaran Selection (for class report) --}}
                        <div>
                            <flux:select
                                wire:model.live="mataPelajaranId"
                                label="Mata Pelajaran"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                :disabled="!$kelasId"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                            >
                                <option value="">{{ $kelasId ? 'Pilih Mata Pelajaran' : 'Pilih kelas terlebih dahulu' }}</option>
                                @foreach($this->mataPelajaranList as $mapel)
                                    <option value="{{ $mapel->id }}">
                                        {{ $mapel->nama_mapel }} ({{ $mapel->guru?->name ?? 'Belum ada guru' }})
                                    </option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Sort By (for class report) --}}
                        <div>
                            <flux:select
                                wire:model.live="sortBy"
                                label="Urutkan Berdasarkan"
                                label:class="text-xs font-medium text-slate-600 dark:text-slate-400"
                                class="border-slate-200 dark:border-slate-600 dark:bg-slate-900 focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                            >
                                <option value="nilai">Nilai (Tertinggi)</option>
                                <option value="nilai_asc">Nilai (Terendah)</option>
                                <option value="kehadiran">Kehadiran (Tertinggi)</option>
                                <option value="nama">Nama (A-Z)</option>
                            </flux:select>
                        </div>
                    @endif
                </div>

                {{-- Generate Preview Button --}}
                <div class="pt-1">
                    <button
                        wire:click="generatePreview"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors"
                    >
                        <flux:icon wire:loading.remove wire:target="generatePreview" name="eye" class="w-4 h-4" />
                        <flux:icon wire:loading wire:target="generatePreview" name="arrow-path" class="w-4 h-4 animate-spin" />
                        <span>Lihat Pratinjau</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Preview Section --}}
        @if($showPreview)
            @if($reportType === 'student')
                {{-- Student Report Preview --}}
                @if($this->selectedSiswa && $this->contextTahunAjaran)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">Pratinjau Laporan Siswa</span>
                            @if($this->studentReportData->isNotEmpty())
                                <button
                                    wire:click="downloadStudentPdf"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                                >
                                    <flux:icon name="arrow-down-tray" class="w-3.5 h-3.5" />
                                    <span>Download PDF</span>
                                </button>
                            @endif
                        </div>
                        <div class="p-3 space-y-3">
                            {{-- Student Info --}}
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-slate-500">NIS:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedSiswa->nis }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Kelas:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedSiswa->kelas?->tingkat_kelas }}-{{ $this->selectedSiswa->kelas?->grup_kelas }}</span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-slate-500">Nama:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedSiswa->user?->name }}</span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-slate-500">Tahun Ajaran:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->contextTahunAjaran->nama_tahun }} - {{ $this->contextTahunAjaran->semester }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($this->studentReportData->isNotEmpty())
                                {{-- Summary Stats --}}
                                @php
                                    $avgKehadiran = $this->studentReportData->avg('rata_kehadiran');
                                    $avgNilai = $this->studentReportData->avg('rata_nilai');
                                    $avgPartisipasi = $this->studentReportData->avg('rata_partisipasi');
                                @endphp
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="text-center p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                        <div class="text-lg font-bold text-emerald-600">{{ number_format($avgKehadiran, 1) }}%</div>
                                        <div class="text-[10px] text-slate-500">Kehadiran</div>
                                    </div>
                                    <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="text-lg font-bold text-blue-600">{{ number_format($avgNilai, 1) }}</div>
                                        <div class="text-[10px] text-slate-500">Rata-rata Nilai</div>
                                    </div>
                                    <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <div class="text-lg font-bold text-amber-600">{{ number_format($avgPartisipasi, 1) }}/5</div>
                                        <div class="text-[10px] text-slate-500">Partisipasi</div>
                                    </div>
                                </div>

                                {{-- Subject Details --}}
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Detail per Mata Pelajaran</p>
                                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                                        @foreach($this->studentReportData as $laporan)
                                            <div class="py-2 flex items-center justify-between text-sm">
                                                <div class="font-medium text-slate-900 dark:text-white">{{ $laporan->mataPelajaran?->nama_mapel }}</div>
                                                <div class="flex gap-3 text-xs">
                                                    <span class="text-emerald-600">{{ number_format($laporan->rata_kehadiran, 1) }}%</span>
                                                    <span class="text-blue-600 font-medium">{{ number_format($laporan->rata_nilai, 1) }}</span>
                                                    <span class="text-amber-600">{{ $laporan->rata_partisipasi }}/5</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-6">
                                    <flux:icon name="document-magnifying-glass" class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600" />
                                    <p class="mt-2 text-sm text-slate-500">Belum ada data laporan untuk siswa ini</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                {{-- Class Report Preview --}}
                @if($this->selectedKelas && $this->selectedMataPelajaran && $this->contextTahunAjaran)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">Pratinjau Laporan Kelas</span>
                            @if($this->classReportData->isNotEmpty())
                                <div class="flex gap-2">
                                    <button
                                        wire:click="downloadClassPdf"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                                    >
                                        <flux:icon name="arrow-down-tray" class="w-3.5 h-3.5" />
                                        <span>Download PDF</span>
                                    </button>

                                    <button
                                        wire:click="exportClassExcel"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors"
                                    >
                                        <flux:icon name="table-cells" class="w-3.5 h-3.5" />
                                        <span>Export Excel</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="p-3 space-y-3">
                            {{-- Class Info --}}
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-slate-500">Kelas:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedKelas->tingkat_kelas }}-{{ $this->selectedKelas->grup_kelas }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Wali Kelas:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedKelas->waliKelas?->name ?? '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Mata Pelajaran:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedMataPelajaran->nama_mapel }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Guru:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->selectedMataPelajaran->guru?->name ?? '-' }}</span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-slate-500">Tahun Ajaran:</span>
                                        <span class="font-medium text-slate-900 dark:text-white ml-1">{{ $this->contextTahunAjaran->nama_tahun }} - {{ $this->contextTahunAjaran->semester }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($this->classReportData->isNotEmpty())
                                {{-- Class Summary Stats --}}
                                @php
                                    $avgKehadiran = $this->classReportData->avg('rata_kehadiran');
                                    $avgNilai = $this->classReportData->avg('rata_nilai');
                                    $avgPartisipasi = $this->classReportData->avg('rata_partisipasi');
                                    $tuntas = $this->classReportData->where('rata_nilai', '>=', 70)->count();
                                @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <div class="text-center p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                        <div class="text-base font-bold text-emerald-600">{{ number_format($avgKehadiran, 1) }}%</div>
                                        <div class="text-[10px] text-slate-500">Kehadiran</div>
                                    </div>
                                    <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="text-base font-bold text-blue-600">{{ number_format($avgNilai, 1) }}</div>
                                        <div class="text-[10px] text-slate-500">Nilai</div>
                                    </div>
                                    <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <div class="text-base font-bold text-amber-600">{{ number_format($avgPartisipasi, 1) }}/5</div>
                                        <div class="text-[10px] text-slate-500">Partisipasi</div>
                                    </div>
                                    <div class="text-center p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                        <div class="text-base font-bold text-purple-600">{{ $tuntas }}/{{ $this->classReportData->count() }}</div>
                                        <div class="text-[10px] text-slate-500">Tuntas (≥70)</div>
                                    </div>
                                </div>

                                {{-- Student Count Info --}}
                                <div class="flex items-center justify-between text-xs text-slate-500">
                                    <span>Total: <strong class="text-slate-900 dark:text-white">{{ $this->classReportData->count() }}</strong> siswa</span>
                                    @if($this->classReportData->count() > 10)
                                        <span class="text-[10px]">(scroll untuk melihat semua)</span>
                                    @endif
                                </div>

                                {{-- Student List - Mobile Cards (shown on small screens) --}}
                                <div class="sm:hidden space-y-2 max-h-[55vh] overflow-y-auto pr-1 -mr-1 scroll-smooth">
                                    @foreach($this->classReportData as $index => $laporan)
                                        @php
                                            $cardClass = match($index) {
                                                0 => 'border-l-4 border-l-amber-400 bg-amber-50 dark:bg-amber-900/20',
                                                1 => 'border-l-4 border-l-slate-400 bg-slate-100 dark:bg-slate-800',
                                                2 => 'border-l-4 border-l-orange-400 bg-orange-50 dark:bg-orange-900/20',
                                                default => 'border-l-4 border-l-transparent',
                                            };
                                            $nilaiClass = match(true) {
                                                $laporan->rata_nilai >= 85 => 'text-emerald-600 font-bold',
                                                $laporan->rata_nilai >= 70 => 'text-blue-600',
                                                $laporan->rata_nilai >= 55 => 'text-amber-600',
                                                default => 'text-red-600',
                                            };
                                        @endphp
                                        <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 {{ $cardClass }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-slate-200 dark:bg-slate-700">{{ $index + 1 }}</span>
                                                        <span class="font-medium text-sm text-slate-900 dark:text-white truncate">{{ $laporan->siswa?->user?->name }}</span>
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 mt-0.5 ml-7">NIS: {{ $laporan->siswa?->nis }}</div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <div class="text-base {{ $nilaiClass }}">{{ number_format($laporan->rata_nilai, 1) }}</div>
                                                    <div class="text-[10px] text-slate-500">Nilai</div>
                                                </div>
                                            </div>
                                            <div class="mt-1.5 pt-1.5 border-t border-slate-200 dark:border-slate-700 flex justify-between text-[10px]">
                                                <div>
                                                    <span class="text-slate-500">Kehadiran:</span>
                                                    <span class="font-medium text-emerald-600 ml-1">{{ number_format($laporan->rata_kehadiran, 1) }}%</span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-500">Partisipasi:</span>
                                                    <span class="font-medium text-amber-600 ml-1">{{ $laporan->rata_partisipasi }}/5</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Student List Table (shown on larger screens) --}}
                                <div class="hidden sm:block max-h-[50vh] overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-white dark:bg-slate-800 z-10">
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left py-2 px-2 font-medium text-slate-600 dark:text-slate-400">No</th>
                                                <th class="text-left py-2 px-2 font-medium text-slate-600 dark:text-slate-400">Nama Siswa</th>
                                                <th class="text-center py-2 px-2 font-medium text-slate-600 dark:text-slate-400">NIS</th>
                                                <th class="text-center py-2 px-2 font-medium text-slate-600 dark:text-slate-400">Kehadiran</th>
                                                <th class="text-center py-2 px-2 font-medium text-slate-600 dark:text-slate-400">Nilai</th>
                                                <th class="text-center py-2 px-2 font-medium text-slate-600 dark:text-slate-400">Partisipasi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            @foreach($this->classReportData as $index => $laporan)
                                                @php
                                                    $rowClass = match($index) {
                                                        0 => 'bg-amber-50 dark:bg-amber-900/20',
                                                        1 => 'bg-slate-100 dark:bg-slate-800',
                                                        2 => 'bg-orange-50 dark:bg-orange-900/20',
                                                        default => '',
                                                    };
                                                    $nilaiClass = match(true) {
                                                        $laporan->rata_nilai >= 85 => 'text-emerald-600 font-bold',
                                                        $laporan->rata_nilai >= 70 => 'text-blue-600',
                                                        $laporan->rata_nilai >= 55 => 'text-amber-600',
                                                        default => 'text-red-600',
                                                    };
                                                @endphp
                                                <tr class="{{ $rowClass }}">
                                                    <td class="py-2 px-2">{{ $index + 1 }}</td>
                                                    <td class="py-2 px-2 font-medium text-slate-900 dark:text-white">{{ $laporan->siswa?->user?->name }}</td>
                                                    <td class="py-2 px-2 text-center text-slate-500">{{ $laporan->siswa?->nis }}</td>
                                                    <td class="py-2 px-2 text-center">{{ number_format($laporan->rata_kehadiran, 1) }}%</td>
                                                    <td class="py-2 px-2 text-center {{ $nilaiClass }}">{{ number_format($laporan->rata_nilai, 1) }}</td>
                                                    <td class="py-2 px-2 text-center">{{ $laporan->rata_partisipasi }}/5</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-[10px] text-slate-500">
                                    <strong>Keterangan:</strong>
                                    <span class="inline-block w-2.5 h-2.5 rounded bg-amber-400 ml-2"></span> Peringkat 1
                                    <span class="inline-block w-2.5 h-2.5 rounded bg-slate-400 ml-2"></span> Peringkat 2
                                    <span class="inline-block w-2.5 h-2.5 rounded bg-orange-400 ml-2"></span> Peringkat 3
                                </div>
                            @else
                                <div class="text-center py-6">
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
