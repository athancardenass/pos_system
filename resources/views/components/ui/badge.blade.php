@props(['variant' => 'neutral'])

@php
    $variantClasses = [
        'neutral' => 'badge-neutral',
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'pending' => 'badge-pending',
        'balanced' => 'badge-balanced',
        'shortage' => 'badge-shortage',
        'overage' => 'badge-overage',
        'slate' => 'badge-slate',
        'points' => 'badge-points',
        'success' => 'badge-success',
        'warning' => 'badge-warning',
    ];
@endphp

<span {{ $attributes->class(['badge', $variantClasses[$variant] ?? $variantClasses['neutral']]) }}>{{ $slot }}</span>
