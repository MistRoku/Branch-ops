{{-- Badge Component - Flat design --}}
@props(['variant' => 'default', 'type' => null])

@php
    // Both `variant` and legacy `type` are accepted.
    $variant = $type ?? $variant;
    $variantClasses = [
        'default' => 'bg-brand-700 text-white',
        'secondary' => 'bg-brand-500 text-white',
        'success' => 'bg-success text-white',
        'warning' => 'bg-warning text-white',
        'danger' => 'bg-danger text-white',
        'info' => 'bg-info text-white',
    ];
@endphp

<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium uppercase tracking-wide {{ $variantClasses[$variant] ?? $variantClasses['default'] }}">
    {{ $slot }}
</span>
