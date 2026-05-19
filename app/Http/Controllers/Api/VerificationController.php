<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MerchantVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;

class VerificationController extends Controller
{
    // الحصول على حالة التحقق
    public function getStatus(Request $request)
    {
        $verification = $request->user()->verification ?? 
                       MerchantVerification::create(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب حالة التحقق',
            'data' => [
                'status' => $verification->status,
                'completion' => $verification->getCompletionPercentage(),
                'is_complete' => $verification->isComplete(),
                'verified_at' => $verification->verified_at,
                'rejection_reason' => $verification->rejection_reason,
                'store_name' => $verification->store_name,
                'store_description' => $verification->store_description,
                'store_category' => $verification->store_category,
                'images' => $verification->getImages(),
            ]
        ]);
    }

    // رفع صورة واحدة
    public function uploadImage(Request $request)
    {
        $validated = $request->validate([
            'image_type' => 'required|in:id_card_front,id_card_back,commercial_register,store_front,owner_photo',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240' // 10MB
        ]);

        $verification = $request->user()->verification ?? 
                       MerchantVerification::create(['user_id' => $request->user()->id]);

        $imageType = $validated['image_type'];
        
        // حذف الصورة القديمة
        if ($verification->$imageType && Storage::disk('public')->exists($verification->$imageType)) {
            Storage::disk('public')->delete($verification->$imageType);
        }

        // حفظ الصورة الجديدة
        $path = $request->file('image')->store('verifications', 'public');
        $verification->update([$imageType => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح ✅',
            'data' => [
                'image_type' => $imageType,
                'image_url' => asset('storage/' . $path),
                'completion' => $verification->getCompletionPercentage(),
                'is_complete' => $verification->isComplete(),
            ]
        ]);
    }

    // تحديث بيانات المتجر
    public function updateStoreInfo(Request $request)
    {

        $validCategories = Category::pluck('name')->toArray();
        $validated = $request->validate([
            'store_name' => 'required|string|min:3',
            'store_description' => 'required|string|min:10',
            'store_category' => 'required|string|in:' . implode(',', $validCategories),
        ]);

        $verification = $request->user()->verification ?? 
                       MerchantVerification::create(['user_id' => $request->user()->id]);

        $verification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات المتجر بنجاح ✅',
            'data' => $verification
        ]);
    }

    // إرسال للتحقق
    public function submit(Request $request)
    {
        $verification = $request->user()->verification;

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على بيانات التحقق'
            ], 404);
        }

        if (!$verification->isComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء رفع جميع الصور المطلوبة',
                'completion' => $verification->getCompletionPercentage()
            ], 400);
        }

        // if (!$verification->store_name || !$verification->store_description) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'الرجاء ملء بيانات المتجر'
        //     ], 400);
        // }

        // تغيير الحالة إلى قيد المراجعة
        $verification->update(['status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب التحقق ✅ سيتم المراجعة خلال 24 ساعة',
            'data' => [
                'status' => $verification->status,
                'completion' => $verification->getCompletionPercentage(),
            ]
        ]);
    }

    // للأدمن: الموافقة على التحقق
    public function approve(Request $request, $userId)
    {
        $verification = MerchantVerification::where('user_id', $userId)->firstOrFail();

        $verification->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        // تحديث دور المستخدم إلى تاجر
        $user = $verification->user;
        $user->role = 'merchant';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على التحقق ✅',
            'data' => [
                'verification' => $verification,
                'user_role' => $user->role
            ]
        ]);
    }

    // للأدمن: رفض التحقق
    public function reject(Request $request, $userId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10'
        ]);

        $verification = MerchantVerification::where('user_id', $userId)->firstOrFail();

        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحقق',
            'data' => $verification
        ]);
    }

    // للأدمن: الحصول على جميع طلبات التحقق
    public function getPendingVerifications(Request $request)
    {
        $verifications = MerchantVerification::where('status', 'pending')
            ->with('user')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلبات التحقق',
            'data' => $verifications
        ]);
    }
}