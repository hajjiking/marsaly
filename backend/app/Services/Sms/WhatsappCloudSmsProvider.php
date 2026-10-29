<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use Illuminate\Support\Facades\Http;

/**
 * Meta WhatsApp Cloud API template sender (WABA). Env: WHATSAPP_TOKEN,
 * WHATSAPP_PHONE_ID, WHATSAPP_TEMPLATE (with {1} = code).
 */
class WhatsappCloudSmsProvider implements SmsProvider
{
    public function send(string $to, string $message): string
    {
        $code = trim(substr($message, strrpos($message, ' ')));
        $res = Http::withToken(config('services.whatsapp.token'))
            ->post(
                sprintf('https://graph.facebook.com/v21.0/%s/messages', config('services.whatsapp.phone_id')),
                [
                    'messaging_product' => 'whatsapp',
                    'to'                => ltrim($to, '+'),
                    'type'              => 'template',
                    'template'          => [
                        'name'       => config('services.whatsapp.template', 'marsaly_otp'),
                        'language'   => ['code' => 'fr'],
                        'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]]],
                    ],
                ],
            )
            ->throw()->json();

        return $res['messages'][0]['id'] ?? 'unknown';
    }
}
