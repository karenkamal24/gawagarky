<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;

trait UserProductTrait
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
            'details' => $product->details,
            'price' => $product->price,
            'weight' => $product->weight,
            'status' => $product->status,
            'user' => new UserResource($product->user),
            'rating' => $product->rating,
            'images' => $imageUrls,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug ?? null,
                'description' => $product->category->description,
                'image' => $product->category->image ?? null,
            ] : null,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }
}
