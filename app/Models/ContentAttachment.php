<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentAttachment extends Model
{
    protected $guarded = [];

    protected $hidden = ['path'];

    public function item()
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }
}
