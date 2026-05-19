<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'logo',
        'banner',
        'phone',
        'email',
        'whatsapp',
        'address',
        'rating',
        'followers',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }
    
    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'store_product_id');
    }
    
    public function carts()
    {
        return $this->hasMany(Cart::class, 'store_product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'store_product_id');
    }

    // الحصول على صور المتجر مع الـ URL
    public function getLogoUrl(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        // تحقق إذا كان بالفعل URL
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }
        return asset('storage/' . $this->logo);
    }

    public function getBannerUrl(): ?string
    {
        if (!$this->banner) {
            return null;
        }
        // تحقق إذا كان بالفعل URL
        if (str_starts_with($this->banner, 'http')) {
            return $this->banner;
        }
        return asset('storage/' . $this->banner);
    }
}