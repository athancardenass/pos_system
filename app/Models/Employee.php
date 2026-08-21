<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model implements Authenticatable
{
    use HasFactory, AuthenticatableTrait;

    protected $table = 'employee';
    protected $primaryKey = 'employee_id';
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'username',
        'password',
        'contact_number',
        'hire_date',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function saleTransactions()
    {
        return $this->hasMany(SaleTransaction::class, 'employee_id', 'employee_id');
    }
}