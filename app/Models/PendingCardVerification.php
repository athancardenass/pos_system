<?php

namespace App\Models;

use App\Services\PaymentReferenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingCardVerification extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'pending_card_verifications';

    protected $hidden = ['reference_ciphertext', 'reference_fingerprint'];

    protected $fillable = [
        'employee_id',
        'checkout_idempotency_key',
        'payment_provider',
        'reference_ciphertext',
        'reference_fingerprint',
        'card_last4',
        'submitted_amount',
        'checkout_payload',
        'status',
        'expires_at',
        'verified_by_employee_id',
        'verified_at',
        'rejected_by_employee_id',
        'rejected_at',
        'sale_transaction_id',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_amount' => 'decimal:2',
            'checkout_payload' => 'array',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by_employee_id', 'employee_id');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rejected_by_employee_id', 'employee_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'sale_transaction_id', 'transaction_id');
    }

    public function revealedReference(): ?string
    {
        return app(PaymentReferenceService::class)->revealCardApproval(
            app(PaymentReferenceService::class)->decrypt($this->reference_ciphertext),
        );
    }

    public function maskedReference(): string
    {
        return app(PaymentReferenceService::class)->mask('card', $this->revealedReference(), $this->card_last4);
    }

    public function maskedApprovalCode(): string
    {
        return app(PaymentReferenceService::class)->maskedApprovalCode($this->revealedReference());
    }
}
