<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\KehadiranStatus;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\Laporan;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Console\Command;

/**
 * Calculate and cache report statistics in the laporan table.
 *
 * This command calculates aggregated statistics (attendance, grades, participation)
 * for each student, for each subject, for each academic year. The results are cached
 * in the laporan table to improve report generation performance.
 */
final class CalculateReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:calculate
                            {--tahun-ajaran= : Calculate only for specific academic year ID}
                            {--siswa= : Calculate only for specific student ID}
                            {--force : Force recalculate all reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and cache report statistics in laporan table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting report calculation...');

        $tahunAjaranList = $this->resolveTahunAjaranList($this->option('tahun-ajaran'));

        if ($tahunAjaranList->isEmpty()) {
            $this->warn('No academic years found to process.');

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($tahunAjaranList as $tahunAjaran) {
            $this->info("Processing academic year: {$tahunAjaran->nama_tahun} - {$tahunAjaran->semester}");

            $stats = $this->processTahunAjaran($tahunAjaran, $this->option('siswa'), (bool) $this->option('force'));

            $totalCreated += $stats['created'];
            $totalUpdated += $stats['updated'];
            $totalSkipped += $stats['skipped'];
        }

        $this->newLine();
        $this->info('Report calculation completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $totalCreated],
                ['Updated', $totalUpdated],
                ['Skipped (no data)', $totalSkipped],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Resolve the list of academic years to process based on CLI option.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TahunAjaran>
     */
    private function resolveTahunAjaranList(?string $tahunAjaranId): \Illuminate\Database\Eloquent\Collection
    {
        $query = TahunAjaran::query();

        if ($tahunAjaranId) {
            $query->where('id', $tahunAjaranId);
        } else {
            // By default, only process active academic year
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * Process all students × subjects for one academic year.
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    private function processTahunAjaran(TahunAjaran $tahunAjaran, ?string $siswaId, bool $force): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        // Get all classes for this academic year
        $kelasIds = Kelas::where('tahun_ajaran_id', $tahunAjaran->id)->pluck('id');

        if ($kelasIds->isEmpty()) {
            $this->warn('  No classes found for this academic year.');

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        }

        // Get all subjects for these classes
        $mataPelajaranList = MataPelajaran::whereIn('kelas_id', $kelasIds)->get();

        if ($mataPelajaranList->isEmpty()) {
            $this->warn('  No subjects found for this academic year.');

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        }

        // Get all students enrolled in these classes for this year.
        // Prefer kelasHistory, but fall back to current kelas_id when history has not been backfilled.
        // withTrashed() includes graduated (soft-deleted) students.
        $siswaQuery = Siswa::withTrashed()
            ->where(function ($query) use ($tahunAjaran, $kelasIds): void {
                $query
                    ->whereHas(
                        'kelasHistory',
                        fn ($q) => $q
                            ->where('tahun_ajaran_id', $tahunAjaran->id)
                            ->whereIn('kelas_id', $kelasIds)
                    )
                    ->orWhereIn('kelas_id', $kelasIds);
            });

        if ($siswaId) {
            $siswaQuery->where('id', $siswaId);
        }

        $siswaList = $siswaQuery->with(['kelasHistory.kelas'])->get();

        if ($siswaList->isEmpty()) {
            $this->warn('  No students found for this academic year.');

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        }

        $bar = $this->output->createProgressBar($siswaList->count() * $mataPelajaranList->count());
        $bar->start();

        foreach ($siswaList as $siswa) {
            // Resolve once per student per year — uses eager-loaded kelasHistory
            $studentKelasId = $siswa->getKelasForTahunAjaran($tahunAjaran->id)?->id;

            foreach ($mataPelajaranList as $mataPelajaran) {
                // Only process if the subject belongs to the student's class in THIS year
                if ($mataPelajaran->kelas_id !== $studentKelasId) {
                    $bar->advance();

                    continue;
                }

                $result = $this->calculateAndSaveReport($siswa, $mataPelajaran, $tahunAjaran, $force);

                match ($result) {
                    'created' => $created++,
                    'updated' => $updated++,
                    default => $skipped++,
                };

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Calculate statistics and save/update laporan record.
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    private function calculateAndSaveReport(
        Siswa $siswa,
        MataPelajaran $mataPelajaran,
        TahunAjaran $tahunAjaran,
        bool $force
    ): string {
        // Get all detail_aktivitas records for this student + subject combination
        $detailAktivitas = DetailAktivitas::query()
            ->where('siswa_id', $siswa->id)
            ->whereHas('aktivitasPembelajaran', function ($query) use ($mataPelajaran): void {
                $query->where('mata_pelajaran_id', $mataPelajaran->id)
                    ->whereNull('deleted_at');
            })
            ->get();

        // Skip if no activities found
        if ($detailAktivitas->isEmpty()) {
            // Delete existing laporan if force mode
            if ($force) {
                Laporan::where('siswa_id', $siswa->id)
                    ->where('mata_pelajaran_id', $mataPelajaran->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->delete();
            }

            return 'skipped';
        }

        // Calculate statistics
        $stats = $this->calculateStatistics($detailAktivitas);

        // Find or create laporan record
        $laporan = Laporan::withTrashed()
            ->where('siswa_id', $siswa->id)
            ->where('mata_pelajaran_id', $mataPelajaran->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->first();

        $isNew = $laporan === null;

        if ($isNew) {
            $laporan = new Laporan();
            $laporan->siswa_id = $siswa->id;
            $laporan->mata_pelajaran_id = $mataPelajaran->id;
            $laporan->tahun_ajaran_id = $tahunAjaran->id;
        }

        // Restore if soft deleted
        if ($laporan->trashed()) {
            $laporan->restore();
        }

        // Update statistics
        $laporan->rata_kehadiran = $stats['rata_kehadiran'];
        $laporan->hadir_count = $stats['hadir_count'];
        $laporan->izin_count = $stats['izin_count'];
        $laporan->sakit_count = $stats['sakit_count'];
        $laporan->alpa_count = $stats['alpa_count'];
        $laporan->total_kehadiran = $stats['total_kehadiran'];
        $laporan->rata_nilai = $stats['rata_nilai'];
        $laporan->rata_partisipasi = $stats['rata_partisipasi'];
        $laporan->save();

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Calculate statistics from detail_aktivitas records.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, DetailAktivitas>  $detailAktivitas
     * @return array{rata_kehadiran: float, hadir_count: int, izin_count: int, sakit_count: int, alpa_count: int, total_kehadiran: int, rata_nilai: float|null, rata_partisipasi: int|null}
     */
    private function calculateStatistics($detailAktivitas): array
    {
        $total = $detailAktivitas->count();

        // Calculate attendance counts by status
        $hadirCount = $detailAktivitas->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Hadir)->count();
        $izinCount = $detailAktivitas->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Izin)->count();
        $sakitCount = $detailAktivitas->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Sakit)->count();
        $alpaCount = $detailAktivitas->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Alpa)->count();

        // Calculate attendance percentage
        $rataKehadiran = $total > 0 ? round(($hadirCount / $total) * 100, 2) : 0;

        // Calculate average grade (excluding null values)
        $nilaiValues = $detailAktivitas->whereNotNull('nilai')->pluck('nilai');
        $rataNilai = $nilaiValues->isNotEmpty() ? round($nilaiValues->avg(), 2) : null;

        // Calculate average participation (excluding null values)
        $partisipasiValues = $detailAktivitas->whereNotNull('partisipasi')->pluck('partisipasi');
        $rataPartisipasi = $partisipasiValues->isNotEmpty()
            ? (int) round($partisipasiValues->avg())
            : null;

        return [
            'rata_kehadiran' => $rataKehadiran,
            'hadir_count' => $hadirCount,
            'izin_count' => $izinCount,
            'sakit_count' => $sakitCount,
            'alpa_count' => $alpaCount,
            'total_kehadiran' => $total,
            'rata_nilai' => $rataNilai,
            'rata_partisipasi' => $rataPartisipasi,
        ];
    }
}
