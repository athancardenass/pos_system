<?php

namespace App\Models;

use App\Models\Concerns\HasDateWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rule-based promotion (auto-applied at checkout — the cashier never picks it).
 *
 * All math for this model lives in App\Services\PromotionService (single source of
 * truth for discounting); this model is data + cheap self-describing helpers only.
 */
class Promotion extends Model
{
    use HasDateWindow;

    protected $table = 'promotion';

    protected $primaryKey = 'promotion_id';

    public const TYPES = ['percentage', 'fixed', 'bundle_price', 'buy_x_get_y'];

    public const SCOPES = ['product', 'category', 'cart'];

    protected $fillable = [
        'name',
        'type',
        'value',
        'scope',
        'scope_id',
        'bundle_qty',
        'bundle_price',
        'x_qty',
        'y_qty',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'bundle_price' => 'decimal:2',
            'bundle_qty' => 'integer',
            'x_qty' => 'integer',
            'y_qty' => 'integer',
            'scope_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function appliedOnSales(): HasMany
    {
        return $this->hasMany(SalePromotion::class, 'promotion_id', 'promotion_id');
    }

    /**
     * The scoped Product when scope=product (null otherwise).
     */
    public function scopedProduct(): ?Product
    {
        return $this->scope === 'product' && $this->scope_id
            ? Product::query()->find($this->scope_id)
            : null;
    }

    /**
     * The scoped Category when scope=category (null otherwise).
     * Deliberately NOT a single Eloquent relation: scope_id points at product, category
     * or nothing depending on `scope`, so a polymorphic-looking relation would lie.
     */
    public function scopedCategory(): ?Category
    {
        return $this->scope === 'category' && $this->scope_id
            ? Category::query()->find($this->scope_id)
            : null;
    }

    /**
     * Human-readable scope, e.g. "Cart-wide", "Product: Jim Beam", "Category: Drinks".
     */
    public function scopeLabel(): string
    {
        return match ($this->scope) {
            'cart' => 'Cart-wide',
            'product' => 'Product: '.($this->scopedProduct()?->product_name ?? "#{$this->scope_id}"),
            'category' => 'Category: '.($this->scopedCategory()?->category_name ?? "#{$this->scope_id}"),
            default => ucfirst((string) $this->scope),
        };
    }

    /**
     * The "active + in date window" rule lives in App\Models\Concerns\HasDateWindow
     * (isLive()) — single source of truth shared with Coupon.
     */

    /**
     * Short rule description for the receipt and the manager lists.
     */
    public function ruleLabel(): string
    {
        return match ($this->type) {
            'percentage' => round((float) $this->value, 2).'% off',
            'fixed' => '₱'.number_format((float) $this->value, 2).' off',
            'bundle_price' => $this->bundle_qty.' for ₱'.number_format((float) $this->bundle_price, 2),
            'buy_x_get_y' => 'Buy '.($this->x_qty ?? 0).' get '.($this->y_qty ?? 0).' free',
            default => (string) $this->type,
        };
    }

    /**
     * The rule as it stood at the moment it was applied (stored in sale_promotion.snapshot).
     */
    public function snapshot(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value === null ? null : (float) $this->value,
            'scope' => $this->scope,
            'scope_id' => $this->scope_id,
            'bundle_qty' => $this->bundle_qty,
            'bundle_price' => $this->bundle_price === null ? null : (float) $this->bundle_price,
            'x_qty' => $this->x_qty,
            'y_qty' => $this->y_qty,
            'rule' => $this->ruleLabel(),
        ];
    }
}
