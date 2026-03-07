<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\Concerns;

use App\Exports\ClassReportExport;
use App\Models\AktivitasPembelajaran;
use App\Models\Kelas;
use App\Models\Laporan as LaporanModel;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HasLaporanDownloads
{
    private const string MSG_NO_CLASS_ACCESS = 'Anda tidak memiliki akses ke kelas ini.';

    private const string MSG_INCOMPLETE_DATA = 'Data tidak lengkap.';

    /**
     * Download student report as PDF
     */
    public function downloadStudentPdf(): ?StreamedResponse
    {
        $data = $this->resolveStudentPdfData();
        if ($data === null) {
            return null;
        }

        $pdf = Pdf::loadView('reports.student-report', $data);
        $pdf->setPaper('A4', 'portrait');

        $sanitizedTahun = str_replace(['/', '\\'], '-', $data['tahunAjaran']->nama_tahun);
        $filename = 'laporan-siswa-'.$data['siswa']->nis.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Download class report as PDF
     */
    public function downloadClassPdf(): ?StreamedResponse
    {
        $data = $this->resolveClassPdfData();
        if ($data === null) {
            return null;
        }

        $pdf = Pdf::loadView('reports.class-report', $data);
        $pdf->setPaper('A4', 'portrait');

        $sanitizedTahun = str_replace(['/', '\\'], '-', $data['tahunAjaran']->nama_tahun);
        $filename = 'laporan-kelas-'.$data['kelas']->tingkat_kelas.$data['kelas']->grup_kelas.'-'.$sanitizedTahun.'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Export class report as Excel
     */
    public function exportClassExcel(): mixed
    {
        $data = $this->resolveClassExcelData();
        if ($data === null) {
            return null;
        }

        $sanitizedTahun = str_replace(['/', '\\'], '-', $data['tahunAjaran']->nama_tahun);
        $filename = sprintf(
            'laporan-kelas-%s%s-%s.xlsx',
            $data['kelas']->tingkat_kelas,
            $data['kelas']->grup_kelas,
            $sanitizedTahun
        );

        return Excel::download(new ClassReportExport($data['kelas'], null, null, $data['mataPelajaran']), $filename);
    }

    // ──────────────────────────────────────────────────────
    // Preview validation helpers
    // ──────────────────────────────────────────────────────

    /**
     * Run the correct access check depending on the selected report type.
     * Dispatches error notifications on failure and returns false.
     */
    private function validatePreviewRequest(): bool
    {
        if ($this->reportType === 'student') {
            return $this->validateStudentPreviewAccess();
        }

        return $this->validateClassPreviewAccess();
    }

    /**
     * Verify that a valid, accessible student is selected for student-type previews.
     */
    private function validateStudentPreviewAccess(): bool
    {
        if ($this->siswaId === null || $this->siswaId === 0) {
            $this->dispatch('notify', type: 'error', message: 'Pilih siswa terlebih dahulu.');

            return false;
        }

        $siswa = Siswa::withTrashed()->find($this->siswaId);
        $kelasWaliIds = $this->kelasWali->pluck('id');
        $hasAccess = $siswa && (
            $kelasWaliIds->contains($siswa->kelas_id) ||
            $siswa->kelasHistory()->whereIn('kelas_id', $kelasWaliIds)->exists()
        );

        if (! $hasAccess) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke data siswa ini.');

            return false;
        }

        return true;
    }

    /**
     * Verify that a valid, accessible class and subject are selected for class-type previews.
     */
    private function validateClassPreviewAccess(): bool
    {
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0)) {
            $this->dispatch('notify', type: 'error', message: 'Pilih kelas dan mata pelajaran terlebih dahulu.');

            return false;
        }

        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

            return false;
        }

        return true;
    }

    // ──────────────────────────────────────────────────────
    // PDF/Excel data resolvers
    // ──────────────────────────────────────────────────────

    /**
     * Load and validate all data required for the student PDF download.
     * Dispatches error notifications and returns null on any failure.
     *
     * @return array{siswa: Siswa, contextKelas: Kelas|null, tahunAjaran: TahunAjaran, laporanData: Collection<int, LaporanModel>}|null
     */
    private function resolveStudentPdfData(): ?array
    {
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->siswaId === null || $this->siswaId === 0 || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: self::MSG_INCOMPLETE_DATA);

            return null;
        }

        $siswa = Siswa::withTrashed()->with(['user', 'kelas.waliKelas'])->find($this->siswaId);
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

        return [
            'siswa' => $siswa,
            'contextKelas' => $contextKelas,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ];
    }

    /**
     * Load and validate all data required for the class PDF download.
     * Dispatches error notifications and returns null on any failure.
     *
     * @return array{kelas: Kelas, mataPelajaran: MataPelajaran, tahunAjaran: TahunAjaran, laporanData: Collection<int, LaporanModel>}|null
     */
    private function resolveClassPdfData(): ?array
    {
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: self::MSG_INCOMPLETE_DATA);

            return null;
        }

        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

            return null;
        }

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);

        if (! $kelas || ! $mataPelajaran) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak ditemukan.');

            return null;
        }

        $laporanData = $this->classReportData;

        if ($laporanData->isEmpty()) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada data laporan untuk kelas dan mata pelajaran ini.');

            return null;
        }

        return [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'tahunAjaran' => $tahunAjaran,
            'laporanData' => $laporanData,
        ];
    }

    /**
     * Load and validate all data required for the class Excel export.
     * Dispatches error notifications and returns null on any failure.
     *
     * @return array{kelas: Kelas, mataPelajaran: MataPelajaran|null, tahunAjaran: TahunAjaran}|null
     */
    private function resolveClassExcelData(): ?array
    {
        $tahunAjaran = $this->contextTahunAjaran;
        if ($this->kelasId === null || $this->kelasId === 0 || ($this->mataPelajaranId === null || $this->mataPelajaranId === 0) || ! $tahunAjaran) {
            $this->dispatch('notify', type: 'error', message: self::MSG_INCOMPLETE_DATA);

            return null;
        }

        if (! $this->kelasWali->contains('id', $this->kelasId)) {
            $this->dispatch('notify', type: 'error', message: self::MSG_NO_CLASS_ACCESS);

            return null;
        }

        $kelas = Kelas::with('waliKelas')->find($this->kelasId);
        $mataPelajaran = MataPelajaran::with('guru')->find($this->mataPelajaranId);

        if (! $kelas) {
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

        return [
            'kelas' => $kelas,
            'mataPelajaran' => $mataPelajaran,
            'tahunAjaran' => $tahunAjaran,
        ];
    }
}
