<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Enums\KehadiranStatus;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SiswaKelasHistory;
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

        return MataPelajaran::with(['kelas' => fn ($q) => $q->withCount('siswa')])
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->get();
    }

    #[Computed]
    public function dashboardStats(): array
    {
        $tahunAjaran = $this->activeTahunAjaran();
        $cacheKey = 'teacher_dashboard_stats_'.Auth::id().'_'.($tahunAjaran?->id ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($tahunAjaran): array {
            if (! $tahunAjaran instanceof TahunAjaran) {
                return [
                    'kelas_diampu' => 0,
                    'total_siswa' => 0,
                    'aktivitas_minggu_ini' => 0,
                    'rata_kehadiran' => 0,
                ];
            }

            // Count subjects taught by this teacher
            $mapelDiampu = MataPelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->count();

            // Total students across all classes taught (use history for past years)
            $kelasIds = MataPelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->pluck('kelas_id')
                ->unique();
            $totalSiswa = SiswaKelasHistory::where('tahun_ajaran_id', $tahunAjaran->id)
                ->whereIn('kelas_id', $kelasIds)
                ->distinct('siswa_id')
                ->count('siswa_id');

            // Activities this week by this teacher
            $aktivitasMingguIni = AktivitasPembelajaran::where('guru_id', Auth::id())
                ->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
                ->whereBetween('tanggal', [now()->copy()->startOfWeek(), now()->copy()->endOfWeek()])
                ->count();

            // Average attendance across all activities by this teacher
            $totalDetails = DetailAktivitas::whereHas(
                'aktivitasPembelajaran',
                fn ($q) => $q->where('guru_id', Auth::id())
                    ->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
            )->count();

            $hadirCount = DetailAktivitas::whereHas(
                'aktivitasPembelajaran',
                fn ($q) => $q->where('guru_id', Auth::id())
                    ->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $tahunAjaran->id))
            )->where('kehadiran', KehadiranStatus::Hadir)->count();

            $rataKehadiran = $totalDetails > 0 ? round(($hadirCount / $totalDetails) * 100, 1) : 0;

            return [
                'kelas_diampu' => $mapelDiampu,
                'total_siswa' => $totalSiswa,
                'aktivitas_minggu_ini' => $aktivitasMingguIni,
                'rata_kehadiran' => $rataKehadiran,
            ];
        });
    }

    /**
     * Get recent activities (last 7 days) for the "Aktivitas Terkini" table.
     */
    #[Computed]
    public function recentActivities(): Collection
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, AktivitasPembelajaran> $aktivitasList */
        $aktivitasList = AktivitasPembelajaran::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->where('tanggal', '>=', now()->subDays(7))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->take(11)
            ->get();

        return $aktivitasList->map(function (AktivitasPembelajaran $aktivitas): array {
            $details = $aktivitas->detailAktivitas;
            $totalDetail = $details->count();
            $hadirCount = $details->where('kehadiran', KehadiranStatus::Hadir)->count();
            $kehadiranPct = $totalDetail > 0 ? round(($hadirCount / $totalDetail) * 100, 0) : 0;

            // Average participation for hadir students
            $avgPartisipasi = $details
                ->where('kehadiran', KehadiranStatus::Hadir)
                ->whereNotNull('partisipasi')
                ->avg('partisipasi');
            $partisipasiLabel = match (true) {
                $avgPartisipasi === null => '-',
                $avgPartisipasi >= 3.5 => 'Sangat Aktif',
                $avgPartisipasi >= 2.5 => 'Aktif',
                $avgPartisipasi >= 1.5 => 'Cukup',
                default => 'Pasif',
            };

            /** @var Kelas $kelas */
            $kelas = $aktivitas->kelas;

            return [
                'id' => $aktivitas->id,
                'tanggal' => $aktivitas->tanggal->translatedFormat('d M Y'),
                'waktu' => $aktivitas->created_at->setTimezone('Asia/Jakarta')->format('H:i'),
                'kelas' => $kelas->tingkat_kelas.'-'.$kelas->grup_kelas,
                'mapel' => $aktivitas->mataPelajaran?->nama_mapel ?? '-',
                'topik' => $aktivitas->topik ?? '-',
                'kehadiran' => $kehadiranPct.'%',
                'kehadiran_pct' => $kehadiranPct,
                'partisipasi' => $partisipasiLabel,
            ];
        });
    }

    /**
     * Get partisipasi status per class for "Kelas Saya" card badges.
     */
    #[Computed]
    public function partisipasiPerKelas(): Collection
    {
        $tahunAjaran = $this->activeTahunAjaran();
        if (! $tahunAjaran instanceof TahunAjaran) {
            return collect();
        }

        return MataPelajaran::with(['kelas' => fn ($q) => $q->withCount('siswa')])
            ->where('guru_id', Auth::id())
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->get()
            ->map(function (MataPelajaran $mapel): array {
                $avgPartisipasi = DetailAktivitas::whereHas(
                    'aktivitasPembelajaran',
                    fn ($q) => $q
                        ->where('mata_pelajaran_id', $mapel->id)
                        ->where('kelas_id', $mapel->kelas_id)
                )
                    ->whereNotNull('partisipasi')
                    ->avg('partisipasi');

                $totalDetail = DetailAktivitas::whereHas(
                    'aktivitasPembelajaran',
                    fn ($q) => $q
                        ->where('mata_pelajaran_id', $mapel->id)
                        ->where('kelas_id', $mapel->kelas_id)
                )->count();
                $hadirCount = DetailAktivitas::whereHas(
                    'aktivitasPembelajaran',
                    fn ($q) => $q
                        ->where('mata_pelajaran_id', $mapel->id)
                        ->where('kelas_id', $mapel->kelas_id)
                )->where('kehadiran', KehadiranStatus::Hadir)->count();
                $kehadiranPct = $totalDetail > 0 ? round(($hadirCount / $totalDetail) * 100, 0) : 0;
                $partisipasiScore = $avgPartisipasi !== null ? min(max($avgPartisipasi, 0), 4) / 4 * 100 : 0;
                $score = round(($kehadiranPct * 0.6) + ($partisipasiScore * 0.4), 1);

                /** @var Kelas $kelas */
                $kelas = $mapel->kelas;

                return [
                    'mapel_id' => $mapel->id,
                    'kelas' => $kelas->tingkat_kelas.'-'.$kelas->grup_kelas,
                    'mapel' => $mapel->nama_mapel,
                    'siswa_count' => $kelas->siswa_count ?? 0,
                    'avg' => round((float) ($avgPartisipasi ?? 0), 1),
                    'kehadiran_pct' => $kehadiranPct,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->take(3);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.dashboard');
    }
}
