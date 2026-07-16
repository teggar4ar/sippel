<div class="flex items-center gap-2 min-w-0" x-data="{}"
     @tahun-ajaran-changed.window="window.location.reload()">
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $variant === 'teal' ? 'bg-teal-50 dark:bg-teal-900/30' : 'bg-slate-100 dark:bg-slate-800' }}">
        <flux:icon name="calendar" class="w-4 h-4 {{ $variant === 'teal' ? 'text-teal-600 dark:text-teal-400' : 'text-slate-500 dark:text-slate-400' }}" />
    </div>
    <flux:select
        wire:model.live="selectedTahunAjaranId"
        class="min-w-0 text-sm rounded-lg focus:ring-0 cursor-pointer shadow-sm
            {{ $variant === 'teal'
                ? 'border-teal-200 bg-white text-teal-950 dark:border-teal-700 dark:bg-slate-800 dark:text-teal-100 focus:border-teal-500'
                : 'border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500'
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
