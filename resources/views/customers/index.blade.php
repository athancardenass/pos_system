@extends('layouts.app')

@section('title', 'Customers & Loyalty')

@section('content')
    <div class="page-head">
        <div>
            <h1>Customers & Loyalty</h1>
            <p class="muted">Manage customer directory and accumulated loyalty rewards points.</p>
        </div>
        <a class="btn" href="{{ route('customers.create') }}">+ New Customer</a>
    </div>

    <div class="card">
        @if ($customers->isEmpty())
            <p class="empty">No customer records found. Add one above.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                            <th>Contact / Email</th>
                            <th style="text-align: right;">Loyalty Points</th>
                            <th>Membership Status</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <code>#{{ $customer->customer_id }}</code>
                                </td>
                                <td>
                                    <strong>{{ $customer->fullName() }}</strong>
                                </td>
                                <td class="muted" style="font-size: 0.88rem;">
                                    {{ $customer->contact_number ?: $customer->email ?: '—' }}
                                </td>
                                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                    <span class="badge" style="background: var(--bg-tint); color: var(--text); border: 1px solid var(--rule-faint); font-size: 0.8rem;">
                                        {{ number_format($customer->loyalty_points) }} pts
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $customer->customer_status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ ucfirst($customer->customer_status) }}
                                    </span>
                                </td>
                                <td class="actions">
                                    <div class="actions">
                                        <a class="btn-ghost" href="{{ route('customers.edit', $customer) }}" style="color: var(--text);">Edit</a>
                                        <form class="inline-form" method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer record?')">
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
            </div>
            {{ $customers->links('partials.pagination') }}
        @endif
    </div>
@endsection
