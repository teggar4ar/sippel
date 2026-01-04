<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extract role data
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Create user
        $user = self::getModel()::create($data);

        // Assign role
        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }
}
