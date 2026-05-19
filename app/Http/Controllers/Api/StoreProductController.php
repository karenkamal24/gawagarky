<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ProductTrait;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreProductController extends Controller
{
    use ProductTrait;

    public function createProduct(Request $request)
    {
        if (!$request->user()->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التحقق من حسابك أولاً'
            ], 403);
        }
        
        $store = $request->user()->store;
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إنشاء متجر أولاً'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'description' => 'required|string|min:10',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'color' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'slug' => 'nullable|string|unique:store_products,slug',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // slug auto generate
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        // images upload
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('products', 'public');
            }
        }

        $product = $store->products()->create(array_merge(
            $validated,
            ['images' => json_encode($images)]
        ));

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنتج بنجاح ✅',
            'data' => $this->formatProduct($product)
        ], 201);
    }

    public function updateProduct(Request $request, $productId)
    {
        $store = $request->user()->store;
        $product = StoreProduct::where('store_id', $store->id)->findOrFail($productId);

        $validated = $request->validate([
            'name' => 'nullable|string|min:3',
            'description' => 'nullable|string|min:10',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'color' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'slug' => 'nullable|string|unique:store_products,slug,' . $productId,
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $updateData = array_filter($validated, fn($v) => $v !== null && $v !== '');

        // auto slug if name changed
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $updateData['slug'] = Str::slug($validated['name']);
        }

        // images update
        if ($request->hasFile('images')) {
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

    public function deleteProduct(Request $request, $productId)
    {
        $store = $request->user()->store;
        $product = StoreProduct::where('store_id', $store->id)->findOrFail($productId);

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

    public function getStoreProducts(Request $request)
    {
        $store = $request->user()->store;
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المتجر'
            ], 404);
        }

        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $newArrivals = $request->query('new_arrivals');
        $featuredProducts = $request->query('featured_products');
        $categoryId = $request->query('category_id');

        $query = $store->products();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($newArrivals == 1) {
            $query->latest();
        }

        if ($featuredProducts == 1) {
            $query->withCount('favorites')
                  ->orderByDesc('favorites_count');
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المنتجات',
            'data' => $products->map(fn($p) => $this->formatProduct($p)),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }

    public function getProduct(Request $request, $productId)
    {
        $store = $request->user()->store;
        $product = StoreProduct::where('store_id', $store->id)->findOrFail($productId);

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product)
        ]);
    }

    public function getStoreProductsPublic($storeName)
    {
        $store = Store::where('name', $storeName)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $store->products()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn($p) => $this->formatProduct($p))
        ]);
    }

    public function getAllProducts(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $featured = $request->query('featured');
        $newArrivals = $request->query('new_arrivals');
        $categoryId = $request->query('category_id');

        $query = StoreProduct::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($featured == 1) {
            $query->withCount('favorites')
                  ->orderByDesc('favorites_count');
        } elseif ($newArrivals == 1) {
            $query->latest();
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المنتجات بنجاح',
            'data' => $products->map(fn($p) => $this->formatProduct($p)),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }
}