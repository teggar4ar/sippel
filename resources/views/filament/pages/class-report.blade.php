<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-group class="h-5 w-5 text-primary-500" />
                    Pilih Kelas & Mata Pelajaran
                </div>
            </x-slot>

            <form wire:submit="generatePreview" class="space-y-4">
                {{ $this->form }}

                <div class="flex gap-3">
                    <x-filament::button type="submit" icon="heroicon-o-eye">
                        Lihat Preview
                    </x-filament::button>

                    @if($previewData && $previewData['hasData'])
                        <x-filament::button
                            wire:click="downloadPdf"
                            color="success"
                            icon="heroicon-o-arrow-down-tray">
                            Download PDF
                        </x-filament::button>

                        <x-filament::button
                            wire:click="exportExcel"
                            color="info"
                            icon="heroicon-o-table-cells">
                            Export Excel
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>

        {{-- Preview Section --}}
        @if($previewData)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="h-5 w-5 text-success-500" />
                        Preview Laporan Kelas
                    </div>
                </x-slot>

                @if($previewData['hasData'])
                    {{-- Class Info --}}
                    <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Kelas</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $previewData['kelas']->tingkat_kelas }}-{{ $previewData['kelas']->grup_kelas }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Mata Pelajaran</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['mataPelajaran']->nama_mapel }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tahun Ajaran</p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $previewData['tahunAjaran']->nama_tahun }} - {{ $previewData['tahunAjaran']->semester }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Jumlah Siswa</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $previewData['laporanData']->count() }} siswa</p>
                            </div>
                        </div>
                    </div>

                    {{-- Summary --}}
                    @php
                        $avgKehadiran = $previewData['laporanData']->avg('rata_kehadiran');
                        $avgNilai = $previewData['laporanData']->avg('rata_nilai');
                        $avgPartisipasi = $previewData['laporanData']->avg('rata_partisipasi');
                        $tuntas = $previewData['laporanData']->where('rata_nilai', '>=', 70)->count();
                    @endphp
                    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="rounded-lg bg-success-50 p-4 text-center dark:bg-success-500/10">
                            <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ number_format($avgKehadiran, 1) }}%</p>
                            <p class="text-sm text-success-700 dark:text-success-300">Rata-rata Kehadiran</p>
                        </div>
                        <div class="rounded-lg bg-primary-50 p-4 text-center dark:bg-primary-500/10">
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($avgNilai, 1) }}</p>
                            <p class="text-sm text-primary-700 dark:text-primary-300">Rata-rata Nilai</p>
                        </div>
                        <div class="rounded-lg bg-warning-50 p-4 text-center dark:bg-warning-500/10">
                            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($avgPartisipasi, 1) }}/5</p>
                            <p class="text-sm text-warning-700 dark:text-warning-300">Rata-rata Partisipasi</p>
                        </div>
                        <div class="rounded-lg bg-info-50 p-4 text-center dark:bg-info-500/10">
                            <p class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $tuntas }}/{{ $previewData['laporanData']->count() }}</p>
                            <p class="text-sm text-info-700 dark:text-info-300">Siswa Tuntas (≥70)</p>
                        </div>
                    </div>

                    {{-- Student Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="pb-3 text-left font-medium text-gray-500 dark:text-gray-400">No</th>
                                    <th class="pb-3 text-left font-medium text-gray-500 dark:text-gray-400">NIS</th>
                                    <th class="pb-3 text-left font-medium text-gray-500 dark:text-gray-400">Nama Siswa</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Kehadiran (%)</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Hadir</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Izin</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Sakit</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Alpa</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Nilai</th>
                                    <th class="pb-3 text-center font-medium text-gray-500 dark:text-gray-400">Partisipasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                @foreach($previewData['laporanData'] as $index => $laporan)
                                    <tr class="{{ $index < 3 ? 'bg-yellow-50 dark:bg-yellow-500/10' : '' }}">
                                        <td class="py-3 text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300">{{ $laporan->siswa?->nis }}</td>
                                        <td class="py-3 text-gray-900 dark:text-white">{{ $laporan->siswa?->user?->name }}</td>
                                        <td class="py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $laporan->rata_kehadiran >= 80 ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' :
                                                   ($laporan->rata_kehadiran >= 60 ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' :
                                                   'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400') }}">
                                                {{ number_format($laporan->rata_kehadiran, 1) }}%
                                            </span>
                                        </td>
                                        <td class="py-3 text-center text-sm text-slate-700 dark:text-slate-300">
                                            {{ $laporan->hadir_count }}
                                        </td>
                                        <td class="py-3 text-center text-sm text-slate-700 dark:text-slate-300">
                                            {{ $laporan->izin_count }}
                                        </td>
                                        <td class="py-3 text-center text-sm text-slate-700 dark:text-slate-300">
                                            {{ $laporan->sakit_count }}
                                        </td>
                                        <td class="py-3 text-center text-sm text-slate-700 dark:text-slate-300">
                                            {{ $laporan->alpa_count }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $laporan->rata_nilai >= 80 ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' :
                                                   ($laporan->rata_nilai >= 60 ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' :
                                                   'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400') }}">
                                                {{ number_format($laporan->rata_nilai, 1) }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-center text-gray-900 dark:text-white">
                                            {{ $laporan->rata_partisipasi }}/5
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-warning-200 bg-warning-50 p-6 text-center dark:border-warning-800 dark:bg-warning-900/20">
                        <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-warning-500" />
                        <h3 class="mt-2 text-lg font-medium text-warning-800 dark:text-warning-200">Tidak Ada Data</h3>
                        <p class="mt-1 text-sm text-warning-600 dark:text-warning-400">
                            Kelas ini belum memiliki data laporan untuk mata pelajaran dan tahun ajaran yang dipilih.
                            Pastikan sudah ada aktivitas pembelajaran yang direkam.
                        </p>
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
