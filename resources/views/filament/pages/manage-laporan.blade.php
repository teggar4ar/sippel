<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-primary-100 p-3 dark:bg-primary-500/20">
                        <x-heroicon-o-document-chart-bar class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Laporan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalLaporan) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-success-100 p-3 dark:bg-success-500/20">
                        <x-heroicon-o-academic-cap class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Laporan Tahun Aktif</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($laporanTahunAktif) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-warning-100 p-3 dark:bg-warning-500/20">
                        <x-heroicon-o-calendar class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tahun Ajaran Aktif</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $activeTahunAjaran?->nama_tahun ?? 'Tidak Ada' }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Instructions --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-primary-500" />
                    Tentang Laporan
                </div>
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Tabel <span class="font-semibold text-gray-900 dark:text-white">laporan</span> menyimpan statistik yang sudah dihitung untuk setiap siswa, per mata pelajaran, per tahun ajaran.
                    Data ini digunakan untuk mempercepat pembuatan laporan PDF.
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="rounded-full bg-success-100 p-2 dark:bg-success-500/20">
                            <x-heroicon-o-check-badge class="h-5 w-5 text-success-600 dark:text-success-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Kehadiran</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Hadir / Total × 100%</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="rounded-full bg-primary-100 p-2 dark:bg-primary-500/20">
                            <x-heroicon-o-academic-cap class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Nilai</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata semua aktivitas</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="rounded-full bg-warning-100 p-2 dark:bg-warning-500/20">
                            <x-heroicon-o-hand-raised class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Partisipasi</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Skor keaktifan (1-5)</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                    <x-heroicon-o-clock class="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        Kalkulasi berjalan otomatis setiap hari pukul <strong>01:00</strong>, atau klik tombol <strong>"Perbarui Laporan"</strong> untuk menghitung ulang manual.
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Recent Reports --}}
        @if($recentLaporan->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    Laporan Terbaru Diperbarui
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Siswa</th>
                                <th class="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                                <th class="pb-2 text-center font-medium text-gray-500 dark:text-gray-400">Kehadiran</th>
                                <th class="pb-2 text-center font-medium text-gray-500 dark:text-gray-400">Nilai</th>
                                <th class="pb-2 text-center font-medium text-gray-500 dark:text-gray-400">Partisipasi</th>
                                <th class="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Terakhir Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            @foreach($recentLaporan as $laporan)
                                <tr>
                                    <td class="py-2 text-gray-900 dark:text-white">
                                        {{ $laporan->siswa?->user?->name ?? '-' }}
                                    </td>
                                    <td class="py-2 text-gray-600 dark:text-gray-300">
                                        {{ $laporan->mataPelajaran?->nama_mapel ?? '-' }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                            {{ $laporan->rata_kehadiran >= 80 ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' :
                                               ($laporan->rata_kehadiran >= 60 ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' :
                                               'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400') }}">
                                            {{ round($laporan->rata_kehadiran, 1) }}%
                                        </span>
                                    </td>
                                    <td class="py-2 text-center">
                                        @if($laporan->rata_nilai !== null)
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                                {{ $laporan->rata_nilai >= 80 ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' :
                                                   ($laporan->rata_nilai >= 60 ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' :
                                                   'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400') }}">
                                                {{ round($laporan->rata_nilai, 1) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-center">
                                        @if($laporan->rata_partisipasi !== null)
                                            <span class="text-gray-900 dark:text-white">{{ $laporan->rata_partisipasi }}/5</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right text-gray-500 dark:text-gray-400">
                                        {{ $laporan->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
