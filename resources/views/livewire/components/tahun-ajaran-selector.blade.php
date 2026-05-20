<div class="flex items-center gap-2" x-data="{}"
     @tahun-ajaran-changed.window="window.location.reload()">
    <flux:icon name="calendar" class="w-4 h-4 {{ $variant === 'teal' ? 'text-teal-500 dark:text-teal-400' : 'text-slate-500 dark:text-slate-400' }}" />
    <flux:select
        wire:model.live="selectedTahunAjaranId"
        class="text-sm rounded-lg focus:ring-0 cursor-pointer
            {{ $variant === 'teal'
                ? 'border-teal-300 dark:border-teal-600 dark:bg-slate-800 focus:border-teal-500'
                : 'border-slate-300 dark:border-slate-600 dark:bg-slate-800 focus:border-blue-500'
            }}"
    >
        @foreach($this->tahunAjaranList as $ta)
            <option value="{{ $ta->id }}">
                {{ $ta->nama_tahun }} - {{ $ta->semester }}
                @if($ta->status) ⭐ @endif
            </option>
        @endforeach
    </flux:select>
</div>
