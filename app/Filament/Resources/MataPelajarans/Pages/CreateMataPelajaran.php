<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Pages;

use App\Filament\Resources\MataPelajarans\MataPelajaranResource;
use App\Models\MataPelajaran;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class CreateMataPelajaran extends CreateRecord
{
    protected static string $resource = MataPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $applyToOthers = (bool) ($data['apply_to_other_groups'] ?? false);
        $otherGroups = $data['other_groups'] ?? [];

        // Only the true model attributes go to create()
        $primaryData = Arr::only($data, ['nama_mapel', 'kelas_id', 'guru_id']);

        return DB::transaction(function () use ($primaryData, $applyToOthers, $otherGroups): MataPelajaran {
            /** @var MataPelajaran $primary */
            $primary = MataPelajaran::create($primaryData);

            if (! $applyToOthers || empty($otherGroups)) {
                return $primary;
            }

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($otherGroups as $group) {
                // Skip unchecked rows
                if (empty($group['enabled'])) {
                    continue;
                }

                $kelasId = isset($group['kelas_id']) ? (int) $group['kelas_id'] : null;
                $guruId = isset($group['guru_id']) ? (int) $group['guru_id'] : null;
                if (! $kelasId) {
                    continue;
                }
                if (! $guruId) {
                    continue;
                }

                // Skip if this subject already exists for this class
                $exists = MataPelajaran::where('nama_mapel', $primaryData['nama_mapel'])
                    ->where('kelas_id', $kelasId)
                    ->exists();

                if ($exists) {
                    $skippedCount++;

                    continue;
                }

                MataPelajaran::create([
                    'nama_mapel' => $primaryData['nama_mapel'],
                    'kelas_id' => $kelasId,
                    'guru_id' => $guruId,
                ]);

                $createdCount++;
            }

            if ($createdCount > 0) {
                Notification::make()
                    ->title("Mata pelajaran diterapkan ke {$createdCount} grup kelas lain".($skippedCount > 0 ? " ({$skippedCount} dilewati karena sudah ada)" : '').'.')
                    ->success()
                    ->send();
            } elseif ($skippedCount > 0) {
                Notification::make()
                    ->title("{$skippedCount} grup dilewati karena mata pelajaran sudah ada.")
                    ->warning()
                    ->send();
            }

            return $primary;
        });
    }
}
