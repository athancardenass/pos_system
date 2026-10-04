@extends('layouts.app')

@section('title', 'Discounts')

@push('styles')
<style>
    .discount-page-actions { display: flex; align-items: center; gap: 0.55rem; }
    .discount-edit-button {
        display: inline-flex;
        min-height: 32px;
        align-items: center;
        justify-content: center;
        padding: 6px 11px;
        border: 1px solid var(--text);
        border-radius: 8px;
        background: var(--text);
        color: #fff !important;
        font-size: .75rem;
        font-weight: 800;
        line-height: 1.1;
        text-decoration: none;
    }
    .discount-edit-button:hover, .discount-edit-button:focus-visible {
        border-color: #2b4d4e;
        background: #2b4d4e;
        color: #fff !important;
        text-decoration: none;
    }
    .discount-edit-button:focus-visible { outline: 3px solid rgba(32,60,61,.2); outline-offset: 2px; }
    @media (max-width: 560px) {
        .discount-page-actions { width: 100%; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <h1>Discounts</h1>
        <div class="discount-page-actions">
            <x-ui.button variant="secondary" :href="route('vat-settings.edit')">VAT settings</x-ui.button>
            <x-ui.button variant="secondary" :href="route('receipt-settings.edit')">Receipt settings</x-ui.button>
            <x-ui.button :href="route('discounts.create')">New discount</x-ui.button>
        </div>
    </div>
    <div class="card">
        @if ($discounts->isEmpty())
            <p class="empty">No discounts yet.</p>
        @else
            <x-ui.table>
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
                            <td style="font-weight: 700;">{{ $discount->policyDisplayName() }}</td>
                            <td>{{ $discount->specialPolicyType() ? 'percentage' : $discount->discount_type }}</td>
                            <td style="font-weight: 700;">{{ $discount->specialPolicyType() ? '20%' : ($discount->discount_type === 'percentage' ? $discount->discount_value.'%' : number_format($discount->discount_value, 2)) }}</td>
                            <td class="muted">
                                {{ optional($discount->start_date)->format('Y-m-d') ?: '—' }}
                                to
                                {{ optional($discount->end_date)->format('Y-m-d') ?: '—' }}
                            </td>
                            <td class="actions">
                                <a class="discount-edit-button" href="{{ route('discounts.edit', $discount) }}">Edit</a>
                                <form class="inline-form" method="POST" action="{{ route('discounts.destroy', $discount) }}" onsubmit="return confirm('Delete this discount?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="danger" type="submit" size="compact">Delete</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
            {{ $discounts->links('partials.pagination') }}
        @endif
    </div>
@endsection
