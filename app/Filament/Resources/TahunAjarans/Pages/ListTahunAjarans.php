<?php

declare(strict_types=1);

namespace App\Filament\Resources\TahunAjarans\Pages;

use App\Filament\Pages\GantiSemesterPage;
use App\Filament\Pages\KenaikanKelasPage;
use App\Filament\Resources\TahunAjarans\TahunAjaranResource;
use App\Models\TahunAjaran;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListTahunAjarans extends ListRecords
{
    protected static string $resource = TahunAjaranResource::class;

    protected function getHeaderActions(): array
    {
        $hasTahunAjaran = TahunAjaran::exists();
        $activeTahunAjaran = TahunAjaran::getActive();

        // If no tahun ajaran exists, show only Create button
        if (! $hasTahunAjaran) {
            return [
                CreateAction::make()
                    ->label('Buat Tahun Ajaran Pertama')
                    ->icon(Heroicon::OutlinedPlusCircle),
            ];
        }

        // Otherwise show semester/kenaikan buttons based on active semester
        return [
            Action::make('gantiSemester')
                ->label('Ganti Semester')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('primary')
                ->url(GantiSemesterPage::getUrl())
                ->visible(fn (): bool => $activeTahunAjaran !== null && $activeTahunAjaran->isGanjil()),

            Action::make('kenaikanKelas')
                ->label('Kenaikan Kelas')
                ->icon(Heroicon::OutlinedAcademicCap)
                ->color('success')
                ->url(KenaikanKelasPage::getUrl())
                ->visible(fn (): bool => $activeTahunAjaran !== null && $activeTahunAjaran->isGenap()),
        ];
    }
}
