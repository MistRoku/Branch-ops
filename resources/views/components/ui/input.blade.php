{{-- Input Component - Flat design, no shadows --}}
@props(['type' => 'text', 'name' => '', 'label' => '', 'error' => '', 'required' => false, 'disabled' => false])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium mb-2">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}" 
        id="{{ $name }}" 
        name="{{ $name }}"
        class="input {{ $error ? 'border-danger' : '' }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes }}
    >
    
    @if($error)
        <p class="mt-1 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
