<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\DetailAktivitas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
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
    public function performancePerMapel(): Collection
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa || ! $siswa->kelas_id) {
            return collect();
        }

        $cacheKey = 'student_performance_'.$siswa->id;

        return Cache::remember($cacheKey, 300, function () use ($siswa) {
            // Get all subjects for student's class
            $subjects = MataPelajaran::where('kelas_id', $siswa->kelas_id)->get();

            return $subjects->map(function (MataPelajaran $mapel) use ($siswa): array {
                $avgNilai = $siswa->getAverageGrade($mapel->id);

                return [
                    'nama_mapel' => $mapel->nama_mapel,
                    'avg_nilai' => $avgNilai ?? 0,
                ];
            })->filter(fn (array $data): bool => $data['avg_nilai'] > 0)
                ->sortByDesc('avg_nilai')
                ->take(5)
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

        $attendance = $siswa->getAttendancePercentage();
        $grade = $siswa->getAverageGrade() ?? 0;

        return match (true) {
            $attendance >= 90 && $grade >= 85 => [
                'text' => 'Luar biasa! Kamu siswa teladan! 🌟',
                'variant' => 'success',
            ],
            $attendance >= 90 => [
                'text' => 'Kehadiran sempurna! Pertahankan! ✨',
                'variant' => 'info',
            ],
            $grade >= 85 => [
                'text' => 'Nilai bagus! Terus semangat! 📚',
                'variant' => 'success',
            ],
            $attendance >= 75 && $grade >= 70 => [
                'text' => 'Kamu di jalur yang tepat! Pertahankan! 👍',
                'variant' => 'info',
            ],
            default => [
                'text' => 'Ayo tingkatkan belajar! Kamu pasti bisa! 💪',
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

        $cacheKey = 'student_streak_'.$siswa->id;

        return Cache::remember($cacheKey, 300, function () use ($siswa): int {
            // Get all activities for this student ordered by date desc using DB query
            /** @var Collection<int, object{kehadiran: string, tanggal: string}> $activities */
            $activities = \Illuminate\Support\Facades\DB::table('detail_aktivitas')
                ->where('detail_aktivitas.siswa_id', $siswa->id)
                ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
                ->whereNull('aktivitas_pembelajaran.deleted_at')
                ->whereNull('detail_aktivitas.deleted_at')
                ->orderByDesc('aktivitas_pembelajaran.tanggal')
                ->select('detail_aktivitas.kehadiran', 'aktivitas_pembelajaran.tanggal')
                ->get();

            if ($activities->isEmpty()) {
                return 0;
            }

            $streak = 0;
            $lastDate = null;

            foreach ($activities as $activity) {
                $kehadiran = $activity->kehadiran;
                $tanggal = $activity->tanggal;

                if (mb_strtolower($kehadiran) === 'hadir') {
                    $activityDate = \Carbon\Carbon::parse($tanggal);

                    // If first activity or consecutive day (allowing same day)
                    if (! $lastDate instanceof \Carbon\Carbon) {
                        $streak++;
                        $lastDate = $activityDate;
                    } elseif ($activityDate->isSameDay($lastDate) || $activityDate->diffInDays($lastDate) <= 1) {
                        $streak++;
                        $lastDate = $activityDate;
                    } else {
                        break; // Gap in attendance, stop counting
                    }
                } else {
                    break; // Not present, stop counting
                }
            }

            return $streak;
        });
    }

    public function render(): View
    {
        $siswa = $this->siswa();

        $totalAktivitas = 0;
        $recentAktivitas = collect();

        if ($siswa instanceof Siswa) {
            // Eager load relationships for stats
            $siswa->load('detailAktivitas.aktivitasPembelajaran.mataPelajaran', 'kelas');

            $totalAktivitas = $siswa->detailAktivitas()->count();

            // Get recent activities (last 5)
            $recentAktivitas = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->with(['aktivitasPembelajaran.mataPelajaran'])
                ->whereHas('aktivitasPembelajaran', fn ($q) => $q->whereNull('deleted_at'))
                ->orderByDesc(
                    DetailAktivitas::query()
                        ->select('tanggal')
                        ->from('aktivitas_pembelajaran')
                        ->whereColumn('aktivitas_pembelajaran.id', 'detail_aktivitas.aktivitas_pembelajaran_id')
                        ->limit(1)
                )
                ->limit(5)
                ->get();
        }

        return view('livewire.student.dashboard', [
            'siswa' => $siswa,
            'totalAktivitas' => $totalAktivitas,
            'recentAktivitas' => $recentAktivitas,
        ]);
    }
}
