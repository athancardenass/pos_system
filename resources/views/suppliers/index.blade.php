@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    <div class="page-head">
        <h1>Suppliers</h1>
        <a class="btn" href="{{ route('suppliers.create') }}">New supplier</a>
    </div>
    <div class="card">
        @if ($suppliers->isEmpty())
            <p class="empty">No suppliers yet.</p>
        @else
            <table>
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
                                <a class="btn-ghost" href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $suppliers->links('partials.pagination') }}
        @endif
    </div>
@endsection
