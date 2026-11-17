<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'messages' => 'encrypted:array'];
    }
}
