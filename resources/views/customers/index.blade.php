@extends('layouts.app')

@section('title', 'Customers & Loyalty')

@push('styles')
    <style>
        .customer-cell-content {
            display: inline-block;
            max-width: 100%;
            padding: 6px 9px;
            border-radius: 7px;
            background: var(--text);
            color: #fff;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        .customer-id-content, .customer-name-content { display: inline; padding: 0; border-radius: 0; background: transparent; color: var(--text); font-weight: 800; }
        .customer-id-content { font-variant-numeric: tabular-nums; }
        .customer-contact-content { color: #fff; font-size: .84rem; }
        .customer-table .customer-points-heading { text-align: right; }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Customers & Loyalty</h1>
            <p class="muted">Manage customer directory and accumulated loyalty rewards points.</p>
        </div>
    </div>

    <div class="card">
        @if ($customers->isEmpty())
            <p class="empty">No customer records found.</p>
        @else
            <div class="table-wrap">
                <x-ui.table class="customer-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                            <th>Contact / Email</th>
                            <th class="customer-points-heading">Loyalty Points</th>
                            <th>Membership Status</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <span class="customer-cell-content customer-id-content">#{{ $customer->customer_id }}</span>
                                </td>
                                <td>
                                    <span class="customer-cell-content customer-name-content">{{ $customer->fullName() }}</span>
                                </td>
                                <td>
                                    <span class="customer-cell-content customer-contact-content">
                                    {{ $customer->contact_number ?: $customer->email ?: '—' }}
                                    </span>
                                </td>
                                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                    <x-ui.badge variant="points">
                                        {{ number_format($customer->loyalty_points) }} pts
                                    </x-ui.badge>
                                </td>
                                <td>
                                    <x-ui.badge :variant="$customer->customer_status === 'active' ? 'active' : 'inactive'">
                                        {{ ucfirst($customer->customer_status) }}
                                    </x-ui.badge>
                                </td>
                                <td class="actions">
                                    <div class="actions">
                                        <x-ui.button variant="slate" size="compact" :href="route('customers.edit', $customer)">Edit</x-ui.button>
                                        <form class="inline-form" method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer record?')">
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
            </div>
            {{ $customers->links('partials.pagination') }}
        @endif
    </div>
@endsection
