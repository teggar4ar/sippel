<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.teacher')]
#[Title('Dashboard - SIPPEL Guru')]
final class Dashboard extends Component
{
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

        return MataPelajaran::with(['kelas' => fn($q) => $q->withCount('siswa')])
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->get();
    }

    #[Computed]
    public function dashboardStats(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        $cacheKey = 'teacher_dashboard_stats_' . Auth::id() . '_' . ($tahunAjaran?->id ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran): array {
            if (! $tahunAjaran instanceof TahunAjaran) {
                return [
                    'aktivitas_bulan_ini' => 0,
                    'rata_kehadiran' => 0,
                    'total_mapel' => 0,
                ];
            }

            // Activities this month by this teacher (filtered by context year)
            $aktivitasBulanIni = AktivitasPembelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count();

            // Average attendance across all activities by this teacher
            $totalDetails = DetailAktivitas::whereHas(
                'aktivitasPembelajaran',
                fn($q) => $q->where('guru_id', Auth::id())
                    ->whereHas('kelas', fn($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
            )->count();

            $hadirCount = DetailAktivitas::whereHas(
                'aktivitasPembelajaran',
                fn($q) => $q->where('guru_id', Auth::id())
                    ->whereHas('kelas', fn($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
            )->where('kehadiran', 'Hadir')->count();

            $rataKehadiran = $totalDetails > 0 ? round(($hadirCount / $totalDetails) * 100, 1) : 0;

            // Total subjects taught
            $totalMapel = MataPelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->count();

            return [
                'aktivitas_bulan_ini' => $aktivitasBulanIni,
                'rata_kehadiran' => $rataKehadiran,
                'total_mapel' => $totalMapel,
            ];
        });
    }

    #[Computed]
    public function partisipasiPerKelas(): Collection
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        return MataPelajaran::with('kelas')
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->get()
            ->map(function (MataPelajaran $mapel): array {
                $avgPartisipasi = DetailAktivitas::whereHas(
                    'aktivitasPembelajaran',
                    fn($q) => $q->where('mata_pelajaran_id', $mapel->id)
                )->whereNotNull('partisipasi')->avg('partisipasi');

                /** @var Kelas $kelas */
                $kelas = $mapel->kelas;

                return [
                    'kelas' => $kelas->tingkat_kelas . '-' . $kelas->grup_kelas,
                    'mapel' => $mapel->nama_mapel,
                    'avg' => round((float) ($avgPartisipasi ?? 0), 1),
                ];
            })
            ->sortByDesc('avg')
            ->take(5);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.dashboard');
    }
}
