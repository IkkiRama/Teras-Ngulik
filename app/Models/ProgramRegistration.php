<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramRegistration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function item()
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }
}
