            {{-- Chart 1: Tren Kehadiran Siswa --}}
            <x-ui.card variant="teacher" title="Tren Kehadiran Siswa"
                subtitle="Perbandingan hadir, sakit, izin, alpa & total per periode" flush>
                {{-- Custom horizontal legend (ApexCharts forces vertical on combo charts) --}}
                <div
                    class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 pt-2 text-xs text-slate-600 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm"
                            style="background:#3b82f6"></span> Hadir</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm"
                            style="background:#f59e0b"></span> Sakit</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm"
                            style="background:#a855f7"></span> Izin</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm"
                            style="background:#f43f5e"></span> Alpa</span>
                    <span class="flex items-center gap-1.5"><span class="w-5 h-0.5 rounded"
                            style="background:#334155"></span> Total</span>
                </div>
                <div class="p-2 sm:p-4" wire:ignore x-data="chartTrenKehadiran(@js($this->chartTrenKehadiran()))"
                    @update-charts.window="handleUpdate($event.detail[0])">
                    {{-- Empty state --}}
                    <div x-show="empty" class="flex flex-col items-center justify-center py-16 text-center">
                        <flux:icon name="chart-bar" class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-3" />
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Belum ada data kehadiran</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs">Data kehadiran akan muncul
                            setelah Anda membuat aktivitas pembelajaran dan mencatat kehadiran siswa</p>
                        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                            class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <flux:icon name="plus" variant="outline" class="w-3.5 h-3.5" />
                            Buat Aktivitas Pertama
                        </a>
                    </div>
                    <div id="chart-tren-kehadiran" x-show="!empty"></div>
                </div>
            </x-ui.card>

            {{-- Baris 2: Chart 2 + Chart 3 berdampingan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Chart 2: Evaluasi Keaktifan per Topik --}}
                <x-ui.card variant="teacher" title="Evaluasi Keaktifan per Topik" subtitle="10 topik terbaru" flush>
                    <div class="p-2 sm:p-3" wire:ignore x-data="chartKeaktifanTopik(@js($this->chartKeaktifanPerTopik()))"
                        @update-charts.window="handleUpdate($event.detail[0])">
                        <div x-show="empty" class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="academic-cap" class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada data keaktifan untuk periode
                                ini</p>
                        </div>
                        <div id="chart-keaktifan-topik" x-show="!empty"></div>
                    </div>
                </x-ui.card>

                {{-- Chart 3: Distribusi Tingkat Keaktifan Kelas --}}
                <x-ui.card variant="teacher" title="Distribusi Tingkat Keaktifan Kelas"
                    subtitle="Proporsi keaktifan siswa" flush>
                    <div class="p-2 sm:p-3" wire:ignore x-data="chartDistribusiKeaktifan(@js($this->chartDistribusiKeaktifan()))"
                        @update-charts.window="handleUpdate($event.detail[0])">
                        <div x-show="empty" class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="chart-pie" class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada data distribusi keaktifan
                            </p>
                        </div>
                        <div id="chart-distribusi-keaktifan" x-show="!empty"></div>
                    </div>
                </x-ui.card>
            </div>

            {{-- Load ApexCharts hanya di halaman dashboard ini --}}
            @pushOnce('vendor-scripts')
                @vite(['resources/js/apexcharts-loader.js'])
            @endPushOnce
