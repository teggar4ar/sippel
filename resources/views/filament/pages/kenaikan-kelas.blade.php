<x-filament-panels::page>
    @if(!$activeTahunAjaran)
        <x-filament::section>
            <div class="flex items-center gap-4 text-warning-600 dark:text-warning-400">
                <x-heroicon-o-exclamation-triangle class="h-8 w-8" />
                <div>
                    <p class="font-semibold">Tidak Ada Tahun Ajaran Aktif</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Silakan buat tahun ajaran terlebih dahulu.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @elseif($activeTahunAjaran->isGanjil())
        <x-filament::section>
            <div class="flex items-center gap-4 text-info-600 dark:text-info-400">
                <x-heroicon-o-information-circle class="h-8 w-8" />
                <div>
                    <p class="font-semibold">Belum Waktunya Kenaikan Kelas</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Kenaikan kelas hanya dapat dilakukan pada akhir semester Genap.
                        Semester saat ini: <strong>{{ $activeTahunAjaran->nama_tahun }} - Ganjil</strong>
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
