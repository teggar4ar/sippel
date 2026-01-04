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

        return view('livewire.student.profil', [
            'user' => $user,
            'siswa' => $siswa,
        ]);
    }
}
