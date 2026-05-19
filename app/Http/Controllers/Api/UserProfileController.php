<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الملف الشخصي',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role' => $user->role,
                'created_at' => $user->created_at
            ]
        ]);
    }

    // تحديث الملف الشخصي - JSON
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'phone' => 'required|string|unique:users,phone,' . $request->user()->id,
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف بنجاح ✅',
            'data' => $request->user()
        ]);
    }

    // تحديث الملف الشخصي مع صورة - Form Data
    public function updateWithAvatar(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|min:3',
            'email' => 'nullable|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|unique:users,phone,' . $request->user()->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // 2MB
        ]);

        $user = $request->user();

        // معالجة الصورة
        if ($request->hasFile('avatar')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // حفظ الصورة الجديدة
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        // تحديث البيانات (تحديث الحقول الموجودة فقط)
        $updateData = array_filter($validated, fn($value) => $value !== null);
        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف بنجاح ✅',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role' => $user->role,
                'updated_at' => $user->updated_at
            ]
        ]);
    }
}