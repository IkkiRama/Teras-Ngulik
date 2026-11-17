<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewCard extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }
}
