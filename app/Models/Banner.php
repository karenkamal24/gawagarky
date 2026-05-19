<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['name'];

    public function slides()
    {
        return $this->hasMany(Slide::class);
    }
}