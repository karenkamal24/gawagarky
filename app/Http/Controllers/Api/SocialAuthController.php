<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
class SocialAuthController extends Controller
{

    public function googleUrl()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'success' => true,
            'message' => 'رابط تسجيل الدخول عبر Google جاهز',
            'data' => [
                'google_auth_url' => $url
            ]
        ]);
    }
    // Redirect to Google for authentication
    public function googleRedirect()
    {
        // نحصل على رابط تسجيل الدخول لـ Google
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl(); // ده الرابط اللي هيوجه المستخدم لـ Google
    
        return response()->json([
            'success' => true,
            'message' => 'رابط تسجيل الدخول عبر Google جاهز',
            'data' => [
                'google_auth_url' => $url
            ]
        ]);
    }
    // Handle callback from Google
    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
    
            // تسجيل أو إيجاد المستخدم
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(\Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول عبر Google بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'phone_verified' => $user->phone_verified,
                        'email_verified' => $user->email_verified_at ? true : false,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول عبر Google',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function googleLogin(Request $request)
    {
        $code = $request->input('code'); // الكود اللي جاي من Google

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Google authorization code',
            ], 400);
        }

        try {
            // جلب بيانات المستخدم من Google باستخدام الكود
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($code);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول عبر Google بنجاح',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'authorization_code' => $request->input('code') 
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول عبر Google',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}