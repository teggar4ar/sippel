<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Enums\KehadiranStatus;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\StudentDashboardCacheService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Dashboard - SIPPEL Siswa')]
final class Dashboard extends Component
{
    #[Url(as: 'dari')]
    public ?string $tanggalMulai = null;

    #[Url(as: 'sampai')]
    public ?string $tanggalSelesai = null;

    public string $filterCepat = 'semester';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }

        $tahunAjaran = TahunAjaran::getContext();
        if ($tahunAjaran instanceof TahunAjaran) {
            $this->tanggalMulai = $tahunAjaran->tanggal_mulai->toDateString();
            $this->tanggalSelesai = $tahunAjaran->tanggal_selesai->toDateString();
        }
    }

    public function updatedFilterCepat(string $value): void
    {
        $tahunAjaran = TahunAjaran::getContext();

        switch ($value) {
            case 'semester':
                if ($tahunAjaran instanceof TahunAjaran) {
                    $this->tanggalMulai = $tahunAjaran->tanggal_mulai->toDateString();
                    $this->tanggalSelesai = $tahunAjaran->tanggal_selesai->toDateString();
                }
                break;
            case 'bulan':
                $this->tanggalMulai = now()->startOfMonth()->toDateString();
                $this->tanggalSelesai = now()->toDateString();
                break;
            case 'minggu':
                $this->tanggalMulai = now()->startOfWeek()->toDateString();
                $this->tanggalSelesai = now()->toDateString();
                break;
        }
    }

    public function updatedTanggalMulai(): void
    {
        $this->filterCepat = '';
    }

    public function updatedTanggalSelesai(): void
    {
        $this->filterCepat = '';
    }

    #[Computed]
    public function siswa(): ?Siswa
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->siswa;
    }

    #[Computed]
    public function contextKelas(): ?\App\Models\Kelas
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return null;
        }

        $contextTahunAjaran = TahunAjaran::getContext();

        return $contextTahunAjaran instanceof TahunAjaran
            ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)
            : $siswa->kelas;
    }

    #[Computed]
    public function stats(): array
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $cacheVersion = $contextTahunAjaran instanceof TahunAjaran
            ? app(StudentDashboardCacheService::class)->version($siswa->id, $contextTahunAjaran->id)
            : 'none';
        $cacheKey = 'student_dashboard_stats_'
            .$siswa->id.'_'
            .($contextTahunAjaran?->id ?? 'none').'_'
            .$cacheVersion.'_'
            .($this->tanggalMulai ?? 'none').'_'
            .($this->tanggalSelesai ?? 'none');

        return Cache::remember($cacheKey, 300, function () use ($siswa, $contextTahunAjaran): array {
            $query = DetailAktivitas::where('siswa_id', $siswa->id)
                ->whereHas('aktivitasPembelajaran', function ($q) use ($contextTahunAjaran): void {
                    $q->whereNull('deleted_at')
                        ->when($contextTahunAjaran, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
                        ->when($this->tanggalMulai, fn ($q) => $q->where('tanggal', '>=', $this->tanggalMulai))
                        ->when($this->tanggalSelesai, fn ($q) => $q->where('tanggal', '<=', $this->tanggalSelesai));
                });

            $stats = $query->selectRaw(
                'SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN kehadiran = ? THEN 1 ELSE 0 END) as alpa',
                [
                    KehadiranStatus::Hadir->value,
                    KehadiranStatus::Izin->value,
                    KehadiranStatus::Sakit->value,
                    KehadiranStatus::Alpa->value,
                ],
            )->first();

            return [
                'hadir' => (int) $stats?->hadir,
                'izin' => (int) $stats?->izin,
                'sakit' => (int) $stats?->sakit,
                'alpa' => (int) $stats?->alpa,
            ];
        });
    }

    #[Computed]
    public function heatmapData(): array
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return ['weeks' => [], 'total_columns' => 0, 'enrolled' => false];
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        if (! $contextTahunAjaran instanceof TahunAjaran) {
            return ['weeks' => [], 'total_columns' => 0, 'enrolled' => false];
        }

        $kelas = $siswa->getKelasForTahunAjaran($contextTahunAjaran->id);
        if (! $kelas instanceof \App\Models\Kelas) {
            return ['weeks' => [], 'total_columns' => 0, 'enrolled' => false];
        }

        $startDate = $contextTahunAjaran->tanggal_mulai->copy()->startOfDay();
        $endDate = $contextTahunAjaran->tanggal_selesai->copy()->startOfDay();
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        $aktivitasByDate = AktivitasPembelajaran::where('kelas_id', $kelas->id)
            ->whereBetween('tanggal', [$startDateStr, $endDateStr])
            ->whereNull('deleted_at')
            ->with(['detailAktivitas' => fn ($q) => $q->where('siswa_id', $siswa->id)])
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn ($a) => $a->tanggal->format('Y-m-d'));

        $weeks = [];
        $seenMonths = [];

        // Anchor to Monday of the start-date week; extend to Sunday of the end-date week
        // so every column is a complete Mon–Fri block with blank padding on both ends.
        $weekStart = $startDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $endDate->copy()->endOfWeek(Carbon::SUNDAY);
        $currentDate = $weekStart->copy();
        $todayStr = now()->startOfDay()->format('Y-m-d');

        $currentWeekDays = [];
        $weekMonthSet = [];
        $iterations = 0;

        while ($currentDate->lte($weekEnd)) {
            $iterations++;
            if ($iterations > 750) {
                break;
            }

            $isWeekend = in_array($currentDate->dayOfWeek, [0, 6], true);

            if ($isWeekend) {
                $currentDate = $currentDate->addDay();

                continue;
            }

            $dateStr = $currentDate->format('Y-m-d');
            $inRange = $dateStr >= $startDateStr && $dateStr <= $endDateStr;

            $dayData = [
                'date_string' => $dateStr,
                'day_name' => $currentDate->translatedFormat('D'),
                'formatted_date' => '',
            ];

            if (! $inRange) {
                $dayData['status'] = 'blank';
            } elseif ($dateStr > $todayStr) {
                $dayData['status'] = 'future';
                $dayData['formatted_date'] = $currentDate->translatedFormat('d M Y');
            } else {
                $dayData['formatted_date'] = $currentDate->translatedFormat('d M Y');
                $aktivitasGroup = $aktivitasByDate->get($dateStr);

                if (! $aktivitasGroup || $aktivitasGroup->isEmpty()) {
                    $dayData['status'] = 'no_activity';
                } else {
                    // Merge detail records from ALL activities sharing this date
                    // (one AktivitasPembelajaran per subject — keyBy would have dropped all but one).
                    $mergedDetails = collect();
                    foreach ($aktivitasGroup as $ap) {
                        foreach ($ap->detailAktivitas as $detail) {
                            $mergedDetails->push($detail);
                        }
                    }

                    if ($mergedDetails->isEmpty()) {
                        $dayData['status'] = 'no_activity';
                    } else {
                        $allHadir = $mergedDetails->every(
                            fn ($d): bool => $d->kehadiran === KehadiranStatus::Hadir
                        );
                        $noneHadir = $mergedDetails->every(
                            fn ($d): bool => $d->kehadiran !== KehadiranStatus::Hadir
                        );

                        if ($allHadir) {
                            $dayData['status'] = 'hadir';
                        } elseif ($noneHadir) {
                            $dayData['status'] = 'absent';
                        } else {
                            $dayData['status'] = 'incomplete';
                        }
                    }
                }
            }

            // Track which month this day belongs to (only for in-range days).
            if ($inRange) {
                $month = $currentDate->translatedFormat('F');
                $weekMonthSet[$month] = true;
            }

            $currentWeekDays[] = $dayData;

            // Finalize the week once we have 5 weekday slots.
            if (count($currentWeekDays) === 5) {
                $monthLabel = null;
                $isFirstWeekOfMonth = false;

                foreach (array_keys($weekMonthSet) as $month) {
                    if (! isset($seenMonths[$month])) {
                        $seenMonths[$month] = true;
                        $monthLabel = $month;
                        $isFirstWeekOfMonth = true;
                        break;
                    }
                }

                $weeks[] = [
                    'days' => $currentWeekDays,
                    'month_label' => $monthLabel,
                    'is_first_week_of_month' => $isFirstWeekOfMonth,
                ];

                $currentWeekDays = [];
                $weekMonthSet = [];
            }

            $currentDate = $currentDate->addDay();
        }

        return [
            'weeks' => $weeks,
            'total_columns' => count($weeks),
            'enrolled' => true,
        ];
    }

    #[Computed]
    public function mataPelajaranList(): Collection
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return collect();
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $cacheVersion = $contextTahunAjaran instanceof TahunAjaran
            ? app(StudentDashboardCacheService::class)->version($siswa->id, $contextTahunAjaran->id)
            : 'none';
        $cacheKey = 'student_all_mapel_'.$siswa->id.'_'.($contextTahunAjaran?->id ?? 'none').'_'.$cacheVersion;

        return Cache::remember($cacheKey, 300, function () use ($siswa, $contextTahunAjaran) {
            $kelasId = $contextTahunAjaran instanceof TahunAjaran
                ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)?->id
                : $siswa->kelas_id;

            if (! $kelasId) {
                return collect();
            }

            $subjects = MataPelajaran::where('kelas_id', $kelasId)->orderBy('nama_mapel')->get();

            if ($subjects->isEmpty()) {
                return collect();
            }

            $mapelIds = $subjects->pluck('id')->all();

            $allDetails = DetailAktivitas::where('siswa_id', $siswa->id)
                ->whereNull('deleted_at')
                ->whereHas('aktivitasPembelajaran', function ($q) use ($mapelIds, $contextTahunAjaran): void {
                    $q->whereIn('mata_pelajaran_id', $mapelIds)
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
                        ->whereNull('deleted_at');
                })
                ->with(['aktivitasPembelajaran' => fn ($q) => $q->select('id', 'mata_pelajaran_id')])
                ->get()
                ->groupBy(fn ($d) => $d->aktivitasPembelajaran->mata_pelajaran_id);

            return $subjects->map(function (MataPelajaran $mapel) use ($allDetails): array {
                $details = $allDetails->get($mapel->id, collect());

                return $this->computeMapelStatsFromCollection($mapel, $details);
            })->values();
        });
    }

    #[Computed]
    public function attendanceStreak(): int
    {
        $siswa = $this->siswa();
        if (! $siswa instanceof Siswa) {
            return 0;
        }

        $contextTahunAjaran = TahunAjaran::getContext();
        $cacheVersion = $contextTahunAjaran instanceof TahunAjaran
            ? app(StudentDashboardCacheService::class)->version($siswa->id, $contextTahunAjaran->id)
            : 'none';
        $cacheKey = 'student_streak_'.$siswa->id.'_'.($contextTahunAjaran?->id ?? 'none').'_'.$cacheVersion;

        return Cache::remember($cacheKey, 300, fn (): int => $siswa->getAttendanceStreak($contextTahunAjaran?->id));
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
                'text' => 'Keaktifan hebat! Terus semangat! 📚',
                'variant' => 'success',
            ],
            $attendance >= 75 && $avgPartisipasi >= 2.5 => [
                'text' => 'Kamu di jalur yang tepat! Pertahankan! 👍',
                'variant' => 'info',
            ],
            default => [
                'text' => 'Ayo tingkatkan keaktifan! Kamu pasti bisa! 💪',
                'variant' => 'warning',
            ],
        };
    }

    private function computeMapelStatsFromCollection(MataPelajaran $mapel, Collection $details): array
    {
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
            'total_activities' => $total,
        ];
    }
}
