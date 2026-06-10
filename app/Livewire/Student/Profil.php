<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Profil - SIPPEL Siswa')]
final class Profil extends Component
{
    public string $nama = '';

    public string $nis = '';

    public string $kelas = '';

    public string $email = '';

    #[Locked]
    public string $current_password = '';

    #[Locked]
    public string $new_password = '';

    #[Locked]
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }

        $this->nama = $user->name;
        $this->email = $user->email;

        /** @var Siswa|null $siswa */
        $siswa = $user->siswa;
        $this->nis = $siswa?->nis ?? '-';

        $contextTahunAjaran = TahunAjaran::getContext();
        $contextKelas = $contextTahunAjaran && $siswa
            ? $siswa->getKelasForTahunAjaran($contextTahunAjaran->id)
            : null;

        if ($contextKelas && $contextTahunAjaran) {
            $this->kelas = sprintf(
                '%s-%s (%s - %s)',
                $contextKelas->tingkat_kelas,
                $contextKelas->grup_kelas,
                $contextTahunAjaran->nama_tahun,
                $contextTahunAjaran->semester
            );
        } else {
            $this->kelas = '-';
        }
    }

    public function updateProfile(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        if ($this->new_password !== '' && $this->new_password !== '0' && ! Hash::check($this->current_password, $user->password)) {
            session()->flash('error', 'Password lama tidak sesuai.');
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $user->email = $this->email;

        if ($this->new_password !== '' && $this->new_password !== '0') {
            $user->password = Hash::make($this->new_password);
        }

        $user->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('student-profile-saved');
    }

    public function render(): View
    {
        return view('livewire.student.profil');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh pengguna lain.',
            'current_password.required_with' => 'Password lama wajib diisi jika ingin mengubah password.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
