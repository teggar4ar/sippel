<div class="space-y-4">

    {{-- ── Welcome Header ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white">Halo, {{ explode(' ', auth()->user()->name ?? 'Guru')[0] }}!</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300 mt-0.5">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
           class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm cursor-pointer">
            <flux:icon name="plus" class="w-4 h-4" />
            Buat Aktivitas
        </a>
    </div>

    {{-- ── 4 Metric Cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-1.5 sm:gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg items-center justify-center">
                <flux:icon name="building-library" class="w-4.5 h-4.5 text-blue-500/70 dark:text-blue-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-blue-500/80 dark:text-blue-400/70 leading-tight">
                <span class="sm:hidden">Mapel</span>
                <span class="hidden sm:inline">Mata Pelajaran Diampu</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['kelas_diampu'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Mapel</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
                <flux:icon name="user-group" class="w-4.5 h-4.5 text-emerald-500/70 dark:text-emerald-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-500/80 dark:text-emerald-400/70 leading-tight">
                <span class="sm:hidden">Siswa</span>
                <span class="hidden sm:inline">Total Siswa</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['total_siswa'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Siswa</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-violet-50 dark:bg-violet-900/30 rounded-lg items-center justify-center">
                <flux:icon name="clipboard-document-check" class="w-4.5 h-4.5 text-violet-500/70 dark:text-violet-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-violet-500/80 dark:text-violet-400/70 leading-tight">
                <span class="sm:hidden">Minggu Ini</span>
                <span class="hidden sm:inline">Aktivitas Minggu Ini</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['aktivitas_minggu_ini'] }} <span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">Aktivitas</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-2.5 py-2 sm:p-4 relative overflow-hidden">
            <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg items-center justify-center">
                <flux:icon name="hand-raised" class="w-4.5 h-4.5 text-amber-500/70 dark:text-amber-400/60" />
            </div>
            <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wider text-amber-500/80 dark:text-amber-400/70 leading-tight">
                <span class="sm:hidden">Kehadiran</span>
                <span class="hidden sm:inline">Rata-rata Kehadiran</span>
            </p>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1">{{ $this->dashboardStats['rata_kehadiran'] }}<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500">%</span></p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- MAIN 2-COLUMN LAYOUT: Charts (kiri) + Sidebar (kanan)                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- ── KOLOM KIRI: Filter + 3 Chart ──────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Filter Bar --}}
            <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 px-4 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    {{-- Dropdowns kiri --}}
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="kelasId" size="sm" placeholder="Semua Kelas">
                                <flux:select.option value="">Semua Kelas</flux:select.option>
                                @foreach($this->kelasList as $kelas)
                                    <flux:select.option value="{{ $kelas['id'] }}">Kelas {{ $kelas['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <flux:select wire:model.live="mapelId" size="sm" placeholder="Semua Mapel">
                                <flux:select.option value="">Semua Mapel</flux:select.option>
                                @foreach($this->mapelList as $mapel)
                                    <flux:select.option value="{{ $mapel['id'] }}">{{ $mapel['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    {{-- Button group kanan --}}
                    <div class="flex rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 self-start sm:self-auto">
                        @foreach(['semester' => 'Semester ini', 'bulan' => 'Bulan ini', 'minggu' => 'Minggu ini'] as $value => $label)
                            <button wire:click="$set('rentangWaktu', '{{ $value }}')"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium transition-colors border-r border-slate-200 dark:border-slate-700 last:border-r-0
                                        {{ $rentangWaktu === $value
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Chart 1: Tren Kehadiran Siswa --}}
            <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Tren Kehadiran Siswa</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Perbandingan hadir, sakit, izin, alpa & total per periode</p>
                    </div>
                    <flux:icon name="chart-bar" class="w-4 h-4 text-slate-400" />
                </div>
                {{-- Custom horizontal legend (ApexCharts forces vertical on combo charts) --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 pt-2 text-xs text-slate-600 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#3b82f6"></span> Hadir</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#f59e0b"></span> Sakit</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#a855f7"></span> Izin</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:#f43f5e"></span> Alpa</span>
                    <span class="flex items-center gap-1.5"><span class="w-5 h-0.5 rounded" style="background:#334155"></span> Total</span>
                </div>
                <div class="p-2 sm:p-4" wire:ignore
                     x-data="chartTrenKehadiran(@js($this->chartTrenKehadiran()))"
                     x-init="init()"
                     @update-charts.window="handleUpdate($event.detail[0])">
                    {{-- Empty state --}}
                    <div x-show="empty" class="flex flex-col items-center justify-center py-16 text-center">
                        <flux:icon name="chart-bar" class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-3" />
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Belum ada data kehadiran</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs">Data kehadiran akan muncul setelah Anda membuat aktivitas pembelajaran dan mencatat kehadiran siswa</p>
                        <a href="{{ route('teacher.aktivitas.create') }}" wire:navigate
                           class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <flux:icon name="plus" class="w-3.5 h-3.5" />
                            Buat Aktivitas Pertama
                        </a>
                    </div>
                    <div id="chart-tren-kehadiran" x-show="!empty"></div>
                </div>
            </div>

            {{-- Baris 2: Chart 2 + Chart 3 berdampingan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Chart 2: Evaluasi Keaktifan per Topik --}}
                <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
                        <h2 class="font-semibold text-slate-900 dark:text-white text-sm">Evaluasi Keaktifan per Topik</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">10 topik terbaru</p>
                    </div>
                    <div class="p-2 sm:p-3" wire:ignore
                         x-data="chartKeaktifanTopik(@js($this->chartKeaktifanPerTopik()))"
                         x-init="init()"
                         @update-charts.window="handleUpdate($event.detail[0])">
                        <div x-show="empty" class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="academic-cap" class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada data keaktifan untuk periode ini</p>
                        </div>
                        <div id="chart-keaktifan-topik" x-show="!empty"></div>
                    </div>
                </div>

                {{-- Chart 3: Distribusi Tingkat Keaktifan Kelas --}}
                <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
                        <h2 class="font-semibold text-slate-900 dark:text-white text-sm">Distribusi Tingkat Keaktifan Kelas</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Proporsi keaktifan siswa</p>
                    </div>
                    <div class="p-2 sm:p-3" wire:ignore
                         x-data="chartDistribusiKeaktifan(@js($this->chartDistribusiKeaktifan()))"
                         x-init="init()"
                         @update-charts.window="handleUpdate($event.detail[0])">
                        <div x-show="empty" class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="chart-pie" class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada data distribusi keaktifan</p>
                        </div>
                        <div id="chart-distribusi-keaktifan" x-show="!empty"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── KOLOM KANAN: Sidebar 2 Card ───────────────────────────────── --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Sidebar Card 1: Mata Pelajaran Teraktif --}}
            <div class="bg-white dark:bg-slate-900/95 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/90 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/90">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Mata Pelajaran Diampu</h2>
                </div>
                <div class="grid gap-3 p-3">
                    @forelse($this->keaktifanPerKelas as $mapel)
                        @php
                            $pl = match(true) {
                                $mapel['avg'] >= 3.5 => 'Sangat Aktif',
                                $mapel['avg'] >= 2.5 => 'Aktif',
                                $mapel['avg'] >= 1.5 => 'Cukup',
                                default              => 'Pasif',
                            };
                            $bc = match($pl) {
                                'Sangat Aktif' => 'green',
                                'Aktif'        => 'blue',
                                'Cukup'        => 'amber',
                                'Pasif'        => 'red',
                            };
                            $kh = $mapel['kehadiran_pct'] ?? 0;
                            $ta = $mapel['total_aktivitas'] ?? 0;
                        @endphp
                        <div class="border border-slate-200 dark:border-slate-700/90 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/60 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <flux:icon name="building-library" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $mapel['kelas'] }} — {{ $mapel['mapel'] }}
                                    </p>
                                    <div class="flex items-center gap-1 mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        <flux:icon name="user-group" class="w-3.5 h-3.5" />
                                        <span>{{ $mapel['siswa_count'] ?? 0 }} Siswa</span>
                                    </div>
                                </div>
                                <flux:badge size="sm" class="ml-auto shrink-0">{{ $ta }} Aktivitas</flux:badge>
                            </div>
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Kehadiran</span>
                                    <span class="font-semibold text-slate-800 dark:text-white">{{ $kh }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                                         x-data="{ w: @js(min($kh, 100)) }"
                                         x-bind:style="`width: ${w}%`"></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Keaktifan</span>
                                    <flux:badge color="{{ $bc }}" size="sm">{{ $pl }}</flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="border border-dashed border-slate-200 dark:border-slate-700/90 rounded-lg p-4 text-center">
                            <flux:icon name="book-open" class="w-5 h-5 mx-auto text-slate-300 dark:text-slate-600 mb-1" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada mata pelajaran diampu.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Sidebar Card 2: Panduan Indikator Keaktifan --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Panduan</p>
                    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mt-0.5">Indikator Keaktifan</h2>
                </div>
                <div class="px-4 py-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach([
                        ['color' => 'bg-emerald-500', 'label' => 'Sangat Aktif', 'textColor' => 'text-emerald-600 dark:text-emerald-400', 'desc' => 'Proaktif, memimpin diskusi, sering bertanya.'],
                        ['color' => 'bg-blue-500',    'label' => 'Aktif',        'textColor' => 'text-blue-600 dark:text-blue-400',    'desc' => 'Fokus, merespons pertanyaan dengan baik.'],
                        ['color' => 'bg-amber-400',   'label' => 'Cukup',        'textColor' => 'text-amber-600 dark:text-amber-400',   'desc' => 'Mendengarkan, jarang mengambil inisiatif.'],
                        ['color' => 'bg-rose-500',    'label' => 'Pasif',        'textColor' => 'text-rose-600 dark:text-rose-400',     'desc' => 'Kurang fokus, pasif, atau tidak merespons.'],
                    ] as $item)
                        <div class="flex items-start gap-3 py-2.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }} shrink-0 mt-0.5"></span>
                            <div>
                                <span class="text-xs font-semibold {{ $item['textColor'] }}">{{ $item['label'] }}</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Load ApexCharts hanya di halaman dashboard ini --}}
@pushOnce('vendor-scripts')
    @vite(['resources/js/apexcharts-loader.js'])
@endPushOnce

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- APEXCHARTS ALPINE SCRIPTS — @pushOnce prevents duplicate injection        --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
@pushOnce('scripts')
<script>
// ── Shared helpers ──────────────────────────────────────────────────────────

function apexIsDark() {
    return document.documentElement.classList.contains('dark');
}

function apexBaseOptions() {
    const dark = apexIsDark();
    return {
        chart: {
            background: 'transparent',
            fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 450 },
        },
        theme: { mode: dark ? 'dark' : 'light' },
        grid: {
            borderColor: dark ? '#334155' : '#e2e8f0',
            strokeDashArray: 4,
        },
        tooltip: { theme: dark ? 'dark' : 'light' },
    };
}

const SIPPEL_COLORS = {
    sangatAktif : '#10b981',
    aktif       : '#3b82f6',
    cukup       : '#f59e0b',
    pasif       : '#f43f5e',
    hadir       : '#3b82f6',
    sakit       : '#f59e0b',
    izin        : '#a78bfa',
    alpa        : '#f43f5e',
};

// ── Chart 1: Tren Kehadiran (Combo: Grouped Bar + Line) ─────────────────────

// Compute 'Total' line series client-side from column series to avoid
// sending redundant data from the backend (reduces Livewire payload + PHP memory).
function prepareSeries(rawSeries) {
    // rawSeries has 4 column series: Hadir, Sakit, Izin, Alpa
    const columns = rawSeries.filter(s => s.type === 'column');
    if (columns.length === 0) return rawSeries;
    const len = columns[0].data.length;
    const totalData = [];
    for (let i = 0; i < len; i++) {
        let sum = 0;
        for (const col of columns) { sum += (col.data[i] || 0); }
        totalData.push(sum);
    }
    // Return columns + computed total line (no 'Total' from backend needed)
    return [
        ...columns,
        { name: 'Total', type: 'line', data: totalData },
    ];
}

function chartTrenKehadiran(initialData) {
    const axisFormatter = (val) => Math.round(val);

    function buildYAxis(dark) {
        const titleStyle = { fontSize: '12px', fontWeight: 500, color: dark ? '#94a3b8' : '#64748b' };
        return [
            {
                seriesName: 'Hadir',
                title: { text: 'Jumlah per Status', style: titleStyle },
                labels: { formatter: axisFormatter, style: { fontSize: '11px' } },
                min: 0,
            },
                { seriesName: 'Hadir', show: false },
                { seriesName: 'Hadir', show: false },
                { seriesName: 'Hadir', show: false },
            {
                seriesName: 'Total',
                opposite: true,
                title: { text: 'Total Kehadiran', style: titleStyle },
                labels: { formatter: axisFormatter, style: { fontSize: '11px' } },
                min: 0,
            },
        ];
    }

    function buildOpts(data, dark) {
        const base = apexBaseOptions();
        const series = prepareSeries(data.series);
        return {
            ...base,
            chart: {
                ...base.chart,
                type: 'bar',
                height: 330,
                id: 'chart-tren',
                stacked: false,
                animations: { enabled: false },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 3,
                    borderRadiusApplication: 'end',
                },
            },
            series: series,
            xaxis: {
                categories: data.categories,
                labels: { style: { fontSize: '11px' }, rotate: -20 },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: buildYAxis(dark),
            colors: [
                SIPPEL_COLORS.hadir,
                SIPPEL_COLORS.sakit,
                SIPPEL_COLORS.izin,
                SIPPEL_COLORS.alpa,
                '#334155',
            ],
            stroke: {
                width: [0, 0, 0, 0, 3],
                curve: 'smooth',
            },
            fill: { opacity: [1, 1, 1, 1, 1] },
            dataLabels: { enabled: false },
            legend: { show: false },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (val) => val + ' siswa' },
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: { height: 240 },
                    plotOptions: { bar: { columnWidth: '75%' } },
                    legend: { fontSize: '10px' },
                },
            }],
        };
    }

    return {
        chartInstance: null,
        empty: false,

        init() {
            if (initialData.empty) {
                this.empty = true;
                return;
            }
            this.empty = false;
            const existing = ApexCharts.getChartByID('chart-tren');
            if (existing) existing.destroy();
            this.chartInstance = new ApexCharts(
                document.querySelector('#chart-tren-kehadiran'),
                buildOpts(initialData, apexIsDark()),
            );
            this.chartInstance.render();
        },

        handleUpdate(payload) {
            if (!payload.tren) return;

            if (payload.tren.empty) {
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                    this.chartInstance = null;
                }
                this.empty = true;
                return;
            }

            this.empty = false;

            if (!this.chartInstance) {
                this.chartInstance = new ApexCharts(
                    document.querySelector('#chart-tren-kehadiran'),
                    buildOpts(payload.tren, apexIsDark()),
                );
                this.chartInstance.render();
                return;
            }

            const dark = apexIsDark();
            const series = prepareSeries(payload.tren.series);
            this.chartInstance.updateOptions({
                series: series,
                xaxis: {
                    categories: payload.tren.categories,
                    labels: { style: { fontSize: '11px' }, rotate: -20 },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                theme: { mode: dark ? 'dark' : 'light' },
                grid: { borderColor: dark ? '#334155' : '#e2e8f0' },
            }, true, false);
        },
    };
}

