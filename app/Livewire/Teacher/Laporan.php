<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Exports\ClassReportExport;
use App\Models\AktivitasPembelajaran;
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
 * @property-read Kelas|null $selectedKelas
 * @property-read Siswa|null $selectedSiswa
 * @property-read MataPelajaran|null $selectedMataPelajaran
 * @property-read TahunAjaran|null $contextTahunAjaran
 * @property-read Collection<int, LaporanModel> $studentReportData
 * @property-read Collection<int, LaporanModel> $classReportData
 * @property-read User $teacher
 */
final class Laporan extends Component
{
    private const string MSG_NO_CLASS_ACCESS = 'Anda tidak memiliki akses ke kelas ini.';

    private const string MSG_INCOMPLETE_DATA = 'Data tidak lengkap.';

    // Report type: 'student' or 'class'
    public string $reportType = 'student';

    // Form fields
    public ?int $kelasId = null;

    public ?int $siswaId = null;

    public ?int $mataPelajaranId = null;

    public string $sortBy = 'nilai';

    // Preview data
    public bool $showPreview = false;

    /**
     * Get the current teacher (logged in user)
     */
    #[Computed]
    public function teacher(): User
    {
        return Auth::user();
    }

    /**
     * Get classes where the teacher is the homeroom teacher (wali kelas)
     *
     * @return Collection<int, Kelas>
     */
    #[Computed]
    public function kelasWali(): Collection
    {
        $query = Kelas::where('wali_kelas_id', Auth::id())
            ->with('tahunAjaran')
            ->orderBy('tingkat_kelas')
            ->orderBy('grup_kelas');

        // Always scope to the context academic year
        $contextId = TahunAjaran::getContext()?->id;
        if ($contextId) {
            $query->where('tahun_ajaran_id', $contextId);
        }

        return $query->get();
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

        // Each Kelas record belongs to exactly one academic year, so querying via
        // kelasHistory by kelas_id gives us only the students from that year.
        // withTrashed() catches graduated (soft-deleted) students in past years.
        // Fallback on current siswa.kelas_id for systems where kelasHistory has not
        // yet been fully backfilled.
        return Siswa::withTrashed()
            ->where(function ($query): void {
                $query
                    ->whereHas('kelasHistory', fn ($q) => $q->where('kelas_id', $this->kelasId))
                    ->orWhere('kelas_id', $this->kelasId);
            })
            ->with('user')
            ->get()
            ->sortBy(fn (Siswa $s): string => $s->user->name ?? '');
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

        // Security: verify student was (or is currently) in teacher's wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');

        $siswa = Siswa::withTrashed()
            ->with(['user', 'kelas.waliKelas'])
            ->find($this->siswaId);

        if (! $siswa) {
            return null;
        }

        // Check current class OR any historical class
        $inCurrentClass = $kelasWaliIds->contains($siswa->kelas_id);
        $inHistoricalClass = $siswa->kelasHistory()->whereIn('kelas_id', $kelasWaliIds)->exists();

        return ($inCurrentClass || $inHistoricalClass) ? $siswa : null;
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
     * Get the context academic year (from sidebar switcher)
     */
    #[Computed]
    public function contextTahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::getContext();
    }

    /**
     * Get preview data for student report
     *
     * @return Collection<int, LaporanModel>
     */
    #[Computed]
    public function studentReportData(): Collection
    {
        $contextId = $this->contextTahunAjaran?->id;
        if ($this->siswaId === null || $this->siswaId === 0 || ! $contextId) {
            return new Collection();
        }

        return LaporanModel::where('siswa_id', $this->siswaId)
            ->where('tahun_ajaran_id', $contextId)
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
        $contextId = $this->contextTahunAjaran?->id;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $contextId) {
            return new Collection();
        }

        $laporanData = LaporanModel::where('tahun_ajaran_id', $contextId)
            ->where('mata_pelajaran_id', $this->mataPelajaranId)
            ->whereHas(
                'siswa.kelasHistory',
                fn ($q) => $q
                    ->where('tahun_ajaran_id', $contextId)
                    ->where('kelas_id', $this->kelasId)
            )
            ->with(['siswa.user', 'mataPelajaran'])
            ->get();

