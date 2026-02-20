<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\SiswaKelasHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the siswa_kelas_history table from existing data.
 *
 * Uses two passes:
 *   Pass 1 — Current enrollment: for every student (including soft-deleted /
 *             graduated ones still in DB), record their kelas_id + that
 *             class's tahun_ajaran_id.
 *   Pass 2 — Historical from activities: walk detail_aktivitas →
 *             aktivitas_pembelajaran → kelas to discover which class a
 *             student attended in each academic year, even after kelas_id
 *             was overwritten by a semester/promotion transition.
 *
 * Both passes are idempotent (firstOrCreate). Safe to re-run.
 */
final class BackfillSiswaKelasHistory extends Command
{
    protected $signature = 'history:backfill-siswa-kelas
                            {--dry-run : Show what would be inserted without writing to DB}';

    protected $description = 'Backfill siswa_kelas_history from current enrollment and activity records';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $created = 0;
        $skipped = 0;

        // ----------------------------------------------------------------
        // Pass 1: Current enrollment (including soft-deleted / lulus siswa)
        // ----------------------------------------------------------------
        $this->info('Pass 1: current enrollment...');

        $siswaList = Siswa::withTrashed()
            ->whereNotNull('kelas_id')
            ->with('kelas')
            ->get();

        $bar = $this->output->createProgressBar($siswaList->count());
        $bar->start();

        foreach ($siswaList as $siswa) {
            $bar->advance();

            if (! $siswa->kelas) {
                $skipped++;

                continue;
            }

            $tahunAjaranId = $siswa->kelas->tahun_ajaran_id;

            if ($dryRun) {
                $this->line("  [DRY] siswa_id={$siswa->id} tahun_ajaran_id={$tahunAjaranId} kelas_id={$siswa->kelas_id}");
                $created++;

                continue;
            }

            $record = SiswaKelasHistory::firstOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                ],
                ['kelas_id' => $siswa->kelas_id]
            );

            $record->wasRecentlyCreated ? $created++ : $skipped++;
        }

        $bar->finish();
        $this->newLine();

        // ----------------------------------------------------------------
        // Pass 2: Historical enrollment inferred from activity records
        // ----------------------------------------------------------------
        $this->info('Pass 2: historical enrollment from activity records...');

        /**
         * Distinct (siswa_id, kelas_id, tahun_ajaran_id) triples that exist
         * inside detail_aktivitas → aktivitas_pembelajaran → kelas.
         *
         * @var \Illuminate\Support\Collection<int, object{siswa_id: int, kelas_id: int, tahun_ajaran_id: int}>
         */
        $historicalRows = DB::table('detail_aktivitas as da')
            ->join('aktivitas_pembelajaran as ap', 'da.aktivitas_pembelajaran_id', '=', 'ap.id')
            ->join('kelas as k', 'ap.kelas_id', '=', 'k.id')
            ->whereNull('da.deleted_at')
            ->whereNull('ap.deleted_at')
            ->whereNull('k.deleted_at')
            ->select('da.siswa_id', 'ap.kelas_id', 'k.tahun_ajaran_id')
            ->distinct()
            ->get();

        $bar2 = $this->output->createProgressBar($historicalRows->count());
        $bar2->start();

        foreach ($historicalRows as $row) {
            $bar2->advance();

            if ($dryRun) {
                $this->line("  [DRY] siswa_id={$row->siswa_id} tahun_ajaran_id={$row->tahun_ajaran_id} kelas_id={$row->kelas_id}");
                $created++;

                continue;
            }

            $record = SiswaKelasHistory::firstOrCreate(
                [
                    'siswa_id' => $row->siswa_id,
                    'tahun_ajaran_id' => $row->tahun_ajaran_id,
                ],
                ['kelas_id' => $row->kelas_id]
            );

            $record->wasRecentlyCreated ? $created++ : $skipped++;
        }

        $bar2->finish();
        $this->newLine(2);

        $this->table(
            ['Result', 'Count'],
            [
                ['Inserted', $created],
                ['Already existed (skipped)', $skipped],
            ]
        );

        $this->info('Done.');

        return self::SUCCESS;
    }
}