// ── Chart 2: Keaktifan per Topik (Stacked Bar Horizontal) ──────────────────

function chartKeaktifanTopik(initialData) {
    function buildOpts(data) {
        return {
            ...apexBaseOptions(),
            chart: {
                ...apexBaseOptions().chart,
                type: 'bar',
                height: 300,
                id: 'chart-topik',
                stacked: true,
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '65%',
                    borderRadius: 3,
                    borderRadiusWhenStacked: 'last',
                },
            },
            series: data.series,
            xaxis: {
                categories: data.categories,
                labels: {
                    formatter: (val) => Math.round(val),
                    style: { fontSize: '10px' },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { fontSize: '10px' },
                    maxWidth: 130,
                },
            },
            colors: [SIPPEL_COLORS.sangatAktif, SIPPEL_COLORS.aktif, SIPPEL_COLORS.cukup, SIPPEL_COLORS.pasif],
            dataLabels: {
                enabled: true,
                formatter: (val) => val > 0 ? val : '',
                style: { fontSize: '10px', fontWeight: '600', colors: ['#fff'] },
                dropShadow: { enabled: false },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '11px',
                markers: { size: 6 },
                itemMargin: { horizontal: 6 },
            },
            tooltip: {
                shared: false,
                intersect: true,
                y: { formatter: (val) => val + ' siswa' },
            },
        };
    }

    return {
        chartInstance: null,
        empty: false,

        init() {
            if (initialData.empty) {
                this.empty = true;
                return;
            }
            this.empty = false;
            const existing = ApexCharts.getChartByID('chart-topik');
            if (existing) existing.destroy();
            this.chartInstance = new ApexCharts(
                document.querySelector('#chart-keaktifan-topik'),
                buildOpts(initialData),
            );
            this.chartInstance.render();
        },

        handleUpdate(payload) {
            if (!payload.topik) return;

            if (payload.topik.empty) {
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                    this.chartInstance = null;
                }
                this.empty = true;
                return;
            }

            this.empty = false;

            if (!this.chartInstance) {
                this.chartInstance = new ApexCharts(
                    document.querySelector('#chart-keaktifan-topik'),
                    buildOpts(payload.topik),
                );
                this.chartInstance.render();
                return;
            }

            const dark = apexIsDark();
            this.chartInstance.updateOptions({
                xaxis: { categories: payload.topik.categories },
                theme: { mode: dark ? 'dark' : 'light' },
                grid: { borderColor: dark ? '#334155' : '#e2e8f0' },
            }, false, false);
            this.chartInstance.updateSeries(payload.topik.series);
        },
    };
}

