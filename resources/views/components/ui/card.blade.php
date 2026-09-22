{{-- Card Component - Flat design, no shadows, no rounded corners --}}
@props(['padding' => 'default', 'bordered' => true])

@php
    $paddingClasses = [
        'none' => 'p-0',
        'sm' => 'p-3',
        'default' => 'p-6',
        'lg' => 'p-8',
    ];
@endphp

<div class="bg-brand-100 {{ $bordered ? 'border border-brand-200' : '' }} {{ $paddingClasses[$padding] }}">
    {{ $slot }}
</div>
