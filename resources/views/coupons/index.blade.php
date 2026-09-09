@extends('layouts.app')

@section('title', 'Coupons')

@section('content')
    <div class="page-head">
        <h1>Coupons</h1>
        <a class="btn" href="{{ route('coupons.create') }}">New coupon</a>
    </div>
    <div class="card">
        @if ($coupons->isEmpty())
            <p class="empty">No coupons yet.</p>
        @else
            <table>
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
                                    <span class="badge badge-inactive">Inactive</span>
                                @elseif ($coupon->isExhausted())
                                    <span class="badge badge-inactive">Used up</span>
                                @elseif ($coupon->isLive())
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-inactive">Expired</span>
                                @endif
                            </td>
                            <td class="actions">
                                <div class="actions">
                                    <a class="btn-ghost" href="{{ route('coupons.edit', $coupon) }}">Edit</a>
                                    <form class="inline-form" method="POST" action="{{ route('coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?')">
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
            {{ $coupons->links('partials.pagination') }}
        @endif
    </div>
@endsection
