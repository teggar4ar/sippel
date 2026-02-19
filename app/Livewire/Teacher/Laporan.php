<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Exports\ClassReportExport;
use App\Models\Kelas;
use App\Models\Laporan as LaporanModel;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.teacher')]
#[Title('Laporan - SIPPEL Guru')]
/**
 * @property-read Collection<int, Kelas> $kelasWali
 * @property-read bool $hasKelasWali
 * @property-read Collection<int, Siswa> $siswaList
 * @property-read Collection<int, MataPelajaran> $mataPelajaranList
 * @property-read Collection<int, TahunAjaran> $tahunAjaranList
 * @property-read Kelas|null $selectedKelas
 * @property-read Siswa|null $selectedSiswa
 * @property-read MataPelajaran|null $selectedMataPelajaran
 * @property-read TahunAjaran|null $selectedTahunAjaran
 * @property-read Collection<int, LaporanModel> $studentReportData
 * @property-read Collection<int, LaporanModel> $classReportData
 * @property-read User $teacher
 */
final class Laporan extends Component
{
    // Report type: 'student' or 'class'
    public string $reportType = 'student';

    // Form fields
    public ?int $kelasId = null;

    public ?int $siswaId = null;

    public ?int $mataPelajaranId = null;

    public ?int $tahunAjaranId = null;

    public string $sortBy = 'nilai';

    // Preview data
    public bool $showPreview = false;

    /**
     * Get the current teacher (logged in user)
     */
    #[Computed]
    public function teacher(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * Get classes where the teacher is the homeroom teacher (wali kelas)
     *
     * @return Collection<int, Kelas>
     */
    #[Computed]
    public function kelasWali(): Collection
    {
        return Kelas::where('wali_kelas_id', Auth::id())
            ->with('tahunAjaran')
            ->orderBy('tingkat_kelas')
            ->orderBy('grup_kelas')
            ->get();
    }

    /**
     * Check if teacher has any class as homeroom teacher
     */
    #[Computed]
    public function hasKelasWali(): bool
    {
        return $this->kelasWali->isNotEmpty();
    }

    /**
     * Get students in the selected class
     *
     * @return Collection<int, Siswa>
     */
    #[Computed]
    public function siswaList(): Collection
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return new Collection();
        }

