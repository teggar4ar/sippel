@props(['variant' => 'teacher', 'title' => '', 'subtitle' => null])
<div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-xl lg:text-2xl font-bold tracking-tight {{ $variant === 'student' ? 'text-teal-900 dark:text-white' : 'text-slate-900 dark:text-white' }}">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm mt-0.5 {{ $variant === 'student' ? 'text-teal-600 dark:text-teal-300' : 'text-slate-500 dark:text-slate-300' }}">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
