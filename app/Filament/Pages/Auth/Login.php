<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\Siswa;
use App\Models\User;
use Filament\Auth\Pages\Login as BasePage;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

final class Login extends BasePage
{
    /**
     * Custom heading for login page.
     */
    public function getHeading(): string
    {
        return 'Login ke SIPPEL';
    }

    public function mount(): void
    {
        // If already authenticated, redirect based on role
        if (Filament::auth()->check()) {
            redirect($this->getRoleBasedRedirectUrl());

            return;
        }

        $this->form->fill();
    }

    /**
     * Override to show Indonesian error message for failed login.
     * Uses 'identifier' field name since we accept both email and NIS.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.identifier' => __('Email/NIS atau kata sandi salah.'),
        ]);
    }

    /**
     * Override the email form component to accept both email and NIS.
     */
    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('identifier')
            ->label('Email atau NIS')
            ->placeholder('contoh@email.com atau 12345')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Convert the form data to credentials.
     * If identifier is numeric (NIS), look up the student's email.
     * Otherwise, treat it as email directly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $identifier = $data['identifier'] ?? '';
        $email = $identifier;

        // Check if identifier looks like NIS (numeric only)
        if ($this->looksLikeNis($identifier)) {
            // Try to find active (non-soft-deleted) student by NIS
            $siswa = Siswa::where('nis', $identifier)
                ->whereNull('deleted_at')
                ->first();

            if ($siswa && $siswa->user) {
                $email = $siswa->user->email;
            }
            // If not found, $email stays as the original identifier
            // which will fail authentication naturally
        }

        return [
            'email' => $email,
            'password' => $data['password'],
        ];
    }

    /**
     * Determine if the given identifier looks like a NIS (student ID number).
     * NIS is typically numeric only, up to 20 characters.
     */
    private function looksLikeNis(string $identifier): bool
    {
        // NIS format: numeric only, reasonable length (not empty, not too long)
        return preg_match('/^\d{1,20}$/', $identifier) === 1;
    }

    /**
     * Get the appropriate redirect URL based on user role.
     */
    private function getRoleBasedRedirectUrl(): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return Filament::getUrl();
        }

        // Admin goes to FilamentPHP dashboard
        if ($user->hasRole('admin')) {
            return Filament::getUrl();
        }

        // Teacher goes to Flux UI teacher interface
        if ($user->hasRole('teacher')) {
            return '/teacher';
        }

        // Student goes to Flux UI student interface
        if ($user->hasRole('student')) {
            return '/student';
        }

        // Fallback to FilamentPHP dashboard
        return Filament::getUrl();
    }
}
