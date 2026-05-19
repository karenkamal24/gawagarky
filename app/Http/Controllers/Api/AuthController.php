<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /* ===================== Helper ===================== */
    private function normalizePhone($phone)
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+20')) {
            return [
                'intl' => $phone,
                'local' => '0' . substr($phone, 3)
            ];
        }

        if (str_starts_with($phone, '0')) {
            return [
                'intl' => '+20' . substr($phone, 1),
                'local' => $phone
            ];
        }

        return [
            'intl' => '+' . $phone,
            'local' => $phone
        ];
    }

    private function errorResponse($message, $e = null, $code = 500)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e?->getMessage() : null
        ], $code);
    }

    /* ===================== Register ===================== */
   public function register(RegisterRequest $request): JsonResponse
{
    try {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'fcm_token' => $request->fcm_token ?? null,
        ]);

        $phone = $this->normalizePhone($user->phone);

        $otp = $this->otpService->generate($phone['intl'], 'phone_verification');
        $this->otpService->sendSms($phone['intl'], $otp);

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل، تحقق من رقم الهاتف'
        ], 201);

    } catch (\Exception $e) {
        return $this->errorResponse('خطأ أثناء التسجيل', $e);
    }
}

public function login(LoginRequest $request): JsonResponse
{
    try {
        $login = $request->login;
        $password = $request->password;

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة'
            ], 401);
        }

        $user->update([
            'fcm_token' => $request->fcm_token
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'requires_verification' => !$user->phone_verified
            ]
        ]);

    } catch (\Exception $e) {
        return $this->errorResponse('خطأ في تسجيل الدخول', $e);
    }
}

    /* ===================== Send OTP ===================== */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $identifier = preg_replace('/\s+/', '', $request->identifier);
            $type = $request->type;

            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            $phone = !$isEmail ? $this->normalizePhone($identifier) : null;

            $target = $isEmail ? $identifier : $phone['intl'];

            if (!$this->otpService->checkRateLimit($target)) {
                return response()->json([
                    'success' => false,
                    'message' => 'حاول لاحقًا'
                ], 429);
            }

            if ($type === 'password_reset') {
                $user = User::where('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->orWhere('phone', $phone['intl'] ?? null)
                    ->orWhere('phone', $phone['local'] ?? null)
                    ->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المستخدم غير موجود'
                    ], 404);
                }
            }

            $otp = $this->otpService->generate($target, $type);

            $sent = $isEmail
                ? $this->otpService->sendEmail($identifier, $otp)
                : $this->otpService->sendSms($phone['intl'], $otp);

            if (!$sent) {
                return $this->errorResponse('فشل إرسال OTP');
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الكود'
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في OTP', $e);
        }
    }

    /* ===================== Verify OTP ===================== */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $identifier = $request->identifier;
            $otp = $request->otp;
            $type = $request->type;

            $phone = $this->normalizePhone($identifier);

            $isValid =
                $this->otpService->verify($identifier, $otp, $type) ||
                $this->otpService->verify($phone['intl'], $otp, $type) ||
                $this->otpService->verify($phone['local'], $otp, $type);

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP غير صحيح'
                ], 400);
            }

            if ($type === 'phone_verification') {
                $user = User::where('phone', $identifier)
                    ->orWhere('phone', $phone['intl'])
                    ->orWhere('phone', $phone['local'])
                    ->first();

                if ($user) {
                    $user->update([
                        'phone_verified' => true,
                        'phone_verified_at' => now()
                    ]);

                    $token = $user->createToken('auth_token')->plainTextToken;

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'user' => new UserResource($user),
                            'token' => $token
                        ]
                    ]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في التحقق', $e);
        }
    }

    /* ===================== Reset Password ===================== */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $identifier = $request->identifier;
            $otp = $request->otp;

            $phone = $this->normalizePhone($identifier);

            $isValid =
                $this->otpService->verify($identifier, $otp, 'password_reset') ||
                $this->otpService->verify($phone['intl'], $otp, 'password_reset') ||
                $this->otpService->verify($phone['local'], $otp, 'password_reset');

            if (!$isValid) {
                return response()->json(['success' => false], 400);
            }

            $user = User::where('email', $identifier)
                ->orWhere('phone', $identifier)
                ->orWhere('phone', $phone['intl'])
                ->orWhere('phone', $phone['local'])
                ->first();

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('خطأ في تغيير الباسورد', $e);
        }
    }

    /* ===================== Logout ===================== */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج'
        ]);
    }

    /* ===================== Me ===================== */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user())
        ]);
    }

    /* ===================== Google ===================== */
    public function googleCallback()
    {
        try {
            $gUser = Socialite::driver('google')->stateless()->user();

            if (!$gUser->getEmail()) {
                return $this->errorResponse('Google بدون إيميل');
            }

            $user = User::firstOrCreate(
                ['email' => $gUser->getEmail()],
                [
                    'name' => $gUser->getName(),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Google error', $e);
        }
    }

    /* ===================== Delete Account ===================== */
public function deleteAccount(Request $request): JsonResponse
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح'
            ], 401);
        }

        // حذف التوكنات
        $user->tokens()->delete();

        // حذف المستخدم
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحساب بنجاح'
        ]);

    } catch (\Exception $e) {
        return $this->errorResponse('خطأ أثناء حذف الحساب', $e);
    }
}
}
