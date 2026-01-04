<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\DetailAktivitas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property array $stats
 */
#[Layout('layouts.student')]
#[Title('Riwayat Kehadiran - SIPPEL Siswa')]
final class RiwayatKehadiran extends Component
{
    use WithPagination;

    public string $filterMapel = '';

    public string $filterStatus = '';

    public string $filterDariTanggal = '';

    public string $filterSampaiTanggal = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }
    }

    public function updatedFilterMapel(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDariTanggal(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSampaiTanggal(): void
    {
        $this->resetPage();
    }

    public function getStatsProperty(): array
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Siswa|null $siswa */
        $siswa = $user->siswa;

        if (! $siswa) {
            return $this->emptyStats();
        }

        $query = DetailAktivitas::query()
            ->where('siswa_id', $siswa->id)
            ->whereHas('aktivitasPembelajaran', fn ($q) => $q->whereNull('deleted_at'));

        if ($this->filterMapel !== '' && $this->filterMapel !== '0') {
            $query->whereHas('aktivitasPembelajaran', fn ($q) => $q->where('mata_pelajaran_id', $this->filterMapel));
        }

        $total = (clone $query)->count();
        $hadir = (clone $query)->where('kehadiran', 'Hadir')->count();
        $izin = (clone $query)->where('kehadiran', 'Izin')->count();
        $sakit = (clone $query)->where('kehadiran', 'Sakit')->count();
        $alpa = (clone $query)->where('kehadiran', 'Alpa')->count();

        return [
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'hadir_pct' => $total > 0 ? round(($hadir / $total) * 100) : 0,
            'izin_pct' => $total > 0 ? round(($izin / $total) * 100) : 0,
            'sakit_pct' => $total > 0 ? round(($sakit / $total) * 100) : 0,
            'alpa_pct' => $total > 0 ? round(($alpa / $total) * 100) : 0,
        ];
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Siswa|null $siswa */
        $siswa = $user->siswa;

        $riwayat = collect();
        $mataPelajaran = collect();

        if ($siswa) {
            $riwayat = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran.kelas'])
                ->whereHas('aktivitasPembelajaran', fn ($q) => $q->whereNull('deleted_at'))
                ->when($this->filterMapel, fn ($q) => $q->whereHas('aktivitasPembelajaran', fn ($sq) => $sq->where('mata_pelajaran_id', $this->filterMapel)))
                ->when($this->filterStatus, fn ($q) => $q->where('kehadiran', $this->filterStatus))
                ->when($this->filterDariTanggal, fn ($q) => $q->whereHas('aktivitasPembelajaran', fn ($sq) => $sq->whereDate('tanggal', '>=', $this->filterDariTanggal)))
                ->when($this->filterSampaiTanggal, fn ($q) => $q->whereHas('aktivitasPembelajaran', fn ($sq) => $sq->whereDate('tanggal', '<=', $this->filterSampaiTanggal)))
                ->orderByDesc(
                    DetailAktivitas::query()
                        ->select('tanggal')
                        ->from('aktivitas_pembelajaran')
                        ->whereColumn('aktivitas_pembelajaran.id', 'detail_aktivitas.aktivitas_pembelajaran_id')
                        ->limit(1)
                )
                ->paginate(10);

            // Get subjects for filter dropdown
            $mataPelajaran = MataPelajaran::query()
                ->whereHas('aktivitasPembelajaran.detailAktivitas', fn ($q) => $q->where('siswa_id', $siswa->id))
                ->orderBy('nama_mapel')
                ->get();
        }

        return view('livewire.student.riwayat-kehadiran', [
            'riwayat' => $riwayat,
            'mataPelajaran' => $mataPelajaran,
            'stats' => $this->stats,
        ]);
    }

    private function emptyStats(): array
    {
        return [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'hadir_pct' => 0,
            'izin_pct' => 0,
            'sakit_pct' => 0,
            'alpa_pct' => 0,
        ];
    }
}
