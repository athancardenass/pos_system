@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="page-head">
        <h1>Customers</h1>
        <a class="btn" href="{{ route('customers.create') }}">New customer</a>
    </div>
    <div class="card">
        @if ($customers->isEmpty())
            <p class="empty">No customers yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td style="font-weight: 700;">{{ $customer->fullName() }}</td>
                            <td class="muted">{{ $customer->contact_number ?: $customer->email }}</td>
                            <td>{{ $customer->loyalty_points }}</td>
                            <td><span class="badge {{ $customer->customer_status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ $customer->customer_status }}</span></td>
                            <td class="actions">
                                <a class="btn-ghost" href="{{ route('customers.edit', $customer) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $customers->links('partials.pagination') }}
        @endif
    </div>
@endsection
