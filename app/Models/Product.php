<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'product_name',
        'description',
        'barcode',
        'unit_price',
        'cost_price',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_id', 'product_id');
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'product_id', 'product_id');
    }

    public function purchaseOrderDetails(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'product_id', 'product_id');
    }

    public function stockQuantity(): int
    {
        return (int) ($this->inventory?->stock_quantity ?? 0);
    }

    public function isInUse(): bool
    {
        return $this->saleDetails()->exists() || $this->purchaseOrderDetails()->exists();
    }

    /**
     * Generate a unique, valid EAN-13 barcode string.
     * 12 random digits + a correct checksum digit, looped until unique.
     * (JsBarcode in the product index rejects EAN-13 codes with a bad check digit.)
     */
    public static function generateBarcode(): string
    {
        do {
            $digits = (string) random_int(100000000000, 999999999999); // 12 digits
            $barcode = $digits . self::ean13Checksum($digits);
        } while (self::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Compute the EAN-13 check digit for the first 12 digits.
     */
    public static function ean13Checksum(string $twelveDigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $twelveDigits[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;

        return (string) $check;
    }
}