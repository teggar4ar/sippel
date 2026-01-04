<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action): void {
                    $record = $this->record;

                    // Prevent deletion if user has linked siswa record
                    if ($record->siswa()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Tidak dapat menghapus user')
                            ->body('User ini terhubung dengan data siswa. Hapus data siswa terlebih dahulu.')
                            ->persistent()
                            ->send();

                        $action->cancel();

                        return;
                    }

                    // Prevent deletion if user is a teacher with assigned classes or subjects
                    if ($record->hasRole('teacher')) {
                        if ($record->kelasAsWali()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus user')
                                ->body('User ini adalah wali kelas. Hapus atau pindahkan kelas terlebih dahulu.')
                                ->persistent()
                                ->send();

                            $action->cancel();

                            return;
                        }

                        if ($record->mataPelajaranAsGuru()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus user')
                                ->body('User ini mengajar mata pelajaran. Hapus atau pindahkan mata pelajaran terlebih dahulu.')
                                ->persistent()
                                ->send();

                            $action->cancel();

                            return;
                        }
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load user role for editing (get first role name)
        $data['role'] = $this->record->roles->first()?->name;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract role data
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Update user
        $record->update($data);

        // Sync role
        if ($role) {
            $record->syncRoles([$role]);
        }

        return $record;
    }
}
