@props(['expiresAt', 'remainingSeconds', 'class' => ''])

{{--
    Uses the globally registered qrCountdown Alpine component from bootstrap.js
    This ensures proper cleanup of setInterval when navigating with wire:navigate
--}}
<div x-data="qrCountdown({{ $expiresAt }}, {{ $remainingSeconds }})" {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</div>
