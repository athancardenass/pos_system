@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    <div class="page-head">
        <h1>Suppliers</h1>
        <x-ui.button :href="route('suppliers.create')">New supplier</x-ui.button>
    </div>
    <div class="card">
        @if ($suppliers->isEmpty())
            <p class="empty">No suppliers yet.</p>
        @else
<x-ui.table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr>
                            <td style="font-weight: 700;">{{ $supplier->supplier_name }}</td>
                            <td>{{ $supplier->contact_number }}</td>
                            <td class="muted">{{ $supplier->email }}</td>
                            <td class="actions">
                                <x-ui.button variant="slate" size="compact" :href="route('suppliers.edit', $supplier)">Edit</x-ui.button>
                                <form class="inline-form" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="danger" type="submit" size="compact">Delete</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
</x-ui.table>
            {{ $suppliers->links('partials.pagination') }}
        @endif
    </div>
@endsection
