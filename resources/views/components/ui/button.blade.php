{{-- Button Component - Flat design, no hover animations --}}
@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'disabled' => false, 'fullWidth' => false])

@php
    $baseClasses = 'btn inline-flex items-center justify-center font-medium disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variantClasses = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'hover:bg-brand-100 text-brand-700',
    ];
    
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-5 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
@endphp

<button 
    type="{{ $type }}" 
    class="{{ $baseClasses }} {{ $variantClasses[$variant] ?? $variantClasses['primary'] }} {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} {{ $fullWidth ? 'w-full' : '' }}"
    {{ $disabled ? 'disabled' : '' }}
>
    @if(isset($icon))
        <span class="mr-2">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
