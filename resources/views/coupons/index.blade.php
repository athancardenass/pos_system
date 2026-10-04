@extends('layouts.app')

@section('title', 'Coupons')

@section('content')
    <div class="page-head">
        <h1>Coupons</h1>
        <x-ui.button :href="route('coupons.create')">New coupon</x-ui.button>
    </div>
    <div class="card">
        @if ($coupons->isEmpty())
            <p class="empty">No coupons yet.</p>
        @else
<x-ui.table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Rule</th>
                        <th>Min purchase</th>
                        <th>Uses</th>
                        <th>Per customer</th>
                        <th>Window</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coupons as $coupon)
                        <tr>
                            <td><code>{{ $coupon->code }}</code></td>
                            <td>{{ $coupon->ruleLabel() }}</td>
                            <td>{{ (float) $coupon->min_purchase > 0 ? '₱'.number_format((float) $coupon->min_purchase, 2) : '—' }}</td>
                            <td>{{ $coupon->usageLabel() }}</td>
                            <td>{{ $coupon->per_customer_limit !== null ? $coupon->per_customer_limit : '—' }}</td>
                            <td class="muted">
                                {{ optional($coupon->starts_at)->format('Y-m-d H:i') ?: '—' }}
                                to
                                {{ optional($coupon->ends_at)->format('Y-m-d H:i') ?: '—' }}
                            </td>
                            <td>
                                @if (! $coupon->is_active)
                                    <x-ui.badge variant="inactive">Inactive</x-ui.badge>
                                @elseif ($coupon->isExhausted())
                                    <x-ui.badge variant="inactive">Used up</x-ui.badge>
                                @elseif ($coupon->isLive())
                                    <x-ui.badge variant="active">Active</x-ui.badge>
                                @else
                                    <x-ui.badge variant="inactive">Expired</x-ui.badge>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="actions">
                                    <x-ui.button variant="slate" size="compact" :href="route('coupons.edit', $coupon)">Edit</x-ui.button>
                                    <form class="inline-form" method="POST" action="{{ route('coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?')">
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
            {{ $coupons->links('partials.pagination') }}
        @endif
    </div>
@endsection
