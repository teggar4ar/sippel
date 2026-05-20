<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Enums\KehadiranStatus;
use App\Models\DetailAktivitas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Dashboard - SIPPEL Siswa')]
final class Dashboard extends Component
{
    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }
    }

    #[Computed]
    public function siswa(): ?Siswa
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->siswa;
    }

    #[Computed]
    public function totalMapel(): int
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return 0;
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $kelasId = $contextTahunAjaran instanceof TahunAjaran
            ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)?->id
            : $siswa->kelas_id;

        if (! $kelasId) {
            return 0;
        }

        return MataPelajaran::where('kelas_id', $kelasId)->count();
    }

    #[Computed]
    public function topPerformaMapel(): Collection
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return collect();
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $cacheKey = 'student_top_mapel_'.$siswa->id.'_'.($contextTahunAjaran?->id ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($siswa, $contextTahunAjaran) {
            $kelasId = $contextTahunAjaran instanceof TahunAjaran
                ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)?->id
                : $siswa->kelas_id;

            if (! $kelasId) {
                return collect();
            }

            $subjects = MataPelajaran::where('kelas_id', $kelasId)->get();

            return $subjects->map(function (MataPelajaran $mapel) use ($siswa, $contextTahunAjaran): array {
                $details = DetailAktivitas::where('siswa_id', $siswa->id)
                    ->whereNull('deleted_at')
                    ->whereHas('aktivitasPembelajaran', function ($q) use ($mapel, $contextTahunAjaran): void {
                        $q->where('mata_pelajaran_id', $mapel->id)
                            ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
                            ->whereNull('deleted_at');
                    })
                    ->get();

                $total = $details->count();
                $hadir = $details->where('kehadiran', KehadiranStatus::Hadir)->count();
                $attendancePct = $total > 0 ? round(($hadir / $total) * 100) : 0;

                $avgPartisipasi = $details->whereNotNull('partisipasi')->avg('partisipasi');
                $partisipasiLabel = match (true) {
                    $avgPartisipasi === null => '-',
                    $avgPartisipasi < 1.5 => 'Pasif',
                    $avgPartisipasi < 2.5 => 'Cukup',
                    $avgPartisipasi < 3.5 => 'Aktif',
                    default => 'Sangat Aktif',
                };

                $compositeScore = ($attendancePct * 0.6)
                    + (($avgPartisipasi ? ($avgPartisipasi / 4 * 100) : 0) * 0.4);

                return [
                    'nama_mapel' => $mapel->nama_mapel,
                    'attendance_pct' => $attendancePct,
                    'partisipasi_label' => $partisipasiLabel,
                    'composite_score' => $compositeScore,
                ];
            })
                ->filter(fn (array $data): bool => $data['composite_score'] > 0)
                ->sortByDesc('composite_score')
                ->take(3)
                ->values();
        });
    }

    #[Computed]
    public function motivationalMessage(): array
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return ['text' => 'Selamat datang di SIPPEL! 👋', 'variant' => 'info'];
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $attendance = $siswa->getAttendancePercentage(null, null, null, $contextTahunAjaran?->id);
        $avgPartisipasi = $siswa->getAverageParticipation(null, null, null, $contextTahunAjaran?->id) ?? 0;

        return match (true) {
            $attendance >= 90 && $avgPartisipasi >= 3.5 => [
                'text' => 'Luar biasa! Kamu siswa teladan! 🌟',
                'variant' => 'success',
            ],
            $attendance >= 90 => [
                'text' => 'Kehadiran sempurna! Pertahankan! ✨',
                'variant' => 'info',
            ],
            $avgPartisipasi >= 3.5 => [
                'text' => 'Partisipasi hebat! Terus semangat! 📚',
                'variant' => 'success',
            ],
            $attendance >= 75 && $avgPartisipasi >= 2.5 => [
                'text' => 'Kamu di jalur yang tepat! Pertahankan! 👍',
                'variant' => 'info',
            ],
            default => [
                'text' => 'Ayo tingkatkan partisipasi! Kamu pasti bisa! 💪',
                'variant' => 'warning',
            ],
        };
    }

    #[Computed]
    public function attendanceStreak(): int
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return 0;
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $cacheKey = 'student_streak_'.$siswa->id.'_'.($contextTahunAjaran?->id ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($siswa, $contextTahunAjaran): int {
            // Get all activities for this student ordered by date desc using DB query
            $query = \Illuminate\Support\Facades\DB::table('detail_aktivitas')
                ->where('detail_aktivitas.siswa_id', $siswa->id)
                ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
                ->whereNull('aktivitas_pembelajaran.deleted_at')
                ->whereNull('detail_aktivitas.deleted_at');

            if ($contextTahunAjaran instanceof TahunAjaran) {
                $query->join('kelas', 'aktivitas_pembelajaran.kelas_id', '=', 'kelas.id')
                    ->where('kelas.tahun_ajaran_id', $contextTahunAjaran->id);
            }

            /** @var Collection<int, object{kehadiran: string, tanggal: string}> $activities */
            $activities = $query->orderByDesc('aktivitas_pembelajaran.tanggal')
                ->orderByDesc('aktivitas_pembelajaran.created_at')
                ->orderByDesc('detail_aktivitas.id')
                ->select('detail_aktivitas.kehadiran', 'aktivitas_pembelajaran.tanggal')
                ->get();

            if ($activities->isEmpty()) {
                return 0;
            }

            return $this->calculateStreakFromActivities($activities);
        });
    }

    public function render(): View
    {
        $siswa = $this->siswa();
        $contextTahunAjaran = TahunAjaran::getContext();

        $recentAktivitas = collect();
        $attendancePercentage = 0;
        $averageParticipationLabel = '-';
        $totalMapel = 0;

        if ($siswa instanceof Siswa) {
            $totalMapel = $this->totalMapel();

            // Get recent activities (last 7 days)
            $recentAktivitas = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->with(['aktivitasPembelajaran.mataPelajaran'])
                ->whereHas('aktivitasPembelajaran', function ($q) use ($contextTahunAjaran): void {
                    $q->whereNull('deleted_at')
                        ->where('tanggal', '>=', now()->subDays(7))
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
                })
                ->orderByDesc(
                    DetailAktivitas::query()
                        ->select('tanggal')
                        ->from('aktivitas_pembelajaran')
                        ->whereColumn('aktivitas_pembelajaran.id', 'detail_aktivitas.aktivitas_pembelajaran_id')
                        ->limit(1)
                )
                ->orderByDesc(
                    DetailAktivitas::query()
                        ->select('created_at')
                        ->from('aktivitas_pembelajaran')
                        ->whereColumn('aktivitas_pembelajaran.id', 'detail_aktivitas.aktivitas_pembelajaran_id')
                        ->limit(1)
                )
                ->limit(11)
                ->get();

            // Context-aware stats for view
            $attendancePercentage = $siswa->getAttendancePercentage(null, null, null, $contextTahunAjaran?->id);
            $averageParticipationLabel = $siswa->getAverageParticipationLabel(null, null, null, $contextTahunAjaran?->id);
        }

        // Resolve the class for the selected academic year via kelasHistory,
        // falling back to the student's current class when no context is set.
        $contextKelas = null;
        if ($siswa instanceof Siswa) {
            $contextKelas = $contextTahunAjaran instanceof TahunAjaran
                ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)
                : $siswa->kelas;
        }

        return view('livewire.student.dashboard', [
            'siswa' => $siswa,
            'contextKelas' => $contextKelas,
            'recentAktivitas' => $recentAktivitas,
            'attendancePercentage' => $attendancePercentage,
            'averageParticipationLabel' => $averageParticipationLabel,
            'totalMapel' => $totalMapel,
        ]);
    }

    /**
     * Calculate consecutive attendance streak from a descending-date activity list.
     *
     * @param  iterable<object{kehadiran: string, tanggal: string}>  $activities
     */
    private function calculateStreakFromActivities(iterable $activities): int
    {
        $streak = 0;
        $lastDate = null;

        foreach ($activities as $activity) {
            if (mb_strtolower($activity->kehadiran) !== 'hadir') {
                break; // Not present, stop counting
            }

            $activityDate = \Carbon\Carbon::parse($activity->tanggal);

            if (! $lastDate instanceof \Carbon\Carbon) {
                $streak++;
                $lastDate = $activityDate;
            } elseif ($activityDate->isSameDay($lastDate) || $activityDate->diffInDays($lastDate) <= 1) {
                $streak++;
                $lastDate = $activityDate;
            } else {
                break; // Gap in attendance, stop counting
            }
        }

        return $streak;
    }
}
