<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VatSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $table = 'vat_settings';

    public $incrementing = false;

    protected $fillable = ['id', 'rate'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:4'];
    }
}
