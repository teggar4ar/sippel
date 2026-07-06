<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\TahunAjaran;
use App\Services\LaporanCalculatorService;
use App\Services\StudentDashboardCacheService;
use App\Services\TeacherDashboardCacheService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.teacher')]
#[Title('Detail Aktivitas - SIPPEL Guru')]
final class ViewAktivitas extends Component
{
    public AktivitasPembelajaran $aktivitas;

    public bool $showDeleteModal = false;

    public function mount(int $id): void
    {
        $this->aktivitas = AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->with([
                'mataPelajaran',
                'kelas',
                'detailAktivitas' => fn ($q) => $q->orderBy('siswa_id'),
                'detailAktivitas.siswa.user',
            ])
            ->findOrFail($id);
    }

    #[Computed]
    public function stats(): array
    {
        $details = $this->aktivitas->detailAktivitas;
        $total = $details->count();

        $hadir = $details->filter(fn ($d): bool => $d->kehadiran === \App\Enums\KehadiranStatus::Hadir)->count();
        $izin = $details->filter(fn ($d): bool => $d->kehadiran === \App\Enums\KehadiranStatus::Izin)->count();
        $sakit = $details->filter(fn ($d): bool => $d->kehadiran === \App\Enums\KehadiranStatus::Sakit)->count();
        $alpa = $details->filter(fn ($d): bool => $d->kehadiran === \App\Enums\KehadiranStatus::Alpa)->count();

        return [
            'total' => $total,
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'percentage' => $total > 0 ? round(($hadir / $total) * 100) : 0,
            'avg_keaktifan' => $details->whereNotNull('keaktifan')->isNotEmpty()
                ? $details->whereNotNull('keaktifan')->avg(fn ($detail): int => $detail->keaktifan->weight())
                : null,
        ];
    }

    public function deleteAktivitas(): void
    {
        $contextTahunAjaran = TahunAjaran::getContext();

        if (
            ! $contextTahunAjaran instanceof TahunAjaran
            || ! $contextTahunAjaran->status
            || $this->aktivitas->kelas?->tahun_ajaran_id !== $contextTahunAjaran->id
        ) {
            session()->flash('error', 'Tidak dapat menghapus aktivitas pada tahun ajaran yang tidak aktif.');
            $this->closeDeleteModal();

            return;
        }

        if ($this->aktivitas->guru_id !== Auth::id()) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus aktivitas ini.');
            $this->closeDeleteModal();

            return;
        }

        try {
            $affectedSiswaIds = $this->aktivitas->detailAktivitas()->pluck('siswa_id')->all();
            $this->aktivitas->delete();

            $calculator = app(LaporanCalculatorService::class);
            foreach ($affectedSiswaIds as $siswaId) {
                $calculator->recalculateForCombination(
                    (int) $siswaId,
                    $this->aktivitas->mata_pelajaran_id,
                    $contextTahunAjaran->id,
                    true
                );
            }

            app(TeacherDashboardCacheService::class)->invalidate(
                (int) Auth::id(),
                $contextTahunAjaran->id
            );
            app(StudentDashboardCacheService::class)->invalidateMany(
                $affectedSiswaIds,
                $contextTahunAjaran->id
            );

            session()->flash('success', 'Aktivitas berhasil dihapus.');

            $this->redirect(route('teacher.aktivitas.list'), navigate: true);
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Gagal menghapus aktivitas. Silakan coba lagi.');
            $this->closeDeleteModal();
        }
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.view-aktivitas');
    }
}
