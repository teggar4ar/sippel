<?php

declare(strict_types=1);

namespace App\Livewire\Teacher\AktivitasPembelajaran;

use App\Models\AktivitasPembelajaran;
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

    public function mount(int $id): void
    {
        $this->aktivitas = AktivitasPembelajaran::query()
            ->where('guru_id', Auth::id())
            ->with([
                'mataPelajaran',
                'kelas',
                'detailAktivitas' => fn($q) => $q->orderBy('siswa_id'),
                'detailAktivitas.siswa.user',
                'sesiAbsensi',
            ])
            ->findOrFail($id);
    }

    #[Computed]
    public function stats(): array
    {
        $details = $this->aktivitas->detailAktivitas;
        $total = $details->count();

        // Use case-insensitive comparison for kehadiran
        $hadir = $details->filter(fn($d): bool => mb_strtolower((string) $d->kehadiran) === 'hadir')->count();
        $izin = $details->filter(fn($d): bool => mb_strtolower((string) $d->kehadiran) === 'izin')->count();
        $sakit = $details->filter(fn($d): bool => mb_strtolower((string) $d->kehadiran) === 'sakit')->count();
        $alpa = $details->filter(fn($d): bool => mb_strtolower((string) $d->kehadiran) === 'alpa')->count();

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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.aktivitas-pembelajaran.view-aktivitas');
    }
}
