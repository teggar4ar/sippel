<x-filament-panels::page>
    @if (!$activeTahunAjaran)
        <x-filament::section>
            <div class="flex items-center gap-4 text-warning-600 dark:text-warning-400">
                <x-heroicon-o-exclamation-triangle class="h-8 w-8" />
                <div>
                    <p class="font-semibold">Tidak Ada Tahun Ajaran Aktif</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Silakan buat tahun ajaran terlebih dahulu melalui menu Master Data > Tahun Ajaran.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @else
        <form wire:submit="create">
            {{ $this->form }}
        </form>
    @endif
</x-filament-panels::page>
