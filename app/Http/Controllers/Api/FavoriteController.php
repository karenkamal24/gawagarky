<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductTrait;

class FavoriteController extends Controller
{
    use ProductTrait;

    // 📥 جلب المفضلة
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favorites()
            ->with('product')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المفضلة',
            'data' => $favorites->map(function ($fav) {
                return [
                    'favorite_id' => $fav->id,
                    'product' => $this->formatProduct($fav->product)
                ];
            }),
            'count' => $request->user()->favorites()->count()
        ]);
    }

    // ➕ إضافة للمفضلة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $favorite = $request->user()->favorites()->firstOrCreate([
            'product_id' => $validated['product_id']
        ]);

        $product = Product::find($validated['product_id']);

        return response()->json([
            'success' => true,
            'message' => 'تمت الإضافة للمفضلة ❤️',
            'data' => [
                'favorite_id' => $favorite->id,
                'product' => $this->formatProduct($product)
            ]
        ], 201);
    }

    // ❌ حذف من المفضلة
    public function destroy(Request $request, $productId)
    {
        $deleted = $request->user()
            ->favorites()
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'تم حذف من المفضلة' : 'المنتج غير موجود في المفضلة'
        ]);
    }

    // ⭐ فحص إذا كان المنتج في المفضلة
    public function isFavorite(Request $request, $productId)
    {
        $isFavorite = $request->user()
            ->favorites()
            ->where('product_id', $productId)
            ->exists();

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite
        ]);
    }
}