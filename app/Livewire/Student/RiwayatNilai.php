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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property Collection $summaryPerMapel
 */
#[Layout('layouts.student')]
#[Title('Riwayat Nilai - SIPPEL Siswa')]
final class RiwayatNilai extends Component
{
    use WithPagination;

    public string $filterMapel = '';

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

    public function updatedFilterDariTanggal(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSampaiTanggal(): void
    {
        $this->resetPage();
    }

    public function getSummaryPerMapelProperty(): Collection
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Siswa|null $siswa */
        $siswa = $user->siswa;

        if (! $siswa) {
            return collect();
        }

        $contextTahunAjaran = \App\Models\TahunAjaran::getContext();

        // Get subjects that have activities for this student (filtered by context year)
        $mataPelajaran = MataPelajaran::query()
            ->whereHas('aktivitasPembelajaran.detailAktivitas', function ($q) use ($siswa, $contextTahunAjaran) {
                $q->where('siswa_id', $siswa->id)
                    ->when($contextTahunAjaran, fn ($query) => $query->whereHas('aktivitasPembelajaran.kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
            })
            ->get();

        return $mataPelajaran->map(function (MataPelajaran $mapel) use ($siswa, $contextTahunAjaran): array {
            $nilai = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->whereHas('aktivitasPembelajaran', function ($q) use ($mapel, $contextTahunAjaran) {
                    $q->where('mata_pelajaran_id', $mapel->id)
                        ->whereNull('deleted_at')
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
                })
                ->whereNotNull('nilai')
                ->pluck('nilai');

            return [
                'nama' => $mapel->nama_mapel,
                'avg' => $nilai->isNotEmpty() ? round($nilai->avg(), 1) : '-',
                'max' => $nilai->max() ?? '-',
                'min' => $nilai->min() ?? '-',
            ];
        });
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Siswa|null $siswa */
        $siswa = $user->siswa;

        $contextTahunAjaran = \App\Models\TahunAjaran::getContext();
        $riwayat = collect();
        $mataPelajaran = collect();

        if ($siswa) {
            $riwayat = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran.kelas'])
                ->whereHas('aktivitasPembelajaran', function ($q) use ($contextTahunAjaran) {
                    $q->whereNull('deleted_at')
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
                })
                ->when($this->filterMapel, fn ($q) => $q->whereHas('aktivitasPembelajaran', fn ($sq) => $sq->where('mata_pelajaran_id', $this->filterMapel)))
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

            // Get subjects for filter dropdown (only from context year)
            $mataPelajaran = MataPelajaran::query()
                ->whereHas('aktivitasPembelajaran.detailAktivitas', function ($q) use ($siswa, $contextTahunAjaran) {
                    $q->where('siswa_id', $siswa->id)
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('aktivitasPembelajaran.kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
                })
                ->orderBy('nama_mapel')
                ->get();
        }

        return view('livewire.student.riwayat-nilai', [
            'riwayat' => $riwayat,
            'mataPelajaran' => $mataPelajaran,
            'summaryPerMapel' => $this->summaryPerMapel,
        ]);
    }
}
