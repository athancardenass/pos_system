<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawerTransfer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'accepted_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function source()
    {
        return $this->belongsTo(CashDrawer::class, 'source_drawer_id', 'drawer_id');
    }

    public function destination()
    {
        return $this->belongsTo(CashDrawer::class, 'destination_drawer_id', 'drawer_id');
    }
}
