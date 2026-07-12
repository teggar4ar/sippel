@props(['variant' => 'teacher', 'title' => '', 'subtitle' => null])
@php
    $roleVariant = $variant;
    unset($variant);
@endphp
<div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-xl lg:text-2xl font-bold tracking-tight {{ $roleVariant === 'student' ? 'text-teal-900 dark:text-white' : 'text-slate-900 dark:text-white' }}">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm mt-0.5 {{ $roleVariant === 'student' ? 'text-teal-600 dark:text-teal-300' : 'text-slate-500 dark:text-slate-300' }}">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
