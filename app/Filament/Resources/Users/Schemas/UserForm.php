<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

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
                    ->rule(Password::default())
                    ->autocomplete('new-password')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.'),

                Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->required()
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->placeholder('Pilih jenis kelamin'),

                Select::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'teacher' => 'Guru',
                        'student' => 'Siswa',
                    ])
                    ->required()
                    ->placeholder('Pilih role pengguna')
                    ->helperText('Role menentukan hak akses pengguna di sistem')
                    ->rules([
                        fn (?User $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                            // Only validate on edit (when record exists)
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