        return Siswa::where('kelas_id', $this->kelasId)
            ->with('user')
            ->get()
            ->sortBy(fn(Siswa $s): string => $s->user->name ?? '');
    }

    /**
     * Get subjects for the selected class
     *
     * @return Collection<int, MataPelajaran>
     */
    #[Computed]
    public function mataPelajaranList(): Collection
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return new Collection();
        }

        return MataPelajaran::where('kelas_id', $this->kelasId)
            ->with('guru')
            ->orderBy('nama_mapel')
            ->get();
    }

    /**
     * Get available academic years
     *
     * @return Collection<int, TahunAjaran>
     */
    #[Computed]
    public function tahunAjaranList(): Collection
    {
        return TahunAjaran::orderByDesc('status')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get selected kelas model (only if teacher is wali kelas)
     */
    #[Computed]
    public function selectedKelas(): ?Kelas
    {
        if ($this->kelasId === null || $this->kelasId === 0) {
            return null;
        }

        // Security: Only return if teacher is wali kelas of this class
        return Kelas::with('waliKelas')
            ->where('id', $this->kelasId)
            ->where('wali_kelas_id', Auth::id())
            ->first();
    }

    /**
     * Get selected siswa model (only if in teacher's class)
     */
    #[Computed]
    public function selectedSiswa(): ?Siswa
    {
        if ($this->siswaId === null || $this->siswaId === 0) {
            return null;
        }

        // Security: Only return if siswa is in a class where teacher is wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');

        return Siswa::with(['user', 'kelas.waliKelas'])
            ->where('id', $this->siswaId)
            ->whereIn('kelas_id', $kelasWaliIds)
            ->first();
    }

    /**
     * Get selected mata pelajaran model (only if in teacher's class)
     */
    #[Computed]
    public function selectedMataPelajaran(): ?MataPelajaran
    {
        if ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) {
            return null;
        }

        // Security: Only return if mata pelajaran is in a class where teacher is wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');

        return MataPelajaran::with('guru')
            ->where('id', $this->mataPelajaranId)
            ->whereIn('kelas_id', $kelasWaliIds)
            ->first();
    }

    /**
     * Get selected tahun ajaran model
     */
    #[Computed]
    public function selectedTahunAjaran(): ?TahunAjaran
    {
        if ($this->tahunAjaranId === null || $this->tahunAjaranId === 0) {
            return null;
        }

        return TahunAjaran::find($this->tahunAjaranId);
    }

    /**
     * Get preview data for student report
     *
     * @return Collection<int, LaporanModel>
     */
    #[Computed]
    public function studentReportData(): Collection
    {
        if ($this->siswaId === null || $this->siswaId === 0 || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            return new Collection();
        }

        return LaporanModel::where('siswa_id', $this->siswaId)
            ->where('tahun_ajaran_id', $this->tahunAjaranId)
            ->with('mataPelajaran')
            ->get();
    }

    /**
     * Get preview data for class report
     *
     * @return Collection<int, LaporanModel>
     */
    #[Computed]
    public function classReportData(): Collection
    {
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            return new Collection();
        }

        $laporanData = LaporanModel::where('tahun_ajaran_id', $this->tahunAjaranId)
            ->where('mata_pelajaran_id', $this->mataPelajaranId)
            ->whereHas('siswa', fn($q) => $q->where('kelas_id', $this->kelasId))
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        // Apply sorting
        return match ($this->sortBy) {
            'nilai' => $laporanData->sortByDesc('rata_nilai')->values(),
            'nilai_asc' => $laporanData->sortBy('rata_nilai')->values(),
            'kehadiran' => $laporanData->sortByDesc('rata_kehadiran')->values(),
            'nama' => $laporanData->sortBy(fn(LaporanModel $l): string => $l->siswa->user->name ?? '')->values(),
            default => $laporanData->sortByDesc('rata_nilai')->values(),
        };
    }

    /**
     * Initialize component with defaults
     */
    public function mount(): void
    {
        // Set default to context academic year
        $contextTahunAjaran = TahunAjaran::getContext();
        $this->tahunAjaranId = $contextTahunAjaran?->id;

        // Auto-select first class if teacher has only one
        if ($this->kelasWali->count() === 1) {
            $this->kelasId = $this->kelasWali->first()?->id;
        }
    }

    /**
     * Reset dependent fields when report type changes
     */
    public function updatedReportType(): void
    {
        $this->siswaId = null;
        $this->mataPelajaranId = null;
        $this->showPreview = false;
    }

    /**
     * Reset dependent fields when class changes
     */
    public function updatedKelasId(): void
    {
        $this->siswaId = null;
        $this->mataPelajaranId = null;
        $this->showPreview = false;
    }

    /**
     * Reset preview when student changes
     */
    public function updatedSiswaId(): void
    {
        $this->showPreview = false;
    }

    /**
     * Reset preview when subject changes
     */
    public function updatedMataPelajaranId(): void
    {
        $this->showPreview = false;
    }

    /**
     * Reset preview when academic year changes
     */
    public function updatedTahunAjaranId(): void
    {
        $this->showPreview = false;
    }

    /**
     * Reset preview when sort changes (for class report)
     */
    public function updatedSortBy(): void
    {
        // Preview will auto-refresh due to computed property
    }

    /**
     * Generate preview
     */
    public function generatePreview(): void
    {
        if ($this->reportType === 'student') {
            if ($this->siswaId === null || $this->siswaId === 0 || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
                $this->dispatch('notify', type: 'error', message: 'Pilih siswa dan tahun ajaran terlebih dahulu.');

                return;
            }

            // Verify siswa belongs to teacher's class
            $siswa = Siswa::find($this->siswaId);
            if (! $siswa || ! $this->kelasWali->contains('id', $siswa->kelas_id)) {
                $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke data siswa ini.');

                return;
            }
        } else {
            if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
                $this->dispatch('notify', type: 'error', message: 'Pilih kelas, mata pelajaran, dan tahun ajaran terlebih dahulu.');

                return;
            }

            // Verify class belongs to teacher
            if (! $this->kelasWali->contains('id', $this->kelasId)) {
                $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke kelas ini.');

                return;
            }
        }

        $this->showPreview = true;
    }

    /**
     * Download student report as PDF
     */
    public function downloadStudentPdf(): ?StreamedResponse
    {
        if ($this->siswaId === null || $this->siswaId === 0 || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak lengkap.');

            return null;
        }

        $siswa = Siswa::with(['user', 'kelas.waliKelas'])->find($this->siswaId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        // Security check: verify siswa belongs to teacher's class
        if (! $siswa || ! $this->kelasWali->contains('id', $siswa->kelas_id)) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke data siswa ini.');

            return null;
        }

        if (! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: 'Tahun ajaran tidak ditemukan.');

            return null;
        }

        $laporanData = LaporanModel::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->with('mataPelajaran')
            ->get();

        if ($laporanData->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada data laporan untuk siswa ini.');

            return null;
        }

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa' => $siswa,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'laporan-siswa-' . $siswa->nis . '-' . $sanitizedTahun . '.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Download class report as PDF
     */
    public function downloadClassPdf(): ?StreamedResponse
    {
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak lengkap.');

            return null;
        }

        // Security check: verify class belongs to teacher
        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke kelas ini.');

            return null;
        }

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        if (! $kelas || ! $mataPelajaran || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak ditemukan.');

            return null;
        }

        $laporanData = $this->classReportData;

        if ($laporanData->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada data laporan untuk kelas dan mata pelajaran ini.');

            return null;
        }

        $pdf = Pdf::loadView('reports.class-report', [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'laporan-kelas-' . $kelas->tingkat_kelas . $kelas->grup_kelas . '-' . $sanitizedTahun . '.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Export class report as Excel
     */
    public function exportClassExcel(): mixed
    {
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ($this->tahunAjaranId === null || $this->tahunAjaranId === 0)) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak lengkap.');

            return null;
        }

        // Security check: verify class belongs to teacher
        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke kelas ini.');

            return null;
        }

        $kelas = Kelas::find($this->kelasId);
        $tahunAjaran = TahunAjaran::find($this->tahunAjaranId);

        if (! $kelas || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak ditemukan.');

            return null;
        }

        $laporanData = $this->classReportData;

        if ($laporanData->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada data laporan untuk kelas dan mata pelajaran ini.');

            return null;
        }

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = sprintf(
            'laporan-kelas-%s%s-%s.xlsx',
            $kelas->tingkat_kelas,
            $kelas->grup_kelas,
            $sanitizedTahun
        );

        return Excel::download(new ClassReportExport($kelas), $filename);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.laporan');
    }
}
