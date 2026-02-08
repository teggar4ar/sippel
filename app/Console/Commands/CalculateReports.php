<?php

declare(strict_types=1);

namespace App\Console\Commands;

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

        $tahunAjaranId = $this->option('tahun-ajaran');
        $siswaId = $this->option('siswa');
        $force = $this->option('force');

        // Get academic years to process
        $tahunAjaranQuery = TahunAjaran::query();
        if ($tahunAjaranId) {
            $tahunAjaranQuery->where('id', $tahunAjaranId);
        } else {
            // By default, only process active academic year
            $tahunAjaranQuery->where('status', true);
        }
        $tahunAjaranList = $tahunAjaranQuery->get();

        if ($tahunAjaranList->isEmpty()) {
            $this->warn('No academic years found to process.');

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($tahunAjaranList as $tahunAjaran) {
            $this->info("Processing academic year: {$tahunAjaran->nama_tahun} - {$tahunAjaran->semester}");

            // Get all classes for this academic year
            $kelasIds = Kelas::where('tahun_ajaran_id', $tahunAjaran->id)->pluck('id');

            if ($kelasIds->isEmpty()) {
                $this->warn('  No classes found for this academic year.');

                continue;
            }

            // Get all subjects for these classes
            $mataPelajaranList = MataPelajaran::whereIn('kelas_id', $kelasIds)->get();

            if ($mataPelajaranList->isEmpty()) {
                $this->warn('  No subjects found for this academic year.');

                continue;
            }

            // Get all students in these classes
            $siswaQuery = Siswa::whereIn('kelas_id', $kelasIds);
            if ($siswaId) {
                $siswaQuery->where('id', $siswaId);
            }
            $siswaList = $siswaQuery->get();

            if ($siswaList->isEmpty()) {
                $this->warn('  No students found for this academic year.');

                continue;
            }

            $bar = $this->output->createProgressBar($siswaList->count() * $mataPelajaranList->count());
            $bar->start();

            foreach ($siswaList as $siswa) {
                foreach ($mataPelajaranList as $mataPelajaran) {
                    // Only process if the subject is for the student's class
                    if ($mataPelajaran->kelas_id !== $siswa->kelas_id) {
                        $bar->advance();

                        continue;
                    }

                    $result = $this->calculateAndSaveReport($siswa, $mataPelajaran, $tahunAjaran, $force);

                    match ($result) {
                        'created' => $totalCreated++,
                        'updated' => $totalUpdated++,
                        default => $totalSkipped++,
                    };

                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine();
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
        $hadirCount = $detailAktivitas->filter(fn ($d): bool => mb_strtolower((string) $d->kehadiran) === 'hadir')->count();
        $izinCount = $detailAktivitas->filter(fn ($d): bool => mb_strtolower((string) $d->kehadiran) === 'izin')->count();
        $sakitCount = $detailAktivitas->filter(fn ($d): bool => mb_strtolower((string) $d->kehadiran) === 'sakit')->count();
        $alpaCount = $detailAktivitas->filter(fn ($d): bool => mb_strtolower((string) $d->kehadiran) === 'alpa')->count();

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
