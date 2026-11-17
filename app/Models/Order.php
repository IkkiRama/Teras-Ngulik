<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected $hidden = ['snap_token'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'recipients' => 'array', 'delivery' => 'encrypted:array', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'total' => 'integer', 'subtotal' => 'integer', 'discount' => 'integer', 'quantity' => 'integer'];
    }
}
