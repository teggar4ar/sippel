<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Pages;

use App\Filament\Resources\MataPelajarans\MataPelajaranResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditMataPelajaran extends EditRecord
{
    protected static string $resource = MataPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
