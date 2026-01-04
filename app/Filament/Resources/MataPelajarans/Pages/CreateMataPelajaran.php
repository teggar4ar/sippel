<?php

declare(strict_types=1);

namespace App\Filament\Resources\MataPelajarans\Pages;

use App\Filament\Resources\MataPelajarans\MataPelajaranResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMataPelajaran extends CreateRecord
{
    protected static string $resource = MataPelajaranResource::class;
}
