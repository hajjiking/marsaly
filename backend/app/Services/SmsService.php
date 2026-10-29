<?php

namespace App\Services;

use App\Contracts\SmsProvider;
use App\Models\User;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\TwilioSmsProvider;
use App\Services\Sms\WhatsappCloudSmsProvider;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function driverFor(?User $user = null): SmsProvider
    {
        return match (config('marsaly.otp.driver')) {
            'twilio'   => new TwilioSmsProvider(),
            'whatsapp_cloud' => new WhatsappCloudSmsProvider(),
            default    => new LogSmsProvider(),
        };
    }

    public function sendOtp(User $user, string $code): void
    {
        $brand = config('marsaly.brand.name');
        $message = str_replace(
            ['{brand}', '{code}'],
            [$brand, $code],
            config('marsaly.otp.template', '{brand}: your code is {code}'),
        );

        try {
            $this->driverFor($user)->send($user->phone, $message);
        } catch (\Throwable $e) {
            // Never break login because of an SMS outage — log loudly.
            Log::error("Marsaly SMS failure: {$e->getMessage()}");
            report($e);
        }
    }
}
