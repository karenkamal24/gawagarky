<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\Request;

class SlideController extends Controller
{
    // 📥 جلب كل الـ slides
    public function index()
    {
        $slides = Slide::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $slides
        ]);
    }

    // ➕ إضافة slide
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $slide = Slide::create([
            'title' => $request->title,
            'description' => $request->description
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Slide created successfully',
            'data' => $slide
        ]);
    }

    // 🔍 عرض slide واحد
    public function show($id)
    {
        $slide = Slide::find($id);

        if (!$slide) {
            return response()->json([
                'status' => false,
                'message' => 'Slide not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $slide
        ]);
    }

    // ✏️ تعديل slide
    public function update(Request $request, $id)
    {
        $slide = Slide::find($id);

        if (!$slide) {
            return response()->json([
                'status' => false,
                'message' => 'Slide not found'
            ], 404);
        }

        $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string'
        ]);

        $slide->update($request->only(['title', 'description']));

        return response()->json([
            'status' => true,
            'message' => 'Slide updated',
            'data' => $slide
        ]);
    }

    // ❌ حذف slide
    public function destroy($id)
    {
        $slide = Slide::find($id);

        if (!$slide) {
            return response()->json([
                'status' => false,
                'message' => 'Slide not found'
            ], 404);
        }

        $slide->delete();

        return response()->json([
            'status' => true,
            'message' => 'Slide deleted'
        ]);
    }
}