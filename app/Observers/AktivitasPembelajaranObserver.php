<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AktivitasPembelajaran;

final class AktivitasPembelajaranObserver
{
    /**
     * Handle the AktivitasPembelajaran "deleting" event.
     * When an activity is soft deleted, close all related QR attendance sessions
     */
    public function deleting(AktivitasPembelajaran $aktivitas): void
    {
        // Close all open QR sessions for this activity
        $aktivitas->sesiPresensi()
            ->where('status', 'open')
            ->update([
                'status' => 'closed',
                'ditutup_pada' => now(),
            ]);
    }

    /**
     * Handle the AktivitasPembelajaran "force deleted" event.
     * When an activity is force deleted, delete all related sessions and details
     */
    public function forceDeleted(): void
    {
        // This will trigger cascade delete on sesi_presensi
        // DetailAktivitas will also be cascade deleted
    }
}
