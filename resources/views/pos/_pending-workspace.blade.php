@php
    $isCard = $paymentKind === 'card';
    $paymentTitle = $isCard ? 'Pending Card' : 'Pending E-Wallet';
    $routePrefix = $isCard ? 'pos.pending-card' : 'pos.pending-ewallet';
    $selected = $requests->firstWhere('id', request()->integer('payment')) ?? $requests->first();
@endphp
<main class="reference-workspace" aria-label="{{ $paymentTitle }}">
    <header class="reference-header">
        <h1>{{ $paymentTitle }} <span class="reference-count">{{ $requests->count() }}</span></h1>
        <a class="reference-close" href="{{ route('pos.index') }}">Close <span>(Esc)</span></a>
    </header>
    <div class="reference-payment-layout">
        <section class="reference-payment-list" aria-label="Pending payment requests">
            <p class="reference-section-bar">{{ $isCard ? 'Card payments waiting for manual verification' : 'E-wallet payments waiting for manual verification' }}</p>
            @forelse ($requests as $paymentRequest)
                <a class="reference-payment-row {{ $selected?->id === $paymentRequest->id ? 'is-selected' : '' }}" href="{{ route($routePrefix.'.index', ['payment' => $paymentRequest->id]) }}" @if ($selected?->id === $paymentRequest->id) aria-current="true" @endif>
                    <div class="reference-payment-row-top">
                        <strong>{{ $paymentRequest->payment_provider }} <span>#{{ str_pad((string) $paymentRequest->id, 6, '0', STR_PAD_LEFT) }}</span></strong>
                        <strong class="reference-row-amount">₱{{ number_format((float) $paymentRequest->submitted_amount, 2) }}</strong>
                    </div>
                    <div class="reference-payment-row-bottom">
                        <span class="reference-status">Pending review</span>
                        <span>{{ $paymentRequest->requester?->displayName() ?? 'Unknown cashier' }}</span>
                    </div>
                    @if ($paymentRequest->expires_at)
                        <p>Reserved until {{ $paymentRequest->expires_at->timezone('Asia/Manila')->format('g:i A') }}</p>
                    @endif
                </a>
            @empty
                <div class="reference-list-empty" role="status">
                    <strong>{{ $isCard ? 'No pending Card payments' : 'No pending e-wallet payments' }}</strong>
                    <p>Submitted payments will appear here for manual review.</p>
                </div>
            @endforelse
        </section>
        <section class="reference-payment-detail" aria-label="Selected payment">
            @if ($selected)
                <div class="reference-detail-heading">
                    <h2>{{ $selected->payment_provider }} <span>#{{ str_pad((string) $selected->id, 6, '0', STR_PAD_LEFT) }}</span></h2>
                    <span class="reference-status">Pending review</span>
                </div>
                <div class="reference-payment-hero">
                    <p>{{ $isCard ? 'Amount recorded at terminal' : 'Amount to verify' }}</p>
                    <strong>₱{{ number_format((float) $selected->submitted_amount, 2) }}</strong>
                    <p class="reference-manual-copy">{{ $isCard ? 'Compare this amount with the completed card terminal record.' : 'Compare this amount with the completed payment in the receiving merchant app.' }}</p>
                </div>
                <div class="reference-check-steps" aria-label="Manual review checklist">
                    <div><span>Step 1</span><strong>{{ $isCard ? 'Check terminal record' : 'Check merchant app' }}</strong></div>
                    <div><span>Step 2</span><strong>Match amount & reference</strong></div>
                    <div><span>Step 3</span><strong>Manager confirms</strong></div>
                </div>
                <label class="reference-field-label">{{ $isCard ? 'Card reference' : 'Submitted reference number' }}</label>
                <div class="reference-readonly-field"><x-masked-value :value="$selected->maskedReference()" /></div>
                <p class="reference-detail-note">{{ $isCard ? 'Approval completes the sale and issues the receipt. Rejection releases reserved stock.' : 'Check the completed payment in the receiving merchant app before approving a sale.' }}</p>
                <div class="reference-bottom-actions">
                    <a class="btn btn-secondary" href="{{ route('pos.index') }}">Back to POS</a>
                    <a class="btn" href="{{ route($routePrefix.'.show', $selected) }}">Review payment</a>
                </div>
            @else
                <div class="reference-detail-empty"><h2>Ready for the next payment</h2><p>Select a pending payment to see its amount and reference.</p></div>
            @endif
        </section>
    </div>
</main>
@push('scripts')
<script>
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !event.defaultPrevented) window.location.assign(@json(route('pos.index')));
    });
</script>
@endpush
