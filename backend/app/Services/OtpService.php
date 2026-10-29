<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpService
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsService $sms) {}

    /** @return array{sent:bool, retry_after?:int} */
    public function requestLoginOtp(string $phone): array
    {
        $phone = Phone::normalize($phone);
        $key = 'otp:' . $phone;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return ['sent' => false, 'retry_after' => RateLimiter::availableIn($key)];
        }
        RateLimiter::hit($key, 60);

        // Invalidate previous unused codes for this phone/purpose
        OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNull('verified_at')
            ->update(['verified_at' => null, 'expires_at' => now()->subMinute()]);

        $code = (string) random_int(10 ** (config('marsaly.phone.otp_length') - 1), 10 ** config('marsaly.phone.otp_length') - 1);

        OtpCode::create([
            'phone'      => $phone,
            'code'       => $code,
            'purpose'    => 'login',
            'expires_at' => now()->addSeconds(config('marsaly.phone.otp_ttl_seconds')),
        ]);

        $user = User::withTrashed()->where('phone', $phone)->first()
            ?? new User(['phone' => $phone]);
        // For brand-new phones we still need a model instance to carry the phone for sending
        $user->phone = $phone;
        $this->sms->sendOtp($user, $code);

        return ['sent' => true];
    }

    /** Validate code; returns remaining attempts or throws via array result. */
    public function verifyLoginOtp(string $phone, string $code): ?OtpCode
    {
        $phone = Phone::normalize($phone);

        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otp || $otp->isExpired()) {
            return null;
        }
        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return null;
        }
        if (!hash_equals($otp->code, $code)) {
            $otp->increment('attempts');
            return null;
        }

        $otp->update(['verified_at' => now()]);
        return $otp;
    }
}
