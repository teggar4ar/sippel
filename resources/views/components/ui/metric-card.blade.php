@props([
    'label' => '',
    'shortLabel' => null,
    'value' => '0',
    'unit' => null,
    'icon' => null,
    'accent' => 'blue',
])
@php
    $accents = [
        'blue' => ['ring' => 'text-blue-500/80 dark:text-blue-400/70', 'bg' => 'bg-blue-50 dark:bg-blue-900/30', 'ic' => 'text-blue-500'],
        'emerald' => ['ring' => 'text-emerald-500/80 dark:text-emerald-400/70', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'ic' => 'text-emerald-500'],
        'violet' => ['ring' => 'text-violet-500/80 dark:text-violet-400/70', 'bg' => 'bg-violet-50 dark:bg-violet-900/30', 'ic' => 'text-violet-500'],
        'amber' => ['ring' => 'text-amber-500/80 dark:text-amber-400/70', 'bg' => 'bg-amber-50 dark:bg-amber-900/30', 'ic' => 'text-amber-500'],
        'teal' => ['ring' => 'text-teal-600/80 dark:text-teal-400/70', 'bg' => 'bg-teal-50 dark:bg-teal-900/30', 'ic' => 'text-teal-500'],
        'rose' => ['ring' => 'text-rose-500/80 dark:text-rose-400/70', 'bg' => 'bg-rose-50 dark:bg-rose-900/30', 'ic' => 'text-rose-500'],
    ];
    $a = $accents[$accent] ?? $accents['blue'];
@endphp
<div {{ $attributes->class(['card-surface border-slate-200 dark:border-slate-800 px-2.5 py-2 sm:p-4 relative overflow-hidden']) }}>
    @if($icon)
        <div class="hidden sm:flex absolute top-3 right-3 w-9 h-9 {{ $a['bg'] }} rounded-lg items-center justify-center">
            <flux:icon name="{{ $icon }}" class="w-4 h-4 {{ $a['ic'] }}" />
        </div>
    @endif
    <p class="text-[9px] sm:text-xs font-semibold uppercase tracking-wide {{ $a['ring'] }} leading-tight">
        <span class="sm:hidden">{{ $shortLabel ?? $label }}</span>
        <span class="hidden sm:inline">{{ $label }}</span>
    </p>
    <p class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-white mt-0.5 sm:mt-1 tabular-nums">
        {{ $value }}@if($unit)<span class="text-xs sm:text-sm font-normal text-slate-400 dark:text-slate-500"> {{ $unit }}</span>@endif
    </p>
</div>
