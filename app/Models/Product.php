<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'store_id',
        'user_id',
        'role',
        'used',
        'name',
        'details',
        'price',
        'discount_price',
        'weight',
        'stock',
        'images',
        'rating',
        'status',
    ];

    // تحويل البيانات تلقائياً
    protected $casts = [
        'images' => 'array',
        'price' => 'float',
        'discount_price' => 'float',
        'weight' => 'float',
        'rating' => 'float',
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    // المنتج يتبع كاتيجوري
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // المنتج يتبع مستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // المنتج يتبع متجر (اختياري)
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}