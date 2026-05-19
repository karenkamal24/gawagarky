<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;

trait ProductTrait245
{
    public function formatProduct($product): array
    {
        $images = $product->images ? json_decode($product->images, true) : [];
        $imageUrls = array_map(fn($img) => asset('storage/' . $img), $images);

        $user = Auth::user();
        $isFavorite = $user ? $user->favorites()->where('store_product_id', $product->id)->exists() : false;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'slug' => $product->slug,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'discount_percentage' => $product->discount_price
                ? round((($product->price - $product->discount_price) / $product->price) * 100)
                : 0,
            'stock' => $product->stock,
            'color' => $product->color,
            'rating' => $product->rating,
            'images' => $imageUrls,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug ?? null,
                'description' => $product->category->description,
                'image' => $product->category->image ?? null,
            ] : null,
            'is_favorite' => $isFavorite,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }
}
