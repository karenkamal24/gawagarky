<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'price',
        'discount_price',
        'stock',
        'color',
        'images',
        'category_id',
        'slug',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
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
