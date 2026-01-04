<?php

declare(strict_types=1);

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class EditSiswa extends EditRecord
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Mutate form data before filling the form
     * Load user data into the form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user'] = [
            'name' => $this->record->user->name,
            'email' => $this->record->user->email,
            'jenis_kelamin' => $this->record->user->jenis_kelamin,
            'password' => '', // Don't pre-fill password
        ];

        return $data;
    }

    /**
     * Handle the record update with database transaction
     * Update both User and Siswa records
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            // Update User account
            $user = $record->user;
            $user->name = $data['user']['name'];
            $user->email = $data['user']['email'];
            $user->jenis_kelamin = $data['user']['jenis_kelamin'];

            // Only update password if provided
            if (! empty($data['user']['password'])) {
                $user->password = Hash::make($data['user']['password']);
            }

            $user->save();

            // Update Siswa record
            $record->update([
                'nis' => $data['nis'],
                'kelas_id' => $data['kelas_id'],
            ]);

            return $record;
        });
    }

    /**
     * Customize success notification
     */
    protected function getSavedNotificationTitle(): string
    {
        return 'Data siswa berhasil diperbarui!';
    }
}
