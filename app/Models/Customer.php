<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customer';
    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'first_name',
        'last_name',
        'contact_number',
        'email',
        'address',
        'loyalty_points',
        'date_of_birth',
        'total_purchases',
        'customer_status',
    ];

    public function saleTransactions()
    {
        return $this->hasMany(SaleTransaction::class, 'customer_id', 'customer_id');
    }
}