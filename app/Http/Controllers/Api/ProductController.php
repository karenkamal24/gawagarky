<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Controllers\Api\ProductTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    use ProductTrait;

    /*
    |--------------------------------------------------------------------------
    | 📥 عرض كل المنتجات
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $products = Product::with(['category', 'user'])->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $products->map(fn($p) => $this->formatProduct($p))
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 📥 عرض منتج واحد
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $product = Product::with(['category', 'user'])->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatProduct($product)
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ➕ إنشاء منتج (مع منطق role و used)
    |--------------------------------------------------------------------------
    */
public function store(Request $request)
{
    $user = $request->user();

    // 🎯 تحديد نوع المستخدم
    if ($user->role === 'merchant') {

        // 🔒 لازم المستخدم يكون متحقق
        if (!$user->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التحقق من حسابك أولاً'
            ], 403);
        }

        // 🏪 لازم يكون عنده متجر
        $store = $user->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إنشاء متجر أولاً'
            ], 404);
        }

        $productRole = 'merchant';
        $used = 0;
        $storeId = $store->id;

    } else {
        // 👤 user عادي
        $productRole = 'user';
        $used = 1;
        $storeId = null;
    }

    // ✅ الفاليديشن (سيبناها زي ما هي تقريبًا)
    $validated = $request->validate([
        'name' => 'required|string|min:3',
        'details' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'discount_price' => 'nullable|numeric|min:0',
        'stock' => 'nullable|integer|min:0',
        'weight' => 'nullable|numeric',
        'category_id' => 'required|exists:categories,id',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
    ]);

    // 📤 رفع الصور (بنفس أسلوبك القديم)
    $images = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $images[] = $image->store('products', 'public');
        }
    }

    // 💾 إنشاء المنتج
    $product = \App\Models\Product::create([
        'category_id' => $validated['category_id'],
        'store_id' => $storeId,
        'user_id' => $user->id,
        'role' => $productRole,

        'name' => $validated['name'],
        'details' => $validated['details'] ?? null,
        'price' => $validated['price'],
        'discount_price' => $validated['discount_price'] ?? null,
        'weight' => $validated['weight'] ?? null,
        'stock' => $validated['stock'] ?? 0,

        'images' => json_encode($images),
        'used' => $used,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم إضافة المنتج بنجاح ✅',
        'data' => $this->formatProduct($product)
    ], 201);
}

    /*
    |--------------------------------------------------------------------------
    | ✏️ تحديث منتج (مع حماية الملكية)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // 🔒 حماية: صاحب المنتج فقط
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // 📤 رفع صور جديدة
        if ($request->hasFile('images')) {
            $product->images = $this->uploadImages($request->file('images'));
        }

        $product->update([
            'name' => $request->name ?? $product->name,
            'details' => $request->details ?? $product->details,
            'price' => $request->price ?? $product->price,
            'discount_price' => $request->discount_price ?? $product->discount_price,
            'weight' => $request->weight ?? $product->weight,
            'stock' => $request->stock ?? $product->stock,
            'status' => $request->status ?? $product->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $this->formatProduct($product)
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ❌ حذف منتج (مع حماية الملكية)
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // 🔒 حماية
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 👤 منتجات المستخدم الحالي
    |--------------------------------------------------------------------------
    */
    public function myProducts()
    {
        $products = Product::where('user_id', Auth::id())
            ->with(['category', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products->map(fn($p) => $this->formatProduct($p))
        ]);
    }
}