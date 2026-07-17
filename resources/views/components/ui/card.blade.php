@props([
    'variant' => 'teacher',
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'flush' => false,
])
@php
    $roleVariant = $variant;
    unset($variant);

    $border =
        $roleVariant === 'student' ? 'border-teal-100 dark:border-slate-800' : 'border-slate-200 dark:border-slate-800';
@endphp
<div {{ $attributes->except('flush')->class(['card-surface overflow-hidden', $border]) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b {{ $border }}">
            <div class="flex items-center gap-2 min-w-0">
                @if ($icon)
                    <flux:icon name="{{ $icon }}" variant="outline"
                        class="w-4 h-4 shrink-0 {{ $roleVariant === 'student' ? 'text-teal-500' : 'text-slate-400' }}" />
                @endif
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'p-4' }}">
        {{ $slot }}
    </div>
</div>
