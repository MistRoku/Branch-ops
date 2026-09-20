{{-- Badge Component - Flat design --}}
@props(['variant' => 'default'])

@php
    $variantClasses = [
        'default' => 'bg-brand-100 text-brand-700',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'danger' => 'bg-danger/10 text-danger',
        'info' => 'bg-info/10 text-info',
    ];
@endphp

<span class="badge {{ $variantClasses[$variant] ?? $variantClasses['default'] }}">
    {{ $slot }}
</span>
