<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Siswa;

final class SiswaObserver
{
    /**
     * Handle the Siswa "force deleted" event.
     * When a student is force deleted, also delete their user account
     */
    public function forceDeleted(Siswa $siswa): void
    {
        // Delete the associated user account
        if ($siswa->user) {
            $siswa->user->delete();
        }
    }
}