// ── Chart 3: Distribusi Keaktifan (Donut) ──────────────────────────────────

function chartDistribusiKeaktifan(initialData) {
    function buildOpts(data, dark) {
        return {
            ...apexBaseOptions(),
            chart: {
                ...apexBaseOptions().chart,
                type: 'donut',
                height: 300,
                id: 'chart-distribusi',
            },
            series: data.series,
            labels: data.labels,
            colors: [SIPPEL_COLORS.sangatAktif, SIPPEL_COLORS.aktif, SIPPEL_COLORS.cukup, SIPPEL_COLORS.pasif],
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '12px',
                                fontWeight: 600,
                                color: dark ? '#cbd5e1' : '#475569',
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontWeight: 700,
                                color: dark ? '#f1f5f9' : '#1e293b',
                                formatter: (val) => val + ' siswa',
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '11px',
                                color: dark ? '#94a3b8' : '#64748b',
                                formatter: (w) => {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total + ' siswa';
                                },
                            },
                        },
                    },
                },
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => val.toFixed(1) + '%',
                style: { fontSize: '10px', fontWeight: '600' },
                dropShadow: { enabled: false },
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '11px',
                markers: { size: 7 },
                itemMargin: { horizontal: 8, vertical: 3 },
            },
            stroke: {
                width: 2,
                colors: [dark ? '#1e293b' : '#ffffff'],
            },
            tooltip: {
                y: { formatter: (val) => val + ' siswa' },
            },
        };
    }

    return {
        chartInstance: null,
        empty: false,

        init() {
            if (initialData.empty) {
                this.empty = true;
                return;
            }
            this.empty = false;
            const existing = ApexCharts.getChartByID('chart-distribusi');
            if (existing) existing.destroy();
            this.chartInstance = new ApexCharts(
                document.querySelector('#chart-distribusi-keaktifan'),
                buildOpts(initialData, apexIsDark()),
            );
            this.chartInstance.render();
        },

        handleUpdate(payload) {
            if (!payload.distribusi) return;

            if (payload.distribusi.empty) {
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                    this.chartInstance = null;
                }
                this.empty = true;
                return;
            }

            this.empty = false;

            if (!this.chartInstance) {
                this.chartInstance = new ApexCharts(
                    document.querySelector('#chart-distribusi-keaktifan'),
                    buildOpts(payload.distribusi, apexIsDark()),
                );
                this.chartInstance.render();
                return;
            }

            const dark = apexIsDark();
            this.chartInstance.updateOptions({
                labels: payload.distribusi.labels,
                theme: { mode: dark ? 'dark' : 'light' },
                stroke: { colors: [dark ? '#1e293b' : '#ffffff'] },
            }, false, false);
            this.chartInstance.updateSeries(payload.distribusi.series);
        },
    };
}
</script>
@endPushOnce
