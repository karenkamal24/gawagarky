<?php

namespace App\Http\Controllers\Api;

trait ProductTrait
{
    public function formatProduct($product): array
    {
        $images = $product->images ? json_decode($product->images, true) : [];

        $imageUrls = array_map(function ($img) {
            return asset('storage/' . $img);
        }, $images);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'details' => $product->details,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'weight' => $product->weight,
            'stock' => $product->stock,
            'rating' => $product->rating,
            'status' => $product->status,
            'used' => $product->used,
            'images' => $imageUrls,

            'category' => $product->category ? $product->category->name : null,
            'user' => $product->user ? $product->user->name : null,
        ];
    }

    // 📤 رفع الصور
    public function uploadImages($files)
    {
        $images = [];

        foreach ($files as $file) {
            $path = $file->store('products', 'public');
            $images[] = $path;
        }

        return json_encode($images);
    }
}