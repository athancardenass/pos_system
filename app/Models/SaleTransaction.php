<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleTransaction extends Model
{
    use HasFactory;

    protected $table = 'sale_transaction';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'employee_id',
        'discount_id',
        'transaction_date',
        'subtotal',
        'total_amount',
        'payment_method',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id', 'discount_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'transaction_id', 'transaction_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'transaction_id');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'transaction_id', 'transaction_id');
    }
}