@props(['variant' => 'teacher', 'items' => [], 'fab' => null])
@php
    $roleVariant = $variant;
    unset($variant);

    $activeCls = $roleVariant === 'student' ? 'text-teal-600 dark:text-teal-400' : 'text-blue-600 dark:text-blue-400';
    $idleCls = 'text-slate-400 dark:text-slate-500';
    $fabBg = $roleVariant === 'student' ? 'bg-teal-600 hover:bg-teal-700' : 'bg-blue-600 hover:bg-blue-700';
    $gridCls = $fab
        ? 'grid-cols-5'
        : match (count($items)) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-2',
            4 => 'grid-cols-4',
            default => 'grid-cols-3',
        };
@endphp
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 safe-area-bottom"
    aria-label="Navigasi Bawah">
    <div class="grid {{ $gridCls }} h-16">
        @foreach ($items as $i => $item)
            {{-- Insert FAB in the visual center --}}
            @if ($fab && $i === intdiv(count($items), 2))
                <div class="flex items-center justify-center">
                    <a href="{{ $fab['href'] }}" wire:navigate
                        class="w-12 h-12 -mt-5 rounded-full {{ $fabBg }} text-white flex items-center justify-center shadow-lg"
                        aria-label="{{ $fab['label'] }}">
                        <flux:icon name="{{ $fab['icon'] }}" variant="outline" class="w-6 h-6" />
                    </a>
                </div>
            @endif
            <a href="{{ $item['href'] }}" wire:navigate
                class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium {{ $item['active'] ? $activeCls : $idleCls }}">
                <flux:icon name="{{ $item['icon'] }}" variant="outline" class="w-5 h-5" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
