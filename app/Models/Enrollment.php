<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
