<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Services\LaporanCalculatorService;

final class DetailAktivitasObserver
{
    public function created(DetailAktivitas $detailAktivitas): void
    {
        $this->calculator()->recalculateForDetail($detailAktivitas);
    }

    public function updated(DetailAktivitas $detailAktivitas): void
    {
        $this->calculator()->recalculateForDetail($detailAktivitas);

        if (! $detailAktivitas->wasChanged(['aktivitas_pembelajaran_id', 'siswa_id'])) {
            return;
        }

        $originalAktivitasId = $detailAktivitas->getOriginal('aktivitas_pembelajaran_id');
        $originalSiswaId = $detailAktivitas->getOriginal('siswa_id');

        if (! $originalAktivitasId || ! $originalSiswaId) {
            return;
        }

        $originalAktivitas = AktivitasPembelajaran::withTrashed()
            ->with('kelas')
            ->find($originalAktivitasId);

        $tahunAjaranId = $originalAktivitas?->kelas?->tahun_ajaran_id;
        $mataPelajaranId = $originalAktivitas?->mata_pelajaran_id;

        if (! $tahunAjaranId || ! $mataPelajaranId) {
            return;
        }

        $this->calculator()->recalculateForCombination(
            (int) $originalSiswaId,
            (int) $mataPelajaranId,
            (int) $tahunAjaranId,
            true
        );
    }

    public function deleted(DetailAktivitas $detailAktivitas): void
    {
        $this->calculator()->recalculateForDetail($detailAktivitas, true);
    }

    public function restored(DetailAktivitas $detailAktivitas): void
    {
        $this->calculator()->recalculateForDetail($detailAktivitas);
    }

    private function calculator(): LaporanCalculatorService
    {
        return app(LaporanCalculatorService::class);
    }
}
