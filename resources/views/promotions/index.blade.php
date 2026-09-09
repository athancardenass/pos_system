@extends('layouts.app')

@section('title', 'Promotions')

@section('content')
    <div class="page-head">
        <h1>Promotions</h1>
        <a class="btn" href="{{ route('promotions.create') }}">New promotion</a>
    </div>
    <div class="card">
        @if ($promotions->isEmpty())
            <p class="empty">No promotions yet.</p>
        @else
            <table>
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
                                    <span class="badge badge-inactive">Inactive</span>
                                @elseif ($promotion->isLive())
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-inactive">Expired</span>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="actions">
                                    <a class="btn-ghost" href="{{ route('promotions.edit', $promotion) }}">Edit</a>
                                    <form class="inline-form" method="POST" action="{{ route('promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $promotions->links('partials.pagination') }}
        @endif
    </div>
@endsection
