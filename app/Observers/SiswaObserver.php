<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Siswa;

final class SiswaObserver
{
    /**
     * Handle the Siswa "force deleted" event.
     * When a student is force deleted, also force delete their user account
     * so no orphaned soft-deleted User rows remain.
     */
    public function forceDeleted(Siswa $siswa): void
    {
        if ($siswa->user) {
            $siswa->user->forceDelete();
        }
    }
}
