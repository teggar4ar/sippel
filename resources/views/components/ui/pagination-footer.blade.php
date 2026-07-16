@props([
    'paginator',
    'variant' => 'teacher',
    'label' => 'item',
    'perPage' => null,
    'perPageOptions' => [10, 25, 50],
])

@php
    $roleVariant = $variant;
    unset($variant);

    $activeClass = $roleVariant === 'student' ? 'bg-teal-600 text-white' : 'bg-blue-600 text-white';
    $focusClass = $roleVariant === 'student' ? 'focus:border-teal-500 focus:ring-teal-500/20' : 'focus:border-blue-500 focus:ring-blue-500/20';
@endphp

<div {{ $attributes->class(['border-t border-slate-100 bg-slate-50/40 px-3 py-2.5 dark:border-slate-700/80 dark:bg-slate-900/40 lg:px-5 lg:py-3']) }}>
    <div class="grid grid-cols-[1fr_auto] items-center gap-x-2 gap-y-2 lg:grid-cols-[1fr_auto_1fr]">
        <p class="order-2 text-[11px] leading-tight text-slate-500 dark:text-slate-400 lg:order-1 lg:text-xs">
            <span class="hidden sm:inline">Menampilkan </span><strong class="text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</strong> dari <strong class="text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</strong> {{ $label }}
        </p>

        <div class="order-1 col-span-2 flex min-w-0 justify-center lg:order-2 lg:col-span-1">
            <div class="inline-flex max-w-full overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <button
                    type="button"
                    wire:click="previousPage"
                    @disabled($paginator->onFirstPage())
                    class="flex h-9 w-9 shrink-0 items-center justify-center border-r border-slate-200 text-slate-500 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 lg:h-10 lg:w-10"
                    aria-label="Halaman sebelumnya">
                    <flux:icon variant="outline" name="chevron-left" class="w-4 h-4" />
                </button>

                @for($page = 1; $page <= $paginator->lastPage(); $page++)
                    <button
                        type="button"
                        wire:click="gotoPage({{ $page }})"
                        class="h-9 min-w-9 shrink-0 border-r border-slate-200 px-2.5 text-sm font-medium transition-colors last:border-r-0 dark:border-slate-700 lg:h-10 lg:min-w-10 lg:px-3 {{ $paginator->currentPage() === $page ? $activeClass : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        {{ $page }}
                    </button>
                @endfor

                <button
                    type="button"
                    wire:click="nextPage"
                    @disabled(!$paginator->hasMorePages())
                    class="flex h-9 w-9 shrink-0 items-center justify-center text-slate-500 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:text-slate-400 dark:hover:bg-slate-800 lg:h-10 lg:w-10"
                    aria-label="Halaman berikutnya">
                    <flux:icon variant="outline" name="chevron-right" class="w-4 h-4" />
                </button>
            </div>
        </div>

        @if($perPage !== null)
            <div class="order-3 flex items-center justify-end gap-1.5 lg:gap-2">
                <label for="{{ $attributes->get('id', 'pagination-footer') }}-per-page" class="text-[11px] leading-tight text-slate-500 dark:text-slate-400 lg:text-xs">Per halaman</label>
                <div class="relative">
                    <select
                        id="{{ $attributes->get('id', 'pagination-footer') }}-per-page"
                        wire:model.live="{{ $perPage }}"
                        class="h-9 w-16 appearance-none rounded-lg border border-slate-200 bg-white px-2.5 pr-7 text-sm font-medium text-slate-700 shadow-sm transition-colors focus:outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 lg:h-10 lg:w-20 lg:px-3 lg:pr-8 {{ $focusClass }}"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <flux:icon variant="outline" name="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 lg:right-2.5" />
                </div>
            </div>
        @else
            <div class="order-3 hidden lg:block"></div>
        @endif
    </div>
</div>
