<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.teacher')]
#[Title('Profil - SIPPEL Guru')]
final class TeacherProfile extends Component
{
    public string $nama = '';

    public string $email = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->nama = $user->name;
        $this->email = $user->email;
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

    public function updateProfile(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        // Verify current password if changing password
        if ($this->new_password !== '' && $this->new_password !== '0') {
            if (! Hash::check($this->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password lama tidak sesuai.',
                ]);
            }
        }

        // Update email
        $user->email = $this->email;

        // Update password if provided
        if ($this->new_password !== '' && $this->new_password !== '0') {
            $user->password = Hash::make($this->new_password);
        }

        $user->save();

        // Clear password fields
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('profile-saved');
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.teacher.teacher-profile');
    }
}
