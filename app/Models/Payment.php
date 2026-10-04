<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payment';

    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $hidden = [
        'reference_number',
        'reference_ciphertext',
        'reference_fingerprint',
    ];

    protected $fillable = [
        'transaction_id',
        'payment_method',
        'reference_number',
        'reference_ciphertext',
        'reference_fingerprint',
        'card_last4',
        'payment_provider',
        'amount_paid',
        'change_amount',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function setReferenceNumberAttribute(mixed $value): void
    {
        $this->attributes['reference_number'] = null;
    }

    public function saleTransaction(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function revealedReference(): ?string
    {
        return app(\App\Services\PaymentReferenceService::class)->decrypt($this->reference_ciphertext);
    }

    public function maskedReference(): string
    {
        return app(\App\Services\PaymentReferenceService::class)->mask(
            (string) $this->payment_method,
            $this->revealedReference(),
            $this->card_last4,
        );
    }
}
