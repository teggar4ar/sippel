<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\DetailAktivitas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property array $stats
 */
#[Layout('layouts.student')]
#[Title('Riwayat Aktivitas - SIPPEL Siswa')]
final class RiwayatAktivitas extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'mapel')]
    public string $filterMapel = '';

    #[Url(as: 'kehadiran')]
    public string $filterKehadiran = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMapel(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKehadiran(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterMapel = '';
        $this->filterKehadiran = '';
        $this->resetPage();
    }

    #[Computed]
    public function siswa(): ?Siswa
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->siswa;
    }

    /**
     * @return array<int|string, string>
     */
    #[Computed]
    public function mataPelajaranOptions(): array
    {
        $siswa = $this->siswa;
        if (! $siswa) {
            return [];
        }

        $contextTahunAjaran = TahunAjaran::getContext();

        return \App\Models\MataPelajaran::whereHas('aktivitasPembelajaran', fn ($q) => $q
            ->whereHas('detailAktivitas', fn ($dq) => $dq->where('siswa_id', $siswa->id))
            ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
        )
            ->get()
            ->mapWithKeys(fn ($mapel): array => [(string) $mapel->id => $mapel->nama_mapel])
            ->toArray();
    }

    public function render(): View
    {
        $siswa = $this->siswa;

        $contextTahunAjaran = TahunAjaran::getContext();
        $riwayat = collect();

        if ($siswa) {
            $riwayat = DetailAktivitas::query()
                ->where('siswa_id', $siswa->id)
                ->with(['aktivitasPembelajaran.mataPelajaran'])
                ->whereHas('aktivitasPembelajaran', function ($q) use ($contextTahunAjaran): void {
                    $q->whereNull('deleted_at')
                        ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
                })
                ->when($this->search, fn ($q) => $q->where(function ($sub): void {
                    $sub->whereHas('aktivitasPembelajaran', fn ($aq) => $aq->where('topik', 'like', "%{$this->search}%"))
                        ->orWhereHas('aktivitasPembelajaran.mataPelajaran', fn ($mq) => $mq->where('nama_mapel', 'like', "%{$this->search}%"));
                }))
                ->when($this->filterKehadiran, fn ($q) => $q->where('kehadiran', $this->filterKehadiran))
                ->when($this->filterMapel, fn ($q) => $q->whereHas('aktivitasPembelajaran', fn ($aq) => $aq->where('mata_pelajaran_id', $this->filterMapel)))
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
                ->paginate(25);
        }

        return view('livewire.student.riwayat-aktivitas', [
            'riwayat' => $riwayat,
        ]);
    }

    public function exportPdf(): ?StreamedResponse
    {
        $siswa = $this->siswa;
        $tahunAjaran = TahunAjaran::getContext();
        if (! $siswa || ! $tahunAjaran instanceof TahunAjaran) {
            session()->flash('error', 'Data tidak lengkap.');

            return null;
        }

        $contextKelas = $siswa->getKelasForTahunAjaran($tahunAjaran->id);
        $contextKelas?->load('waliKelas');

        $activityData = DetailAktivitas::where('siswa_id', $siswa->id)
            ->withTimelineJoin($contextKelas?->id, $tahunAjaran->id)
            ->get();

        if ($activityData->isEmpty()) {
            session()->flash('error', 'Belum ada data aktivitas.');

            return null;
        }

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa' => $siswa->load(['user', 'kelas']),
            'contextKelas' => $contextKelas,
            'tahunAjaran' => $tahunAjaran,
            'activityData' => $activityData,
        ]);

        $tahunAjaranSafe = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'riwayat-aktivitas-'.$siswa->nis.'-'.$tahunAjaranSafe.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }
}
