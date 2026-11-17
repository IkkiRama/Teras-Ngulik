<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
