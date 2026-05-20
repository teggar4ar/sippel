<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
use App\Models\TahunAjaran;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            'avg_nilai' => $details->whereNotNull('nilai')->avg('nilai'),
            'avg_partisipasi' => $details->whereNotNull('partisipasi')->avg('partisipasi'),
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

        try {
            $this->aktivitas->delete();

            Cache::forget('teacher_dashboard_stats_'.Auth::id().'_'.($contextTahunAjaran->id ?? 'none'));

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
