@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
    'size' => 'default',
    'disabled' => false,
])

@php
    $variantClasses = [
        'primary' => 'btn',
        'secondary' => 'btn btn-secondary',
        'slate' => 'btn btn-slate',
        'blue' => 'btn btn-blue',
        'ghost' => 'btn-ghost',
        'danger' => 'btn btn-danger',
    ];
    $buttonClasses = trim(($variantClasses[$variant] ?? $variantClasses['primary']).($size === 'compact' ? ' btn-compact' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$buttonClasses]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class([$buttonClasses]) }}>{{ $slot }}</button>
@endif
