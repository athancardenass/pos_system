@extends('layouts.app')

@section('title', 'Promotions')

@section('content')
    <div class="page-head">
        <h1>Promotions</h1>
        <x-ui.button :href="route('promotions.create')">New promotion</x-ui.button>
    </div>
    <div class="card">
        @if ($promotions->isEmpty())
            <p class="empty">No promotions yet.</p>
        @else
<x-ui.table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Rule</th>
                        <th>Scope</th>
                        <th>Window</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotions as $promotion)
                        @php
                            // Scope name comes from the preloaded lookup maps — no query per row.
                            $scopeName = match ($promotion->scope) {
                                'cart' => 'Cart-wide',
                                'product' => 'Product: '.($productNames[$promotion->scope_id] ?? '#'.$promotion->scope_id),
                                'category' => 'Category: '.($categoryNames[$promotion->scope_id] ?? '#'.$promotion->scope_id),
                                default => ucfirst((string) $promotion->scope),
                            };
                        @endphp
                        <tr>
                            <td>{{ $promotion->name }}</td>
                            <td>{{ $promotion->ruleLabel() }}</td>
                            <td>{{ $scopeName }}</td>
                            <td class="muted">
                                {{ optional($promotion->starts_at)->format('Y-m-d H:i') ?: '—' }}
                                to
                                {{ optional($promotion->ends_at)->format('Y-m-d H:i') ?: '—' }}
                            </td>
                            <td>
                                @if (! $promotion->is_active)
                                    <x-ui.badge variant="inactive">Inactive</x-ui.badge>
                                @elseif ($promotion->isLive())
                                    <x-ui.badge variant="active">Active</x-ui.badge>
                                @else
                                    <x-ui.badge variant="inactive">Expired</x-ui.badge>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="actions">
                                    <x-ui.button variant="slate" size="compact" :href="route('promotions.edit', $promotion)">Edit</x-ui.button>
                                    <form class="inline-form" method="POST" action="{{ route('promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant="danger" type="submit" size="compact">Delete</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
</x-ui.table>
            {{ $promotions->links('partials.pagination') }}
        @endif
    </div>
@endsection
