<?php

namespace App\Observers;

use App\Models\TahunAjaran;
use Illuminate\Validation\ValidationException;

class TahunAjaranObserver
{
    /**
     * Handle the TahunAjaran "creating" event.
     * Ensure only one tahun ajaran can be active.
     */
    public function creating(TahunAjaran $tahunAjaran): void
    {
        if ($tahunAjaran->status) {
            $activeExists = TahunAjaran::where('status', true)->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak dapat membuat tahun ajaran dengan status aktif karena ada tahun ajaran lain yang masih aktif. Harap nonaktifkan tahun ajaran yang aktif terlebih dahulu.',
                ]);
            }

            // Deactivate all others as backup
            TahunAjaran::query()->update(['status' => false]);
        }
    }

    /**
     * Handle the TahunAjaran "updating" event.
     * Ensure only one tahun ajaran can be active.
     */
    public function updating(TahunAjaran $tahunAjaran): void
    {
        if ($tahunAjaran->status && $tahunAjaran->isDirty('status')) {
            $activeExists = TahunAjaran::where('id', '!=', $tahunAjaran->id)
                ->where('status', true)
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak dapat mengaktifkan tahun ajaran ini karena ada tahun ajaran lain yang masih aktif. Harap nonaktifkan tahun ajaran yang aktif terlebih dahulu.',
                ]);
            }

            // Deactivate all others as backup
            TahunAjaran::where('id', '!=', $tahunAjaran->id)
                ->update(['status' => false]);
        }
    }

    /**
     * Handle the TahunAjaran "deleting" event.
     * Prevent deletion of active academic year.
     */
    public function deleting(TahunAjaran $tahunAjaran): void
    {
        if ($tahunAjaran->status) {
            throw ValidationException::withMessages([
                'status' => 'Tidak dapat menghapus tahun ajaran yang masih aktif. Harap nonaktifkan tahun ajaran terlebih dahulu.',
            ]);
        }
    }

    /**
     * Handle the TahunAjaran "forceDeleting" event.
     * Prevent force deletion of active academic year.
     */
    public function forceDeleting(TahunAjaran $tahunAjaran): void
    {
        if ($tahunAjaran->status) {
            throw ValidationException::withMessages([
                'status' => 'Tidak dapat menghapus permanen tahun ajaran yang masih aktif. Harap nonaktifkan tahun ajaran terlebih dahulu.',
            ]);
        }
    }
}
