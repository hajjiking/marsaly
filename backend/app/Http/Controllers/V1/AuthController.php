<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OtpRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use App\Support\ApiResponse;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /** POST /api/v1/auth/otp/request — rate limited (3/min per phone in OtpService). */
    public function requestOtp(OtpRequest $request)
    {
        $result = $this->otp->requestLoginOtp($request->input('phone'));

        if (!$result['sent']) {
            return ApiResponse::error(__('api.otp_rate_limited', ['seconds' => $result['retry_after']]), 429);
        }
        return ApiResponse::success(null, __('api.otp_sent'));
    }

    /** POST /api/v1/auth/otp/verify — verifies code, creates client user if new, issues Sanctum token. */
    public function verifyOtp(OtpVerifyRequest $request)
    {
        $phone = Phone::normalize($request->input('phone'));
        $verified = $this->otp->verifyLoginOtp($phone, $request->input('code'));

        if (!$verified) {
            return ApiResponse::error(__('api.otp_invalid'), 422);
        }

        $user = User::where('phone', $phone)->first();
        $isNew = false;

        if (!$user) {
            // Only brand-new phones auto-register as clients. Merchants/drivers
            // must complete their registration flow after OTP verification.
            DB::transaction(function () use ($phone, $request, &$user) {
                $user = User::create([
                    'name'   => $request->input('name'),
                    'phone'  => $phone,
                    'role_id'=> Role::where('name', Role::CLIENT)->value('id'),
                    'locale' => app()->getLocale(),
                ]);
                $user->forceFill(['phone_verified_at' => now()])->save();
            });
            $isNew = true;
        }

        if (!$user->is_active) {
            return ApiResponse::error(__('api.account_disabled'), 403);
        }

        $tokenName = $request->input('device_name', 'mobile');
        $plainTextToken = $user->createToken($tokenName)->plainTextToken;

        if ($deviceId = $request->input('device_id')) {
            DeviceToken::updateOrCreate(
                ['device_id' => $deviceId],
                ['user_id' => $user->id, 'token' => $request->input('fcm_token'), 'platform' => $request->input('platform', 'android'), 'last_seen_at' => now()]
            );
        }

        return ApiResponse::success([
            'token'      => $plainTextToken,
            'token_type' => 'Bearer',
            'is_new_user'=> $isNew,
            'user'       => new UserResource($user->load(['role', 'merchant', 'driver'])),
        ], __('api.login_ok'));
    }

    /** POST /api/v1/auth/logout — revoke current token + remove device push token. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        if ($deviceId = $request->input('device_id')) {
            DeviceToken::where('device_id', $deviceId)->delete();
        }
        return ApiResponse::success(null, __('api.logout_ok'));
    }

    /** GET /api/v1/auth/me */
    public function me(Request $request)
    {
        return ApiResponse::success(
            new UserResource($request->user()->load(['role', 'merchant.category', 'driver']))
        );
    }

    /** PATCH /api/v1/auth/profile */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'   => ['sometimes', 'string', 'max:120'],
            'locale' => ['sometimes', 'in:' . implode(',', config('marsaly.localization.supported'))],
        ]);
        $request->user()->fill($data)->save();
        return ApiResponse::success(new UserResource($request->user()), __('api.updated'));
    }
}
