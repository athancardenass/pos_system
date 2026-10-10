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
        'crm_external_id',
        'crm_discount_eligible',
        'crm_discount_type',
        'contact_number',
        'email',
        'address',
        'loyalty_points',
        'date_of_birth',
        'total_purchases',
        'customer_status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'total_purchases' => 'decimal:2',
            'crm_discount_eligible' => 'boolean',
        ];
    }

    public function saleTransactions()
    {
        return $this->hasMany(SaleTransaction::class, 'customer_id', 'customer_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
