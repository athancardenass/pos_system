<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'product_id', 'product_id');
    }
}