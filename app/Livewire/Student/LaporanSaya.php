<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Laporan;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Laporan Saya - SIPPEL Siswa')]
final class LaporanSaya extends Component
{
    public ?int $tahunAjaranId = null;

    public ?int $mataPelajaranId = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }

        // Default to active academic year
        $activeTahunAjaran = TahunAjaran::where('status', true)->first();
        if ($activeTahunAjaran) {
            $this->tahunAjaranId = $activeTahunAjaran->id;
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
    public function tahunAjaranList(): Collection
    {
        return TahunAjaran::orderByDesc('status')
            ->orderByDesc('nama_tahun')
            ->get();
    }

    #[Computed]
    public function mataPelajaranList(): Collection
    {
        $siswa = $this->siswa;
        if (! $siswa || ! $siswa->kelas_id) {
            return new Collection();
        }

        return MataPelajaran::where('kelas_id', $siswa->kelas_id)
            ->orderBy('nama_mapel')
            ->get();
    }

    #[Computed]
    public function laporanData(): Collection
    {
        $siswa = $this->siswa;
        if (! $siswa || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            return new Collection();
        }

        return Laporan::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $this->tahunAjaranId)
            ->when($this->mataPelajaranId, fn ($q) => $q->where('mata_pelajaran_id', $this->mataPelajaranId))
            ->with(['mataPelajaran', 'tahunAjaran'])
            ->orderBy('mata_pelajaran_id')
            ->get();
    }

    #[Computed]
    public function selectedTahunAjaran(): ?TahunAjaran
    {
        if ($this->tahunAjaranId === null || $this->tahunAjaranId === 0) {
            return null;
        }

        return TahunAjaran::find($this->tahunAjaranId);
    }

    #[Computed]
    public function summaryStats(): array
    {
        $data = $this->laporanData;

        if ($data->isEmpty()) {
            return [
                'avgKehadiran' => 0,
                'avgNilai' => 0,
                'avgPartisipasi' => 0,
                'totalMapel' => 0,
            ];
        }

        return [
            'avgKehadiran' => $data->avg('rata_kehadiran') ?? 0,
            'avgNilai' => $data->avg('rata_nilai') ?? 0,
            'avgPartisipasi' => $data->avg('rata_partisipasi') ?? 0,
            'totalMapel' => $data->count(),
        ];
    }

    public function downloadPdf(): mixed
    {
        $siswa = $this->siswa;
        if (! $siswa || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            session()->flash('error', 'Tidak dapat mengunduh laporan. Data tidak lengkap.');

            return null;
        }

        $laporanData = $this->laporanData;
        if ($laporanData->isEmpty()) {
            session()->flash('error', 'Tidak ada data laporan untuk diunduh.');

            return null;
        }

        $tahunAjaran = $this->selectedTahunAjaran;

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa' => $siswa->load(['user', 'kelas']),
            'laporanData' => $laporanData,
            'tahunAjaran' => $tahunAjaran,
        ]);

        // Sanitize filename - replace / and \ with -
        $tahunAjaranSafe = str_replace(['/', '\\'], '-', $tahunAjaran?->nama_tahun ?? 'unknown');
        $filename = 'laporan-'.$siswa->nis.'-'.$tahunAjaranSafe.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    public function render(): View
    {
        return view('livewire.student.laporan-saya');
    }
}
