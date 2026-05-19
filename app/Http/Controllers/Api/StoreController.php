<?php
// app/Http/Controllers/API/StoreController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    // التاجر: إنشاء متجر جديد - الخطوة 1
    public function createStore(Request $request)
    {
        // التحقق من أن المستخدم متحقق منه
        if (!$request->user()->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب التحقق من حسابك أولاً'
            ], 403);
        }

        // تحقق من عدم وجود متجر سابق
        if ($request->user()->store) {
            return response()->json([
                'success' => false,
                'message' => 'لديك متجر واحد فقط'
            ], 400);
        }

        $validCategories = Category::pluck('name')->toArray();

        $validated = $request->validate([
            'store_name' => 'required|string|min:3|unique:stores,name',
            'description' => 'required|string|min:10',
            'store_category' => 'required|string|in:' . implode(',', $validCategories),
            'phone' => 'required|string|regex:/^\+?[0-9]{10,}$/',
            'email' => 'nullable|email',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'name' => $validated['store_name'],
            'description' => $validated['description'],
            'category' => $validated['store_category'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => ($validated['country'] ?? '') . ', ' . ($validated['city'] ?? ''),
        ];

        // رفع الشعار إذا كان موجود
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('stores/logos', 'public');
            $data['logo'] = $logoPath;
        }

        // رفع البانر إذا كان موجود
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('stores/banners', 'public');
            $data['banner'] = $bannerPath;
        }

        $store = Store::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المتجر بنجاح ✅',
            'step' => 'completed',
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'category' => $validated['store_category'],
                'logo' => $store->getLogoUrl(),
                'banner' => $store->getBannerUrl(),
            ]
        ], 201);
    }

    // التاجر: تحديث متجر موجود - Form Data كامل
    public function updateStoreComplete(Request $request)
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المتجر'
            ], 404);
        }

        $validCategories = Category::pluck('name')->toArray();

        $validated = $request->validate([
            'store_name' => 'nullable|string|min:3|unique:stores,name,' . $store->id,
            'description' => 'nullable|string|min:10',
            'phone' => 'nullable|string|regex:/^\+?[0-9]{10,}$/',
            'email' => 'nullable|email',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $updateData = [];

        if (isset($validated['store_name'])) {
            $updateData['name'] = $validated['store_name'];
        }

        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        if (isset($validated['phone'])) {
            $updateData['phone'] = $validated['phone'];
        }

        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }

        if (isset($validated['country']) || isset($validated['city'])) {
            $updateData['address'] = ($validated['country'] ?? $store->address) . ', ' . ($validated['city'] ?? '');
        }

        // معالجة الشعار
        if ($request->hasFile('logo')) {
            if ($store->logo && Storage::disk('public')->exists($store->logo)) {
                Storage::disk('public')->delete($store->logo);
            }
            $updateData['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        // معالجة البانر
        if ($request->hasFile('banner')) {
            if ($store->banner && Storage::disk('public')->exists($store->banner)) {
                Storage::disk('public')->delete($store->banner);
            }
            $updateData['banner'] = $request->file('banner')->store('stores/banners', 'public');
        }

        $store->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المتجر بنجاح ✅',
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'description' => $store->description,
                'phone' => $store->phone,
                'email' => $store->email,
                'address' => $store->address,
                'logo' => $store->getLogoUrl(),
                'banner' => $store->getBannerUrl(),
            ]
        ]);
    }

    // التاجر: الحصول على معلومات المتجر
    public function getStore(Request $request)
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المتجر'
            ], 404);
        }

        $products = $store->products()->get(); 

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات المتجر',
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'description' => $store->description,
                'phone' => $store->phone,
                'email' => $store->email,
                'address' => $store->address,
                'logo' => $store->getLogoUrl(),
                'banner' => $store->getBannerUrl(),
                'rating' => $store->rating,
                'followers' => $store->followers,
                'is_active' => $store->is_active,
                'products' => $products->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price' => $p->price,
                    'discount_price' => $p->discount_price,
                    'stock' => $p->stock,
                    'color' => $p->color,
                    'images' => json_decode($p->images, true) ? array_map(fn($img) => asset('storage/' . $img), json_decode($p->images, true)) : [],
                ]),
            ]
        ]);
    }

    // التاجر: تنشيط الإشعارات
    public function toggleNotifications(Request $request)
    {
        $validated = $request->validate([
            'request_notifications' => 'boolean', // إشعار الطلبات
            'message_notifications' => 'boolean', // إشعار الرسائل
        ]);

        // حفظ في جدول settings أو في user table
        $user = $request->user();
        $settings = $user->notification_settings ?? [];

        $settings['request_notifications'] = $validated['request_notifications'] ?? true;
        $settings['message_notifications'] = $validated['message_notifications'] ?? true;

        // يمكن حفظها في جدول منفصل أو في JSON column
        // $user->notification_settings = $settings;
        // $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإشعارات ✅',
            'data' => $settings
        ]);
    }

    // العام: الحصول على جميع المتاجر
    public function getAllStores()
    {
        $stores = Store::where('is_active', true)
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $stores->map(function($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'description' => $store->description,
                    'logo' => $store->getLogoUrl(),
                    'banner' => $store->getBannerUrl(),
                    'rating' => $store->rating,
                    'followers' => $store->followers,
                    'products_count' => $store->products()->count(),
                ];
            })
        ]);
    }

    // العام: الحصول على متجر محدد
    public function getStoreByName($storeName)
    {
        $store = Store::where('name', $storeName)
            ->where('is_active', true)
            ->firstOrFail();

            $products = $store->products()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'description' => $store->description,
                    'phone' => $store->phone,
                    'email' => $store->email,
                    'logo' => $store->getLogoUrl(),
                    'banner' => $store->getBannerUrl(),
                    'rating' => $store->rating,
                    'followers' => $store->followers,
                    'products_count' => $products->count(),
                    'products' => $products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'description' => $p->description,
                        'price' => $p->price,
                        'discount_price' => $p->discount_price,
                        'stock' => $p->stock,
                        'color' => $p->color,
                        'images' => json_decode($p->images, true) 
                            ? array_map(fn($img) => asset('storage/' . $img), json_decode($p->images, true)) 
                            : [],
                    ]),
                ]
            ]);
    }
}