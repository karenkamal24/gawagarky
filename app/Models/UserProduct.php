<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProduct extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'price',
        'weight',
        'images',
        'status',
        'user_id',
        'details',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
