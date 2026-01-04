<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Pages;

use App\Filament\Resources\MataPelajarans\MataPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMataPelajarans extends ListRecords
{
    protected static string $resource = MataPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
