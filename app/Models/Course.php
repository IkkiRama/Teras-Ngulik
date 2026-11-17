<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $guarded = [];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
