<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Enums\KehadiranStatus;
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

    #[Url(as: 'kehadiran')]
    public string $filterKehadiran = '';

    #[Url(as: 'partisipasi')]
    public string $filterPartisipasi = '';

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

    public function updatedFilterKehadiran(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPartisipasi(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterKehadiran = '';
        $this->filterPartisipasi = '';
        $this->resetPage();
    }

    #[Computed]
    public function siswa(): ?Siswa
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->siswa;
    }

    #[Computed]
    public function stats(): array
    {
        $siswa = $this->siswa;

        if (! $siswa) {
            return $this->emptyStats();
        }

        $contextTahunAjaran = TahunAjaran::getContext();

        $query = DetailAktivitas::query()
            ->where('siswa_id', $siswa->id)
            ->whereHas('aktivitasPembelajaran', function ($q) use ($contextTahunAjaran): void {
                $q->whereNull('deleted_at')
                    ->when($contextTahunAjaran, fn ($query) => $query->whereHas('kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)));
            });

        $total = (clone $query)->count();
        $hadir = (clone $query)->where('kehadiran', KehadiranStatus::Hadir)->count();
        $izin = (clone $query)->where('kehadiran', KehadiranStatus::Izin)->count();
        $sakit = (clone $query)->where('kehadiran', KehadiranStatus::Sakit)->count();
        $alpa = (clone $query)->where('kehadiran', KehadiranStatus::Alpa)->count();
        $avgPartisipasi = $siswa->getAverageParticipationLabel(null, null, null, $contextTahunAjaran?->id);

        return [
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'hadir_pct' => $total > 0 ? round(($hadir / $total) * 100) : 0,
            'izin_pct' => $total > 0 ? round(($izin / $total) * 100) : 0,
            'sakit_pct' => $total > 0 ? round(($sakit / $total) * 100) : 0,
            'alpa_pct' => $total > 0 ? round(($alpa / $total) * 100) : 0,
            'partisipasi_label' => $avgPartisipasi,
        ];
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
                ->when($this->filterPartisipasi, fn ($q) => $q->where('partisipasi', $this->mapPartisipasiLabelToValue()))
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
            'stats' => $this->stats,
        ]);
    }

    public function exportPdf(): ?StreamedResponse
    {
        $siswa = $this->siswa;
        $tahunAjaran = TahunAjaran::getContext();
        if (! $siswa || ! $tahunAjaran) {
            session()->flash('error', 'Data tidak lengkap.');

            return null;
        }

        $contextKelas = $siswa->getKelasForTahunAjaran($tahunAjaran->id);
        $contextKelas?->load('waliKelas');

        $activityData = DetailAktivitas::where('siswa_id', $siswa->id)
            ->whereHas('aktivitasPembelajaran', function ($q) use ($contextKelas, $tahunAjaran): void {
                if ($contextKelas) {
                    $q->where('kelas_id', $contextKelas->id);
                }
                $q->whereHas('kelas', fn ($kq) => $kq->where('tahun_ajaran_id', $tahunAjaran->id));
            })
            ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran'])
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->orderByDesc('aktivitas_pembelajaran.tanggal')
            ->orderByDesc('detail_aktivitas.id')
            ->select('detail_aktivitas.*')
            ->get();

        if ($activityData->isEmpty()) {
            session()->flash('error', 'Belum ada data aktivitas.');

            return null;
        }

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa'        => $siswa->load(['user', 'kelas']),
            'contextKelas' => $contextKelas,
            'tahunAjaran'  => $tahunAjaran,
            'activityData' => $activityData,
        ]);

        $tahunAjaranSafe = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'riwayat-aktivitas-'.$siswa->nis.'-'.$tahunAjaranSafe.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Map a participation label string to its numeric DB value.
     * Returns a float to safely compare against the decimal:2 column cast.
     */
    private function mapPartisipasiLabelToValue(): ?float
    {
        return match ($this->filterPartisipasi) {
            'pasif'        => 1.0,
            'cukup'        => 2.0,
            'aktif'        => 3.0,
            'sangat_aktif' => 4.0,
            default        => null,
        };
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
            'partisipasi_label' => '-',
        ];
    }
}
