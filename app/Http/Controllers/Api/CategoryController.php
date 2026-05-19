<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // ✅ عرض كل الكاتيجوري
    public function index()
    {
        $categories = Category::latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $categories
        ]);
    }

    // ✅ إنشاء كاتيجوري
public function store(Request $request)
{
    $validated = $request->validate([
        'name'        => 'required|string|min:2|max:255',
        'slug'        => 'nullable|string|max:255|unique:categories,slug',
        'description' => 'nullable|string',
        'image'       => 'nullable|image|mimes:jpg,jpeg,png'
    ]);

    // رفع الصورة
    if ($request->hasFile('image')) {
        $validated['image'] = $request->file('image')->store('categories', 'public');
    }

    // 🔥 slug: لو المستخدم بعت slug استخدمه، غير كده توليد تلقائي
    if (!empty($request->slug)) {
        $baseSlug = Str::slug($request->slug);
    } else {
        $baseSlug = Str::slug($request->name);
    }

    $slug = $baseSlug;
    $counter = 1;

    while (Category::where('slug', $slug)->exists()) {
        $slug = $baseSlug . '-' . $counter++;
    }

    $validated['slug'] = $slug;

    $category = Category::create($validated);

    return response()->json([
        'status'  => true,
        'message' => 'تم إنشاء الكاتيجوري بنجاح',
        'data'    => $category
    ]);
}

    // ✅ عرض كاتيجوري واحد
    public function show($id)
    {
        $category = Category::with('StoreProduct')->find($id);

        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'الكاتيجوري غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $category
        ]);
    }

    // ✅ تحديث كاتيجوري
public function update(Request $request, $id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'status'  => false,
            'message' => 'الكاتيجوري غير موجود'
        ], 404);
    }

    $validated = $request->validate([
        'name'        => 'sometimes|string|min:2|max:255',
        'slug'        => 'nullable|string|max:255|unique:categories,slug,' . $id,
        'description' => 'nullable|string',
        'image'       => 'nullable|image|mimes:jpg,jpeg,png'
    ]);

    // صورة
    if ($request->hasFile('image')) {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $validated['image'] = $request->file('image')->store('categories', 'public');
    }

    // slug
    if ($request->has('slug') && $request->slug != null) {
        $baseSlug = Str::slug($request->slug);
    } elseif ($request->has('name')) {
        $baseSlug = Str::slug($request->name);
    } else {
        $baseSlug = $category->slug;
    }

    $slug = $baseSlug;
    $counter = 1;

    while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
        $slug = $baseSlug . '-' . $counter++;
    }

    $validated['slug'] = $slug;

    $category->update($validated);

    return response()->json([
        'status'  => true,
        'message' => 'تم التحديث بنجاح',
        'data'    => $category
    ]);
}

    // ✅ حذف كاتيجوري
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'الكاتيجوري غير موجود'
            ], 404);
        }

        // حذف الصورة
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الكاتيجوري'
        ]);
    }
}