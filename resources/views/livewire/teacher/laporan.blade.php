<div class="space-y-6">
    {{-- Header --}}
    <div>
        <flux:heading size="xl" level="1">Laporan</flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">Lihat dan cetak laporan siswa kelas perwalian Anda</flux:text>
    </div>

    <flux:separator variant="subtle" />

    @if(!$this->hasKelasWali)
        {{-- No homeroom class assigned --}}
        <div class="p-6 sm:p-12 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700">
            <div class="text-center max-w-md mx-auto">
                <flux:icon name="exclamation-triangle" class="w-16 h-16 mx-auto text-amber-500" />
                <flux:heading size="lg" class="mt-4">Belum Ada Kelas Perwalian</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Anda belum ditugaskan sebagai wali kelas. Fitur laporan hanya tersedia untuk wali kelas.
                    Silakan hubungi administrator untuk penugasan wali kelas.
                </flux:text>
            </div>
        </div>
    @else
        {{-- Report Type Selection --}}
        <div class="p-4 lg:p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
            <flux:heading size="sm">Jenis Laporan</flux:heading>
            <div class="flex flex-col sm:flex-row gap-3">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" wire:model.live="reportType" value="student" class="peer hidden" />
                    <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                        <div class="flex items-center gap-3">
                            <flux:icon name="user" class="w-6 h-6 text-blue-500" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">Laporan Siswa</div>
                                <div class="text-sm text-zinc-500">Laporan individu per siswa</div>
                            </div>
                        </div>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" wire:model.live="reportType" value="class" class="peer hidden" />
                    <div class="p-4 rounded-lg border-2 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600">
                        <div class="flex items-center gap-3">
                            <flux:icon name="user-group" class="w-6 h-6 text-green-500" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">Laporan Kelas</div>
                                <div class="text-sm text-zinc-500">Rekap seluruh siswa per mata pelajaran</div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="p-4 lg:p-6 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
            <flux:heading size="sm">Filter Laporan</flux:heading>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Kelas Selection --}}
                <div>
                    <flux:select
                        wire:model.live="kelasId"
                        label="Kelas Perwalian"
                        placeholder="Pilih kelas..."
                        class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
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

                {{-- Tahun Ajaran Selection --}}
                <div>
                    <flux:select
                        wire:model.live="tahunAjaranId"
                        label="Tahun Ajaran"
                        placeholder="Pilih tahun ajaran..."
                        class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
                    >
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach($this->tahunAjaranList as $ta)
                            <option value="{{ $ta->id }}">
                                {{ $ta->nama_tahun }} - {{ $ta->semester }}
                                @if($ta->status) (Aktif) @endif
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
                            placeholder="Pilih siswa..."
                            :disabled="!$kelasId"
                            class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
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
                            placeholder="Pilih mata pelajaran..."
                            :disabled="!$kelasId"
                            class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
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
                            class="focus:border-blue-500 focus:outline-none focus-visible:outline-none focus:ring-0 focus-visible:ring-0"
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
            <div class="pt-2">
                <flux:button
                    wire:click="generatePreview"
                    variant="primary"
                    class="w-full sm:w-auto"
                    icon="eye"
                >
                    Lihat Pratinjau
                </flux:button>
            </div>
        </div>

        {{-- Preview Section --}}
        @if($showPreview)
            @if($reportType === 'student')
                {{-- Student Report Preview --}}
                @if($this->selectedSiswa && $this->selectedTahunAjaran)
                    <div class="p-4 lg:p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <flux:heading size="sm">Pratinjau Laporan Siswa</flux:heading>
                            @if($this->studentReportData->isNotEmpty())
                                <flux:button
                                    wire:click="downloadStudentPdf"
                                    variant="primary"
                                    icon="arrow-down-tray"
                                    size="sm"
                                >
                                    Download PDF
                                </flux:button>
                            @endif
                        </div>

                        {{-- Student Info --}}
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-zinc-500">NIS:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedSiswa->nis }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">Kelas:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedSiswa->kelas?->tingkat_kelas }}-{{ $this->selectedSiswa->kelas?->grup_kelas }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-zinc-500">Nama:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedSiswa->user?->name }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-zinc-500">Tahun Ajaran:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedTahunAjaran->nama_tahun }} - {{ $this->selectedTahunAjaran->semester }}</span>
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
                            <div class="grid grid-cols-3 gap-3">
                                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ number_format($avgKehadiran, 1) }}%</div>
                                    <div class="text-xs text-zinc-500">Kehadiran</div>
                                </div>
                                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ number_format($avgNilai, 1) }}</div>
                                    <div class="text-xs text-zinc-500">Rata-rata Nilai</div>
                                </div>
                                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">{{ number_format($avgPartisipasi, 1) }}/5</div>
                                    <div class="text-xs text-zinc-500">Partisipasi</div>
                                </div>
                            </div>

                            {{-- Subject Details --}}
                            <div class="space-y-2">
                                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Detail per Mata Pelajaran</flux:text>
                                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($this->studentReportData as $laporan)
                                        <div class="py-3 flex items-center justify-between">
                                            <div class="font-medium">{{ $laporan->mataPelajaran?->nama_mapel }}</div>
                                            <div class="flex gap-4 text-sm">
                                                <span class="text-green-600">{{ number_format($laporan->rata_kehadiran, 1) }}%</span>
                                                <span class="text-blue-600 font-medium">{{ number_format($laporan->rata_nilai, 1) }}</span>
                                                <span class="text-yellow-600">{{ $laporan->rata_partisipasi }}/5</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <flux:icon name="document-magnifying-glass" class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                                <flux:text class="mt-2 text-zinc-500">Belum ada data laporan untuk siswa ini</flux:text>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                {{-- Class Report Preview --}}
                @if($this->selectedKelas && $this->selectedMataPelajaran && $this->selectedTahunAjaran)
                    <div class="p-4 lg:p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <flux:heading size="sm">Pratinjau Laporan Kelas</flux:heading>
                            @if($this->classReportData->isNotEmpty())
                                <flux:button
                                    wire:click="downloadClassPdf"
                                    variant="primary"
                                    icon="arrow-down-tray"
                                    size="sm"
                                >
                                    Download PDF
                                </flux:button>
                            @endif
                        </div>

                        {{-- Class Info --}}
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-zinc-500">Kelas:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedKelas->tingkat_kelas }}-{{ $this->selectedKelas->grup_kelas }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">Wali Kelas:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedKelas->waliKelas?->name ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">Mata Pelajaran:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedMataPelajaran->nama_mapel }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500">Guru:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedMataPelajaran->guru?->name ?? '-' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-zinc-500">Tahun Ajaran:</span>
                                    <span class="font-medium ml-1">{{ $this->selectedTahunAjaran->nama_tahun }} - {{ $this->selectedTahunAjaran->semester }}</span>
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
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                    <div class="text-xl font-bold text-green-600">{{ number_format($avgKehadiran, 1) }}%</div>
                                    <div class="text-xs text-zinc-500">Rata-rata Kehadiran</div>
                                </div>
                                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div class="text-xl font-bold text-blue-600">{{ number_format($avgNilai, 1) }}</div>
                                    <div class="text-xs text-zinc-500">Rata-rata Nilai</div>
                                </div>
                                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                    <div class="text-xl font-bold text-yellow-600">{{ number_format($avgPartisipasi, 1) }}/5</div>
                                    <div class="text-xs text-zinc-500">Rata-rata Partisipasi</div>
                                </div>
                                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                    <div class="text-xl font-bold text-purple-600">{{ $tuntas }}/{{ $this->classReportData->count() }}</div>
                                    <div class="text-xs text-zinc-500">Siswa Tuntas (≥70)</div>
                                </div>
                            </div>

                            {{-- Student Count Info --}}
                            <div class="flex items-center justify-between text-sm text-zinc-500">
                                <span>Total: <strong class="text-zinc-900 dark:text-white">{{ $this->classReportData->count() }}</strong> siswa</span>
                                @if($this->classReportData->count() > 10)
                                    <span class="text-xs">(scroll untuk melihat semua)</span>
                                @endif
                            </div>

                            {{-- Student List - Mobile Cards (shown on small screens) --}}
                            <div class="sm:hidden space-y-3 max-h-[60vh] overflow-y-auto pr-1 -mr-1 scroll-smooth">
                                @foreach($this->classReportData as $index => $laporan)
                                    @php
                                        $cardClass = match($index) {
                                            0 => 'border-l-4 border-l-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
                                            1 => 'border-l-4 border-l-zinc-400 bg-zinc-100 dark:bg-zinc-800',
                                            2 => 'border-l-4 border-l-orange-400 bg-orange-50 dark:bg-orange-900/20',
                                            default => 'border-l-4 border-l-transparent',
                                        };
                                        $nilaiClass = match(true) {
                                            $laporan->rata_nilai >= 85 => 'text-green-600 font-bold',
                                            $laporan->rata_nilai >= 70 => 'text-blue-600',
                                            $laporan->rata_nilai >= 55 => 'text-yellow-600',
                                            default => 'text-red-600',
                                        };
                                    @endphp
                                    <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 {{ $cardClass }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold rounded-full bg-zinc-200 dark:bg-zinc-700">{{ $index + 1 }}</span>
                                                    <span class="font-medium text-zinc-900 dark:text-white truncate">{{ $laporan->siswa?->user?->name }}</span>
                                                </div>
                                                <div class="text-xs text-zinc-500 mt-1 ml-8">NIS: {{ $laporan->siswa?->nis }}</div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div class="text-lg {{ $nilaiClass }}">{{ number_format($laporan->rata_nilai, 1) }}</div>
                                                <div class="text-xs text-zinc-500">Nilai</div>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-700 flex justify-between text-xs">
                                            <div>
                                                <span class="text-zinc-500">Kehadiran:</span>
                                                <span class="font-medium text-green-600 ml-1">{{ number_format($laporan->rata_kehadiran, 1) }}%</span>
                                            </div>
                                            <div>
                                                <span class="text-zinc-500">Partisipasi:</span>
                                                <span class="font-medium text-yellow-600 ml-1">{{ $laporan->rata_partisipasi }}/5</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Student List Table (shown on larger screens) --}}
                            <div class="hidden sm:block max-h-[50vh] overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-white dark:bg-zinc-800 z-10">
                                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                            <th class="text-left py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">No</th>
                                            <th class="text-left py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">Nama Siswa</th>
                                            <th class="text-center py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">NIS</th>
                                            <th class="text-center py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">Kehadiran</th>
                                            <th class="text-center py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">Nilai</th>
                                            <th class="text-center py-2 px-2 font-medium text-zinc-600 dark:text-zinc-400">Partisipasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach($this->classReportData as $index => $laporan)
                                            @php
                                                $rowClass = match($index) {
                                                    0 => 'bg-yellow-50 dark:bg-yellow-900/20',
                                                    1 => 'bg-zinc-100 dark:bg-zinc-800',
                                                    2 => 'bg-orange-50 dark:bg-orange-900/20',
                                                    default => '',
                                                };
                                                $nilaiClass = match(true) {
                                                    $laporan->rata_nilai >= 85 => 'text-green-600 font-bold',
                                                    $laporan->rata_nilai >= 70 => 'text-blue-600',
                                                    $laporan->rata_nilai >= 55 => 'text-yellow-600',
                                                    default => 'text-red-600',
                                                };
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td class="py-2 px-2">{{ $index + 1 }}</td>
                                                <td class="py-2 px-2 font-medium">{{ $laporan->siswa?->user?->name }}</td>
                                                <td class="py-2 px-2 text-center text-zinc-500">{{ $laporan->siswa?->nis }}</td>
                                                <td class="py-2 px-2 text-center">{{ number_format($laporan->rata_kehadiran, 1) }}%</td>
                                                <td class="py-2 px-2 text-center {{ $nilaiClass }}">{{ number_format($laporan->rata_nilai, 1) }}</td>
                                                <td class="py-2 px-2 text-center">{{ $laporan->rata_partisipasi }}/5</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-xs text-zinc-500">
                                <strong>Keterangan:</strong>
                                <span class="inline-block w-3 h-3 rounded bg-yellow-400 ml-2"></span> Peringkat 1
                                <span class="inline-block w-3 h-3 rounded bg-zinc-400 ml-2"></span> Peringkat 2
                                <span class="inline-block w-3 h-3 rounded bg-orange-400 ml-2"></span> Peringkat 3
                            </div>
                        @else
                            <div class="text-center py-8">
                                <flux:icon name="document-magnifying-glass" class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                                <flux:text class="mt-2 text-zinc-500">Belum ada data laporan untuk kelas dan mata pelajaran ini</flux:text>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        @endif
    @endif
</div>
