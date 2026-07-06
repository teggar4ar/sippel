<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Enums\Keaktifan;
use App\Enums\KehadiranStatus;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SiswaKelasHistory;
use App\Models\TahunAjaran;
use App\Services\TeacherDashboardCacheService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.teacher')]
#[Title('Dashboard - SIPPEL Guru')]
final class Dashboard extends Component
{
    // ── Filter state ─────────────────────────────────────────────────────────

    public string $kelasId = '';

    public string $mapelId = '';

    public string $rentangWaktu = 'semester'; // 'semester' | 'bulan' | 'minggu'

    // ── Computed helpers ─────────────────────────────────────────────────────

    #[Computed]
    public function activeTahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::getContext();
    }

    #[Computed]
    public function mySubjects(): Collection
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        return MataPelajaran::with(['kelas' => fn ($q) => $q->withCount('siswa')])
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->get();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function kelasList(): Collection
    {
        return $this->mySubjects
            ->map(fn (MataPelajaran $m): array => [
                'id' => (string) $m->kelas_id,
                'label' => ($m->kelas->tingkat_kelas ?? '').'-'.($m->kelas->grup_kelas ?? ''),
            ])
            ->unique('id')
            ->sortBy('label')
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function mapelList(): Collection
    {
        $subjects = $this->mySubjects;

        if ($this->kelasId !== '') {
            $subjects = $subjects->where('kelas_id', (int) $this->kelasId);
        }

        return $subjects
            ->map(fn (MataPelajaran $m): array => [
                'id' => (string) $m->id,
                'label' => $m->nama_mapel.' ('.($m->kelas->tingkat_kelas ?? '').'-'.($m->kelas->grup_kelas ?? '').')',
            ])
            ->sortBy('label')
            ->values();
    }

    // ── Dashboard summary stats ───────────────────────────────────────────────

    #[Computed]
    public function dashboardStats(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        $cacheKey = 'teacher_dashboard_stats_v2_'.Auth::id().'_'.($tahunAjaran?->id ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran): array {
            if (! $tahunAjaran instanceof TahunAjaran) {
                return ['kelas_diampu' => 0, 'total_siswa' => 0, 'aktivitas_minggu_ini' => 0, 'rata_kehadiran' => 0];
            }

            $mapel = MataPelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->get(['id', 'kelas_id']);

            $mapelDiampu = $mapel->count();
            $kelasIds = $mapel->pluck('kelas_id')->unique();

            $totalSiswa = SiswaKelasHistory::where('tahun_ajaran_id', $tahunAjaran->id)
                ->whereIn('kelas_id', $kelasIds)
                ->distinct('siswa_id')->count('siswa_id');

            $aktivitasMingguIni = DB::table('aktivitas_pembelajaran as ap')
                ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
                ->whereNull('ap.deleted_at')
                ->whereNull('k.deleted_at')
                ->where('ap.guru_id', Auth::id())
                ->where('k.tahun_ajaran_id', $tahunAjaran->id)
                ->whereBetween('ap.tanggal', [now()->copy()->startOfWeek(), now()->copy()->endOfWeek()])
                ->count();

            // Single aggregate query instead of two separate queries
            $kehadiranStats = DB::table('detail_aktivitas as da')
                ->join('aktivitas_pembelajaran as ap', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
                ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
                ->whereNull('da.deleted_at')
                ->whereNull('ap.deleted_at')
                ->whereNull('k.deleted_at')
                ->where('ap.guru_id', Auth::id())
                ->where('k.tahun_ajaran_id', $tahunAjaran->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as hadir', [KehadiranStatus::Hadir->value])
                ->first();

            $rataKehadiran = ($kehadiranStats && $kehadiranStats->total > 0)
                ? round(($kehadiranStats->hadir / $kehadiranStats->total) * 100, 1)
                : 0;

            return [
                'kelas_diampu' => $mapelDiampu,
                'total_siswa' => $totalSiswa,
                'aktivitas_minggu_ini' => $aktivitasMingguIni,
                'rata_kehadiran' => $rataKehadiran,
            ];
        });
    }

    /**
     * Mata pelajaran diampu — all subjects taught by the teacher with keaktifan stats.
     * Optimized: single JOIN query instead of N×3 subqueries.
     */
    #[Computed]
    public function keaktifanPerKelas(): Collection
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        $mapelList = $this->mySubjects;

        if ($mapelList->isEmpty()) {
            return collect();
        }

        $mapelIds = $mapelList->pluck('id');

        // One aggregate query for all mapel (replaces N×3 separate queries)
        $stats = DB::table('detail_aktivitas as da')
            ->join('aktivitas_pembelajaran as ap', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
            ->whereNull('da.deleted_at')
            ->whereNull('ap.deleted_at')
            ->whereIn('ap.mata_pelajaran_id', $mapelIds)
            ->selectRaw(
                'ap.mata_pelajaran_id,
                 ap.kelas_id,
                 COUNT(*) as total_detail,
                 COUNT(DISTINCT ap.id) as total_aktivitas,
                 SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as hadir_count,
                 AVG(CASE WHEN da.kehadiran = ? THEN '.$this->keaktifanWeightSql('da.keaktifan').' ELSE NULL END) as avg_keaktifan',
                [KehadiranStatus::Hadir->value, KehadiranStatus::Hadir->value]
            )
            ->groupBy('ap.mata_pelajaran_id', 'ap.kelas_id')
            ->get()
            ->keyBy('mata_pelajaran_id');

        return $mapelList->map(function (MataPelajaran $mapel) use ($stats): array {
            $stat = $stats->get($mapel->id);

            $avgKeaktifan = $stat ? (float) $stat->avg_keaktifan : 0.0;
            $totalDetail = $stat ? (int) $stat->total_detail : 0;
            $totalAktivitas = $stat ? (int) $stat->total_aktivitas : 0;
            $hadirCount = $stat ? (int) $stat->hadir_count : 0;

            $kehadiranPct = $totalDetail > 0 ? round(($hadirCount / $totalDetail) * 100, 0) : 0;
            $keaktifanScore = $avgKeaktifan > 0 ? min(max($avgKeaktifan, 0), 4) / 4 * 100 : 0;
            $score = round(($kehadiranPct * 0.6) + ($keaktifanScore * 0.4), 1);

            /** @var Kelas $kelas */
            $kelas = $mapel->kelas;

            return [
                'mapel_id' => $mapel->id,
                'kelas' => $kelas->tingkat_kelas.'-'.$kelas->grup_kelas,
                'mapel' => $mapel->nama_mapel,
                'siswa_count' => $kelas->siswa_count ?? 0,
                'total_aktivitas' => $totalAktivitas,
                'avg' => round($avgKeaktifan, 1),
                'kehadiran_pct' => $kehadiranPct,
                'score' => $score,
            ];
        })
            ->sortBy(['kelas', 'mapel'])
            ->values();
    }

    // ── Chart data ────────────────────────────────────────────────────────────

    /**
     * Chart 1 — Area: tren kehadiran siswa.
     * Optimized: SQL GROUP BY aggregate instead of PHP-level collection iteration.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function chartTrenKehadiran(): array
    {
        return $this->buildChartTren();
    }

    /**
     * Chart 2 — Stacked Bar: keaktifan per topik.
     * Optimized: SQL GROUP BY aggregate instead of PHP-level collection filtering.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function chartKeaktifanPerTopik(): array
    {
        return $this->buildChartTopik();
    }

    /**
     * Chart 3 — Donut: distribusi keaktifan.
     * Optimized: single selectRaw aggregate instead of get() + PHP filter.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function chartDistribusiKeaktifan(): array
    {
        return $this->buildChartDist();
    }

    // ── Lifecycle hooks ───────────────────────────────────────────────────────

    public function updatedKelasId(): void
    {
        $this->mapelId = '';
        $this->dispatchChartData();
    }

    public function updatedMapelId(): void
    {
        $this->dispatchChartData();
    }

    public function updatedRentangWaktu(): void
    {
        $this->dispatchChartData();
    }

    public function dispatchChartData(): void
    {
        // #[Computed] properties cache their result for the entire request.
        // Since updated*() hooks fire BEFORE the blade renders, the cache is
        // empty at this point — calling chartTrenKehadiran() etc. computes
        // the data once, caches it, and blade reuses it at no extra cost.
        $this->dispatch('update-charts', [
            'tren' => $this->chartTrenKehadiran(),
            'topik' => $this->chartKeaktifanPerTopik(),
            'distribusi' => $this->chartDistribusiKeaktifan(),
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.dashboard');
    }

    /** @return array<string, mixed> */
    private function buildChartTren(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return ['series' => [], 'categories' => []];
        }

        $dateRange = $this->getDateRange();
        $kelasId = $this->kelasId;
        $mapelId = $this->mapelId;
        $rentang = $this->rentangWaktu;
        $chartVersion = app(TeacherDashboardCacheService::class)
            ->chartVersion((int) Auth::id(), $tahunAjaran->id);
        $cacheKey = 'chart_tren_v3_'.Auth::id().'_'.$tahunAjaran->id.'_'.$chartVersion.'_'.$rentang.'_'.$kelasId.'_'.$mapelId;

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran, $dateRange, $kelasId, $mapelId, $rentang): array {
            $query = DB::table('detail_aktivitas as da')
                ->join('aktivitas_pembelajaran as ap', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
                ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
                ->whereNull('da.deleted_at')
                ->whereNull('ap.deleted_at')
                ->whereNull('k.deleted_at')
                ->where('ap.guru_id', Auth::id())
                ->where('k.tahun_ajaran_id', $tahunAjaran->id)
                ->whereBetween('ap.tanggal', [$dateRange['start'], $dateRange['end']])
                ->selectRaw(
                    'DATE(ap.tanggal) as tgl,
                     SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as hadir,
                     SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as sakit,
                     SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as izin,
                     SUM(CASE WHEN da.kehadiran = ? THEN 1 ELSE 0 END) as alpa',
                    [
                        KehadiranStatus::Hadir->value,
                        KehadiranStatus::Sakit->value,
                        KehadiranStatus::Izin->value,
                        KehadiranStatus::Alpa->value,
                    ]
                )
                ->groupByRaw('DATE(ap.tanggal)')
                ->orderBy('tgl');

            if ($kelasId !== '') {
                $query->where('ap.kelas_id', (int) $kelasId);
            }
            if ($mapelId !== '') {
                $query->where('ap.mata_pelajaran_id', (int) $mapelId);
            }

            $rows = $query->get();

            if ($rows->isEmpty()) {
                return ['series' => [], 'categories' => [], 'empty' => true];
            }

            // Group by month for semester (5-6 labels), by week for bulan
            // (with day-range labels), by day for minggu.
            switch ($rentang) {
                case 'semester':
                    $grouped = $rows->groupBy(fn (object $row): string => Carbon::parse($row->tgl)->translatedFormat('M Y'));
                    break;
                case 'bulan':
                    $grouped = $rows->groupBy(fn (object $row): string => Carbon::parse($row->tgl)->startOfWeek()->translatedFormat('d M')
                        .' - '.Carbon::parse($row->tgl)->endOfWeek()->translatedFormat('d M'));
                    if ($grouped->count() < 3) {
                        $grouped = $rows->groupBy(fn (object $row): string => Carbon::parse($row->tgl)->translatedFormat('D, d M'));
                    }
                    break;
                default: // minggu
                    $grouped = $rows->groupBy(fn (object $row): string => Carbon::parse($row->tgl)->translatedFormat('D, d M'));
                    break;
            }

            $categories = [];
            $hadirData = [];
            $sakitData = [];
            $izinData = [];
            $alpaData = [];

            foreach ($grouped as $label => $group) {
                $categories[] = $label;
                $hadirData[] = (int) $group->sum('hadir');
                $sakitData[] = (int) $group->sum('sakit');
                $izinData[] = (int) $group->sum('izin');
                $alpaData[] = (int) $group->sum('alpa');
            }

            return [
                'series' => [
                    ['name' => 'Hadir', 'type' => 'column', 'data' => $hadirData],
                    ['name' => 'Sakit', 'type' => 'column', 'data' => $sakitData],
                    ['name' => 'Izin',  'type' => 'column', 'data' => $izinData],
                    ['name' => 'Alpa',  'type' => 'column', 'data' => $alpaData],
                ],
                'categories' => $categories,
            ];
        });
    }

    /** @return array<string, mixed> */
    private function buildChartTopik(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return ['series' => [], 'categories' => []];
        }

        $dateRange = $this->getDateRange();
        $kelasId = $this->kelasId;
        $mapelId = $this->mapelId;
        $rentang = $this->rentangWaktu;
        $chartVersion = app(TeacherDashboardCacheService::class)
            ->chartVersion((int) Auth::id(), $tahunAjaran->id);
        $cacheKey = 'chart_topik_v3_'.Auth::id().'_'.$tahunAjaran->id.'_'.$chartVersion.'_'.$rentang.'_'.$kelasId.'_'.$mapelId;

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran, $dateRange, $kelasId, $mapelId): array {
            $hadirVal = KehadiranStatus::Hadir->value;

            $query = DB::table('aktivitas_pembelajaran as ap')
                ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
                ->join('detail_aktivitas as da', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
                ->whereNull('ap.deleted_at')
                ->whereNull('k.deleted_at')
                ->whereNull('da.deleted_at')
                ->where('ap.guru_id', Auth::id())
                ->where('k.tahun_ajaran_id', $tahunAjaran->id)
                ->whereBetween('ap.tanggal', [$dateRange['start'], $dateRange['end']])
                ->whereNotNull('ap.topik')
                ->selectRaw(
                    'ap.id as aktivitas_id,
                     ap.topik,
                     ap.tanggal,
                     SUM(CASE WHEN da.kehadiran = ? AND da.keaktifan = ? THEN 1 ELSE 0 END) as sangat_aktif,
                     SUM(CASE WHEN da.kehadiran = ? AND da.keaktifan = ? THEN 1 ELSE 0 END) as aktif,
                     SUM(CASE WHEN da.kehadiran = ? AND da.keaktifan = ? THEN 1 ELSE 0 END) as cukup,
                     SUM(CASE WHEN da.kehadiran = ? AND da.keaktifan = ? THEN 1 ELSE 0 END) as pasif',
                    [
                        $hadirVal, Keaktifan::SangatAktif->value,
                        $hadirVal, Keaktifan::Aktif->value,
                        $hadirVal, Keaktifan::Cukup->value,
                        $hadirVal, Keaktifan::Pasif->value,
                    ]
                )
                ->groupBy('ap.id', 'ap.topik', 'ap.tanggal')
                ->orderByDesc('ap.tanggal')
                ->limit(10);

            if ($kelasId !== '') {
                $query->where('ap.kelas_id', (int) $kelasId);
            }
            if ($mapelId !== '') {
                $query->where('ap.mata_pelajaran_id', (int) $mapelId);
            }

            $rows = $query->get();

            if ($rows->isEmpty()) {
                return ['series' => [], 'categories' => [], 'empty' => true];
            }

            $topiks = [];
            $sangat = [];
            $aktif = [];
            $cukup = [];
            $pasif = [];

            foreach ($rows as $row) {
                $topiks[] = mb_strimwidth($row->topik ?? 'Tanpa Topik', 0, 28, '…');
                $sangat[] = (int) $row->sangat_aktif;
                $aktif[] = (int) $row->aktif;
                $cukup[] = (int) $row->cukup;
                $pasif[] = (int) $row->pasif;
            }

            return [
                'series' => [
                    ['name' => 'Sangat Aktif', 'data' => $sangat],
                    ['name' => 'Aktif',        'data' => $aktif],
                    ['name' => 'Cukup',        'data' => $cukup],
                    ['name' => 'Pasif',        'data' => $pasif],
                ],
                'categories' => $topiks,
            ];
        });
    }

    /** @return array<string, mixed> */
    private function buildChartDist(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return ['series' => [0, 0, 0, 0], 'labels' => ['Sangat Aktif', 'Aktif', 'Cukup', 'Pasif']];
        }

        $dateRange = $this->getDateRange();
        $kelasId = $this->kelasId;
        $mapelId = $this->mapelId;
        $chartVersion = app(TeacherDashboardCacheService::class)
            ->chartVersion((int) Auth::id(), $tahunAjaran->id);
        $cacheKey = 'chart_dist_v3_'.Auth::id().'_'.$tahunAjaran->id.'_'.$chartVersion.'_'.$this->rentangWaktu.'_'.$kelasId.'_'.$mapelId;

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran, $dateRange, $kelasId, $mapelId): array {
            $hadirVal = KehadiranStatus::Hadir->value;

            $subQuery = DB::table('detail_aktivitas as da')
                ->join('aktivitas_pembelajaran as ap', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
                ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
                ->whereNull('da.deleted_at')
                ->whereNull('ap.deleted_at')
                ->whereNull('k.deleted_at')
                ->where('ap.guru_id', Auth::id())
                ->where('k.tahun_ajaran_id', $tahunAjaran->id)
                ->whereBetween('ap.tanggal', [$dateRange['start'], $dateRange['end']])
                ->where('da.kehadiran', $hadirVal)
                ->whereNotNull('da.keaktifan')
                ->selectRaw('da.siswa_id, AVG('.$this->keaktifanWeightSql('da.keaktifan').') as avg_keaktifan')
                ->groupBy('da.siswa_id');

            if ($kelasId !== '') {
                $subQuery->where('ap.kelas_id', (int) $kelasId);
            }
            if ($mapelId !== '') {
                $subQuery->where('ap.mata_pelajaran_id', (int) $mapelId);
            }

            $result = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery)
                ->selectRaw(
                    'SUM(CASE WHEN sub.avg_keaktifan >= 3.5 THEN 1 ELSE 0 END) as sangat_aktif,
                     SUM(CASE WHEN sub.avg_keaktifan >= 2.5 AND sub.avg_keaktifan < 3.5 THEN 1 ELSE 0 END) as aktif,
                     SUM(CASE WHEN sub.avg_keaktifan >= 1.5 AND sub.avg_keaktifan < 2.5 THEN 1 ELSE 0 END) as cukup,
                     SUM(CASE WHEN sub.avg_keaktifan < 1.5 THEN 1 ELSE 0 END) as pasif'
                )
                ->first();

            if (! $result || ($result->sangat_aktif + $result->aktif + $result->cukup + $result->pasif) === 0) {
                return ['series' => [0, 0, 0, 0], 'labels' => ['Sangat Aktif', 'Aktif', 'Cukup', 'Pasif'], 'empty' => true];
            }

            return [
                'series' => [
                    (int) $result->sangat_aktif,
                    (int) $result->aktif,
                    (int) $result->cukup,
                    (int) $result->pasif,
                ],
                'labels' => ['Sangat Aktif', 'Aktif', 'Cukup', 'Pasif'],
            ];
        });
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function keaktifanWeightSql(string $column): string
    {
        return sprintf(
            "CASE %s WHEN '%s' THEN 1 WHEN '%s' THEN 2 WHEN '%s' THEN 3 WHEN '%s' THEN 4 ELSE NULL END",
            $column,
            Keaktifan::Pasif->value,
            Keaktifan::Cukup->value,
            Keaktifan::Aktif->value,
            Keaktifan::SangatAktif->value,
        );
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    private function getDateRange(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        $semesterStart = CarbonImmutable::parse(
            $tahunAjaran?->tanggal_mulai ?? now()->startOfYear()
        )->startOfDay();
        $semesterEnd = CarbonImmutable::parse(
            $tahunAjaran?->tanggal_selesai ?? now()->endOfYear()
        )->endOfDay();

        [$start, $end] = match ($this->rentangWaktu) {
            'minggu' => [
                CarbonImmutable::now()->startOfWeek(),
                CarbonImmutable::now()->endOfWeek(),
            ],
            'bulan' => [
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
            ],
            default => [$semesterStart, $semesterEnd],
        };

        return [
            'start' => $start->lessThan($semesterStart) ? $semesterStart : $start,
            'end' => $end->greaterThan($semesterEnd) ? $semesterEnd : $end,
        ];
    }
}
