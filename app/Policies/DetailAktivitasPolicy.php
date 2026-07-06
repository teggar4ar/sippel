<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Policy for DetailAktivitas model.
 *
 * Access control rules:
 * - Admin: Full access (managed via Filament)
 * - Teacher: Can view/create/update/delete for their class activities
 * - Student: Can only view their own records (read-only)
 */
final class DetailAktivitasPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and teacher can view list
        if ($user->hasRole(['admin', 'teacher'])) {
            return true;
        }

        // Students can view their own records via Livewire components
        return $user->hasRole('student') && $user->siswa !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DetailAktivitas $detailAktivitas): bool
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Teacher can view activities from their class
        if ($user->hasRole('teacher')) {
            $allowedKelasIds = $this->getTeacherKelasIds($user);
            /** @var AktivitasPembelajaran|null $aktivitas */
            $aktivitas = $detailAktivitas->aktivitasPembelajaran;

            return $aktivitas !== null && in_array($aktivitas->kelas_id, $allowedKelasIds, true);
        }

        // Student can only view their own data
        if ($user->hasRole('student')) {
            /** @var Siswa|null $siswa */
            $siswa = $user->siswa;

            return $siswa !== null && $siswa->id === $detailAktivitas->siswa_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin and teacher can create
        return $user->hasRole(['admin', 'teacher']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DetailAktivitas $detailAktivitas): bool
    {
        return $this->canModify($user, $detailAktivitas);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DetailAktivitas $detailAktivitas): bool
    {
        return $this->canModify($user, $detailAktivitas);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        // Only admin can restore
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        // Only admin can force delete
        return $user->hasRole('admin');
    }

    /**
     * Shared logic for update and delete: admin can always modify,
     * teachers can modify activities from their own classes.
     */
    private function canModify(User $user, DetailAktivitas $detailAktivitas): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            $allowedKelasIds = $this->getTeacherKelasIds($user);
            /** @var AktivitasPembelajaran|null $aktivitas */
            $aktivitas = $detailAktivitas->aktivitasPembelajaran;

            return $aktivitas !== null && in_array($aktivitas->kelas_id, $allowedKelasIds, true);
        }

        return false;
    }

    /**
     * Get all kelas IDs that a teacher has access to.
     *
     * @return array<int, int>
     */
    private function getTeacherKelasIds(User $user): array
    {
        return Cache::store('array')->rememberForever(
            'detail_aktivitas_policy_teacher_kelas_ids_'.$user->id,
            fn (): array => array_values(array_unique(array_merge(
                $user->kelasAsWali()->pluck('id')->all(),
                $user->mataPelajaranAsGuru()->pluck('kelas_id')->all(),
            ))),
        );
    }
}
