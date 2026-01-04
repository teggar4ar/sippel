<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Siswa;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    /**
     * Handle the record creation with database transaction
     * Create User first, then Siswa record linked to that User
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Step 1: Create User account
            $user = User::create([
                'name' => $data['user']['name'],
                'email' => $data['user']['email'],
                'password' => Hash::make($data['user']['password']),
                'jenis_kelamin' => $data['user']['jenis_kelamin'],
            ]);

            // Step 2: Assign 'student' role to user
            $user->assignRole('student');

            // Step 3: Create Siswa record linked to the user
            $siswa = Siswa::create([
                'nis' => $data['nis'],
                'user_id' => $user->id,
                'kelas_id' => $data['kelas_id'],
            ]);

            return $siswa;
        });
    }

    /**
     * Customize success notification
     */
    protected function getCreatedNotificationTitle(): string
    {
        return 'Siswa berhasil didaftarkan!';
    }

    /**
     * Redirect after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