        // Apply sorting
        return match ($this->sortBy) {
            'nilai' => $laporanData->sortByDesc('rata_nilai')->values(),
            'nilai_asc' => $laporanData->sortBy('rata_nilai')->values(),
            'kehadiran' => $laporanData->sortByDesc('rata_kehadiran')->values(),
            'nama' => $laporanData->sortBy(fn (LaporanModel $l): string => $l->siswa->user->name ?? '')->values(),
            default => $laporanData->sortByDesc('rata_nilai')->values(),
        };
    }

    /**
     * Initialize component with defaults
     */
    public function mount(): void
    {
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
            if ($this->siswaId === null || $this->siswaId === 0) {
                $this->dispatch('notify', type: 'error', message: 'Pilih siswa terlebih dahulu.');

                return;
            }

            // Verify siswa belongs to (or has been in) teacher's wali kelas
            $siswa = Siswa::withTrashed()->find($this->siswaId);
            $kelasWaliIds = $this->kelasWali->pluck('id');
            $hasAccess = $siswa && (
                $kelasWaliIds->contains($siswa->kelas_id) ||
                $siswa->kelasHistory()->whereIn('kelas_id', $kelasWaliIds)->exists()
            );
            if (! $hasAccess) {
                $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke data siswa ini.');

                return;
            }
        } else {
            if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0)) {
                $this->dispatch('notify', type: 'error', message: 'Pilih kelas dan mata pelajaran terlebih dahulu.');

                return;
            }

            // Verify class belongs to teacher
            if (! $this->kelasWali->contains('id', $this->kelasId)) {
                $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

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
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->siswaId === null || $this->siswaId === 0 || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: self::MSG_INCOMPLETE_DATA);

            return null;
        }

        $siswa = Siswa::withTrashed()->with(['user', 'kelas.waliKelas'])->find($this->siswaId);

        // Security check: verify siswa belongs to (or has been in) teacher's wali kelas
        $kelasWaliIds = $this->kelasWali->pluck('id');
        $hasAccess = $siswa && (
            $kelasWaliIds->contains($siswa->kelas_id) ||
            $siswa->kelasHistory()->whereIn('kelas_id', $kelasWaliIds)->exists()
        );
        if (! $hasAccess) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke data siswa ini.');

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

        $contextKelas = $siswa->getKelasForTahunAjaran($tahunAjaran->id);
        $contextKelas?->load('waliKelas');

        $pdf = Pdf::loadView('reports.student-report', [
            'siswa' => $siswa,
            'contextKelas' => $contextKelas,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = 'laporan-siswa-'.$siswa->nis.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Download class report as PDF
     */
    public function downloadClassPdf(): ?StreamedResponse
    {
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak lengkap.');

            return null;
        }

        // Security check: verify class belongs to teacher
        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

            return null;
        }

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);

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
        $filename = 'laporan-kelas-'.$kelas->tingkat_kelas.$kelas->grup_kelas.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Export class report as Excel
     */
    public function exportClassExcel(): mixed
    {
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: self::MSG_INCOMPLETE_DATA);

            return null;
        }

        // Security check: verify class belongs to teacher
        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

            return null;
        }

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);

        if (! $kelas || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak ditemukan.');

            return null;
        }

        // Guard against exporting when there are no learning-activity records for
        // the selected class + subject combination. The export queries DetailAktivitas
        // directly (not LaporanModel), so we check the same source here.
        $hasActivities = AktivitasPembelajaran::where('kelas_id', $kelas->id)
            ->when($mataPelajaran, fn ($q) => $q->where('mata_pelajaran_id', $mataPelajaran->id))
            ->exists();

        if (! $hasActivities) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada data aktivitas untuk kelas dan mata pelajaran ini.');

            return null;
        }

        $sanitizedTahun = str_replace(['/', '\\'], '-', $tahunAjaran->nama_tahun);
        $filename = sprintf(
            'laporan-kelas-%s%s-%s.xlsx',
            $kelas->tingkat_kelas,
            $kelas->grup_kelas,
            $sanitizedTahun
        );

        return Excel::download(new ClassReportExport($kelas, null, null, $mataPelajaran), $filename);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.laporan');
    }
}
