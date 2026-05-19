<?php

namespace App\Services;

use App\Models\Otp;
use Carbon\Carbon;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpService
{
    protected ?Client $twilio = null;
    protected string $twilioFrom;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_FROM');

        // حماية المتغيرات من null
        if (!$sid || !$token || !$from) {
            \Log::error('Twilio credentials are missing in .env');
            throw new \Exception('Twilio credentials not set in .env');
        }

        $this->twilio = new Client($sid, $token);
        $this->twilioFrom = $from;
    }

    /**
     * Generate OTP and save to database
     */
    public function generate(string $identifier, string $type): string
    {
        $otp = rand(1000, 9999);

        Otp::create([
            'identifier' => $identifier,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    /**
     * Send SMS using Twilio
     */
    public function sendSms(string $phone, string $otp): bool
    {
        try {
            if (str_starts_with($phone, '0')) {
                $phone = '+20' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }

            $this->twilio->messages->create($phone, [
                'from' => $this->twilioFrom,
                'body' => "رمز التحقق الخاص بك هو: $otp"
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error("Twilio SMS Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP by email
     */
    public function sendEmail(string $email, string $otp): bool
    {
        try {
            Mail::to($email)->send(new OtpMail($otp));
            return true;
        } catch (\Exception $e) {
            \Log::error("Email OTP Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP
     */
    public function verify(string $identifier, string $otp, string $type): bool
    {
        $record = Otp::where('identifier', $identifier)
                     ->where('otp', $otp)
                     ->where('type', $type)
                     ->where('expires_at', '>=', now())
                     ->latest()
                     ->first();

        if ($record) {
            $record->delete(); // حذف بعد التحقق
            return true;
        }

        return false;
    }

    /**
     * Rate limit check
     */
    public function checkRateLimit(string $identifier, int $limit = 3, int $minutes = 5): bool
    {
        $count = Otp::where('identifier', $identifier)
                    ->where('created_at', '>=', now()->subMinutes($minutes))
                    ->count();

        return $count < $limit;
    }
}
