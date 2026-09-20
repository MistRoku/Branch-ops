{{-- Modal Component - Flat design --}}
@props(['show' => false, 'maxWidth' => 'md', 'closable' => true, 'ariaLabel' => 'Dialog'])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div
    x-show="{{ $show }}"
    class="fixed inset-0 z-50 flex items-center justify-center bg-brand-900/50"
    style="display: none;"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-label="{{ $ariaLabel }}"
>
    <div class="w-full bg-white border border-brand-200 max-h-[90vh] overflow-y-auto {{ $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['md'] }}">
        @if(isset($header))
            <div class="px-6 py-4 border-b border-brand-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ $header }}</h3>
                    @if($closable)
                        <button @click="{{ $show }} = false" aria-label="Close dialog" class="p-1 hover:bg-brand-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="px-6 py-4">
            {{ $slot }}
        </div>
        
        @if(isset($footer))
            <div class="px-6 py-4 border-t border-brand-200 bg-brand-50">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            @if($closable)
                {{ $show }} = false;
            @endif
        }
    });
</script>
@endpush
