<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row: which auto-promotion fired on which sale, for how much, with the rule
 * as it stood at that moment (snapshot) — the promotion definition may change later.
 */
class SalePromotion extends Model
{
    protected $table = 'sale_promotion';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'transaction_id',
        'promotion_id',
        'amount_discounted',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'transaction_id' => 'integer',
            'promotion_id' => 'integer',
            'amount_discounted' => 'decimal:2',
            'snapshot' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SaleTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'promotion_id');
    }

    /**
     * Receipt label — prefers the frozen snapshot so a later rename of the promotion
     * cannot rewrite history on an old receipt.
     */
    public function label(): string
    {
        $rule = $this->snapshot['rule'] ?? null;
        $name = $this->snapshot['name'] ?? $this->promotion?->name ?? 'Promotion';

        return $rule ? "{$name} ({$rule})" : $name;
    }
}
