@props([
    'heading' => null,
    'subheading' => null,
])

@php
    $heading ??= $this->getHeading();
    $subheading ??= $this->getSubHeading();
    $hasLogo = $this->hasLogo();
    $isLoginPage = $this instanceof \Filament\Pages\Auth\Login || $this instanceof \App\Filament\Pages\Auth\Login;
@endphp

<div {{ $attributes->class(['fi-simple-page']) }}>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_START, scopes: $this->getRenderHookScopes()) }}

    <div @class(['fi-simple-page-content', 'max-w-none' => $isLoginPage])>
        @unless ($isLoginPage)
            <x-filament-panels::header.simple
                :heading="$heading"
                :logo="$hasLogo"
                :subheading="$subheading"
            />
        @endunless

        {{ $slot }}
    </div>

    @if (! $this instanceof \Filament\Tables\Contracts\HasTable)
        <x-filament-actions::modals />
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_END, scopes: $this->getRenderHookScopes()) }}
</div>
