<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Development driver: writes the SMS to the log channel instead of sending it.
 * OTP codes are therefore visible in storage/logs/laravel.log.
 */
class LogSmsProvider implements SmsProvider
{
    public function send(string $to, string $message): string
    {
        $id = 'log-' . Str::random(8);
        Log::channel(config('marsaly.otp.driver') === 'log' ? config('logging.default', 'single') : 'single')
            ->info("[Marsaly SMS] to={$to} body=\"{$message}\" id={$id}");
        return $id;
    }
}
