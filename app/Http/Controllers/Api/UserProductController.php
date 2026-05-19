<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\UserProductTrait;
use App\Models\UserProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProductController extends Controller
{
    use UserProductTrait;

    // ✅ إنشاء منتج
    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|min:3|max:255',
            'category_id' => 'required|exists:categories,id',
            'details'     => 'required|string|min:10',
            'price'       => 'required|numeric|min:0',
            'weight'      => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'status'      => 'required|in:new,used',
            'images'      => 'required|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // 🔹 رفع الصور
        $images = [];
        foreach ($request->file('images') as $image) {
            $images[] = $image->store('products', 'public');
        }

        // 🔹 إنشاء المنتج
        $product = UserProduct::create([
            ...$validated,
            'images'  => json_encode($images),
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنتج بنجاح ✅',
            'data' => $this->formatProduct($product)
        ], 201);
    }

    // ✅ تعديل منتج
    public function updateProduct(Request $request, $productId)
    {
        $product = UserProduct::where('user_id', $request->user()->id)
            ->findOrFail($productId);

        $validated = $request->validate([
            'name'        => 'nullable|string|min:3|max:255',
            'details'     => 'nullable|string|min:10',
            'price'       => 'nullable|numeric|min:0',
            'weight'      => 'nullable|numeric|min:0',
            'stock'       => 'nullable|integer|min:0',
            'status'      => 'nullable|in:new,used',
            'category_id' => 'nullable|exists:categories,id',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $updateData = array_filter($validated, fn($v) => $v !== null && $v !== '');

        // 🔹 تحديث الصور
        if ($request->hasFile('images')) {

            if ($product->images) {
                foreach (json_decode($product->images, true) as $img) {
                    if (Storage::disk('public')->exists($img)) {
                        Storage::disk('public')->delete($img);
                    }
                }
            }

            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('products', 'public');
            }

            $updateData['images'] = json_encode($images);
        }

        $product->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح ✅',
            'data' => $this->formatProduct($product)
        ]);
    }

    // ✅ حذف منتج
    public function deleteProduct(Request $request, $productId)
    {
        $product = UserProduct::where('user_id', $request->user()->id)
            ->findOrFail($productId);

        if ($product->images) {
            foreach (json_decode($product->images, true) as $img) {
                if (Storage::disk('public')->exists($img)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ]);
    }

    // ✅ منتجات المستخدم
    public function getMyProducts(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $products = UserProduct::with('category')
        ->where('user_id', $user->id)
        ->latest()
        ->paginate($request->per_page ?? 10);

    return response()->json([
        'success' => true,
        'data' => $products->through(fn($p) => $this->formatProduct($p)),
    ]);
}

    // ✅ منتج واحد
    public function getProduct($productId)
    {
        $product = UserProduct::with('category')->findOrFail($productId);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product)
        ]);
    }

    // ✅ كل المنتجات
    public function getAllProducts(Request $request)
    {
        $query = UserProduct::with('category');

        if ($request->search) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->featured == 1) {
            $query->withCount('favorites')
                  ->orderByDesc('favorites_count');
        } elseif ($request->new_arrivals == 1) {
            $query->latest();
        }

        $products = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $products->through(fn($p) => $this->formatProduct($p)),
            'pagination' => [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }
}