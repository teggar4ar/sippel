<?php

declare(strict_types=1);

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateKelas extends CreateRecord
{
    protected static string $resource = KelasResource::class;
}
