<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Profil - SIPPEL Siswa')]
final class Profil extends Component
{
    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $siswa = $user->siswa;

        // Context-aware stats
        $contextTahunAjaran = \App\Models\TahunAjaran::getContext();
        $attendancePercentage = 0;
        $averageGrade = 0;
        $averageParticipation = 0;
        $totalAktivitas = 0;

        if ($siswa) {
            $attendancePercentage = $siswa->getAttendancePercentage(null, null, null, $contextTahunAjaran?->id);
            $averageGrade = $siswa->getAverageGrade(null, null, null, $contextTahunAjaran?->id) ?? 0;
            $averageParticipation = $siswa->getAverageParticipation(null, null, null, $contextTahunAjaran?->id) ?? 0;

            // Filter activity count by context year
            $totalAktivitas = $siswa->detailAktivitas()
                ->when($contextTahunAjaran, fn ($q) => $q->whereHas('aktivitasPembelajaran.kelas', fn ($k) => $k->where('tahun_ajaran_id', $contextTahunAjaran->id)))
                ->count();
        }

        return view('livewire.student.profil', [
            'user' => $user,
            'siswa' => $siswa,
            'attendancePercentage' => $attendancePercentage,
            'averageGrade' => $averageGrade,
            'averageParticipation' => $averageParticipation,
            'totalAktivitas' => $totalAktivitas,
        ]);
    }
}
