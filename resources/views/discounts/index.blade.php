@extends('layouts.app')

@section('title', 'Discounts')

@section('content')
    <div class="page-head">
        <h1>Discounts</h1>
        <a class="btn" href="{{ route('discounts.create') }}">New discount</a>
    </div>
    <div class="card">
        @if ($discounts->isEmpty())
            <p class="empty">No discounts yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Dates</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($discounts as $discount)
                        <tr>
                            <td>{{ $discount->discount_name }}</td>
                            <td>{{ $discount->discount_type }}</td>
                            <td>{{ $discount->discount_type === 'percentage' ? $discount->discount_value.'%' : number_format($discount->discount_value, 2) }}</td>
                            <td class="muted">
                                {{ optional($discount->start_date)->format('Y-m-d') ?: '—' }}
                                to
                                {{ optional($discount->end_date)->format('Y-m-d') ?: '—' }}
                            </td>
                            <td class="actions">
                                <a href="{{ route('discounts.edit', $discount) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('discounts.destroy', $discount) }}" onsubmit="return confirm('Delete this discount?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">{{ $discounts->links() }}</div>
        @endif
    </div>
@endsection
