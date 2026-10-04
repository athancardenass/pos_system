<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    protected $table = 'receipt_settings';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'store_name',
        'store_address',
        'tin',
        'paper_width',
        'footer_text',
    ];
}
