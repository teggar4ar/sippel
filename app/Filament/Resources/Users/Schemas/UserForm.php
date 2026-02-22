<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->maxLength(255)
                    ->required()
                    ->placeholder('Contoh: Ahmad Fauzi'),

                TextInput::make('email')
                    ->label('Email')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->required()
                    ->placeholder('Contoh: ahmad@email.com'),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn ($livewire): bool => $livewire instanceof CreateUser)
                    ->revealable(filament()->arePasswordsRevealable())
                    ->minLength(8)
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.'),

                Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->required()
                    ->native(false)
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->placeholder('Pilih jenis kelamin'),

                Select::make('role')
                    ->label('Role')
                    ->native(false)
                    ->options(fn (?User $record): array => array_filter([
                        'admin' => 'Admin',
                        'teacher' => 'Guru',
                        // Only show 'student' when editing an existing student user.
                        // Student accounts must be created through the Siswa page.
                        'student' => $record?->roles->first()?->name === 'student' ? 'Siswa' : null,
                    ]))
                    ->required()
                    ->placeholder('Pilih role pengguna')
                    ->helperText('Role menentukan hak akses pengguna di sistem')
                    ->rules([
                        fn (?User $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                            // Prevent creating a user with the 'student' role directly.
                            // Students must be created via the Siswa page.
                            if (! $record instanceof User && $value === 'student') {
                                $fail('Akun siswa hanya dapat dibuat melalui halaman Data Siswa.');

                                return;
                            }

                            // Only validate the remaining rules on edit (when record exists)
                            if (! $record instanceof User) {
                                return;
                            }

                            // Check if user has siswa record and trying to change from student role
                            $currentRole = $record->roles->first()?->name;

                            // Check if user has linked siswa record
                            if ($currentRole === 'student' && $value !== 'student' && $record->siswa()->exists()) {
                                $fail('Tidak dapat mengubah role karena user ini terhubung dengan data siswa. Hapus data siswa terlebih dahulu.');
                            }
                        },
                    ]),
            ])
            ->columns(2);
    }
}
