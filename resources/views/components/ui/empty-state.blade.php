@props(['icon' => 'inbox', 'title' => '', 'message' => null, 'variant' => 'teacher'])
<div class="flex flex-col items-center justify-center py-12 text-center px-4">
    <flux:icon name="{{ $icon }}" class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-3" />
    @if($title)<p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $title }}</p>@endif
    @if($message)<p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-xs">{{ $message }}</p>@endif
    @isset($cta)<div class="mt-3">{{ $cta }}</div>@endisset
</div>
