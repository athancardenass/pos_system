@props(['variant' => 'standard'])

@php
    $tableClass = $variant === 'plain' ? '' : 'table-clean-slate';
@endphp

<table {{ $attributes->class([$tableClass]) }}>{{ $slot }}</table>
