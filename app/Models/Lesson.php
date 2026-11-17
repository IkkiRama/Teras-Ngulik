<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array', 'is_preview' => 'boolean', 'required' => 'boolean'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
