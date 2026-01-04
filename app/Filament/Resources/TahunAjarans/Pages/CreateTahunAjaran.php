<?php

declare(strict_types=1);

namespace App\Filament\Resources\TahunAjarans\Pages;

use App\Filament\Resources\TahunAjarans\TahunAjaranResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTahunAjaran extends CreateRecord
{
    protected static string $resource = TahunAjaranResource::class;
}
