            {{-- Chart 1: Tren Kehadiran Siswa --}}
            <x-ui.card variant="teacher" title="Tren Kehadiran Siswa" subtitle="Perbandingan hadir, sakit, izin, alpa & total per periode" flush>
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
                </x-ui.card>

                {{-- Chart 3: Distribusi Tingkat Keaktifan Kelas --}}
                <x-ui.card variant="teacher" title="Distribusi Tingkat Keaktifan Kelas" subtitle="Proporsi keaktifan siswa" flush>
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
                </x-ui.card>
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

var SIPPEL_COLORS = window.SIPPEL_COLORS || (window.SIPPEL_COLORS = {
    sangatAktif : '#10b981',
    aktif       : '#3b82f6',
    cukup       : '#f59e0b',
    pasif       : '#f43f5e',
    hadir       : '#3b82f6',
    sakit       : '#f59e0b',
    izin        : '#a78bfa',
    alpa        : '#f43f5e',
});

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
