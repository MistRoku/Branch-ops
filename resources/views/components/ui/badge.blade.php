{{-- Badge Component - Flat design --}}
@props(['variant' => 'default', 'type' => null])

@php
    // Both `variant` and legacy `type` are accepted.
    $variant = $type ?? $variant;
    $variantClasses = [
        'default' => 'bg-brand-100 text-brand-700',
        'secondary' => 'bg-brand-100 text-brand-700',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'danger' => 'bg-danger/10 text-danger',
        'info' => 'bg-info/10 text-info',
    ];
@endphp

<span class="inline-flex items-center px-2 py-0.5 text-xs font-medium uppercase tracking-wide {{ $variantClasses[$variant] ?? $variantClasses['default'] }}">
    {{ $slot }}
</span>
