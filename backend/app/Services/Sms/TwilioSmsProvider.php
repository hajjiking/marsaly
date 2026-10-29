<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use Illuminate\Support\Facades\Http;

/**
 * Example real provider (Twilio). Credentials come from env:
 * TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM. Swap for any Moroccan aggregator by
 * implementing App\Contracts\SmsProvider and registering it in SmsService.
 */
class TwilioSmsProvider implements SmsProvider
{
    public function send(string $to, string $message): string
    {
        $res = Http::asForm()
            ->post(sprintf(
                'https://api.twilio.com/2010-04-07/Accounts/%s/Messages.json',
                config('services.twilio.sid'),
            ), [
                'To'   => $to,
                'From' => config('services.twilio.from'),
                'Body' => $message,
            ])
            ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
            ->throw()
            ->json();

        return $res['sid'] ?? 'unknown';
    }
}
