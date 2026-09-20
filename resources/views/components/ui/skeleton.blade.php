{{-- Skeleton Loader Component --}}
@props(['type' => 'text', 'width' => 'full', 'height' => 'default'])

@php
    $heightClasses = [
        'sm' => 'h-4',
        'default' => 'h-5',
        'lg' => 'h-6',
        'xl' => 'h-8',
        'card' => 'h-32',
    ];
    
    $widthClasses = [
        'full' => 'w-full',
        '3/4' => 'w-3/4',
        '2/3' => 'w-2/3',
        '1/2' => 'w-1/2',
        '1/3' => 'w-1/3',
        '1/4' => 'w-1/4',
    ];
@endphp

<div role="status" aria-label="Loading content" class="animate-pulse bg-brand-200 {{ $heightClasses[$height] ?? $heightClasses['default'] }} {{ $widthClasses[$width] ?? $widthClasses['full'] }}"></div>
